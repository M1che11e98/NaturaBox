<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'header.php'; 

$avatar_options_selectable = [
    'avatar_default.png' => 'Standard', 'avatar_cat.png' => 'Gatto',
    'avatar_duck.png' => 'Anatra', 'avatar_meerkat.png' => 'Suricato',
    'avatar_bear.png' => 'Orso', 'avatar_rabbit.png' => 'Coniglio',
    'avatar_panda.png' => 'Panda', 'avatar_owl.png' => 'Gufo'
];
$default_avatar_name = 'avatar_default.png';
$msg = ""; 

function get_shipping_status($order_id) {
    return ['key' => 'in_preparazione', 'label' => 'Ordine in Preparazione'];
}

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $updates = [];
    if (isset($_POST['update_profile'])) {
        $updates[] = "nome='" . $conn->real_escape_string($_POST['nome']) . "'";
        $updates[] = "cognome='" . $conn->real_escape_string($_POST['cognome']) . "'";
        $updates[] = "telefono='" . $conn->real_escape_string($_POST['telefono']) . "'";
        $updates[] = "indirizzo_spedizione='" . $conn->real_escape_string($_POST['indirizzo']) . "'";
        
        if (!empty($updates)) {
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id='$user_id'";
            if ($conn->query($sql)) {
                $_SESSION['user_nome'] = $_POST['nome']; 
                $_SESSION['user_cognome'] = $_POST['cognome'];
                header("Location: profile.php?view=info&success=1");
                exit;
            } else {
                $msg = "<div class='alert alert-danger'>Errore aggiornamento: " . $conn->error . "</div>";
            }
        }
    }

    if (isset($_POST['save_avatar']) && isset($_POST['selected_avatar'])) {
        $new_avatar = $conn->real_escape_string($_POST['selected_avatar']);
        if (array_key_exists($new_avatar, $avatar_options_selectable)) {
            $sql = "UPDATE users SET icona_profilo='$new_avatar' WHERE id='$user_id'";
            if ($conn->query($sql)) {
                header("Location: profile.php?success=avatar_updated");
                exit;
            }
        }
    }
}

$sql_user = "SELECT * FROM users WHERE id = '$user_id'";
$res_user = $conn->query($sql_user);
$user_data = $res_user->fetch_assoc();

if (isset($_GET['success'])) {
    if ($_GET['success'] == '1') {
        $msg = "<div class='alert alert-success'>Dati aggiornati con successo!</div>";
    } elseif ($_GET['success'] == 'avatar_updated') {
        $msg = "<div class='alert alert-success'>Avatar aggiornato con successo!</div>";
    }
}

if (isset($_GET['order_success'])) {
    $order_id = intval($_GET['order_success']);
    $msg = "<div class='alert alert-success'><i class='fa-solid fa-gift'></i> Ordine #$order_id completato con successo!</div>";
}

$current_avatar = $user_data['icona_profilo'] ?? $default_avatar_name;
if(empty($current_avatar)) { $current_avatar = $default_avatar_name; }

$view = isset($_GET['view']) ? $_GET['view'] : 'menu';

function display_current_avatar($filename) {
    return '<img src="img/' . $filename . '" alt="Avatar" class="profile-avatar-img">';
}
?>

<div class="container page-container profile-container">

    <?php if ($view == 'menu'): ?>
        
        <?php echo $msg; ?>

        <div class="profile-header">
            <div class="profile-avatar-container">
                <?php echo display_current_avatar($current_avatar); ?>
                <a href="profile.php?view=avatar_select" class="btn-edit-avatar" title="Modifica Avatar">
                    <i class="fa-solid fa-pencil"></i>
                </a>
            </div>
            <h1 class="profile-title">Ciao, <?php echo htmlspecialchars($user_data['nome']); ?>!</h1>
            <p class="profile-subtitle">Benvenuto nella tua area personale.</p>
        </div>

        <div class="dashboard-grid">
            <a href="profile.php?view=info" class="dashboard-card">
                <i class="fa-solid fa-user-pen"></i>
                <h3>I miei dati</h3>
                <p>Modifica nome, indirizzo e contatti</p>
            </a>
            <a href="profile.php?view=orders" class="dashboard-card">
                <i class="fa-solid fa-box-open"></i>
                <h3>I miei ordini</h3>
                <p>Visualizza lo storico acquisti</p>
            </a>
            <a href="logout.php" class="dashboard-card dashboard-card-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <h3>Esci</h3>
                <p>Disconnetti account</p>
            </a>
        </div>

    <?php elseif ($view == 'avatar_select'): ?>
        
        <a href="profile.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Torna al Menu</a>
        
        <div class="card card-padded text-center">
            <h2 class="form-section-title">Scegli il tuo nuovo look</h2>
            
            <form method="POST" action="profile.php">
                <div class="avatar-selection-grid">
                    <?php foreach ($avatar_options_selectable as $filename => $label): ?>
                        <div class="avatar-option">
                            <input type="radio" name="selected_avatar" id="av_<?php echo $filename; ?>" value="<?php echo $filename; ?>" 
                                   <?php echo ($current_avatar == $filename) ? 'checked' : ''; ?>>
                            <label for="av_<?php echo $filename; ?>" title="<?php echo $label; ?>">
                                <img src="img/<?php echo $filename; ?>" alt="<?php echo $label; ?>">
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" name="save_avatar" class="btn-add btn-large-padding">
                        <i class="fa-solid fa-save"></i> SALVA AVATAR
                    </button>
                </div>
            </form>
        </div>

    <?php elseif ($view == 'info'): ?>
        
        <a href="profile.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Torna al Menu</a>
        
        <div class="card card-padded card-narrow">
            <h2 class="form-section-title">I Miei Dati</h2>
            
            <?php echo $msg; ?>

            <form method="POST" action="profile.php?view=info">
                <div class="form-grid-2-col">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" value="<?php echo htmlspecialchars($user_data['nome']); ?>" required class="form-input-fix">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cognome</label>
                        <input type="text" name="cognome" value="<?php echo htmlspecialchars($user_data['cognome']); ?>" required class="form-input-fix">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled class="form-input-fix">
                </div>
                <div class="form-group">
                    <label class="form-label">Telefono</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($user_data['telefono']); ?>" class="form-input-fix">
                </div>
                <div class="form-group">
                    <label class="form-label">Indirizzo di Spedizione</label>
                    <textarea name="indirizzo" rows="3" class="form-input-fix"><?php echo htmlspecialchars($user_data['indirizzo_spedizione']); ?></textarea>
                </div>
                <button type="submit" name="update_profile" class="btn-add">
                    <i class="fa-solid fa-floppy-disk"></i> AGGIORNA PROFILO
                </button>
            </form>
        </div>

    <?php elseif ($view == 'orders'): ?>

        <a href="profile.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Torna al Menu</a>

        <div class="card card-padded">
            <h2 class="form-section-title">I Miei Ordini</h2>
            <?php echo $msg; ?>

            <?php
            $sql_orders = "SELECT * FROM orders WHERE user_id = '$user_id' ORDER BY data_ordine DESC";
            $res_orders = $conn->query($sql_orders);

            if ($res_orders->num_rows > 0):
            ?>
                <div class="table-responsive-wrapper">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Ordine #</th>
                                <th>Data</th>
                                <th>Totale</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $res_orders->fetch_assoc()): 
                                $shipping = get_shipping_status($order['id']);
                            ?>
                            <tr>
                                <td class="order-id">#<?php echo $order['id']; ?></td>
                                <td class="order-date"><?php echo date('d/m/Y', strtotime($order['data_ordine'])); ?></td>
                                <td class="order-total">€ <?php echo number_format($order['totale'], 2); ?></td>
                                <td class="order-status">
                                    <span class="status-badge status-<?php echo $shipping['key']; ?>">
                                        <?php echo $shipping['label']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state-container">
                    <i class="fa-solid fa-box-open empty-state-icon"></i>
                    <p class="empty-state-text">Non hai ancora effettuato ordini.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>