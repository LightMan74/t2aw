/**
 * Bracket Generator - Gestion des tournois à élimination avec classement complet 1..N
 * Aucune dépendance externe
 */

const BracketGenerator = (() => {

    // ---------- UTILITAIRES ----------

    function nextPowerOfTwo(n) {
        return Math.pow(2, Math.ceil(Math.log2(n)));
    }

    function standardSeedOrder(size) {
        if (size === 1) return [1];
        if (size === 2) return [1, 2];
        let prev = standardSeedOrder(size / 2);
        let result = [];
        prev.forEach(seed => {
            result.push(seed);
            result.push(size + 1 - seed);
        });
        return result;
    }

    /**
     * Construit le winner bracket complet
     * teams: tableau des seeds réels 1..n (n = nombre d'équipes réelles)
     * Retourne { rounds, totalRounds }
     */
    function buildWinnerBracket(nbTeams) {
        const size = nextPowerOfTwo(nbTeams);
        const totalRounds = Math.log2(size);
        const seedOrder = standardSeedOrder(size);

        // Slots initiaux : seed réel ou null (bye) si seed > nbTeams
        let currentSlots = seedOrder.map(seed => ({
            team: seed <= nbTeams ? seed : null,
            fromMatchId: null
        }));

        const rounds = [];
        let matchCounter = 1;

        for (let r = 1; r <= totalRounds; r++) {
            const matches = [];
            const nextSlots = [];

            for (let i = 0; i < currentSlots.length; i += 2) {
                const slotA = currentSlots[i];
                const slotB = currentSlots[i + 1];
                const matchId = `WR${r}M${matchCounter++}`;

                const isBye = (slotA.team === null) !== (slotB.team === null) &&
                    (slotA.team === null || slotB.team === null) &&
                    !(slotA.team === null && slotB.team === null);

                const bothEmpty = slotA.team === null && slotB.team === null;

                const match = {
                    id: matchId,
                    round: r,
                    team1: slotA.team,
                    team2: slotB.team,
                    team1From: slotA.fromMatchId,
                    team2From: slotB.fromMatchId,
                    isBye: isBye,
                    winner: null,
                    loser: null
                };

                // Résolution automatique des byes (propagation immédiate)
                if (isBye) {
                    match.winner = slotA.team !== null ? slotA.team : slotB.team;
                    match.loser = null; // pas de perdant réel sur un bye
                } else if (bothEmpty) {
                    match.winner = null;
                }

                matches.push(match);

                nextSlots.push({
                    team: isBye ? match.winner : null,
                    fromMatchId: isBye ? null : matchId
                });
            }

            rounds.push({ round: r, matches });
            currentSlots = nextSlots;
        }

        return { rounds, totalRounds, size };
    }

    /**
     * Regroupe les perdants par round d'élimination
     * Exclut le round final (géré à part, perdant = place 2 automatiquement)
     */
    function buildLoserGroups(winnerRounds, totalRounds) {
        const groups = {};

        winnerRounds.forEach(roundObj => {
            const r = roundObj.round;
            if (r === totalRounds) return; // la finale est traitée à part, pas un "groupe"

            roundObj.matches.forEach(m => {
                if (m.isBye) return; // pas de perdant sur un bye
                if (!groups[r]) groups[r] = [];
                groups[r].push({ matchId: m.id });
            });
        });

        return groups;
    }

    /**
     * Calcule les plages de classement (places min-max) pour chaque round d'élimination
     */
    function computeRankingRanges(totalRounds, loserGroups) {
        const ranges = {};
        let nextRankStart = 3; // après places 1 et 2 (la grande finale)

        for (let r = totalRounds - 1; r >= 1; r--) {
            const group = loserGroups[r];
            if (!group || group.length === 0) continue;
            const groupSize = group.length;
            const rankFrom = nextRankStart;
            const rankTo = nextRankStart + groupSize - 1;
            ranges[r] = { rankFrom, rankTo, groupSize };
            nextRankStart = rankTo + 1;
        }

        ranges['final'] = { rankFrom: 1, rankTo: 2 };
        return ranges;
    }


    /**
     * Construit les matchs internes d'un groupe de perdants (mini-bracket local)
     * loserSlots: tableau de { fromMatchId } (les perdants viennent de ces matchs winner-bracket)
     * eliminationRound: numéro du round d'élimination (pour nommer les matchs)
     * rankFrom/rankTo: plage de classement à l'intérieur du groupe
     */
    function buildLoserSubBracket(loserSlots, eliminationRound, rankFrom, rankTo) {
        const n = loserSlots.length;
        const size = nextPowerOfTwo(n);
        const subRounds = Math.log2(size);

        // Slots initiaux : team=null pour l'instant (sera rempli à la résolution des matchs winner)
        // On garde la trace du matchId source du winner-bracket pour la propagation plus tard
        let currentSlots = [];
        for (let i = 0; i < size; i++) {
            if (i < n) {
                currentSlots.push({ team: null, fromMatchId: loserSlots[i].matchId, isByeSlot: false });
            } else {
                currentSlots.push({ team: null, fromMatchId: null, isByeSlot: true }); // bye = qualifié direct
            }
        }

        const subBracketRounds = [];
        let matchCounter = 1;

        for (let sr = 1; sr <= subRounds; sr++) {
            const matches = [];
            const nextSlots = [];

            for (let i = 0; i < currentSlots.length; i += 2) {
                const slotA = currentSlots[i];
                const slotB = currentSlots[i + 1];
                const matchId = `LR${eliminationRound}S${sr}M${matchCounter++}`;

                const isBye = slotA.isByeSlot !== slotB.isByeSlot;

                const match = {
                    id: matchId,
                    eliminationRound: eliminationRound,
                    subRound: sr,
                    team1: slotA.team,
                    team2: slotB.team,
                    team1From: slotA.fromMatchId,   // matchId winner-bracket dont vient le perdant (si round 1 du sous-bracket)
                    team2From: slotB.fromMatchId,
                    team1FromSub: slotA.fromSubMatchId || null, // matchId du sous-bracket précédent (si round > 1)
                    team2FromSub: slotB.fromSubMatchId || null,
                    isBye: isBye,
                    winner: null,
                    loser: null
                };

                matches.push(match);

                nextSlots.push({
                    team: null,
                    fromMatchId: null,
                    fromSubMatchId: matchId,
                    isByeSlot: false
                });
            }

            subBracketRounds.push({ subRound: sr, matches });
            currentSlots = nextSlots;
        }

        // Détermination des places finales pour ce groupe
        // Le dernier sous-round donne : gagnant final = rankFrom, perdant final = rankFrom+1 (si groupe de taille 2)
        // Pour groupe de taille 4 : gagnant demi1 vs gagnant demi2 => places rankFrom/rankFrom+1
        //                            perdant demi1 vs perdant demi2 => places rankFrom+2/rankFrom+3
        // On ajoute donc un "petit repêchage" automatique si n > 2

        let placementMatches = [];
        if (subRounds >= 1) {
            const lastRound = subBracketRounds[subRounds - 1];
            // Match(s) pour définir rankFrom / rankFrom+1
            lastRound.matches.forEach(m => {
                placementMatches.push({
                    matchId: m.id,
                    winnerRank: rankFrom,
                    loserRank: rankFrom + 1
                });
            });

            // S'il y a eu plus d'1 match au dernier round (donc groupe de taille 4+),
            // il faut un match de classement pour les perdants du round précédent
            if (subRounds >= 2) {
                const semiRound = subBracketRounds[subRounds - 2];
                // Générer un match de classement entre les perdants des demi-finales
                const rankingMatchTeams = semiRound.matches.map(m => ({ fromSubMatchId: m.id }));
                // Ce match sera ajouté comme un round supplémentaire "placement"
                const placementMatchId = `LR${eliminationRound}PLACEMENT`;
                const placementMatch = {
                    id: placementMatchId,
                    eliminationRound: eliminationRound,
                    isPlacementMatch: true,
                    team1: null,
                    team2: null,
                    team1FromSubLoser: rankingMatchTeams[0].fromSubMatchId,
                    team2FromSubLoser: rankingMatchTeams[1] ? rankingMatchTeams[1].fromSubMatchId : null,
                    winner: null,
                    loser: null
                };
                subBracketRounds.push({ subRound: subRounds + 1, matches: [placementMatch] });
                placementMatches.push({
                    matchId: placementMatchId,
                    winnerRank: rankFrom + 2,
                    loserRank: rankFrom + 3
                });
            }
        }

        return { rounds: subBracketRounds, placementMatches };
    }


    function generateBracket(teams) {
        const nbTeams = teams.length;
        if (nbTeams < 2) throw new Error('Il faut au moins 2 équipes');

        const { rounds, totalRounds, size } = buildWinnerBracket(nbTeams);
        const loserGroups = buildLoserGroups(rounds, totalRounds);
        const rankingRanges = computeRankingRanges(totalRounds, loserGroups);

        // Construction des mini-brackets pour chaque groupe de perdants
        const loserBrackets = {};
        Object.keys(loserGroups).forEach(r => {
            const range = rankingRanges[r];
            loserBrackets[r] = buildLoserSubBracket(
                loserGroups[r],
                parseInt(r),
                range.rankFrom,
                range.rankTo
            );
        });

        return {
            nbTeams,
            bracketSize: size,
            totalRounds,
            winnerRounds: rounds,
            loserGroups,
            loserBrackets,
            rankingRanges
        };
    }

    return {
        generateBracket,
        standardSeedOrder,
        nextPowerOfTwo
    };

})();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = BracketGenerator;
} else if (typeof window !== 'undefined') {
    window.BracketGenerator = BracketGenerator;
}