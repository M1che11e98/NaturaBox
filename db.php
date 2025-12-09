<?php
// ============================================================================
// FILE: db.php
// SCOPO: Stabilire il "ponte" tra il codice PHP (il sito) e MySQL (i dati).
// Questo file viene incluso (include) in quasi tutte le pagine.
// ============================================================================

// 1. LE CREDENZIALI (Le chiavi di accesso)
// Definiamo 4 variabili che servono a identificare il database.

// 'localhost': Significa "questo computer". Il database è sulla stessa macchina del sito.
$host = 'localhost';

// 'root': È il nome utente predefinito di XAMPP/MAMP. È l'amministratore supremo.
$user = 'root';      

// Password vuota '': Su XAMPP, di default, l'utente root non ha password.
// Se mettessi il sito online su un server vero, qui ci andrebbe una password complessa!
$password = '';      

// Il nome esatto del database che hai creato in phpMyAdmin.
// Se sbagli una lettera qui, il sito dirà "Unknown database".
$dbname = 'naturabox'; 


// 2. CREAZIONE CONNESSIONE (Il "Ponte")
// 'new mysqli': È un COMANDO SPECIALE (Costruttore).
// Crea un nuovo OGGETTO che rappresenta la connessione attiva.
// Salviamo questo oggetto nella variabile '$conn'.
// Da ora in poi, ogni volta che scriveremo '$conn', useremo questa linea telefonica aperta col database.
$conn = new mysqli($host, $user, $password, $dbname);


// 3. CONTROLLO ERRORI (La linea è caduta?)
// '$conn->connect_error': È una proprietà dell'oggetto. Se c'è stato un errore, contiene il messaggio (es. "Password errata").
// Se è vuoto (null), significa che tutto va bene.
if ($conn->connect_error) {
    // 'die': Uccide lo script. Ferma tutto.
    // Non ha senso caricare il sito se non possiamo prendere i dati.
    // Mostra a schermo l'errore tecnico (utile per noi sviluppatori).
    die("Connessione fallita: " . $conn->connect_error);
}


// 4. CONFIGURAZIONE LINGUA (Charset)
// '$conn->set_charset': Metodo che dice al database: "Parliamo in UTF-8".
// 'utf8mb4' è lo standard moderno che supporta:
// - Accenti italiani (à, è, ì, ò, ù)
// - Caratteri speciali
// - Emoji (😊)
// Senza questa riga, potresti vedere strani simboli al posto degli accenti.
$conn->set_charset("utf8mb4");

// NOTA BENE:
// Non c'è nessun "echo 'Connesso con successo!'".
// Perché? Perché questo file viene incluso ovunque. Se stampassimo un messaggio qui,
// apparirebbe in alto nell'header, nel carrello, o romperebbe le risposte JSON (AJAX).
// Se non vedi errori, vuol dire che funziona (Silenzio = Successo).
?>