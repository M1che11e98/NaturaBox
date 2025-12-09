/* ==========================================================================
   FILE: js/script.js
   COS'È: Questo file contiene le "azioni" del sito.
   Mentre HTML è lo scheletro e CSS è il vestito, JS sono i muscoli.
   ========================================================================== */

/* EVENTO: DOMContentLoaded
   
   PROBLEMA: Il browser legge il codice dall'alto in basso. Se il JS prova a 
   cercare un elemento HTML (es. il bottone) prima che l'HTML sia stato 
   completamente caricato, darà errore "Element not found".
   
   SOLUZIONE: Questo comando dice: "Browser, non eseguire nulla di quello che 
   c'è qui dentro finché non hai finito di disegnare tutto l'HTML (DOM)".
*/
document.addEventListener("DOMContentLoaded", function () {
  // ============================================================
  // CAPITOLO 1: LO SLIDER (CAROSELLO IMMAGINI)
  // ============================================================

  /* document.getElementById("...")
     È il modo principale con cui JS "afferra" un elemento dalla pagina.
     Stiamo cercando il contenitore principale dello slider.
     
     const vs let:
     - const (costante): Usalo per cose che NON cambieranno mai (il contenitore slider è sempre quello).
     - let: Usalo per variabili che cambieranno valore nel tempo (es. contatore, punteggio).
  */
  const sliderContainer = document.getElementById("imageSlider");

  /* IF (SE): Controllo di sicurezza.
     Se siamo nella pagina "Chi Siamo" o "Login", lo slider non esiste.
     Senza questo controllo, JS cercherebbe di usarlo e crascherebbe bloccando tutto il resto.
  */
  if (sliderContainer) {
    /* querySelectorAll(".classe")
       Cerca TUTTI gli elementi con quella classe e li mette in una lista (simile a un array).
       Qui prendiamo tutte le immagini (slide 1, slide 2, slide 3).
    */
    const slides = sliderContainer.querySelectorAll(".slide-image");

    // .length ci dice quanti elementi ci sono nella lista (es. 3)
    const totalSlides = slides.length;

    // Indice corrente: parte da 0 (la prima immagine in programmazione è sempre la numero 0)
    let currentIndex = 0;

    // Intervallo di tempo in millisecondi (5000ms = 5 secondi)
    const slideInterval = 5000;

    /* FUNZIONE: goToNextSlide
       Questa è una serie di istruzioni che raggruppiamo sotto un nome.
       Ogni volta che la chiamiamo, esegue il calcolo per spostare l'immagine.
    */
    function goToNextSlide() {
      /* MATEMATICA (Operatore Modulo %):
         Questo è un trucco da programmatori per creare cicli infiniti.
         Immagina di avere 3 slide.
         - Se current è 0 -> (0+1) diviso 3 dà resto 1. Nuovo indice: 1
         - Se current è 1 -> (1+1) diviso 3 dà resto 2. Nuovo indice: 2
         - Se current è 2 -> (2+1) diviso 3 dà resto 0. Nuovo indice: 0 (Torna all'inizio!)
      */
      currentIndex = (currentIndex + 1) % totalSlides;

      /* CALCOLO SPOSTAMENTO CSS:
         Se siamo alla slide 1 (indice 1) su 3 totali, dobbiamo spostare tutto a sinistra.
         -1 * (100 / 3) = -33.33%
         Spostiamo il contenitore verso sinistra del 33%.
      */
      const offsetPercentage = -currentIndex * (100 / totalSlides);

      /* Applicazione dello stile CSS via JS:
         Qui stiamo letteralmente scrivendo "transform: translateX(...)" nell'HTML.
         Il simbolo ` (backtick) ci permette di inserire variabili (${...}) nel testo.
      */
      sliderContainer.style.transform = `translateX(${offsetPercentage}%)`;
    }

    // Se c'è solo 1 immagine, è inutile far partire l'animazione.
    if (totalSlides > 1) {
      // Allarghiamo il contenitore per ospitare tutte le immagini affiancate.
      // Es: 3 immagini -> larghezza 300%
      sliderContainer.style.width = `${totalSlides * 100}%`;

      /* CICLO FOR EACH (Per ogni):
         Passiamo su ogni singola immagine e le diciamo:
         "Tu devi occupare esattamente una frazione dello spazio totale".
         flexShrink = 0 impedisce all'immagine di "schiacciarsi" se lo spazio manca.
      */
      slides.forEach((slide) => {
        slide.style.width = `${100 / totalSlides}%`;
        slide.style.flexShrink = 0;
      });

      /* TIMER (setInterval):
         È come una sveglia che suona ogni 5 secondi (slideInterval) 
         e lancia la funzione goToNextSlide. Va avanti all'infinito finché la pagina è aperta.
      */
      setInterval(goToNextSlide, slideInterval);
    }
  }

  // ============================================================
  // CAPITOLO 2: RICERCA IN TEMPO REALE (AJAX LIVE SEARCH)
  // ============================================================

  // 1. Recuperiamo input (dove scrivi) e dropdown (dove appaiono i risultati)
  const searchInput = document.getElementById("searchInput");
  const resultsDropdown = document.getElementById("searchResultsDropdown");

  /* VARIABILE TIMER (Debounce):
     Serve per memorizzare un "conto alla rovescia".
     È fondamentale per non sovraccaricare il server.
  */
  let searchTimeout;

  // Controllo esistenza: eseguiamo solo se la barra di ricerca esiste in questa pagina
  if (searchInput && resultsDropdown) {
    /* ASCOLTATORE EVENTI (EventListener - "keyup"):
       Il browser "ascolta". Appena l'utente alza il dito da un tasto della tastiera ("keyup"),
       esegue questa funzione.
       
       Nota: 'this' all'interno della funzione si riferisce all'elemento scatenante (searchInput).
    */
    searchInput.addEventListener("keyup", function () {
      // PASSO 1: Resetta il timer precedente.
      // Se scrivo "C", parte un timer. Se subito dopo scrivo "A" (diventa "CA"),
      // blocco il timer della "C" e ne faccio partire uno nuovo.
      clearTimeout(searchTimeout);

      // this.value prende quello che c'è scritto nella casella.
      // .trim() è un pulitore: rimuove gli spazi vuoti accidentali all'inizio o alla fine.
      const query = this.value.trim();

      // PASSO 2: Validazione
      // Se l'utente ha scritto meno di 3 lettere, è inutile cercare.
      // Nascondiamo il menu a tendina e usciamo dalla funzione (return).
      if (query.length < 3) {
        resultsDropdown.style.display = "none";
        return; // Stop, non andare oltre.
      }

      // PASSO 3: Imposta il ritardo (Debounce)
      // Aspetta 300 millisecondi (0.3 secondi). Se l'utente non digita altro in questo tempo,
      // ALLORA esegui il codice dentro le graffe {}.
      searchTimeout = setTimeout(() => {
        /* FETCH API (Il cuore di AJAX):
           Fetch è come un "postino invisibile".
           1. Va all'indirizzo 'search_live.php?q=...' portando la query.
           2. encodeURIComponent converte caratteri strani (es. spazi diventano %20) per non rompere l'URL.
           3. Aspetta la risposta SENZA ricaricare la pagina web.
        */
        fetch(`search_live.php?q=${encodeURIComponent(query)}`)
          /* PROMESSE (.then):
             In JS, le operazioni di rete sono lente. Fetch restituisce una "Promessa" (Promise).
             .then significa: "QUANDO il server risponde, ALLORA fai questo..."
          */
          .then((response) => response.text()) // Prendi la risposta grezza e trasformala in testo/HTML

          .then((html) => {
            // Prendi l'HTML che ha generato il file PHP e "iniettalo" dentro il div dei risultati.
            resultsDropdown.innerHTML = html;
            // Cambia il CSS da display:none a display:block per mostrarlo.
            resultsDropdown.style.display = "block";
          })

          // .catch gestisce gli errori (es. server spento, internet caduta)
          .catch((error) => {
            console.error("Errore AJAX:", error); // Scrive l'errore nella console del browser (F12)
          });
      }, 300); // Fine del ritardo di 300ms
    });

    /* UX (User Experience):
       Vogliamo che il menu si chiuda se l'utente clicca da qualsiasi altra parte nella pagina.
       Ascoltiamo ogni click sul documento intero.
    */
    document.addEventListener("click", function (e) {
      // Se l'elemento cliccato (e.target) NON è la barra di ricerca E NON è il dropdown...
      if (
        !searchInput.contains(e.target) &&
        !resultsDropdown.contains(e.target)
      ) {
        // ...allora nascondi il dropdown.
        resultsDropdown.style.display = "none";
      }
    });
  }
}); // <--- Qui finisce il blocco "DOMContentLoaded"

// ============================================================
// CAPITOLO 3: AGGIUNTA AL CARRELLO (FORM HANDLING)
// ============================================================
/* NOTA IMPORTANTE:
   Questa funzione è definita FUORI da DOMContentLoaded.
   Perché? Perché nel file PHP/HTML la richiamiamo direttamente nel tag:
   <form onsubmit="handleCart(event, this)">
   Deve essere "globale" (visibile ovunque) per funzionare.
*/

function handleCart(event, form) {
  /* PREVENT DEFAULT:
     Fondamentale! Di base, quando invii un form, il browser ricarica la pagina.
     Noi vogliamo fermare questo comportamento standard per gestire tutto "dietro le quinte" con JS.
  */
  event.preventDefault();

  /* FormData:
     È un oggetto magico di JS. Prende il form HTML e raccoglie automaticamente
     tutti gli input che hanno un "name" (product_id, quantity, ecc.) impacchettandoli.
  */
  const formData = new FormData(form);

  // Cerchiamo l'elemento grafico del badge (il pallino rosso col numero) nell'header.
  // querySelector cerca il primo elemento che corrisponde alla classe CSS.
  const cartBadge = document.querySelector(".badge");

  /* LOGICA FALLBACK (OR ||):
     Cerchiamo il bottone dentro IL form che è stato inviato.
     Cerca un elemento con classe .btn-add OPPURE (||) se non lo trovi, cerca .btn-large.
     Questo rende il codice flessibile per diversi design.
  */
  const addButton =
    form.querySelector(".btn-add") || form.querySelector(".btn-large");

  // Sicurezza: se per qualche motivo il bottone non c'è, fermiamo tutto per non avere errori.
  if (!addButton) {
    console.error("Errore critico: Bottone submit non trovato nel form.");
    return;
  }

  // --- FASE 1: FEEDBACK VISIVO (L'utente deve capire che sta succedendo qualcosa) ---
  const originalText = addButton.textContent; // Salviamo il testo originale ("Aggiungi")
  addButton.textContent = "Aggiungo..."; // Cambiamo testo
  addButton.disabled = true; // Disabilitiamo il click (evita doppi acquisti per errore)

  // --- FASE 2: COMUNICAZIONE COL SERVER ---
  fetch("cart_action.php", {
    method: "POST", // Usiamo POST perché stiamo inviando dati sensibili/modifiche
    body: formData, // Alleghiamo il pacchetto dati creato prima
  })
    // Quando il server risponde...
    .then((response) => {
      // --- FASE 3: SUCCESSO E AGGIORNAMENTO UI ---

      // Aggiorniamo il numeretto nel carrello in alto a destra
      if (cartBadge) {
        // Leggiamo il numero attuale. Se è vuoto, assumiamo 0. parseInt converte testo in numero intero.
        let currentCount = parseInt(cartBadge.textContent || 0);

        // Cerchiamo se nel form c'era una casella quantità (pagina prodotto), altrimenti default è 1.
        const quantityInput = form.querySelector(".qty-input");
        let addedQuantity = quantityInput ? parseInt(quantityInput.value) : 1;

        // Matematica semplice: contatore vecchio + nuovi oggetti
        cartBadge.textContent = currentCount + addedQuantity;
        cartBadge.style.opacity = 1; // Assicura che sia visibile
      }

      // Cambiamo il colore e il testo del bottone per dire "Tutto ok!"
      addButton.textContent = "FATTO! 🎉";
      addButton.style.backgroundColor = "#00b894"; // Verde brillante
      addButton.style.color = "white";

      // --- FASE 4: PULIZIA (RESET) ---
      // setTimeout esegue una funzione dopo un ritardo (qui 1500ms = 1.5 secondi)
      setTimeout(() => {
        // Ripristina il testo corretto in base al tipo di bottone
        // classList.contains controlla se il bottone ha una certa classe
        addButton.textContent = addButton.classList.contains("btn-large")
          ? "AGGIUNGI AL CARRELLO"
          : "Aggiungi";

        // Rimuovendo lo stile inline (""), il bottone torna ad usare i colori definiti nel file CSS
        addButton.style.backgroundColor = "";
        addButton.style.color = "";

        // Riabilita il bottone per permettere nuovi acquisti
        addButton.disabled = false;
      }, 1500);
    })

    // --- FASE 5: GESTIONE ERRORI ---
    .catch((error) => {
      // Se il server è giù o c'è un errore di rete
      console.error("Errore Fetch:", error);
      addButton.textContent = "Errore!";
      addButton.style.backgroundColor = "var(--danger)"; // Rosso errore
      addButton.disabled = false; // Riabilita comunque il bottone
    });

  /* return false:
     È una vecchia sicurezza per i form HTML classici. 
     Insieme a event.preventDefault(), assicura che la pagina NON si ricarichi.
  */
  return false;
}
