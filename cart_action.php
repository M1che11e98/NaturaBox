<?php
// ============================================================================
// FILE: cart_action.php
// COS'È: Questo è il "Backend" (Controller).
// Non mostra nulla a video (niente HTML), ma riceve dati, parla col Database
// e poi reindirizza l'utente o risponde al JavaScript.
// ============================================================================

// 1. CONFIGURAZIONE ERRORI
// Durante lo sviluppo vogliamo vedere se sbagliamo qualcosa.
// 'display_errors' a 1 accende le notifiche a schermo.
ini_set('display_errors', 1);
error_reporting(E_ALL); // E_ALL significa "segnala qualsiasi tipo di errore"

// 2. AVVIO SESSIONE
// Fondamentale. La sessione è come un "armadietto" sul server dedicato all'utente.
// Ci permette di sapere CHI sta facendo l'azione (user_id).
session_start();

// 3. CONNESSIONE AL DATABASE
// Includiamo il file che contiene le chiavi di accesso al DB ($conn).
include_once 'db.php'; 

// ============================================================
// CONTROLLO SICUREZZA (IL "BUTTAFUORI")
// ============================================================
// Se nell'armadietto della sessione non c'è la tessera 'user_id',
// significa che l'utente non ha fatto il login.
if (!isset($_SESSION['user_id'])) {
    // Lo rispediamo alla pagina di login.
    header("Location: login.php");
    exit; // 'exit' è cruciale: ferma l'esecuzione dello script all'istante.
}

// Recuperiamo l'ID dell'utente dalla sessione per usarlo nelle query SQL
$user_id = $_SESSION['user_id'];

// ============================================================
// RECUPERO DATI IN INGRESSO (INPUT)
// ============================================================

/* SPIEGAZIONE OPERATORE '??' (Null Coalescing):
   $a ?? $b significa: "Prendi $a, ma se $a non esiste o è nullo, prendi $b".
   
   Qui stiamo dicendo: 
   1. Cerca 'action' nei dati inviati via Modulo nascosto (POST).
   2. Se non c'è, cercalo nell'URL (GET, es: cart_action.php?action=remove).
   3. Se non c'è nemmeno lì, metti una stringa vuota ''.
*/
$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* intval():
   È una funzione di pulizia. Converte qualsiasi cosa arrivi in un Numero Intero.
   Se un hacker prova a inviare "product_id=ciao", intval lo trasforma in 0.
   È una prima linea di difesa.
*/
$product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

// La quantità di solito arriva dai form (POST), di default è 1.
$quantita = intval($_POST['quantita'] ?? 1); 

// VALIDAZIONE BASE
// Se l'ID prodotto è 0 o meno, O se non c'è nessuna azione da compiere...
if ($product_id <= 0 || empty($action)) {
    // ...rimandiamo l'utente alla Home, perché c'è qualcosa che non va.
    header("Location: index.php"); 
    exit;
}

// ============================================================================
// CASO 1: AZIONE "ADD" (Aggiungi al carrello)
// ============================================================================
if ($action === 'add') {
    
    // PRIMA DOMANDA AL DB: "L'utente ha già questo prodotto nel carrello?"
    // Usiamo '?' come segnaposto per evitare SQL Injection (sicurezza).
    $check_sql = "SELECT id, quantita FROM cart_items WHERE user_id = ? AND product_id = ?";
    
    // Prepariamo la richiesta (mettiamo la lettera nella busta sicura)
    $stmt = $conn->prepare($check_sql);
    
    // 'bind_param' inserisce i dati veri al posto dei '?'.
    // "ii" significa che stiamo passando due Interi (user_id e product_id).
    $stmt->bind_param("ii", $user_id, $product_id);
    
    // Eseguiamo la query
    $stmt->execute();
    
    // Otteniamo il risultato
    $result = $stmt->get_result();

    // num_rows > 0 significa: "Sì, ho trovato una riga nel database"
    if ($result->num_rows > 0) {
        // --- SCENARIO A: Il prodotto c'è già ---
        // Recuperiamo i dati attuali
        $existing_item = $result->fetch_assoc();
        
        // Calcoliamo la nuova quantità (quella che c'era + quella nuova)
        $new_quantity = $existing_item['quantita'] + $quantita;
        
        // Aggiorniamo (UPDATE) la riga esistente
        $update_sql = "UPDATE cart_items SET quantita = ? WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("ii", $new_quantity, $existing_item['id']);
        $stmt_update->execute();
        
    } else {
        // --- SCENARIO B: Il prodotto non c'è ---
        // Creiamo una nuova riga (INSERT) nel database
        $insert_sql = "INSERT INTO cart_items (user_id, product_id, quantita) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        // "iii" = tre interi: user, product, quantita
        $stmt_insert->bind_param("iii", $user_id, $product_id, $quantita);
        $stmt_insert->execute();
    }
    
    /* REINDIRIZZAMENTO INTELLIGENTE:
       $_SERVER['HTTP_REFERER'] contiene l'URL della pagina da cui arrivava l'utente.
       Lo rimandiamo lì (es. stava sulla pagina prodotto o sulla home).
       Se non c'è un referer, lo mandiamo alla home.
    */
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: " . $redirect_url);
    exit;
}

// ============================================================================
// CASO 2: AZIONE "REMOVE" (Rimuovi dal carrello)
// Questo di solito viene chiamato cliccando un cestino (link GET)
// ============================================================================
if ($action === 'remove') {
    // Comando SQL per cancellare fisicamente la riga
    $delete_sql = "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();

    // Dopo aver cancellato, ricarichiamo la pagina del carrello per mostrare le modifiche
    header("Location: cart.php"); 
    exit;
}

// ============================================================================
// CASO 3: AZIONE "UPDATE" (Cambia quantità dal carrello)
// Esempio: l'utente cambia il numero da 1 a 5 nel carrello e preme "Aggiorna"
// ============================================================================
if ($action === 'update' && $quantita >= 0) {
    
    // Se l'utente mette 0, è come se volesse cancellare il prodotto
    if ($quantita == 0) {
        $delete_sql = "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?";
        $stmt_del = $conn->prepare($delete_sql);
        $stmt_del->bind_param("ii", $user_id, $product_id);
        $stmt_del->execute();
    } else {
        // Altrimenti aggiorniamo il numero
        $update_sql = "UPDATE cart_items SET quantita = ? WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iii", $quantita, $user_id, $product_id);
        $stmt->execute();
    }

    // Torniamo al carrello
    header("Location: cart.php");
    exit;
}

// ============================================================
// FALLBACK (Se qualcosa va storto)
// ============================================================
// Se arriviamo qui significa che 'action' non era né add, né remove, né update.
// Nel dubbio, mandiamo l'utente alla home.
header("Location: index.php");
exit;
?>