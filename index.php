<?php
// ============================================================================
// FILE: index.php
// LEZIONE: LA VETRINA DINAMICA
// Questa pagina cambia aspetto in base a cosa c'è nell'URL (GET parameters).
// Es: index.php (Mostra tutto) vs index.php?cat=1 (Mostra solo Cani)
// ============================================================================

// 1. SETUP BASE
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'header.php'; // Carica logo, menu e apre la sessione
?>

 

<!-- 3. TITOLO DINAMICO -->
<h2 class="dynamic-title">
    <?php
    // Decidiamo cosa scrivere come titolo della pagina
    if (isset($_GET['q'])) {
        // Caso Ricerca: Mostriamo cosa ha cercato
        echo 'Risultati ricerca per: "' . htmlspecialchars($_GET['q']) . '"';
    } elseif (isset($_GET['cat'])) {
        // Caso Categoria: Dobbiamo trovare il nome della categoria (es. ID 1 = "Cani")
        $cat_id = intval($_GET['cat']);
        // Query rapida per prendere il nome
        $cat_res = $conn->query("SELECT nome FROM categories WHERE id = $cat_id");
        if ($cat_res && $row = $cat_res->fetch_assoc()) {
            echo $row['nome']; // Stampa "Cani"
        } else {
            echo 'Tutti i Prodotti'; // Fallback se l'ID categoria non esiste
        }
    } else {
        // Caso Default: Nessun filtro
        echo 'Tutti i nostri Prodotti';
    }
    ?>
</h2>

<!-- 4. GRIGLIA PRODOTTI -->
<div class="product-grid">
    <?php
    // ============================================================
    // COSTRUZIONE DELLA QUERY SQL (La parte più tecnica)
    // ============================================================
    
    /* TRUCCO "WHERE 1=1":
       Iniziamo la query con una condizione sempre vera (1 uguale a 1).
       Perché? Perché così possiamo aggiungere le altre condizioni con "AND ..."
       senza preoccuparci se è la prima condizione o la seconda.
       
       Senza filtro: SELECT * FROM products WHERE 1=1 (Prende tutto)
       Con cat:      SELECT * FROM products WHERE 1=1 AND category_id = 5
    */
    $sql = "SELECT * FROM products WHERE 1=1";
    
    // SE c'è un filtro categoria nell'URL...
    if (isset($_GET['cat'])) {
        $cat_id = intval($_GET['cat']); // intval pulisce l'input (sicurezza)
        $sql .= " AND category_id = $cat_id"; // .= concatena (aggiunge) testo alla stringa
    }
    
    // SE c'è una ricerca nell'URL...
    if (isset($_GET['q'])) {
        // real_escape_string protegge da caratteri speciali che romperebbero l'SQL (es. l'apostrofo)
        $search = $conn->real_escape_string($_GET['q']);
        // Cerchiamo sia nel nome CHE (OR) nella descrizione
        $sql .= " AND (nome LIKE '%$search%' OR descrizione_breve LIKE '%$search%')";
    }

    // Eseguiamo la query finale
    $result = $conn->query($sql);

    if (!$result) {
        // Se la query esplode (errore di sintassi), lo diciamo
        echo "<p style='color:red'>Errore Database: " . $conn->error . "</p>";
    } elseif ($result->num_rows > 0) {
        
        // ============================================================
        // IL LOOP (Generazione Card)
        // ============================================================
        // Per ogni riga trovata nel DB, creiamo una "Card" HTML
        while($row = $result->fetch_assoc()):
    ?>
        
        <!-- INIZIO CARD PRODOTTO SINGOLA -->
        <div class="card">
            
            <!-- Link sull'immagine -->
            <a href="product.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; display: block;">
                <div style="height: 180px; background-color: white; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <!-- Immagine dal DB (cartella uploads/) -->
                    <img src="uploads/<?php echo $row['immagine']; ?>" alt="<?php echo htmlspecialchars($row['nome']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </a>

            <div class="card-body">
                <!-- Titolo Prodotto -->
                <a href="product.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; color: inherit;">
                    <h3><?php echo htmlspecialchars($row['nome']); ?></h3>
                </a>

                <!-- Descrizione breve -->
                <p><?php echo htmlspecialchars($row['descrizione_breve']); ?></p>
                
                <div class="card-footer-row">
                    <!-- Prezzo -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span class="price-tag">€ <?php echo number_format($row['prezzo'], 2); ?></span>
                    </div>

                    <!-- 
                        FORM DI AGGIUNTA AL CARRELLO
                        Questo form non invia l'utente a un'altra pagina.
                        Grazie a 'onsubmit="handleCart..."', JavaScript intercetta il click,
                        manda i dati in background e aggiorna il carrello senza ricaricare.
                    -->
                    <form class="add-to-cart-form" onsubmit="handleCart(event, this);">
                        <!-- Dati nascosti necessari al backend -->
                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <!-- Bottone Visibile -->
                        <button type="submit" class="btn-add">
                            <i class="fa-solid fa-cart-plus"></i> Aggiungi
                        </button>
                    </form>
                </div> 
                
                <!-- Link dettagli (nascosto dal CSS attuale, ma utile per SEO) -->
                <a href="product.php?id=<?php echo $row['id']; ?>" class="link-details">Vedi dettagli</a>
            </div> 
        </div> 
        <!-- FINE CARD -->

    <?php 
        endwhile; // Fine del ciclo while
    } else {
        // Se non ci sono prodotti (o la ricerca non ha dato risultati)
        echo "<p>Nessun prodotto trovato.</p>";
    }
    ?>
</div> 

<?php
// Chiudiamo la pagina caricando il footer (script JS e copyright)
include 'footer.php';
?>