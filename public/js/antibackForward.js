 // Cet événement se déclenche quand la page est affichée, même depuis le cache (back-forward cache)
window.addEventListener('pageshow', function (event) {
    // event.persisted est vrai si la page est chargée depuis le cache "bfcache" (back-forward cache)
    // window.performance.navigation.type === 2 correspond à une navigation "back_forward"
    if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
        // Forcer un rechargement complet depuis le serveur
        window.location.reload();
    }
});