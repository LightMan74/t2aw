<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Test Bracket Generator</title>
</head>

<body>
    <pre id="output"></pre>

    <script src="js/bracket-generator.js"></script>
    <script>
    const teams = [1, 2, 3, 4, 5, 6];
    const bracket = BracketGenerator.generateBracket(teams);

    let log = '';
    log += 'Total rounds: ' + bracket.totalRounds + '\n';
    log += 'Ranking ranges: ' + JSON.stringify(bracket.rankingRanges, null, 2) + '\n\n';

    log += 'Winner Round 1 matches:\n';
    bracket.winnerRounds[0].matches.forEach(m => {
        log += `  ${m.id}: T${m.team1} vs T${m.team2} (bye: ${m.isBye})\n`;
    });

    log += '\nWinner Round 2 matches:\n';
    bracket.winnerRounds[1].matches.forEach(m => {
        log += `  ${m.id}: team1From=${m.team1From} vs team2From=${m.team2From}\n`;
    });

    log += '\n=== LOSER BRACKETS ===\n';
    Object.keys(bracket.loserBrackets).forEach(r => {
        log += `\n-- Round ${r} (loser group) --\n`;
        const lb = bracket.loserBrackets[r];
        lb.rounds.forEach(sr => {
            log += `  SubRound ${sr.subRound}:\n`;
            sr.matches.forEach(m => {
                if (m.isPlacementMatch) {
                    log += `    [PLACEMENT] ${m.id}: loserOf(${m.team1FromSubLoser}) vs loserOf(${m.team2FromSubLoser})\n`;
                } else {
                    log += `    ${m.id}: from=${m.team1From || m.team1FromSub} vs from=${m.team2From || m.team2FromSub} (bye: ${m.isBye})\n`;
                }
            });
        });
        log += `  Placement matches -> ranks:\n`;
        lb.placementMatches.forEach(pm => {
            log += `    ${pm.matchId}: winner=rank${pm.winnerRank}, loser=rank${pm.loserRank}\n`;
        });
    });

    document.getElementById('output').textContent = log;
    </script>
</body>

</html>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Test Bracket Resolver</title>
</head>

<body>
    <pre id="output"></pre>

    <!-- <script src="js/bracket-generator.js"></script> -->
    <script src="js/bracket-resolver.js"></script>
    <script>
    // const teams = [1, 2, 3, 4, 5, 6];
    // const bracket = BracketGenerator.generateBracket(teams);
    const state = BracketResolver.initMatchState(bracket);

    // let log = '';

    function playAndLog(matchId, winner) {
        BracketResolver.resolveMatch(bracket, state, matchId, winner);
        log += `Match ${matchId} joué -> gagnant: T${winner}\n`;
    }
    // playAndLog('WR1_M1', 1);
    playAndLog('WR1_M2', 4);
    // playAndLog('WR1_M3', 4);
    playAndLog('WR1_M4', 3);


    log += '\n-- Playable après Round 1 --\n';
    BracketResolver.getPlayableMatches(bracket, state).forEach(m => {
        log += `  ${m.id}: T${m.team1} vs T${m.team2}\n`;
    });

    playAndLog('WR3_M1', 1);
    playAndLog('LR1_S1_M1', 4);
    // playAndLog('WR2_M3', 2);
    // playAndLog('WR2_M4', 3);
    // playAndLog('LR1_S1_M1', 9);
    // playAndLog('LR1_S1_M2', 12);
    // playAndLog('LR1_S1_M3', 10);
    // playAndLog('LR1_S1_M4', 11);

    // // Winner Round 2 (demi-finales)
    // playAndLog('WR2M5', 1); // T1 bat T4
    // playAndLog('WR2M6', 2); // T2 bat T3

    // Loser bracket round 1(perdants R1: T8, T5, T7, T6)
    log += '\n-- Playable après demi-finales --\n';
    BracketResolver.getPlayableMatches(bracket, state).forEach(m => {
        log += `  ${m.id}: T${m.team1} vs T${m.team2}\n`;
    });

    // playAndLog('LR1S1M1', 5); // T5 bat T8
    // playAndLog('LR1S1M2', 6); // T6 bat T7

    log += '\n-- Playable après LR1 sub-round 1 --\n';
    BracketResolver.getPlayableMatches(bracket, state).forEach(m => {
        log += `  ${m.id}: T${m.team1} vs T${m.team2}\n`;
    });

    // playAndLog('LR1S2M3', 5); // T5 bat T6 -> place 5-6
    // playAndLog('LR1PLACEMENT', 8); // T8 bat T7 -> place 7-8

    // Loser bracket round 2 (perdants demi-finales: T4, T3) -> place 3-4
    // playAndLog('LR2S1M1', 4); // T4 bat T3

    // Finale winner bracket -> place 1-2
    log += '\n-- Match final --\n';
    BracketResolver.getPlayableMatches(bracket, state).forEach(m => {
        log += `  ${m.id}: T${m.team1} vs T${m.team2}\n`;
    });

    // // On cherche le match final (dernier round winner)
    // const finalRound = bracket.winnerRounds[bracket.winnerRounds.length - 1];
    // const finalMatchId = finalRound.matches[0].id;
    // // playAndLog(finalMatchId, 1); // T1 bat T2
    // playAndLog('WR3M7', 1); // T1 bat T2

    log += '\n=== CLASSEMENT FINAL ===\n';
    BracketResolver.getFinalRanking(state).forEach(r => {
        log += `  Rank ${r.rank}: Team ${r.team}\n`;
    });

    document.getElementById('output').textContent = log;
    </script>
</body>

</html>