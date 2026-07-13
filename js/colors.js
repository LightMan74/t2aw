/**
 * Gestion des couleurs catégories / poules
 * Utilisé par : afficheur.js, matchs.js, classement.js
 */

function getCategorieColorClassById(id_categorie) {
    const id = parseInt(id_categorie, 10);
    if (isNaN(id) || id <= 0) return '';
    const num = ((id - 1) % 10) + 1;
    return `categorie-${num}`;
}

function getPouleColorClassById(id_poule, id_poule_2) {
    // Inter-poule : couleur spécifique
    if (id_poule_2 && parseInt(id_poule_2, 10) > 0) {
        return 'poule-inter';
    }
    const id = parseInt(id_poule, 10);
    if (isNaN(id) || id <= 0) return '';
    const num = ((id - 1) % 10) + 1;
    return `poule-${num}`;
}

function getCategorieColorClass(match) {
}
function getPouleColorClass(match) {
}