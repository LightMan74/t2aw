/**
 * colors.js — coloration catégories et poules
 * Harmonisé avec afficheur.css (variables --categorie-color-N / --poule-color-N)
 * Les classes retournées correspondent aux classes .categorie-N / .poule-N de afficheur.css
 */

/**
 * Retourne la classe CSS de couleur pour une catégorie donnée.
 * @param {number|null} id - id_categorie
 * @returns {string} classe CSS (ex: 'categorie-2') ou '' si non reconnu
 */
function getCategorieColorClassById(id) {
    if (id === null || id === undefined) return '';
    // Palette de 10 couleurs, on détermine l'index par modulo
    const index = ((Number(id) - 1) % 10) + 1;
    return 'categorie-' + index;
}

/**
 * Retourne la classe CSS de couleur pour une poule (ou deux poules).
 * Si deux ids fournis, utilise le premier pour la couleur principale.
 * @param {number|null} idPoule1
 * @param {number|null} idPoule2  (optionnel, ignoré ici — réservé pour inter-poule)
 * @returns {string} classe CSS (ex: 'poule-3') ou '' si non reconnu
 */
function getPouleColorClassById(idPoule1, idPoule2) {
    if (idPoule1 === null || idPoule1 === undefined) return '';
    const index = ((Number(idPoule1) - 1) % 10) + 1;
    return 'poule-' + index;
}