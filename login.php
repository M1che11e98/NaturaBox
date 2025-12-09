<?php
// ============================================================================
// FILE: login.php
// LEZIONE: IL PORTONE D'INGRESSO (AUTENTICAZIONE)
// Questo file controlla se le credenziali (Email + Password) sono corrette.
// Se sì, crea una "Sessione" che permette all'utente di navigare senza 
// dover rifare il login a ogni pagina.
// ============================================================================

// 1. SETUP STANDARD
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carichiamo l'header (che contiene session_start() e la connessione al DB)
include 'header.php'; 

// --- CONTROLLO "GIÀ DENTRO" ---
// Se l'utente ha già il "timbro" (sessione attiva), non deve stare qui.
if (isset($_SESSION['user_id'])) {
    // Lo rispediamo alla Home Page tramite JavaScript (o header PHP)
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

$error = ""; // Variabile vuota che riempiremo solo se qualcosa va storto

// 2. GESTIONE DEL TENTATIVO DI LOGIN (Quando premi "Accedi")
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // PULIZIA DATI
    // real_escape_string: Pulisce l'email da caratteri strani per evitare hackeraggi SQL.
    $email = $conn->real_escape_string($_POST['email']);
    // Nota: La password NON va pulita con escape, perché potrebbe contenere caratteri speciali validi.
    $password = $_POST['password'];

    // 3. CERCHIAMO L'UTENTE NEL DATABASE
    // Chiediamo: "Esiste qualcuno con questa email? Se sì, dammi i suoi dati e la password cifrata (hash)".
    $sql = "SELECT id, nome, cognome, password, email FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    // Se troviamo ESATTAMENTE 1 riga...
    if ($result->num_rows == 1) {
        
        // Trasformiamo la riga del DB in un array utilizzabile
        $user = $result->fetch_assoc();
        
        // 4. VERIFICA DELLA PASSWORD (CRUCIALE) 
        /* password_verify($password_inserita, $hash_nel_db):
           Non confrontiamo "pippo" con "pippo".
           Confrontiamo "pippo" con un codice incasinato tipo "$2y$10$X8k...".
           Questa funzione fa la magia matematica per dirci se corrispondono.
        */
        if (password_verify($password, $user['password'])) {
            
            // --- SUCCESSO! ---
            // Creiamo il "Timbro" (Sessione). Da ora il server sa chi siamo.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_cognome'] = $user['cognome']; // Salviamo anche il cognome
            $_SESSION['user_email'] = $user['email'];
            
            // Reindirizziamo l'utente alla Home Page
            header("Location: index.php");
            exit;
            
        } else {
            // Caso: Email giusta, Password sbagliata
            $error = "La password inserita non è corretta.";
        }
    } else {
        // Caso: Email non trovata nel database
        $error = "Nessun account trovato con questa email.";
    }
}
?>

<style>
    /* Il contenitore esterno che centra tutto */
    .login-container {
        width: 90%;
        max-width: 600px; /* Non diventerà mai più largo di così */
        min-width: 320px; /* Non diventerà mai più stretto di così */
        margin: 60px auto 80px; /* Centrato orizzontalmente (auto) */
    }

    /* FIX INPUT: Regola magica per evitare problemi di layout */
    .form-input-fix {
        width: 100%;
        padding: 12px 15px 12px 45px; /* 45px a sinistra lascia spazio all'icona */
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
        
        /* box-sizing: border-box
           Significa: "Se dico larghezza 100%, includi anche il padding e il bordo".
           Senza questo, l'input uscirebbe fuori dal contenitore.
        */
        box-sizing: border-box; 
    }

    /* Wrapper per posizionare l'icona sopra l'input */
    .input-wrapper {
        position: relative; /* Diventa il punto di riferimento per l'icona assoluta */
        width: 100%;
    }

    /* L'iconcina (Lucchetto o Busta) */
    .input-icon {
        position: absolute; /* Si muove liberamente sopra il wrapper */
        left: 15px;         /* Incollata a sinistra */
        top: 50%;           /* A metà altezza */
        transform: translateY(-50%); /* Trucco perfetto per centratura verticale esatta */
        color: #aaa;
        pointer-events: none; /* Se ci clicchi sopra, il click passa attraverso e va all'input */
    }
    
    /* Media Query: Su schermi grandi allarghiamo un po' */
    @media (min-width: 769px) {
        .login-container {
            min-width: 500px;
        }
    }
    
    /* Stile hover per il link di registrazione */
    a[href="register.php"]:hover {
        background-color: var(--primary);
        color: white !important;
    }
</style>

<div class="container login-container">
    
    <div class="card" style="padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
        
        <h2 style="text-align: center; color: var(--primary); margin-bottom: 30px; font-size: 1.8rem; font-weight: 800;">Bentornato!</h2>

        <?php if ($error): ?>
            <div style="background: #ffe6e6; color: #d63031; padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: center;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            
            <div style="margin-bottom: 25px;">
                <label style="font-weight: bold; font-size: 0.95rem; display: block; margin-bottom: 8px;">Email</label>
                
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" required class="form-input-fix">
                </div>
            </div>

            <div style="margin-bottom: 35px;">
                <label style="font-weight: bold; font-size: 0.95rem; display: block; margin-bottom: 8px;">Password</label>
                
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" required class="form-input-fix">
                </div>
            </div>

            <button type="submit" class="btn-add" style="height: 55px; font-size: 1.1rem; letter-spacing: 1px; width: 100%; box-sizing: border-box;">ACCEDI</button>
        
        </form>

        <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #eee; text-align: center;">
            <p style="margin-bottom: 15px; color: #666; font-size: 0.95rem;">Non hai ancora un account?</p>
            
            <a href="register.php" style="color: var(--primary); font-weight: 700; text-decoration: none; font-size: 1.05rem; border: 2px solid var(--primary); padding: 12px 35px; border-radius: 30px; display: inline-block; transition: all 0.3s;">
                CREA UN ACCOUNT
            </a>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>