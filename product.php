<?php
// ============================================================================
// FILE: product.php
// LEZIONE: PAGINE DINAMICHE E PARAMETRI GET
// Questa pagina è un "template". È vuota finché non le diciamo quale prodotto caricare.
// Lo facciamo passando l'ID nell'URL (es: product.php?id=5).
// ============================================================================

// 1. SETUP BASE
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'header.php'; // Carica menu, sessione e database ($conn)

// ============================================================
// 2. CONTROLLO DELL'URL (Input Validation)
// ============================================================

// 'isset($_GET['id'])': Controlla se nell'indirizzo c'è scritto "?id=..."
// $_GET è un array che contiene i dati passati via URL.
if (!isset($_GET['id'])) {
    // Se l'utente apre 'product.php' senza id, non sappiamo cosa mostrare.
    // Mostriamo un errore e blocchiamo tutto.
    echo "<div class='container' style='margin-top:50px;'><p class='alert alert-error'>Prodotto non specificato.</p></div>";
    include 'footer.php'; 
    exit;
}

// 'intval(...)': SICUREZZA FONDAMENTALE.
// Prende il valore dall'URL e lo forza a diventare un NUMERO INTERO.
// Se un hacker scrive "product.php?id=DELETE ALL", intval lo trasforma in 0.
// Questo previene la SQL Injection (attacchi al database).
$id = intval($_GET['id']);

// 3. RECUPERO DATI DAL DATABASE
// Chiediamo al DB: "Dammi tutte le info del prodotto che ha questo ID esatto".
$sql = "SELECT * FROM products WHERE id = $id";
$result = $conn->query($sql);

// '$result->num_rows == 0': Significa "Nessun risultato trovato".
// Può succedere se l'utente scrive un ID che non esiste (es. id=9999).
if ($result->num_rows == 0) {
    echo "<div class='container' style='margin-top:50px;'><p class='alert alert-error'>Prodotto non trovato.</p></div>";
    include 'footer.php'; 
    exit;
}

// 'fetch_assoc()': Trasforma il risultato grezzo del DB in un Array associativo.
// Ora possiamo usare $product['nome'], $product['prezzo'], ecc.
$product = $result->fetch_assoc();
?>

<div class="container">

    <a href="index.php" class="back-to-catalog">
        <i class="fa-solid fa-arrow-left"></i> Torna al catalogo
    </a>

    <div class="product-wrapper">
        
        <div class="product-image">
            <img src="uploads/<?php echo $product['immagine']; ?>" 
                 alt="<?php echo htmlspecialchars($product['nome']); ?>">
        </div>

        <div class="product-info-col">
            
            <span style="text-transform: uppercase; color: var(--accent); font-weight: bold; font-size: 0.8rem; letter-spacing: 1px; margin-bottom: 10px; display: block;">
                NaturaBox Selection
            </span>

            <h2 class="product-title"><?php echo htmlspecialchars($product['nome']); ?></h2>
            
            <div class="product-price">
                € <?php echo number_format($product['prezzo'], 2); ?>
                
                <span>€ <?php echo number_format($product['prezzo'] * 1.2, 2); ?></span> 
            </div>

            <p class="product-desc">
                <?php 
                // 'nl2br': FUNZIONE DI FORMATTAZIONE TESTO.
                // "New Line to Break".
                // Nel database, il testo a capo è salvato come un carattere invisibile.
                // L'HTML ignora gli a capo se non usi il tag <br>.
                // Questa funzione trasforma ogni "Invio" del testo in un <br> HTML.
                echo nl2br(htmlspecialchars($product['descrizione_completa'])); 
                ?>
            </p>

            <form class="add-to-cart-box" onsubmit="handleCart(event, this);">
                
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <input type="hidden" name="action" value="add">
                
                <input type="number" name="quantita" value="1" min="1" max="10" class="qty-input">
                
                <button type="submit" class="btn-large">
                 Aggiungi al Carrello
                </button>
            </form>

            <div class="trust-badges">
                <div class="trust-item">
                    <i class="fa-solid fa-truck-fast"></i> Spedizione Rapida
                </div>
                <div class="trust-item">
                    <i class="fa-solid fa-shield-cat"></i> 100% Sicuro
                </div>
                <div class="trust-item">
                    <i class="fa-solid fa-leaf"></i> Naturale
                </div>
            </div>

        </div>
    </div></div>

<?php
// Chiudiamo la pagina
include 'footer.php';
?>