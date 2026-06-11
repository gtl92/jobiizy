//-- HONEYPOT (anti-bot) -->
// jQuery(document).ajaxSend(function (event, jqxhr, settings) {
//    console.log("AJAX URL :", settings.url);
//     console.log("AJAX DATA :", settings.data);
// });

document.addEventListener('DOMContentLoaded', function () {

    function insertHoneypot() {
        const form = document.querySelector('#cariera_registration');
        if (!form) return;

        // éviter double insertion
        if (document.querySelector('#jobiizy_hp')) return;

        const hp = document.createElement('div');
        hp.style.display = 'none';
        hp.innerHTML = `
            <input type="text" name="jobiizy_hp" id="jobiizy_hp" autocomplete="off">
        `;

        // Ajout à la fin du formulaire (le plus sûr pour Cariera)
        form.appendChild(hp);

        // console.log("💚 Honeypot ajouté via appendChild");
    }

    // Observation du DOM (Cariera AJAX)
    const observer = new MutationObserver(() => insertHoneypot());
    observer.observe(document.body, { childList: true, subtree: true });

    // Exécution immédiate également
    insertHoneypot();
});

(function() {
    // Attendre que jQuery soit prêt (Cariera utilise jQuery.ajax)
    jQuery(document).ajaxSend(function(event, jqxhr, settings) {

        // On cible UNIQUEMENT l'appel d'inscription
        if (settings.data && settings.data.includes('action=cariera_user_registration')) {

            // Notre valeur honeypot
            const hp = document.querySelector('#jobiizy_hp');
            if (!hp) return;

            const value = encodeURIComponent(hp.value);

            // console.log("📌 Injection honeypot dans l'AJAX :", value);

            // Ajouter le champ dans les données POST
            settings.data += '&jobiizy_hp=' + value;
        }
    });
})();