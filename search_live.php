<?php
// ============================================================================
// FILE: search_live.php
// LEZIONE: MICROSERVIZI E AJAX
// Questo script riceve una parola, cerca nel database e restituisce SOLO 
// l'elenco dei risultati. Non carica l'intera pagina web.
// ============================================================================

// 1. PULIZIA
// Spegniamo gli errori a schermo. Perché?
// Perché se questo file stampasse un errore PHP, il JavaScript che lo chiama
// si confonderebbe e la ricerca si romperebbe.
ini_set('display_errors', 0);

// Carichiamo solo il database. Niente header, niente footer.
include 'db.php'; 

// 2. RECUPERO DATI (Input)
// Prendiamo la parola che l'utente sta scrivendo nella barra di ricerca.
// Viene passata via URL, es: search_live.php?q=croccantini
$query = $_GET['q'] ?? '';

// 3. LOGICA DI RICERCA
// Eseguiamo la ricerca solo se l'utente ha scritto più di 2 lettere.
// Cercare "a" o "il" restituirebbe troppi risultati inutili.
if (strlen($query) > 2) {
    
    // A. PREPARAZIONE DATI
    // real_escape_string: Protegge da caratteri pericolosi.
    $search = $conn->real_escape_string($query);
    
    // Aggiungiamo i simboli jolly '%' (Wildcards) per SQL.
    // %gatto% significa: "Qualsiasi cosa che contiene la parola 'gatto' al centro, all'inizio o alla fine".
    $search_term = "%$search%";
    
    // B. QUERY SQL
    // Cerchiamo nel 'nome' OPPURE nella 'descrizione_breve'.
    // LIMIT 5: Importante! Non vogliamo mostrare 100 risultati nel menu a tendina. Ne bastano 5.
    $sql = "SELECT id, nome, descrizione_breve FROM products WHERE nome LIKE ? OR descrizione_breve LIKE ? LIMIT 5";
    
    // Prepariamo la query sicura
    $stmt = $conn->prepare($sql);
    // "ss" = due stringhe (una per il nome, una per la descrizione)
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    
    // Prendiamo i risultati
    $result = $stmt->get_result();

    // 4. GENERAZIONE OUTPUT (HTML Parziale)
    // Qui non stampiamo <html> o <body>. Stampiamo solo la lista <ul>.
    if ($result->num_rows > 0) {
        
        echo '<ul class="search-results-list">';
        
        // Ciclo: Per ogni prodotto trovato...
        while ($row = $result->fetch_assoc()) {
            
            // Creiamo un elemento della lista <li>
            // Cliccando sul link, l'utente viene portato alla pagina del prodotto specifico.
            echo '<li><a href="product.php?id=' . $row['id'] . '">';
            
            // htmlspecialchars: Sempre, per sicurezza, quando stampiamo dati dal DB.
            echo '<strong>' . htmlspecialchars($row['nome']) . '</strong><br>';
            
            // Mostriamo anche la descrizione in piccolo
            echo '<small>' . htmlspecialchars($row['descrizione_breve']) . '</small>';
            
            echo '</a></li>';
        }
        
        echo '</ul>';
        
    } else {
        // Se non troviamo nulla, stampiamo un messaggio gentile
        echo '<p style="padding: 10px; color: #888;">Nessun risultato trovato.</p>';
    }
}
?>