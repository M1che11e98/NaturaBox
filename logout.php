<?php
// 1. Avvia la sessione PHP (necessario per poterla manipolare)
session_start();

// 2. Distrugge tutti i dati registrati nella sessione
// Questo comando svuota la memoria temporanea del browser
session_destroy();
// Cancella anche il cookie della sessione
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Reindirizza l'utente alla Home Page
// L'utente non si accorge di nulla, la pagina index.php viene caricata come ospite
header("Location: index.php");

// Interrompe l'esecuzione dello script (molto importante dopo un redirect)
exit;
?>