<?php
// ============================================================================
// FILE: register.php
// LEZIONE: REGISTRAZIONE UTENTI E CRITTOGRAFIA PASSWORD
// Questo file raccoglie i dati, controlla che siano validi e li salva nel database.
// ============================================================================

// 1. CONFIGURAZIONE
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'header.php'; // Connessione DB ($conn) e Sessione

// Variabili per gestire i messaggi all'utente
$error = "";
$success = "";

// 2. GESTIONE DEL FORM (QUANDO L'UTENTE CLICCA "REGISTRATI")
// $_SERVER["REQUEST_METHOD"]: Controlla se la pagina è stata richiesta per visualizzarla (GET)
// o per inviare dati (POST).
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- A. PULIZIA DATI (SANITIZATION) ---
    // Usiamo real_escape_string per evitare che simboli strani (come l'apostrofo) rompano l'SQL.
    // Esempio: "D'Annunzio" diventerebbe un errore senza questa funzione.
    $nome = $conn->real_escape_string($_POST['nome']);
    $cognome = $conn->real_escape_string($_POST['cognome']);
    $email = $conn->real_escape_string($_POST['email']);
    $indirizzo = $conn->real_escape_string($_POST['indirizzo']); 
    
    // Le password NON vanno pulite con escape (potrebbero contenere caratteri speciali validi),
    // ma le trattiamo con cautela.
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- B. VALIDAZIONE (CONTROLLI DI SICUREZZA) ---
    
    // 1. Le password coincidono?
    if ($password !== $confirm_password) {
        $error = "Le password non coincidono.";
    } 
    // 2. La password è abbastanza lunga?
    elseif (strlen($password) < 6) {
        $error = "La password deve essere di almeno 6 caratteri.";
    } 
    else {
        // --- C. CONTROLLO DUPLICATI ---
        // Prima di salvare, chiediamo al DB: "Esiste già qualcuno con questa email?"
        $check_email = "SELECT id FROM users WHERE email = '$email'";
        $result = $conn->query($check_email);

        if ($result->num_rows > 0) {
            $error = "Questa email è già registrata. <a href='login.php'>Accedi qui</a>.";
        } else {
            // --- D. HASHING DELLA PASSWORD (FONDAMENTALE!) ---
            /* password_hash(): Trasforma "gatto123" in una stringa incomprensibile 
               (es. $2y$10$Kp8...).
               PASSWORD_DEFAULT: Dice a PHP di usare l'algoritmo più sicuro attuale (es. Bcrypt).
               PERCHÉ? Se un hacker ruba il database, non potrà leggere le password degli utenti.
            */
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // --- E. SALVATAGGIO NEL DATABASE ---
            // Inseriamo la nuova riga nella tabella 'users'.
            // Nota: Salviamo $hashed_password, MAI la $password originale.
            $sql = "INSERT INTO users (nome, cognome, email, password, indirizzo_spedizione) 
                    VALUES ('$nome', '$cognome', '$email', '$hashed_password', '$indirizzo')";

            if ($conn->query($sql) === TRUE) {
                $success = "Registrazione avvenuta con successo! Ora puoi accedere.";
            } else {
                $error = "Errore nel database: " . $conn->error;
            }
        }
    }
}
?>

<style>
    .register-container {
        width: 90%;             /* Occupa il 90% della larghezza disponibile */
        max-width: 1100px;      /* Ma fermati se arrivi a 1100px (schermi giganti) */
        min-width: 600px;       /* Non diventare più stretto di 600px (su desktop) */
        margin: 50px auto 80px; /* Centra orizzontalmente */
    }
    
    /* MEDIA QUERY (Responsive):
       Quando lo schermo è piccolo (cellulare/tablet < 768px),
       togliamo il limite minimo di larghezza, altrimenti il sito esce dallo schermo.
    */
    @media (max-width: 768px) {
        .register-container {
            width: 95%;
            min-width: 0; /* Annulla il vincolo dei 600px */
        }
    }
</style>

<div class="container register-container">
    
    <div class="card" style="padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
        
        <h2 style="text-align: center; color: var(--primary); margin-bottom: 30px; font-size: 1.8rem; font-weight: 800;">Crea il tuo Account</h2>

        <?php if ($error): ?>
            <div style="background: #ffe6e6; color: #d63031; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background: #e6fffa; color: #00b894; padding: 30px; border-radius: 8px; margin-bottom: 25px; text-align: center;">
                <i class="fa-solid fa-check-circle" style="font-size: 3rem; margin-bottom: 15px; display:block;"></i> 
                <h3 style="margin-bottom: 10px;">Benvenuto!</h3>
                <p style="margin-bottom: 20px;"><?php echo $success; ?></p>
                <a href="login.php" class="btn-add" style="display: inline-block; width: auto; padding: 12px 40px; text-decoration: none;">VAI AL LOGIN</a>
            </div>
        
        <?php else: ?>
            <form method="POST" action="">
                
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; font-size: 0.95rem; display: block; margin-bottom: 8px;">Nome</label>
                    <input type="text" name="nome" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; font-size: 0.95rem; display: block; margin-bottom: 8px;">Cognome</label>
                    <input type="text" name="cognome" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; font-size: 0.95rem; display: block; margin-bottom: 8px;">Email</label>
                    <input type="email" name="email" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; font-size: 0.95rem; display: block; margin-bottom: 8px;">Indirizzo Spedizione</label>
                    <textarea name="indirizzo" required rows="3" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: sans-serif; font-size: 1rem; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; font-size: 0.95rem; display: block; margin-bottom: 8px;">Password</label>
                    <input type="password" name="password" required minlength="6" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="font-weight: bold; font-size: 0.95rem; display: block; margin-bottom: 8px;">Conferma Password</label>
                    <input type="password" name="confirm_password" required minlength="6" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                </div>

                <button type="submit" class="btn-add" style="height: 55px; font-size: 1.1rem; letter-spacing: 1px; width: 100%;">REGISTRATI ORA</button>
            
            </form>
            
            <p style="text-align: center; margin-top: 25px; font-size: 0.95rem; color: #666;">
                Hai già un account? <a href="login.php" style="color: var(--primary); font-weight: bold;">Accedi</a>
            </p>
        <?php endif; // Fine blocco else ?>

    </div>
</div>

<?php include 'footer.php'; ?>