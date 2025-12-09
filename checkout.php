<?php
// ============================================================================
// FILE: checkout.php
// SCOPO: Raccogliere i dati finali (indirizzo e pagamento) e creare l'ordine.
// È il momento in cui il carrello si svuota e nasce un Ordine nel Database.
// ============================================================================

// 1. CONFIGURAZIONE
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Avvio sessione (fondamentale per sapere CHI sta pagando)
session_start();

// Inclusione Header (Grafica) e DB (Connessione)
include 'header.php'; 
// Nota: 'header.php' include già 'db.php', quindi abbiamo accesso a $conn

// ============================================================
// 2. CONTROLLI DI SICUREZZA
// ============================================================

// Se l'utente non è loggato, via al login!
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Recuperiamo l'ID utente
$user_id = $_SESSION['user_id'];

// Variabili per i calcoli
$shipping_cost = 5.00;
$total_cart_price = 0;
$msg = ""; 

// RECUPERO DATI UTENTE (Per pre-compilare il form indirizzo)
$sql_user = "SELECT nome, cognome, email, telefono, indirizzo_spedizione FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
// Salviamo i dati in un array $user_data per usarli nei campi di input HTML
$user_data = $stmt_user->get_result()->fetch_assoc();


// RECUPERO PRODOTTI CARRELLO (Per il riepilogo e per salvare l'ordine)
// JOIN SQL: Uniamo la tabella carrello con quella prodotti per avere i prezzi
$sql_cart = "SELECT ci.product_id, ci.quantita, p.nome, p.prezzo 
             FROM cart_items ci 
             JOIN products p ON ci.product_id = p.id 
             WHERE ci.user_id = ?";
$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$cart_items = $stmt_cart->get_result();

// Se il carrello è vuoto, non puoi stare qui! Torna al carrello.
if ($cart_items->num_rows == 0) {
    header("Location: cart.php");
    exit;
}

// CALCOLO TOTALE E PREPARAZIONE DATI
// Creiamo un array temporaneo ($cart_data_for_processing) per salvare i dati
// perché dopo averli letti una volta, il puntatore del database si "consuma".
$cart_data_for_processing = [];

while ($item = $cart_items->fetch_assoc()) {
    $subtotal = $item['prezzo'] * $item['quantita'];
    $total_cart_price += $subtotal;
    // Salviamo ogni riga in un array per riusarla dopo (nel riepilogo a destra)
    $cart_data_for_processing[] = $item;
}

// Totale Finale = Prodotti + Spedizione
$total_final = $total_cart_price + $shipping_cost;


// ============================================================================
// 3. GESTIONE DEI FORM (POST)
// ============================================================================
// Se l'utente ha premuto un bottone...
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- CASO A: L'utente vuole solo aggiornare l'indirizzo ---
    if (isset($_POST['update_address'])) {
        
        // Pulizia dati (real_escape_string è una sicurezza base contro SQL Injection)
        $new_nome = $conn->real_escape_string($_POST['nome']);
        $new_cognome = $conn->real_escape_string($_POST['cognome']);
        $new_telefono = $conn->real_escape_string($_POST['telefono']);
        $new_indirizzo = $conn->real_escape_string($_POST['indirizzo_spedizione']);
        
        // Aggiorniamo il database utenti
        $sql_update = "UPDATE users SET nome=?, cognome=?, telefono=?, indirizzo_spedizione=? WHERE id=?";
        $stmt_update = $conn->prepare($sql_update);
        // "ssssi" = string, string, string, string, integer
        $stmt_update->bind_param("ssssi", $new_nome, $new_cognome, $new_telefono, $new_indirizzo, $user_id);
        
        if ($stmt_update->execute()) {
            // Aggiorniamo anche la variabile di sessione (perché il nome è cambiato)
            $_SESSION['user_nome'] = $new_nome;
            
            // Aggiorniamo l'array $user_data così il form mostra subito i dati nuovi
            $user_data['nome'] = $new_nome; 
            $user_data['cognome'] = $new_cognome; 
            $user_data['telefono'] = $new_telefono; 
            $user_data['indirizzo_spedizione'] = $new_indirizzo;
            
            // Messaggio di successo verde
            $msg = "<div class='alert' style='background:#e6fffa; color:#00b894; padding:10px; border-radius:6px; margin-bottom:15px;'>Indirizzo e Dati aggiornati con successo!</div>";
        } else {
            // Messaggio di errore rosso
            $msg = "<div class='alert' style='background:#ffe6e6; color:#d63031; padding:10px; border-radius:6px; margin-bottom:15px;'>Errore durante l'aggiornamento.</div>";
        }
    }


    // --- CASO B: L'utente vuole PAGARE (Complete Order) ---
    if (isset($_POST['complete_order'])) {
        
        // Prendiamo l'indirizzo attuale dell'utente (snapshot)
        // È importante salvare l'indirizzo NELL'ORDINE, perché se l'utente trasloca tra un mese,
        // lo storico di questo ordine deve rimanere col vecchio indirizzo.
        $address_snapshot = $user_data['indirizzo_spedizione']; 
        $status = 'pagato'; // O 'in_preparazione'
        
        // 1. CREAZIONE ORDINE PRINCIPALE
        // Inseriamo una riga nella tabella 'orders'
        $sql_insert_order = "INSERT INTO orders (user_id, totale, stato, data_ordine, indirizzo_spedizione) VALUES (?, ?, ?, NOW(), ?)";
        $stmt_order = $conn->prepare($sql_insert_order);
        // "idss" = integer, double (decimale), string, string
        $stmt_order->bind_param("idss", $user_id, $total_final, $status, $address_snapshot); 
        $stmt_order->execute(); 
        
        // Recuperiamo l'ID appena creato dal database (es. Ordine #105)
        $new_order_id = $conn->insert_id;

        // 2. SALVATAGGIO DETTAGLI PRODOTTI
        // Per ogni prodotto nel carrello, creiamo una riga nella tabella 'order_details'
        // collegandola all'ID dell'ordine appena creato ($new_order_id).
        foreach($cart_data_for_processing as $item) {
            $sql_detail = "INSERT INTO order_details (order_id, product_id, quantita, prezzo_unitario) VALUES (?, ?, ?, ?)";
            $stmt_detail = $conn->prepare($sql_detail);
            $stmt_detail->bind_param("iiid", $new_order_id, $item['product_id'], $item['quantita'], $item['prezzo']);
            $stmt_detail->execute();
        }

        // 3. SVUOTAMENTO CARRELLO
        // L'ordine è salvato, quindi il carrello temporaneo non serve più.
        $sql_clear = "DELETE FROM cart_items WHERE user_id = ?";
        $stmt_clear = $conn->prepare($sql_clear);
        $stmt_clear->bind_param("i", $user_id);
        $stmt_clear->execute();

        // 4. FINE! REINDIRIZZAMENTO
        // Mandiamo l'utente al suo profilo per vedere lo storico ordini.
        // Passiamo un parametro GET ?order_success=... per mostrare un messaggio di "Grazie".
        header("Location: profile.php?view=orders&order_success=" . $new_order_id);
        exit;
    }
}
?>

<div class="container" style="margin-top: 40px; margin-bottom: 80px;">
    
    <h1 style="color: var(--primary); margin-bottom: 30px; border-bottom: 2px solid var(--accent); padding-bottom: 10px;">
        <i class="fa-solid fa-credit-card"></i> Conferma Ordine e Pagamento
    </h1>
    
    <?php echo $msg; ?>

    <div style="display: flex; gap: 40px; flex-wrap: wrap; align-items: flex-start;">

        <div style="flex: 2; min-width: 350px;">
            
            <div style="margin-bottom: 30px; background: white; padding: 25px; border-radius: 8px; box-shadow: var(--shadow);">
                <h3 style="color: var(--text-dark); margin-bottom: 15px; font-size: 1.2rem;">
                    <i class="fa-solid fa-map-location-dot"></i> Indirizzo e Dati di Contatto
                </h3>
                
                <form method="POST" action="checkout.php">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">Nome</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($user_data['nome']); ?>" required class="checkout-input-fix">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">Cognome</label>
                            <input type="text" name="cognome" value="<?php echo htmlspecialchars($user_data['cognome']); ?>" required class="checkout-input-fix">
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Email (Non modificabile)</label>
                        <input type="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" class="checkout-input-fix" disabled style="background:#f9f9f9; color:#999;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Telefono</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($user_data['telefono']); ?>" class="checkout-input-fix">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Indirizzo di Spedizione</label>
                        <textarea name="indirizzo_spedizione" required class="checkout-input-fix" rows="3" style="resize: vertical; font-family:sans-serif;"><?php echo htmlspecialchars($user_data['indirizzo_spedizione']); ?></textarea>
                    </div>

                    <button type="submit" name="update_address" class="btn-add" style="width: auto; padding: 8px 15px; font-size: 0.9rem;">
                        <i class="fa-solid fa-save"></i> Salva Dati
                    </button>
                    <small style="display: block; margin-top: 10px;">Questi dati saranno salvati anche nel tuo profilo.</small>
                </form>
            </div>

            <div class="checkout-section-box" style="background: white; padding: 25px; border-radius: 8px; box-shadow: var(--shadow);">
                <h3 style="color: var(--text-dark); margin-bottom: 20px; font-size: 1.2rem;">
                    <i class="fa-solid fa-credit-card"></i> Metodo di Pagamento (Simulato)
                </h3>
                
                <form method="POST" action="checkout.php">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Numero Carta</label>
                        <input type="text" placeholder="XXXX XXXX XXXX XXXX" required 
                               class="checkout-input-fix">
                    </div>
                    
                    <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">Scadenza</label>
                            <input type="text" placeholder="MM/YY" required class="checkout-input-fix">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">CVC</label>
                            <input type="text" placeholder="XXX" required class="checkout-input-fix">
                        </div>
                    </div>
                    
                    <button type="submit" name="complete_order" id="submit-order-btn" class="btn-large" style="width: 100%; height: 55px; margin-top: 10px;">
                        <i class="fa-solid fa-check"></i> COMPLETA ORDINE E PAGA € <?php echo number_format($total_final, 2); ?>
                    </button>
                </form>

            </div>
        </div>


        <div class="summary-box" style="flex: 1; min-width: 280px; background: var(--bg-light); padding: 25px; border-radius: 8px; box-shadow: var(--shadow); border: 1px solid #ddd;">
            <h2 style="font-size: 1.5rem; margin-bottom: 20px; color: var(--text-dark);">Riepilogo</h2>
            
            <?php 
            // Qui scorriamo l'array che abbiamo salvato prima ($cart_data_for_processing)
            // invece di interrogare di nuovo il database.
            foreach($cart_data_for_processing as $item): 
            ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem;">
                    <span style="color: #666;"><?php echo htmlspecialchars($item['quantita']); ?> x <?php echo htmlspecialchars($item['nome']); ?></span>
                    <span style="font-weight: 600;">€ <?php echo number_format($item['prezzo'] * $item['quantita'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            
            <div style="border-top: 1px dashed #ccc; margin-top: 15px; padding-top: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 1rem;">
                    <span>Subtotale:</span>
                    <span>€ <?php echo number_format($total_cart_price, 2); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1rem;">
                    <span>Spedizione:</span>
                    <span>€ <?php echo number_format($shipping_cost, 2); ?></span>
                </div>
            </div>

            <div style="border-top: 3px solid var(--primary); padding-top: 15px; display: flex; justify-content: space-between; font-size: 1.8rem; font-weight: bold;">
                <span>Totale Finale:</span>
                <span>€ <?php echo number_format($total_final, 2); ?></span>
            </div>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>