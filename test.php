<script>
// console.log("2 -> " + Math.log2(2));
// console.log("4 -> " + Math.log2(4));
// console.log("8 -> " + Math.log2(8));
// console.log("16 -> " + Math.log2(16));
// console.log("32 -> " + Math.log2(32));
// console.log("5 -> " + Math.log2(5));
// console.log("5R -> " + Math.round(Math.log2(5)));
// console.log("6 -> " + Math.log2(6));
// console.log("6R -> " + Math.round(Math.log2(6)));
// console.log("7 -> " + Math.log2(7));
// console.log("7 -> " + Math.round(Math.log2(7)));
// console.log("12 -> " + Math.log2(12));
// console.log("12 -> " + Math.round(Math.log2(12)));
function forcerPuissanceDe2(nbre_team) {
    return Math.pow(2, Math.ceil(Math.log2(nbre_team)));
}


function subroundcalc(nbre_team) {
    nbre_team = forcerPuissanceDe2(nbre_team);
    let nbre_round = Math.round(Math.log2(nbre_team));
    const array_round = [];
    let cnr = 0;
    let fnr = nbre_team;
    array_round[0] = [];
    array_round[0][0] = fnr;
    array_round[0][1] = nbre_team / fnr;
    for (let i = 1; i < nbre_round; i++) {
        cnr++;
        fnr = fnr / 2;
        array_round[cnr] = [];
        array_round[cnr][0] = fnr;
        array_round[cnr][1] = nbre_team / fnr;
    }
    return array_round;
}

function listematchph(nbre_team) {
    array_round = subroundcalc(nbre_team);
    console.table(array_round);
    console.log(array_round.length);
    console.log("ROUND: 0");
    console.log("ROUND: 0 - SUB: 1");
    for (let i = 1; i <= array_round[0][0] / 2; i++) {
        console.log("match: R0_S1_M" + i + " - T" + i + " vs T" + (9 - i));
    }
    for (let j = 1; j < array_round.length; j++) {
        console.log("ROUND: " + j);
        let classementpalce = 1;
        for (let k = 1; k <= array_round[j][1]; k++) {
            let place = "";
            if (j == array_round.length - 1) {
                place = " - CLASSMENT " + classementpalce++ + ' - ' + classementpalce++;
            }
            console.log("ROUND: " + j + " - SUB: " + k + place);
            let wl;
            if (k % 2 == 0) {
                wl = "Loss_";
            } else {
                wl = "Win_";
            }
            let mplus = 0;
            let splus = Math.ceil((k / 2));
            for (let i = 1; i <= array_round[j][0] / 2; i++) {
                console.log("match: R" + j + "_S" + k + "_M" + i + "__" + wl + "R" + (j - 1) + "_S" + splus + "_M" + (i + mplus) + '_vs_' + wl + "R" + (j - 1) + "_S" + splus + "_M" + (i + ++mplus));

            }
        }
    }
}

// console.table(subroundcalc(8));
listematchph(8);
</script>