<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'header.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$shipping_cost = 5.00;
$total_cart_price = 0;
$msg = ""; 

$sql_user = "SELECT nome, cognome, email, telefono, indirizzo_spedizione FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();

$sql_cart = "SELECT ci.product_id, ci.quantita, p.nome, p.prezzo 
             FROM cart_items ci 
             JOIN products p ON ci.product_id = p.id 
             WHERE ci.user_id = ?";
$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$cart_items = $stmt_cart->get_result();

if ($cart_items->num_rows == 0) {
    header("Location: cart.php");
    exit;
}

$cart_data_for_processing = [];
while ($item = $cart_items->fetch_assoc()) {
    $subtotal = $item['prezzo'] * $item['quantita'];
    $total_cart_price += $subtotal;
    $cart_data_for_processing[] = $item;
}
$total_final = $total_cart_price + $shipping_cost;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_address'])) {
        $new_nome = $conn->real_escape_string($_POST['nome']);
        $new_cognome = $conn->real_escape_string($_POST['cognome']);
        $new_telefono = $conn->real_escape_string($_POST['telefono']);
        $new_indirizzo = $conn->real_escape_string($_POST['indirizzo_spedizione']);
        
        $sql_update = "UPDATE users SET nome=?, cognome=?, telefono=?, indirizzo_spedizione=? WHERE id=?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssssi", $new_nome, $new_cognome, $new_telefono, $new_indirizzo, $user_id);
        
        if ($stmt_update->execute()) {
            $_SESSION['user_nome'] = $new_nome; 
            $user_data['nome'] = $new_nome; 
            $user_data['cognome'] = $new_cognome; 
            $user_data['telefono'] = $new_telefono; 
            $user_data['indirizzo_spedizione'] = $new_indirizzo;
            $msg = "<div class='alert alert-success'>Indirizzo e Dati aggiornati con successo!</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Errore durante l'aggiornamento.</div>";
        }
    }

    if (isset($_POST['complete_order'])) {
        $address_snapshot = $user_data['indirizzo_spedizione']; 
        $status = 'pagato';
        
        $sql_insert_order = "INSERT INTO orders (user_id, totale, stato, data_ordine, indirizzo_spedizione) VALUES (?, ?, ?, NOW(), ?)";
        $stmt_order = $conn->prepare($sql_insert_order);
        $stmt_order->bind_param("idss", $user_id, $total_final, $status, $address_snapshot); 
        $stmt_order->execute(); 
        
        $new_order_id = $conn->insert_id;

        foreach($cart_data_for_processing as $item) {
            $sql_detail = "INSERT INTO order_details (order_id, product_id, quantita, prezzo_unitario) VALUES (?, ?, ?, ?)";
            $stmt_detail = $conn->prepare($sql_detail);
            $stmt_detail->bind_param("iiid", $new_order_id, $item['product_id'], $item['quantita'], $item['prezzo']);
            $stmt_detail->execute();
        }

        $sql_clear = "DELETE FROM cart_items WHERE user_id = ?";
        $stmt_clear = $conn->prepare($sql_clear);
        $stmt_clear->bind_param("i", $user_id);
        $stmt_clear->execute();

        header("Location: profile.php?view=orders&order_success=" . $new_order_id);
        exit;
    }
}
?>

<div class="container page-container">
    
    <h1 class="page-title">
        <i class="fa-solid fa-credit-card"></i> Conferma Ordine e Pagamento
    </h1>
    
    <?php echo $msg; ?>

    <div class="checkout-layout">

        <div class="checkout-forms-column">
            
            <div class="checkout-section">
                <h3 class="checkout-section-title">
                    <i class="fa-solid fa-map-location-dot"></i> Indirizzo e Dati di Contatto
                </h3>
                
                <form method="POST" action="checkout.php">
                    <div class="form-grid-2-col">
                        <div class="form-group">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($user_data['nome']); ?>" required class="checkout-input-fix">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cognome</label>
                            <input type="text" name="cognome" value="<?php echo htmlspecialchars($user_data['cognome']); ?>" required class="checkout-input-fix">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email (Non modificabile)</label>
                        <input type="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" class="checkout-input-fix" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Telefono</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($user_data['telefono']); ?>" class="checkout-input-fix">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Indirizzo di Spedizione</label>
                        <textarea name="indirizzo_spedizione" required class="checkout-input-fix" rows="3"><?php echo htmlspecialchars($user_data['indirizzo_spedizione']); ?></textarea>
                    </div>

                    <button type="submit" name="update_address" class="btn-add btn-small">
                        <i class="fa-solid fa-save"></i> Salva Dati
                    </button>
                    <small class="form-text">Questi dati saranno salvati anche nel tuo profilo.</small>
                </form>
            </div>

            <div class="checkout-section">
                <h3 class="checkout-section-title">
                    <i class="fa-solid fa-credit-card"></i> Metodo di Pagamento (Simulato)
                </h3>
                
                <form method="POST" action="checkout.php">
                    <div class="form-group">
                        <label class="form-label">Numero Carta</label>
                        <input type="text" placeholder="XXXX XXXX XXXX XXXX" required class="checkout-input-fix">
                    </div>
                    
                    <div class="form-flex-2-col">
                        <div class="form-group">
                            <label class="form-label">Scadenza</label>
                            <input type="text" placeholder="MM/YY" required class="checkout-input-fix">
                        </div>
                        <div class="form-group">
                            <label class="form-label">CVC</label>
                            <input type="text" placeholder="XXX" required class="checkout-input-fix">
                        </div>
                    </div>
                    
                    <button type="submit" name="complete_order" id="submit-order-btn" class="btn-large btn-full-width">
                        <i class="fa-solid fa-check"></i> COMPLETA ORDINE E PAGA € <?php echo number_format($total_final, 2); ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="summary-box checkout-summary-column">
            <h2 class="summary-title">Riepilogo</h2>
            
            <?php foreach($cart_data_for_processing as $item): ?>
                <div class="summary-item">
                    <span class="summary-item-name"><?php echo htmlspecialchars($item['quantita']); ?> x <?php echo htmlspecialchars($item['nome']); ?></span>
                    <span class="summary-item-price">€ <?php echo number_format($item['prezzo'] * $item['quantita'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            
            <div class="summary-divider">
                <div class="summary-row">
                    <span>Subtotale:</span>
                    <span>€ <?php echo number_format($total_cart_price, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Spedizione:</span>
                    <span>€ <?php echo number_format($shipping_cost, 2); ?></span>
                </div>
            </div>

            <div class="summary-total">
                <span>Totale Finale:</span>
                <span>€ <?php echo number_format($total_final, 2); ?></span>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>