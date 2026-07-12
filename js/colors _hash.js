/**
 * Palette de couleurs partagées — basée sur l'ID des entités.
 * Utilisé par : afficheur.js, matchs.js
 *
 * Le hash est calculé sur l'ID (stable) et non sur le nom.
 * Cela garantit une couleur stable pour une catégorie/poule donnée
 * et une répartition visuelle plus variée qu'un simple modulo.
 */

/**
 * Hash déterministe sur un entier positif → index de couleur 1..10.
 * Utilise un mélange de multiplication premier + XOR décalé.
 */
function hashIdToColorIndex(id) {
    const v = id * 810259;
    // const v = id * 1007767;
    // const v = id * 1215853;
    const h = v ^ (v >>> 16);
    const index = Math.abs(h) % 10;
    return index + 1;
}

function getCategorieColorClassById(id_categorie) {
    const id = parseInt(id_categorie, 10);
    if (isNaN(id) || id <= 0) return '';
    const num = hashIdToColorIndex(id);
    return `categorie-${num}`;
}

function getPouleColorClassById(id_poule, id_poule_2) {
    if (id_poule_2 !== undefined && id_poule_2 !== null && id_poule_2 !== '') {
        return 'poule-inter';
    }
    const id = parseInt(id_poule, 10);
    if (isNaN(id) || id <= 0) return '';
    const num = hashIdToColorIndex(id);
    return `poule-${num}`;
}

function getCategorieColorClass(match) {
    if (!match || !match.id_categorie) return '';
    return getCategorieColorClassById(match.id_categorie);
}

function getPouleColorClass(match) {
    if (!match || !match.id_poule) return '';
    return getPouleColorClassById(match.id_poule, match.id_poule_2);
}
