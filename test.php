<?php
function genererToursRoundRobin(int $nbEquipes): array {
    if ($nbEquipes < 2) return [];
    $impair = ($nbEquipes % 2 !== 0);
    $n = $impair ? $nbEquipes + 1 : $nbEquipes;
    $moitie = $n / 2;
    $nbTours = $n - 1;

    $ordreSeq = range(0, $nbEquipes - 1);
    if ($impair) $ordreSeq[] = null;

    $arr = array_fill(0, $n, null);
    $g = 0; $d = $n - 1;
    for ($i = 0; $i < $moitie; $i++) {
        $arr[$g++] = $ordreSeq[2*$i];
        $arr[$d--] = $ordreSeq[2*$i+1];
    }

    $tours = [];
    for ($t = 0; $t < $nbTours; $t++) {
        $tm = [];
        for ($i = 0; $i < $moitie; $i++) {
            $e1 = $arr[$i]; $e2 = $arr[$n-1-$i];
            if ($e1 !== null && $e2 !== null) $tm[] = [$e1+1, $e2+1]; // +1 pour affichage lisible
        }
        $tours[] = $tm;
        $last = array_pop($arr);
        array_splice($arr, 1, 0, [$last]);
    }
    return $tours;
}

$poules = ['A' => 8, 'B' => 8];
$donnees = [];
$maxTours = 0;
foreach ($poules as $nom => $nb) {
    $donnees[$nom] = genererToursRoundRobin($nb);
    $maxTours = max($maxTours, count($donnees[$nom]));
}

$numMatch = array_fill_keys(array_keys($poules), 1);
for ($t = 0; $t < $maxTours; $t++) {
    foreach ($donnees as $nom => $tours) {
        if (!isset($tours[$t])) continue;
        foreach ($tours[$t] as $paire) {
            echo "$nom - Match {$numMatch[$nom]} (Tour ".($t+1).") : {$paire[0]}$nom vs {$paire[1]}$nom<br>";
            $numMatch[$nom]++;
        }
    }
}