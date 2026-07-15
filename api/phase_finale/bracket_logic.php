<?php
// api/phase_finale/bracket_logic.php

class BracketGenerator {

    public static function forcerPuissanceDe2(int $nbreTeam): int {
        return (int) pow(2, ceil(log($nbreTeam, 2)));
    }

    /**
     * Calcule la structure des rounds (equivalent subroundcalc)
     */
    public static function subRoundCalc(int $nbreTeam): array {
        $nbreTeam = self::forcerPuissanceDe2($nbreTeam);
        $nbreRound = (int) round(log($nbreTeam, 2));

        $arrayRound = [];
        $fnr = $nbreTeam;
        $arrayRound[0] = [$fnr, $nbreTeam / $fnr];

        for ($i = 1; $i < $nbreRound; $i++) {
            $fnr = $fnr / 2;
            $arrayRound[$i] = [$fnr, $nbreTeam / $fnr];
        }

        return $arrayRound;
    }

    /**
     * Génère la liste des matchs pour le bracket "classement complet"
     * (equivalent listematchph)
     */
    public static function genererMatchsClassementComplet(int $nbreTeamOriginal): array {
        $arrayRound = self::subRoundCalc($nbreTeamOriginal);
        $nbreTeam = self::forcerPuissanceDe2($nbreTeamOriginal);
        $matchs = [];

        // Round 0 - seeding classique 1vN, 2vN-1, etc.
        for ($i = 1; $i <= $arrayRound[0][0] / 2; $i++) {
            $matchs[] = [
                'round' => 0,
                'sub_group' => 1,
                'match_num' => $i,
                'match_code' => "R0_S1_M{$i}",
                'source_team1' => "SEED_{$i}",
                'source_team2' => "SEED_" . ($nbreTeam + 1 - $i),
                'classement_min' => null,
                'classement_max' => null,
            ];
        }

        // Rounds suivants
        for ($j = 1; $j < count($arrayRound); $j++) {
            $classementPlace = 1;

            for ($k = 1; $k <= $arrayRound[$j][1]; $k++) {
                $classementMin = null;
                $classementMax = null;

                if ($j == count($arrayRound) - 1) {
                    $classementMin = $classementPlace++;
                    $classementMax = $classementPlace++;
                }

                $wl = ($k % 2 == 0) ? "Loss" : "Win";
                $mplus = 0;
                $splus = (int) ceil($k / 2);

                for ($i = 1; $i <= $arrayRound[$j][0] / 2; $i++) {
                    $sourceT1 = "{$wl}_R" . ($j - 1) . "_S{$splus}_M" . ($i + $mplus);
                    $mplus++;
                    $sourceT2 = "{$wl}_R" . ($j - 1) . "_S{$splus}_M" . ($i + $mplus);

                    $matchs[] = [
                        'round' => $j,
                        'sub_group' => $k,
                        'match_num' => $i,
                        'match_code' => "R{$j}_S{$k}_M{$i}",
                        'source_team1' => $sourceT1,
                        'source_team2' => $sourceT2,
                        'classement_min' => $classementMin,
                        'classement_max' => $classementMax,
                    ];
                }
            }
        }

        return $matchs;
    }

    /**
     * Génère la liste des matchs pour le bracket "classique"
     * élimination directe standard, seeding 1v8, 4v5, 2v7, 3v6...
     */
    public static function genererMatchsClassique(int $nbreTeamOriginal): array {
        $nbreTeam = self::forcerPuissanceDe2($nbreTeamOriginal);
        $nbreRound = (int) log($nbreTeam, 2);
        $matchs = [];

        // Génération du seeding standard (ordre officiel type NBA/tennis)
        $seedOrder = self::genererOrdreSeeding($nbreTeam);

        // Round 0 (premier tour)
        $nbMatchsRound0 = $nbreTeam / 2;
        for ($i = 1; $i <= $nbMatchsRound0; $i++) {
            $seedA = $seedOrder[($i - 1) * 2];
            $seedB = $seedOrder[($i - 1) * 2 + 1];

            $matchs[] = [
                'round' => 0,
                'sub_group' => 1,
                'match_num' => $i,
                'match_code' => "R0_M{$i}",
                'source_team1' => "SEED_{$seedA}",
                'source_team2' => "SEED_{$seedB}",
                'classement_min' => null,
                'classement_max' => null,
            ];
        }

        // Rounds suivants (uniquement les vainqueurs avancent)
        $nbMatchsPrecedent = $nbMatchsRound0;
        for ($r = 1; $r < $nbreRound; $r++) {
            $nbMatchsRound = $nbMatchsPrecedent / 2;

            for ($i = 1; $i <= $nbMatchsRound; $i++) {
                $mA = ($i - 1) * 2 + 1;
                $mB = ($i - 1) * 2 + 2;

                $classementMin = null;
                $classementMax = null;
                // Dernier round = finale (1-2)
                if ($r == $nbreRound - 1) {
                    $classementMin = 1;
                    $classementMax = 2;
                }

                $matchs[] = [
                    'round' => $r,
                    'sub_group' => 1,
                    'match_num' => $i,
                    'match_code' => "R{$r}_M{$i}",
                    'source_team1' => "Win_R" . ($r - 1) . "_M{$mA}",
                    'source_team2' => "Win_R" . ($r - 1) . "_M{$mB}",
                    'classement_min' => $classementMin,
                    'classement_max' => $classementMax,
                ];
            }

            $nbMatchsPrecedent = $nbMatchsRound;
        }

        // Petite finale (3e/4e place) - optionnelle, basée sur les 2 perdants des demi-finales
        if ($nbreRound >= 2) {
            $matchs[] = [
                'round' => $nbreRound - 1,
                'sub_group' => 2,
                'match_num' => 1,
                'match_code' => "R" . ($nbreRound - 1) . "_S2_M1",
                'source_team1' => "Loss_R" . ($nbreRound - 2) . "_M1",
                'source_team2' => "Loss_R" . ($nbreRound - 2) . "_M2",
                'classement_min' => 3,
                'classement_max' => 4,
            ];
        }

        return $matchs;
    }

    /**
     * Génère l'ordre de seeding standard pour un bracket classique
     * Ex pour 8: [1,8,4,5,2,7,3,6]
     */
    private static function genererOrdreSeeding(int $nbreTeam): array {
        $seeds = [1, 2];
        while (count($seeds) < $nbreTeam) {
            $newSeeds = [];
            $sum = count($seeds) * 2 + 1;
            foreach ($seeds as $s) {
                $newSeeds[] = $s;
                $newSeeds[] = $sum - $s;
            }
            $seeds = $newSeeds;
        }
        return $seeds;
    }
}