<?php
// ============================================================================
// FILE: profile.php
// LEZIONE: DASHBOARD UTENTE E GESTIONE VISTE MULTIPLE
// Questa pagina gestisce 3 schermate diverse in base al parametro 'view':
// 1. Menu principale (view=menu)
// 2. Modifica dati (view=info)
// 3. Storico ordini (view=orders)
// 4. Selezione avatar (view=avatar_select)
// ============================================================================

// 1. SETUP BASE
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'header.php'; // Carica sessione e DB

// CONFIGURAZIONE AVATAR DISPONIBILI
// Array associativo: 'nome_file.png' => 'Nome Leggibile'
// Serve per generare la griglia di scelta avatar senza ripetere codice.
$avatar_options_selectable = [
    'avatar_default.png' => 'Standard',
    'avatar_cat.png' => 'Gatto',
    'avatar_duck.png' => 'Anatra',
    'avatar_meerkat.png' => 'Suricato',
    'avatar_bear.png' => 'Orso',
    'avatar_rabbit.png' => 'Coniglio',
    'avatar_panda.png' => 'Panda',
    'avatar_owl.png' => 'Gufo'
];

$default_avatar_name = 'avatar_default.png';
$msg = ""; // Variabile per messaggi di successo/errore

// FUNZIONE UTILITY: SIMULAZIONE STATO SPEDIZIONE
// In un sito vero, leggeremmo lo stato dalla tabella 'orders' (campo 'stato').
// Qui lo simuliamo per semplicità didattica.
function get_shipping_status($order_id) {
    return ['key' => 'in_preparazione', 'label' => 'Ordine in Preparazione'];
}

// 2. CONTROLLO ACCESSO (Must be logged in)
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// ============================================================
// 3. GESTIONE SALVATAGGI (POST)
// Questa parte viene eseguita SOLO se l'utente ha premuto un bottone "Salva".
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $updates = [];

    // --- CASO A: Aggiornamento Dati Personali ---
    if (isset($_POST['update_profile'])) {
        // Raccogliamo i dati dal form e li puliamo (real_escape_string)
        // Costruiamo pezzi di query SQL: "nome='Pippo'", "cognome='Baudo'"...
        $updates[] = "nome='" . $conn->real_escape_string($_POST['nome']) . "'";
        $updates[] = "cognome='" . $conn->real_escape_string($_POST['cognome']) . "'";
        $updates[] = "telefono='" . $conn->real_escape_string($_POST['telefono']) . "'";
        $updates[] = "indirizzo_spedizione='" . $conn->real_escape_string($_POST['indirizzo']) . "'";
        
        if (!empty($updates)) {
            // implode(', ', $updates) unisce l'array con virgole.
            // Risultato: UPDATE users SET nome='...', cognome='...' WHERE id='...'
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id='$user_id'";
            
            if ($conn->query($sql)) {
                // Aggiorniamo anche la sessione (così l'header cambia subito nome)
                $_SESSION['user_nome'] = $_POST['nome']; 
                $_SESSION['user_cognome'] = $_POST['cognome'];
                // Ricarichiamo la pagina con un messaggio di successo
                header("Location: profile.php?view=info&success=1");
                exit;
            } else {
                $msg = "<div class='alert' style='background:#ffe6e6; color:#d63031; margin-bottom:20px; padding:15px; border-radius:8px;'>Errore aggiornamento: " . $conn->error . "</div>";
            }
        }
    }

    // --- CASO B: Cambio Avatar ---
    if (isset($_POST['save_avatar']) && isset($_POST['selected_avatar'])) {
        $new_avatar = $conn->real_escape_string($_POST['selected_avatar']);
        
        // CONTROLLO SICUREZZA:
        // L'utente potrebbe provare a inviare "hacker.jpg".
        // Controlliamo se il file inviato è presente nella nostra lista approvata ($avatar_options_selectable).
        if (array_key_exists($new_avatar, $avatar_options_selectable)) {
            $sql = "UPDATE users SET icona_profilo='$new_avatar' WHERE id='$user_id'";
            if ($conn->query($sql)) {
                header("Location: profile.php?success=avatar_updated");
                exit;
            }
        }
    }
}

// 4. RECUPERO DATI UTENTE AGGIORNATI (Dal DB)
// Lo facciamo sempre, così se abbiamo appena salvato, vediamo i dati nuovi.
$sql_user = "SELECT * FROM users WHERE id = '$user_id'";
$res_user = $conn->query($sql_user);
$user_data = $res_user->fetch_assoc();

// 5. GESTIONE MESSAGGI (GET)
// Se nell'URL c'è ?success=..., mostriamo un box verde.
if (isset($_GET['success'])) {
    if ($_GET['success'] == '1') {
        $msg = "<div class='alert' style='background:#e6fffa; color:#00b894; margin-bottom:20px; padding:15px; border-radius:8px;'>Dati aggiornati con successo!</div>";
    } elseif ($_GET['success'] == 'avatar_updated') {
        $msg = "<div class='alert' style='background:#e6fffa; color:#00b894; margin-bottom:20px; padding:15px; border-radius:8px;'>Avatar aggiornato con successo!</div>";
    }
}

// Messaggio speciale dopo l'acquisto (Checkout)
if (isset($_GET['order_success'])) {
    $order_id = intval($_GET['order_success']);
    $msg = "<div class='alert' style='background:#d4edda; color:#155724; margin-bottom:20px; padding:15px; border-radius:8px; border: 1px solid #c3e6cb;'>
        <i class='fa-solid fa-gift'></i> Ordine #$order_id completato con successo!
    </div>";
}

// Determina quale icona mostrare
$current_avatar = $user_data['icona_profilo'] ?? $default_avatar_name;
if(empty($current_avatar)) { $current_avatar = $default_avatar_name; }

// Determina quale schermata mostrare (default: menu)
$view = isset($_GET['view']) ? $_GET['view'] : 'menu';

// Funzione helper per stampare l'immagine
function display_current_avatar($filename) {
    return '<img src="img/' . $filename . '" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">';
}
?>

<div class="container" style="margin-top: 40px; margin-bottom: 80px; max-width: 900px;">

    <?php if ($view == 'menu'): ?>
        
        <?php echo $msg; ?>

        <div style="text-align: center; margin-bottom: 30px;">
            <div class="profile-avatar-container">
                <?php echo display_current_avatar($current_avatar); ?>
                
                <a href="profile.php?view=avatar_select" class="btn-edit-avatar" title="Modifica Avatar">
                    <i class="fa-solid fa-pencil"></i>
                </a>
            </div>

            <h1 style="color: var(--primary); margin: 0;">Ciao, <?php echo htmlspecialchars($user_data['nome']); ?>!</h1>
            <p style="color: #666;">Benvenuto nella tua area personale.</p>
        </div>

        <div class="dashboard-grid">
            <a href="profile.php?view=info" class="dashboard-card">
                <i class="fa-solid fa-user-pen"></i>
                <h3>I miei dati</h3>
                <p>Modifica nome, indirizzo e contatti</p>
            </a>
<!--             <a href="profile.php?view=avatar_select" class="dashboard-card">
                <i class="fa-solid fa-image"></i>
                <h3>Cambia Avatar</h3>
                <p>Scegli il tuo personaggio preferito</p>
            </a> -->
            <a href="profile.php?view=orders" class="dashboard-card">
                <i class="fa-solid fa-box-open"></i>
                <h3>I miei ordini</h3>
                <p>Visualizza lo storico acquisti</p>
            </a>
            <a href="logout.php" class="dashboard-card" style="border-color: #ffe6e6;">
                <i class="fa-solid fa-right-from-bracket" style="color: var(--danger);"></i>
                <h3 style="color: var(--danger);">Esci</h3>
                <p>Disconnetti account</p>
            </a>
        </div>

    <?php elseif ($view == 'avatar_select'): ?>
        
        <a href="profile.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Torna al Menu</a>
        
        <div class="card" style="padding: 40px; text-align: center;">
            <h2 style="color: var(--primary); margin-bottom: 30px;">Scegli il tuo nuovo look</h2>
            
            <form method="POST" action="profile.php">
                <div class="avatar-selection-grid">
                    <?php foreach ($avatar_options_selectable as $filename => $label): ?>
                        <div class="avatar-option">
                            <input type="radio" name="selected_avatar" id="av_<?php echo $filename; ?>" 
                                   value="<?php echo $filename; ?>" 
                                   <?php echo ($current_avatar == $filename) ? 'checked' : ''; ?>> <label for="av_<?php echo $filename; ?>" title="<?php echo $label; ?>">
                                <img src="img/<?php echo $filename; ?>" alt="<?php echo $label; ?>">
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" name="save_avatar" class="btn-add" style="width: auto; padding: 10px 40px;">
                        <i class="fa-solid fa-save"></i> SALVA AVATAR
                    </button>
                </div>
            </form>
        </div>

    <?php elseif ($view == 'info'): ?>
        
        <a href="profile.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Torna al Menu</a>
        
        <div class="card" style="padding: 40px; max-width: 700px; margin: 0 auto;">
            <h2 style="color: var(--primary); margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">I Miei Dati</h2>
            
            <?php echo $msg; ?>

            <form method="POST" action="profile.php?view=info">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Nome</label>
                        <input type="text" name="nome" value="<?php echo htmlspecialchars($user_data['nome']); ?>" required class="form-input-fix">
                    </div>
                    <div>
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Cognome</label>
                        <input type="text" name="cognome" value="<?php echo htmlspecialchars($user_data['cognome']); ?>" required class="form-input-fix">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled class="form-input-fix" style="background: #f9f9f9; color: #888;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Telefono</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($user_data['telefono']); ?>" class="form-input-fix">
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Indirizzo di Spedizione</label>
                    <textarea name="indirizzo" rows="3" class="form-input-fix" style="resize: vertical; font-family: sans-serif;"><?php echo htmlspecialchars($user_data['indirizzo_spedizione']); ?></textarea>
                </div>

                <button type="submit" name="update_profile" class="btn-add">
                    <i class="fa-solid fa-floppy-disk"></i> AGGIORNA PROFILO
                </button>
            </form>
        </div>

    <?php elseif ($view == 'orders'): ?>

        <a href="profile.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Torna al Menu</a>

        <div class="card" style="padding: 40px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <h2 style="color: var(--primary); margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">I Miei Ordini</h2>
            
            <?php echo $msg; ?>

            <?php
            // Query per prendere gli ordini dell'utente
            $sql_orders = "SELECT * FROM orders WHERE user_id = '$user_id' ORDER BY data_ordine DESC";
            $res_orders = $conn->query($sql_orders);

            if ($res_orders->num_rows > 0):
            ?>
                <div style="overflow-x: auto;"> <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f9f9f9; text-align: left;">
                                <th style="padding: 15px; border-bottom: 2px solid #eee;">Ordine #</th>
                                <th style="padding: 15px; border-bottom: 2px solid #eee;">Data</th>
                                <th style="padding: 15px; border-bottom: 2px solid #eee;">Totale</th>
                                <th style="padding: 15px; border-bottom: 2px solid #eee;">Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $res_orders->fetch_assoc()): 
                                $shipping = get_shipping_status($order['id']);
                            ?>
                            <tr>
                                <td style="padding: 15px; border-bottom: 1px solid #eee; font-weight: bold;">
                                    #<?php echo $order['id']; ?>
                                </td>
                                <td style="padding: 15px; border-bottom: 1px solid #eee; color: #666;">
                                    <?php echo date('d/m/Y', strtotime($order['data_ordine'])); ?>
                                </td>
                                <td style="padding: 15px; border-bottom: 1px solid #eee; font-weight: bold; color: var(--text-dark);">
                                    € <?php echo number_format($order['totale'], 2); ?>
                                </td>
                                <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                    <span class="status-badge status-<?php echo $shipping['key']; ?>" style="padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">
                                        <?php echo $shipping['label']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding: 50px 20px; color:#888;">
                    <i class="fa-solid fa-box-open" style="font-size: 4rem; margin-bottom: 20px; display:block; color:#eee;"></i>
                    <p style="font-size: 1.1rem; margin-bottom: 20px;">Non hai ancora effettuato ordini.</p>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; // FINE IF VISTE ?>

</div>

<?php include 'footer.php'; ?>