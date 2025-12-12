<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<div class='container' style='margin-top: 50px;'><p class='alert alert-error'>Devi accedere per visualizzare il carrello.</p></div>";
    include 'footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];
$total_cart_price = 0;
$shipping_cost = 5.00;

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

$stmt = $conn->prepare($sql_cart);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();
?>

<div class="container page-container">
    <h1 class="page-title">
        <i class="fa-solid fa-shopping-cart"></i> Il tuo Carrello
    </h1>

    <?php if ($cart_items->num_rows > 0): ?>
        
        <div class="cart-layout">
            
            <div class="cart-items-column">
                
                <?php while($item = $cart_items->fetch_assoc()): 
                    $subtotal = $item['prezzo'] * $item['quantita'];
                    $total_cart_price += $subtotal;
                ?>
                
                <div class="cart-item">
                    <div class="cart-item-image">
                        <img src="uploads/<?php echo $item['immagine']; ?>" 
                             alt="<?php echo htmlspecialchars($item['nome']); ?>" 
                             class="cart-item-img">
                    </div>
                    <div class="cart-item-body">
                        <div class="cart-item-header">
                            <h3 class="cart-item-title">
                                <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                    <?php echo htmlspecialchars($item['nome']); ?>
                                </a>
                            </h3>
                            <a href="cart_action.php?action=remove&product_id=<?php echo $item['product_id']; ?>" 
                               class="btn-remove-item" title="Rimuovi">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                        <p class="cart-item-unit-price">
                            Prezzo Unitario: € <?php echo number_format($item['prezzo'], 2); ?>
                        </p>
                        <div class="cart-item-footer">
                            <form action="cart_action.php" method="POST" class="cart-item-quantity-form">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="hidden" name="action" value="update">
                                <label for="qty-<?php echo $item['product_id']; ?>" class="sr-only">Quantità</label>
                                <input type="number" id="qty-<?php echo $item['product_id']; ?>" name="quantita" value="<?php echo $item['quantita']; ?>" min="0" max="99" class="quantity-input">
                                <button type="submit" class="btn-update-quantity">
                                    Aggiorna
                                </button>
                            </form>
                            <div class="cart-item-subtotal">
                                € <?php echo number_format($subtotal, 2); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                
            </div>

            <div class="cart-summary-column">
                <h2 class="summary-title">Riepilogo Ordine</h2>
                
                <div class="summary-row">
                    <span>Subtotale Prodotti:</span>
                    <span>€ <?php echo number_format($total_cart_price, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Spedizione:</span>
                    <span>€ <?php echo number_format($shipping_cost, 2); ?></span>
                </div>

                <div class="summary-total">
                    <span>Totale (IVA Inclusa):</span>
                    <span>€ <?php echo number_format($total_cart_price + $shipping_cost, 2); ?></span>
                </div>

                <a href="checkout.php" class="btn-large btn-checkout">
                    <i class="fa-solid fa-credit-card"></i> Procedi al Checkout
                </a>
            </div>

        </div>

    <?php else: ?>
        <div class="empty-cart-container">
            <i class="fa-solid fa-box-open empty-cart-icon"></i>
            <h3 class="empty-cart-title">Il tuo carrello è vuoto.</h3>
            <a href="index.php" class="btn-add btn-start-shopping">Inizia lo Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>