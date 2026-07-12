/**
 * colors.js — Palette partagée pour les couleurs catégories/poules
 * Utilisé par : afficheur.js, matchs.js
 * Palette : --categorie-color-1..10 et --poule-color-1..10 (style.css)
 * Logique : couleur basée sur l'ID en base ((id-1) % 10 + 1)
 */

/** Retourne la classe CSS couleur pour une catégorie selon son ID */
function getCategorieColorClassById(id_categorie) {
    const id = parseInt(id_categorie, 10);
    if (isNaN(id) || id <= 0) return '';
    const num = ((id - 1) % 10) + 1;
    return `categorie-${num}`;
}

/** Retourne la classe CSS couleur pour une poule selon son ID */
function getPouleColorClassById(id_poule, id_poule_2) {
    // Inter-poule : couleur spécifique
    if (id_poule_2 !== null && id_poule_2 !== undefined && id_poule_2 !== '') {
        return 'poule-inter';
    }
    const id = parseInt(id_poule, 10);
    if (isNaN(id) || id <= 0) return '';
    const num = ((id - 1) % 10) + 1;
    return `poule-${num}`;
}

/** Version compat avec objet match (utilisée par matchs.js) */
function getCategorieColorClass(match) {
    return getCategorieColorClassById(match.id_categorie);
}
function getPouleColorClass(match) {
    return getPouleColorClassById(match.id_poule, match.id_poule_2);
}
