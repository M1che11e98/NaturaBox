<?php
// ============================================================================
// FILE: header.php
// LEZIONE: IL "CERVELLO" DELLA PAGINA E LA BARRA DI NAVIGAZIONE
// Questo file fa due cose:
// 1. Prepara i dati (Sessione, Carrello, Chi sei?)
// 2. Disegna la barra in alto (Logo, Ricerca, Menu)
// Viene incluso in TUTTE le pagine, quindi il codice qui gira sempre.
// ============================================================================

// --- 1. GESTIONE DELLA SESSIONE ---
// La sessione è come un "timbro sulla mano" quando entri in discoteca.
// Serve al server per ricordarsi chi sei mentre cambi pagina.
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // "Avvia la memoria dell'utente"
}

// Includiamo il database per poter fare domande (Query)
include_once 'db.php'; 

// --- 2. VARIABILI DI DEFAULT ---
// Inizializziamo le variabili per evitare errori se l'utente è un ospite.
$cart_count = 0;                  // All'inizio il carrello è vuoto
$user_icon = 'avatar_default.png'; // Icona standard per chi non ha foto
// Cache Buster: Un numero casuale aggiunto alle immagini (es. image.jpg?v=1234)
// Serve a costringere il browser a scaricare la nuova immagine se l'hai appena cambiata,
// invece di usare quella vecchia salvata in memoria (cache).
$cache_buster = rand(1000, 9999); 

// --- 3. SE L'UTENTE È LOGGATO (HA IL TIMBRO) ---
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    
    // A. CHIEDIAMO AL DB: "Quanti oggetti ha nel carrello questo tizio?"
    // Usiamo 'prepare' per sicurezza (anti-hacker).
    $stmt = $conn->prepare("SELECT SUM(quantita) as totale FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    
    // Operatore '??': Se il risultato è nullo (carrello vuoto), metti 0.
    $cart_count = $row['totale'] ?? 0;

    // B. CHIEDIAMO AL DB: "Qual è la sua foto profilo?"
    $res_icon = $conn->query("SELECT icona_profilo FROM users WHERE id = $uid");
    
    // Se troviamo l'utente e ha un'icona impostata, sovrascriviamo quella di default
    if($res_icon && $row_icon = $res_icon->fetch_assoc()){
        if(!empty($row_icon['icona_profilo'])) {
            $user_icon = $row_icon['icona_profilo'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NaturaBox - Pet Shop</title>
    
    <link rel="stylesheet" href="css/style.css?v=<?php echo $cache_buster; ?>"> 
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header>
    <div class="navbar">
        
        <a href="index.php" class="logo">
            <i class="fa-solid fa-paw"></i> NaturaBox
        </a>

       <form action="index.php" method="GET" class="search-bar">
            <input type="text" name="q" placeholder="Cerca prodotti..." id="searchInput" class="search-input-field" 
                   value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            
            <button type="submit"><i class="fa-solid fa-search"></i></button>
            
            <div id="searchResultsDropdown"></div>
        </form>

        <div class="user-actions">
            
            <?php if (isset($_SESSION['user_id'])): ?>
                
                <a href="profile.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
                    
                    <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; border: 2px solid var(--primary); flex-shrink: 0; display: flex; align-items: center; justify-content: center; background-color: #e8f5e9;">
                        
                        <?php 
                        $avatar_path = 'img/' . $user_icon;
                        // Controllo file: Se il file immagine non esiste nella cartella, mostriamo un'icona generica
                        if (!file_exists($avatar_path) && $user_icon !== 'avatar_default.png'): 
                        ?>
                            <i class="fa-solid fa-user" style="font-size: 0.9rem; color: var(--primary);"></i>
                        <?php else: ?>
                            <img src="img/<?php echo $user_icon; ?>?v=<?php echo $cache_buster; ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    
                    <?php
                        $nome = $_SESSION['user_nome'];
                        $lunghezza = strlen($nome); // Contiamo le lettere del nome
                        
                        // Operatore Ternario (if abbreviato):
                        // Se il nome è lungo (>12), rimpicciolisci il testo, altrimenti usa dimensione normale.
                        // Serve per non rompere il layout se uno si chiama "Massimiliano".
                        $font_size = ($lunghezza > 12) ? "0.75rem" : (($lunghezza > 8) ? "0.85rem" : "0.95rem");
                    ?>
                    <span style="font-weight: 600; color: var(--text-dark); font-size: <?php echo $font_size; ?>; white-space: nowrap;">
                        Ciao, <?php echo htmlspecialchars($nome); ?>!
                    </span>
                </a>
            
            <?php else: ?>
                
                <div style="display: flex; align-items: center; gap: 15px;">
                    
                    <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; border: 1px solid #eee; background: #f9f9f9;">
                        <img src="img/avatar_default.png" alt="Ospite" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.6;">
                    </div>

                    <div class="auth-buttons-group">
                        <a href="login.php" class="btn-login">Accedi</a>
                        <a href="register.php" class="btn-register">Registrati</a>
                    </div>
                </div>
            
            <?php endif; // Fine della scelta Loggato/Ospite ?>

            <a href="cart.php" class="cart-icon">
                <i class="fa-solid fa-shopping-cart fa-lg"></i>
                <span class="badge" style="opacity: <?php echo ($cart_count > 0) ? '1' : '0'; ?>;">
                    <?php echo $cart_count; ?>
                </span>
            </a>
        </div>
    </div>

    <div class="sub-nav">
        <ul>
            <li><a href="index.php">Tutti</a></li>
            <?php
            // MENU DINAMICO:
            // Invece di scrivere i link a mano <li>Cani</li> <li>Gatti</li>...
            // Li chiediamo al database. Se domani aggiungi la categoria "Pesci", 
            // apparirà qui automaticamente senza toccare il codice.
            $cat_sql = "SELECT * FROM categories";
            $cat_res = $conn->query($cat_sql);
            
            if ($cat_res) { 
                // Ciclo While: Per ogni categoria trovata, stampa un <li>
                while($cat = $cat_res->fetch_assoc()) {
                    echo '<li><a href="index.php?cat='.$cat['id'].'">'.$cat['nome'].'</a></li>';
                }
            }
            ?>
        </ul>
    </div>
</header>

<?php 
// Mostra lo slider solo nella home page e nelle pagine categoria, ma non nei risultati di ricerca
if (basename($_SERVER['PHP_SELF']) == 'index.php' && !isset($_GET['q'])): 
?>
    <!-- Contenitore Slider (Gestito dal JavaScript in script.js) -->
    <div class="welcome-slider-container">
        <div class="slider-overlay">
            <h1>Benvenuto su NaturaBox</h1>
            <p>Le migliori offerte e novità per i tuoi amici a quattro zampe!</p>
        </div>
        
        <div class="welcome-slider" id="imageSlider">
            <img src="img/slide1.jpg" alt="Offerta 1" class="slide-image">
            <img src="img/slide2.jpg" alt="Offerta 2" class="slide-image">
            <img src="img/slide3.jpg" alt="Offerta 3" class="slide-image">
        </div>
    </div>
<?php endif; ?>

<main class="container">