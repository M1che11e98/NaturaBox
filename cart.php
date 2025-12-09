<?php
// ============================================================================
// FILE: cart.php - VERSIONE "ANALISI GRAMMATICALE"
// Qui spieghiamo ogni singola funzione (le parti solitamente gialle/colorate)
// ============================================================================

// 'ini_set': È una FUNZIONE. Dice al PHP di cambiare un'impostazione.
// Qui diciamo: "Impostazione 'display_errors', metti valore 1 (acceso)".
ini_set('display_errors', 1);

// 'error_reporting': Altra FUNZIONE. Decide quali errori mostrare.
// E_ALL è una COSTANTE (un valore fisso) che significa "Tutti i tipi di errore".
error_reporting(E_ALL);

// 'include': È un COMANDO che prende il contenuto di un altro file ('header.php')
// e lo incolla qui virtualmente. Serve per non riscrivere il menu ogni volta.
include 'header.php';

// 'isset': FUNZIONE FONDAMENTALE. Significa "Esiste questa variabile?".
// $_SESSION['user_id'] è la variabile che contiene l'ID dell'utente loggato.
// Il punto esclamativo ! all'inizio inverte il significato: "SE NON ESISTE..."
if (!isset($_SESSION['user_id'])) {
    
    // 'echo': È il comando di STAMPA. Scrive codice HTML sullo schermo.
    echo "<div class='container' style='margin-top: 50px;'><p class='alert alert-error'>Devi accedere per visualizzare il carrello.</p></div>";
    
    include 'footer.php';
    
    // 'exit': FUNZIONE DI STOP. Ferma immediatamente la lettura del file.
    // È come tirare il freno a mano: niente sotto questa riga verrà eseguito.
    exit;
}

// Assegnazione variabile: copiamo l'ID dalla sessione (global) a una variabile locale ($user_id) più comoda.
$user_id = $_SESSION['user_id'];
$total_cart_price = 0;
$shipping_cost = 5.00;

// STRINGA SQL: È il comando che invieremo al database.
// I punti di domanda (?) sono dei segnaposto di sicurezza.
$sql_cart = "
    SELECT 
        ci.product_id, ci.quantita, 
        p.nome, p.prezzo, p.immagine
    FROM 
        cart_items ci
    JOIN 
        products p ON ci.product_id = p.id
    WHERE 
        ci.user_id = ?
";

// '$conn->prepare': METODO (Funzione di un oggetto).
// Prepara la connessione al database a ricevere la query, ma non la esegue ancora.
$stmt = $conn->prepare($sql_cart);

// '$stmt->bind_param': METODO DI SICUREZZA.
// Sostituisce il '?' della query con la variabile vera ($user_id).
// "i" sta per "integer" (numero intero). Dice al DB: "Tratta questo dato come un numero, non come codice hackerabile".
$stmt->bind_param("i", $user_id);

// '$stmt->execute': METODO DI ESECUZIONE.
// Adesso la query parte davvero e va al database.
$stmt->execute();

// '$stmt->get_result': METODO DI RACCOLTA.
// Prende i dati tornati dal database e li mette nella variabile $cart_items.
$cart_items = $stmt->get_result();
?>

<div class="container" style="margin-top: 40px; margin-bottom: 80px;">
    <h1 style="color: var(--primary); margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <i class="fa-solid fa-shopping-cart"></i> Il tuo Carrello
    </h1>

    <?php 
    // '$cart_items->num_rows': PROPRIETÀ.
    // Chiede: "Quante righe di risultati ci sono?".
    // Se è maggiore (>) di 0, vuol dire che il carrello non è vuoto.
    if ($cart_items->num_rows > 0): 
    ?>
        
        <div style="display: flex; gap: 40px; flex-wrap: wrap; align-items: flex-start;">
            
            <div style="flex: 2; min-width: 350px;">
                
                <?php 
                // 'while': CICLO (Loop).
                // Traduzione: "Finché riesci a estrarre ($fetch_assoc) una riga di prodotto, continua a ripetere questo blocco".
                // '$item' diventerà un array con i dati di UN prodotto alla volta.
                while($item = $cart_items->fetch_assoc()): 
                    
                    // MATEMATICA SEMPLICE
                    // Moltiplichiamo prezzo per quantità.
                    $subtotal = $item['prezzo'] * $item['quantita'];
                    
                    // '+=': Operatore di somma cumulativa.
                    // Aggiunge il subtotale al totale generale. È come dire: $total = $total + $subtotal.
                    $total_cart_price += $subtotal;
                ?>
                
                <div style="display: flex; gap: 20px; align-items: center; border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 15px; background: white;">
                    
                    <div style="width: 80px; height: 80px; overflow: hidden; border-radius: 6px; flex-shrink: 0;">
                        <img src="uploads/<?php echo $item['immagine']; ?>" 
                             alt="<?php 
                                 // 'htmlspecialchars': FUNZIONE DI SICUREZZA (Spesso Gialla).
                                 // Trasforma caratteri speciali (come <, >, &) in codici HTML innocui.
                                 // Se un prodotto si chiamasse "<b>Ciao</b>", questa funzione impedirebbe che la scritta diventi grassetto.
                                 // USALA SEMPRE quando stampi dati che vengono dal database.
                                 echo htmlspecialchars($item['nome']); 
                             ?>" 
                             style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    
                    <div style="flex-grow: 1;">
                        <h3 style="font-size: 1.1rem; margin-bottom: 5px; color: var(--text-dark);">
                            <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                <?php echo htmlspecialchars($item['nome']); ?>
                            </a>
                        </h3>
                        <p style="font-size: 0.9rem; color: #888;">
                            Prezzo Unitario: € 
                            <?php 
                            // 'number_format': FUNZIONE DI FORMATTAZIONE (Spesso Gialla).
                            // Serve per mostrare i soldi correttamente.
                            // Parametri: (Numero, Decimali, Separatore decimali, Separatore migliaia).
                            // Qui diciamo: "Mostra 2 decimali". Quindi 5 diventa 5.00.
                            echo number_format($item['prezzo'], 2); 
                            ?>
                        </p>
                    </div>
                    
                    <form action="cart_action.php" method="POST" style="display: flex; align-items: center; gap: 5px; flex-shrink: 0;">
                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                        <input type="hidden" name="action" value="update">
                        
                        <input type="number" name="quantita" value="<?php echo $item['quantita']; ?>" min="0" max="99" 
                               style="width: 50px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
                        
                        <button type="submit" style="background: var(--accent); color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">
                            Aggiorna
                        </button>
                    </form>

                    <div style="text-align: right; font-weight: bold; font-size: 1.2rem; width: 120px; flex-shrink: 0;">
                        € <?php echo number_format($subtotal, 2); ?>
                    </div>
                    
                    <a href="cart_action.php?action=remove&product_id=<?php echo $item['product_id']; ?>" 
                       style="color: var(--danger); font-size: 1.2rem; margin-left: 10px;" title="Rimuovi">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>
                </div>
                <?php endwhile; // Fine del ciclo while ?>
                
            </div>

            <div style="flex: 1; min-width: 250px; background: var(--white); padding: 25px; border-radius: 8px; box-shadow: var(--shadow); border: 1px solid #eee;">
                <h2 style="font-size: 1.3rem; margin-bottom: 20px; color: var(--text-dark); border-bottom: 1px solid #eee; padding-bottom: 10px;">Riepilogo Ordine</h2>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 1rem;">
                    <span>Subtotale Prodotti:</span>
                    <span style="font-weight: 500;">€ <?php echo number_format($total_cart_price, 2); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1rem;">
                    <span>Spedizione:</span>
                    <span style="font-weight: 500;">€ <?php echo number_format($shipping_cost, 2); ?></span>
                </div>

                <div style="border-top: 2px solid var(--primary); padding-top: 15px; display: flex; justify-content: space-between; font-size: 1.5rem; font-weight: bold;">
                    <span>Totale (IVA Inclusa):</span>
                    <span>€ <?php echo number_format($total_cart_price + $shipping_cost, 2); ?></span>
                </div>

                <a href="checkout.php" class="btn-large" style="margin-top: 30px; text-decoration: none; display: flex; align-items: center; justify-content: center; height: 50px;">
                    <i class="fa-solid fa-credit-card"></i> Procedi al Checkout
                </a>
            </div>

        </div>

    <?php else: ?>
        <div style="text-align:center; padding: 80px 20px; color:#888; background: white; border-radius: 12px; border: 1px solid #eee;">
            <i class="fa-solid fa-box-open" style="font-size: 4rem; margin-bottom: 20px; display:block; color:#ccc;"></i>
            <h3 style="font-size: 1.5rem; color: #666; margin-bottom: 20px;">Il tuo carrello è vuoto.</h3>
            <a href="index.php" class="btn-add" style="display: inline-block; width: auto; padding: 12px 30px; text-decoration: none; margin-top: 15px;">Inizia lo Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>