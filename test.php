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
    const teams = [1, 2, 3, 4, 5, 6, 7, 8];
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