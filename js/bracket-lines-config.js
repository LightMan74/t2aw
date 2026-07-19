/**
 * bracket-lines-config.js
 * ================================================================
 * Variables de personnalisation pour les liaisons visuelles du bracket
 * (leader-line.min.js)
 * ================================================================
 */

const BracketLinesConfig = {
    // ---- Couleurs ----
    couleurGagnant: '#22c55e',   // Vert : progression du gagnant
    couleurPerdant: '#ef4444',   // Rouge : progression du perdant (repêchage / petite finale)

    // ---- Opacité ----
    opaciteNormale: 0.35,       // Opacité légère par défaut
    opaciteSurvol: 0.95,       // Opacité augmentée au survol

    // ---- Épaisseur de trait ----
    epaisseurNormale: 2,         // Épaisseur par défaut
    epaisseurSurvol: 5,         // Épaisseur au survol

    // ---- Style du chemin (leader-line path) ----
    // Options: 'straight' | 'arc' | 'curve' | 'fluid' | 'magnet'
    styleChemin: 'magnet',

    // ---- Extrémités (embouts) des lignes ----
    // socket : côté "depuis" (source)
    // plug   : côté "vers"   (destination)
    // Options socket/plug: 'disc' | 'arrow1' | 'arrow2' | 'arrow3' | 'diamond' | 'none'
    socket: 'disc',
    plug: 'arrow2',

    // ---- Durée de transition CSS (ms) ----
    transitionDuree: 300,

    // ---- Activation / désactivation ----
    enabled: true
};