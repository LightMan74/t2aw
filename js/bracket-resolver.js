const BracketResolver = (function () {

    /**
     * Initialise l'état des matchs (résultats vides) pour tout le bracket
     */
    function initMatchState(bracket) {
        const state = {
            matches: {},      // matchId -> { winner, loser, played }
            ranks: {}         // team -> rank final (rempli au fur et à mesure)
        };

        // Winner bracket
        bracket.winnerRounds.forEach(round => {
            round.matches.forEach(m => {
                state.matches[m.id] = {
                    team1: m.team1 || null,
                    team2: m.team2 || null,
                    winner: null,
                    loser: null,
                    played: false,
                    isBye: m.isBye || false
                };
                // Si bye, on résout immédiatement (team1 gagne automatiquement, pas de perdant)
                if (m.isBye) {
                    const autoWinner = m.team1 !== undefined && m.team1 !== null ? m.team1 : m.team2;
                    state.matches[m.id].winner = autoWinner;
                    state.matches[m.id].loser = null;
                    state.matches[m.id].played = true;
                }
            });
        });

        // Loser brackets (sub-rounds)
        Object.keys(bracket.loserBrackets).forEach(r => {
            const lb = bracket.loserBrackets[r];
            lb.rounds.forEach(sr => {
                sr.matches.forEach(m => {
                    state.matches[m.id] = {
                        team1: null,
                        team2: null,
                        winner: null,
                        loser: null,
                        played: false,
                        isBye: m.isBye || false,
                        isPlacementMatch: m.isPlacementMatch || false
                    };
                });
            });
        });

        return state;
    }

    /**
     * Trouve un match par son id dans le winner bracket
     */
    function findWinnerMatch(bracket, matchId) {
        for (const round of bracket.winnerRounds) {
            const m = round.matches.find(mm => mm.id === matchId);
            if (m) return m;
        }
        return null;
    }

    /**
     * Trouve un match par son id dans les loser brackets, retourne aussi le groupe (round) parent
     */
    function findLoserMatch(bracket, matchId) {
        for (const r of Object.keys(bracket.loserBrackets)) {
            const lb = bracket.loserBrackets[r];
            for (const sr of lb.rounds) {
                const m = sr.matches.find(mm => mm.id === matchId);
                if (m) return { match: m, loserGroupRound: r, subRoundIndex: sr.subRound };
            }
        }
        return null;
    }

    /**
     * Propage le gagnant d'un match vers les matchs suivants (winner bracket + loser bracket)
     */
    function propagateResult(bracket, state, matchId, winnerTeam, loserTeam) {
        // --- 1. Propagation dans le winner bracket (le gagnant avance) ---
        bracket.winnerRounds.forEach(round => {
            round.matches.forEach(m => {
                if (m.team1From === matchId) {
                    setTeamSlot(state, m.id, 'team1', winnerTeam);
                }
                if (m.team2From === matchId) {
                    setTeamSlot(state, m.id, 'team2', winnerTeam);
                }
            });
        });

        // --- 2. Propagation du perdant vers le loser-bracket local ---
        if (loserTeam !== null && loserTeam !== undefined) {
            Object.keys(bracket.loserBrackets).forEach(r => {
                const lb = bracket.loserBrackets[r];
                lb.rounds.forEach(sr => {
                    sr.matches.forEach(m => {
                        if (m.team1From === matchId) {
                            setTeamSlot(state, m.id, 'team1', loserTeam);
                        }
                        if (m.team2From === matchId) {
                            setTeamSlot(state, m.id, 'team2', loserTeam);
                        }
                    });
                });
            });
        }

        // --- 3. Propagation à l'intérieur des loser-brackets (sub-round à sub-round) ---
        Object.keys(bracket.loserBrackets).forEach(r => {
            const lb = bracket.loserBrackets[r];
            lb.rounds.forEach(sr => {
                sr.matches.forEach(m => {
                    if (m.team1FromSub === matchId) {
                        setTeamSlot(state, m.id, 'team1', winnerTeam);
                    }
                    if (m.team2FromSub === matchId) {
                        setTeamSlot(state, m.id, 'team2', winnerTeam);
                    }
                    // Placement match : recoit les PERDANTS des sub-matches précédents
                    if (m.isPlacementMatch) {
                        if (m.team1FromSubLoser === matchId) {
                            setTeamSlot(state, m.id, 'team1', loserTeam);
                        }
                        if (m.team2FromSubLoser === matchId) {
                            setTeamSlot(state, m.id, 'team2', loserTeam);
                        }
                    }
                });
            });
        });
    }

    function setTeamSlot(state, matchId, slot, team) {
        if (!state.matches[matchId]) return;
        state.matches[matchId][slot] = team;

        // Si c'est un bye créé dynamiquement (l'autre équipe est absente), on ne gère pas ici
        // (les byes ne sont normalement que round 1 winner bracket, déjà traités à l'init)
    }

    /**
     * Résout un match : enregistre le gagnant/perdant, propage, met à jour le classement si besoin
     */
    function resolveMatch(bracket, state, matchId, winnerTeam) {
        const ms = state.matches[matchId];
        if (!ms) throw new Error('Match introuvable: ' + matchId);
        if (ms.played) throw new Error('Match déjà joué: ' + matchId);
        if (ms.isBye) throw new Error('Impossible de jouer un match bye: ' + matchId);

        const loserTeam = (winnerTeam === ms.team1) ? ms.team2 : ms.team1;

        if (winnerTeam !== ms.team1 && winnerTeam !== ms.team2) {
            throw new Error('Équipe gagnante invalide pour ce match');
        }

        ms.winner = winnerTeam;
        ms.loser = loserTeam;
        ms.played = true;

        // Propagation
        propagateResult(bracket, state, matchId, winnerTeam, loserTeam);

        // Vérifie si ce match donne un classement final (placement match ou dernier match d'un groupe)
        assignRanksIfApplicable(bracket, state, matchId, winnerTeam, loserTeam);

        return state;
    }

    /**
     * Assigne le rang final si le match résolu correspond à un match de classement
     */
    function assignRanksIfApplicable(bracket, state, matchId, winnerTeam, loserTeam) {
        // Cas 1 : match final du winner bracket (dernier round) => rank 1 et 2
        const lastWinnerRound = bracket.winnerRounds[bracket.winnerRounds.length - 1];
        const isFinal = lastWinnerRound.matches.some(m => m.id === matchId);
        if (isFinal) {
            state.ranks[winnerTeam] = 1;
            state.ranks[loserTeam] = 2;
            return;
        }

        // Cas 2 : placement matches dans les loser-brackets
        Object.keys(bracket.loserBrackets).forEach(r => {
            const lb = bracket.loserBrackets[r];
            lb.placementMatches.forEach(pm => {
                if (pm.matchId === matchId) {
                    state.ranks[winnerTeam] = pm.winnerRank;
                    state.ranks[loserTeam] = pm.loserRank;
                }
            });
        });
    }

    /**
     * Retourne la liste des matchs "jouables maintenant" (les 2 équipes sont connues, pas encore joué)
     */
    function getPlayableMatches(bracket, state) {
        return Object.keys(state.matches)
            .filter(id => {
                const m = state.matches[id];
                return !m.played && !m.isBye && m.team1 !== null && m.team2 !== null;
            })
            .map(id => ({
                id,
                team1: state.matches[id].team1,
                team2: state.matches[id].team2
            }));
    }

    /**
     * Retourne le classement final trié (si complet)
     */
    function getFinalRanking(state) {
        return Object.keys(state.ranks)
            .map(team => ({ team: isNaN(team) ? team : parseInt(team), rank: state.ranks[team] }))
            .sort((a, b) => a.rank - b.rank);
    }

    return {
        initMatchState,
        resolveMatch,
        getPlayableMatches,
        getFinalRanking
    };

})();

if (typeof module !== 'undefined') module.exports = BracketResolver;