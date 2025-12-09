<?php
// ============================================================================
// FILE: debug.php (O test.php)
// SCOPO: Verificare che tutti i pezzi fondamentali del sito esistano e
// si carichino senza errori. È come il check-up dal meccanico.
// ============================================================================

// 1. FORZATURA VISUALIZZAZIONE ERRORI
// Normalmente, i server nascondono gli errori per sicurezza.
// Qui stiamo dicendo: "Mostrami TUTTO, anche i piccoli avvisi".
// Vogliamo vedere il problema in faccia.
ini_set('display_errors', 1);        // Accende la visualizzazione
ini_set('display_startup_errors', 1); // Mostra errori che avvengono proprio all'avvio
error_reporting(E_ALL);              // Segnala ogni tipo di errore (Avvisi, Fatal, ecc.)

echo "<h1>--- INIZIO TEST DI DEBUG ---</h1>";

// ============================================================
// TEST 1: IL DATABASE (Il cuore)
// ============================================================
echo "<h3>1. Provo a caricare db.php...</h3>";

// 'file_exists': FUNZIONE DI CONTROLLO.
// Chiede al server: "Nella cartella in cui siamo, c'è un file chiamato 'db.php'?"
// Restituisce VERO (sì) o FALSO (no).
if (file_exists('db.php')) {
    
    // Se esiste, proviamo a includerlo.
    // Se db.php avesse un errore di sintassi (es. ; mancante), lo vedremmo qui grazie alle righe 12-14.
    include 'db.php';
    
    // Stampiamo un messaggio verde (HTML inline style) per dire OK.
    echo "<span style='color:green'>OK: db.php caricato correttamente.</span><br>";
    
} else {
    // Se il file non c'è:
    echo "<span style='color:red'>ERRORE: Il file db.php non esiste!</span><br>";
    
    // 'exit': STOP D'EMERGENZA.
    // Se manca il database, è inutile controllare l'header o il footer.
    // Il sito non può funzionare. Fermiamo tutto qui.
    exit;
}

// ============================================================
// TEST 2: L'HEADER (La testa)
// ============================================================
echo "<h3>2. Provo a caricare header.php...</h3>";

if (file_exists('header.php')) {
    // Proviamo a caricarlo. 
    // Nota: L'header mostrerà il menu e il logo a schermo, quindi vedrai graficamente se funziona.
    include 'header.php';
    echo "<br><span style='color:green'>OK: header.php caricato correttamente.</span><br>";
} else {
    echo "<span style='color:red'>ERRORE: Il file header.php non esiste!</span><br>";
    // Anche qui usiamo exit, perché senza header la pagina è rotta.
    exit;
}

// ============================================================
// TEST 3: IL FOOTER (I piedi)
// ============================================================
echo "<h3>3. Provo a caricare footer.php...</h3>";

if (file_exists('footer.php')) {
    include 'footer.php';
    echo "<br><span style='color:green'>OK: footer.php caricato correttamente.</span><br>";
} else {
    echo "<span style='color:red'>ERRORE: Il file footer.php non esiste!</span><br>";
    // Qui NON mettiamo 'exit'. Perché?
    // Perché il footer è l'ultima cosa. Se manca, è un errore grave, 
    // ma il test è comunque finito, quindi lo script terminerebbe da solo.
}

echo "<h1>--- TEST COMPLETATO ---</h1>";
?>