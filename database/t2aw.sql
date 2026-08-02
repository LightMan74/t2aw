-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 192.168.3.70
-- Généré le : dim. 02 août 2026 à 14:26
-- Version du serveur : 9.7.0
-- Version de PHP : 8.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `t2aw`
--

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id` int NOT NULL,
  `id_tournoi` varchar(500) NOT NULL,
  `id_categorie` int NOT NULL,
  `nom` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `equipe`
--

CREATE TABLE `equipe` (
  `id` int NOT NULL,
  `id_tournoi` varchar(500) NOT NULL,
  `id_categorie` int NOT NULL,
  `id_poule` int NOT NULL,
  `id_equipe` int NOT NULL,
  `nom` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `equipes_phase_finale`
--

CREATE TABLE `equipes_phase_finale` (
  `id` int NOT NULL,
  `id_tournoi` varchar(500) NOT NULL,
  `id_phase_finale` int NOT NULL,
  `seed_position` int NOT NULL,
  `is_bye` tinyint(1) DEFAULT '0',
  `id_categorie` int DEFAULT NULL,
  `id_poule` int DEFAULT NULL,
  `id_equipe` int DEFAULT NULL,
  `nom_equipe` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `matchs_phase_finale`
--

CREATE TABLE `matchs_phase_finale` (
  `id` int NOT NULL,
  `id_tournoi` varchar(500) NOT NULL,
  `id_phase_finale` int NOT NULL,
  `round` int NOT NULL,
  `sub_group` int NOT NULL,
  `match_num` int NOT NULL,
  `match_code` varchar(50) NOT NULL,
  `source_team1` varchar(100) DEFAULT NULL,
  `source_team2` varchar(100) DEFAULT NULL,
  `equipe1_id` int DEFAULT NULL,
  `equipe2_id` int DEFAULT NULL,
  `score1` varchar(50) DEFAULT NULL,
  `score2` varchar(50) DEFAULT NULL,
  `winner_equipe_id` int DEFAULT NULL,
  `loser_equipe_id` int DEFAULT NULL,
  `classement_min` int DEFAULT NULL,
  `classement_max` int DEFAULT NULL,
  `statut` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `statut_match` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'planifie',
  `terrain` varchar(50) DEFAULT NULL,
  `heure_debut` varchar(500) DEFAULT NULL,
  `date_maj` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `match_poule`
--

CREATE TABLE `match_poule` (
  `id` int NOT NULL,
  `id_tournoi` varchar(500) NOT NULL,
  `id_categorie` int NOT NULL,
  `id_poule` int NOT NULL,
  `id_poule_2` int DEFAULT NULL,
  `id_match` int NOT NULL,
  `terrain` int DEFAULT NULL,
  `id_equipe_1` int NOT NULL,
  `id_equipe_2` int NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'planifie',
  `score_equipe_1` varchar(50) NOT NULL DEFAULT '0*0*0',
  `score_equipe_2` varchar(50) NOT NULL DEFAULT '0*0*0',
  `heure_debut` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `heure_fin` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ordre_affichage` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ordre_match_poule`
--

CREATE TABLE `ordre_match_poule` (
  `id` int NOT NULL,
  `nbre_equipe` int NOT NULL,
  `ordre` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `ordre_match_poule`
--

INSERT INTO `ordre_match_poule` (`id`, `nbre_equipe`, `ordre`) VALUES
(1, 2, 'match1(1;2);'),
(2, 3, 'match1(1;2);match2(1;3);match3(2;3);'),
(3, 4, 'match1(1;2);match2(3;4);match3(1;3);match4(2;4);match5(1;4);match6(2;3);'),
(4, 5, 'match1(1;2);match2(3;4);match3(1;3);match4(2;5);match5(1;4);match6(3;5);match7(1;5);match8(2;4);match9(2;3);match10(4;5);'),
(5, 6, 'match1(1;2);match2(3;4);match3(5;6);match4(1;3);match5(2;5);match6(4;6);match7(1;6);match8(2;3);match9(4;5);match10(1;4);match11(2;6);match12(3;5);match13(1;5);match14(2;4);match15(3;6);'),
(6, 7, 'match1(1;2);match2(3;4);match3(5;6);match4(1;3);match5(5;4);match6(6;7);match7(1;4);match8(2;5);match9(7;3);match10(1;5);match11(6;3);match12(2;7);match13(3;5);match14(2;6);match15(4;7);match16(1;6);match17(2;4);match18(5;7);match19(1;7);match20(2;3);match21(4;6);'),
(7, 8, 'match1(1;2);match2(3;4);match3(5;6);match4(7;8);match5(1;3);match6(2;4);match7(5;7);match8(6;8);match9(1;4);match10(2;3);match11(5;8);match12(6;7);match13(1;5);match14(2;6);match15(3;7);match16(4;8);match17(1;6);match18(2;5);match19(3;8);match20(4;7);match21(1;7);match22(2;8);match23(3;5);match24(4;6);match25(1;8);match26(2;7);match27(3;6);match28(4;5);'),
(8, 9, 'match1(1;2);match2(3;4);match3(5;6);match4(7;8);match5(1;9);match6(2;3);match7(4;5);match8(6;7);match9(1;8);match10(2;4);match11(5;7);match12(6;9);match13(1;3);match14(2;9);match15(4;6);match16(8;5);match17(1;7);match18(2;6);match19(3;5);match20(8;9);match21(4;9);match22(2;5);match23(3;7);match24(6;8);match25(1;4);match26(2;7);match27(3;8);match28(5;9);match29(1;6);match30(3;9);match31(2;8);match32(4;7);match33(1;5);match34(7;9);match35(6;3);match36(4;8);'),
(9, 10, 'match1(1;2);match2(3;4);match3(5;6);match4(7;8);match5(9;10);match6(6;4);match7(2;10);match8(5;8);match9(7;9);match10(1;3);match11(3;5);match12(8;1);match13(10;7);match14(6;9);match15(2;4);match16(8;10);match17(6;3);match18(9;1);match19(2;5);match20(4;7);match21(2;9);match22(10;5);match23(6;8);match24(4;1);match25(7;3);match26(3;10);match27(5;4);match28(7;2);match29(9;8);match30(1;6);match31(10;1);match32(5;7);match33(8;3);match34(2;6);match35(4;9);match36(3;9);match37(6;7);match38(1;5);match39(10;4);match40(8;2);match41(1;7);match42(2;3);match43(9;5);match44(4;8);match45(6;10);'),
(10, 11, 'match1(1;2);match2(7;8);match3(4;5);match4(10;11);match5(2;3);match6(8;9);match7(5;6);match8(3;1);match9(9;7);match10(6;4);match11(2;5);match12(8;11);match13(1;4);match14(7;10);match15(5;3);match16(11;9);match17(1;6);match18(4;2);match19(10;8);match20(3;6);match21(5;1);match22(11;7);match23(3;9);match24(10;4);match25(8;2);match26(5;11);match27(1;7);match28(3;9);match29(10;4);match30(8;2);match31(5;11);match32(1;8);match33(9;2);match34(3;10);match35(4;11);match36(6;7);match37(9;1);match38(2;10);match39(11;3);match40(7;5);match41(6;8);match42(10;1);match43(11;2);match44(4;7);match45(8;5);match46(6;9);match47(11;1);match48(7;3);match49(4;8);match50(9;5);match51(6;10);match52(2;7);match53(8;3);match54(4;9);match55(10;5);match56(6;11);'),
(11, 12, 'match1(1;2);match2(3;4);match3(5;6);match4(7;8);match5(9;10);match6(11;12);match7(1;4);match8(3;6);match9(5;8);match10(7;10);match11(9;12);match12(11;2);match13(1;6);match14(3;8);match15(5;10);match16(7;12);match17(9;2);match18(11;4);match19(1;8);match20(3;10);match21(5;12);match22(7;2);match23(9;4);match24(11;6);match25(1;10);match26(3;12);match27(5;2);match28(7;4);match29(9;6);match30(11;8);match31(1;12);match32(3;2);match33(5;4);match34(7;6);match35(9;8);match36(11;10);match37(1;3);match38(5;7);match39(9;11);match40(2;4);match41(6;8);match42(10;12);match43(1;5);match44(3;9);match45(7;11);match46(2;6);match47(4;10);match48(8;12);match49(1;7);match50(3;11);match51(5;9);match52(2;8);match53(4;12);match54(6;10);match55(1;9);match56(3;7);match57(5;11);match58(2;10);match59(4;8);match60(6;12);match61(1;11);match62(3;5);match63(7;9);match64(2;12);match65(4;6);match66(8;10);'),
(12, 13, 'match1(2;13);match2(3;12);match3(4;11);match4(5;10);match5(6;9);match6(7;8);match7(1;13);match8(2;11);match9(3;10);match10(4;9);match11(5;8);match12(6;7);match13(1;12);match14(13;11);match15(2;9);match16(3;8);match17(4;7);match18(5;6);match19(1;11);match20(12;10);match21(13;9);match22(2;7);match23(3;6);match24(4;5);match25(1;10);match26(11;9);match27(12;8);match28(13;7);match29(2;5);match30(3;4);match31(1;9);match32(10;8);match33(11;7);match34(12;6);match35(13;5);match36(2;3);match37(1;8);match38(9;7);match39(10;6);match40(11;5);match41(12;4);match42(13;3);match43(1;7);match44(8;6);match45(9;5);match46(10;4);match47(11;3);match48(12;2);match49(1;6);match50(7;5);match51(8;4);match52(9;3);match53(10;2);match54(12;13);match55(1;5);match56(6;4);match57(7;3);match58(8;2);match59(10;13);match60(11;12);match61(1;4);match62(5;3);match63(6;2);match64(8;13);match65(9;12);match66(10;11);match67(1;3);match68(4;2);match69(6;13);match70(7;12);match71(8;11);match72(9;10);match73(1;2);match74(4;13);match75(5;12);match76(6;11);match77(7;10);match78(8;9);'),
(13, 14, 'match1(1;14);match2(2;13);match3(3;12);match4(4;11);match5(5;10);match6(6;9);match7(7;8);match8(1;13);match9(14;12);match10(2;11);match11(3;10);match12(4;9);match13(5;8);match14(6;7);match15(1;12);match16(13;11);match17(14;10);match18(2;9);match19(3;8);match20(4;7);match21(5;6);match22(1;11);match23(12;10);match24(13;9);match25(14;8);match26(2;7);match27(3;6);match28(4;5);match29(1;10);match30(11;9);match31(12;8);match32(13;7);match33(14;6);match34(2;5);match35(3;4);match36(1;9);match37(10;8);match38(11;7);match39(12;6);match40(13;5);match41(14;4);match42(2;3);match43(1;8);match44(9;7);match45(10;6);match46(11;5);match47(12;4);match48(13;3);match49(14;2);match50(1;7);match51(8;6);match52(9;5);match53(10;4);match54(11;3);match55(12;2);match56(13;14);match57(1;6);match58(7;5);match59(8;4);match60(9;3);match61(10;2);match62(11;14);match63(12;13);match64(1;5);match65(6;4);match66(7;3);match67(8;2);match68(9;14);match69(10;13);match70(11;12);match71(1;4);match72(5;3);match73(6;2);match74(7;14);match75(8;13);match76(9;12);match77(10;11);match78(1;3);match79(4;2);match80(5;14);match81(6;13);match82(7;12);match83(8;11);match84(9;10);match85(1;2);match86(3;14);match87(4;13);match88(5;12);match89(6;11);match90(7;10);match91(8;9);'),
(14, 15, 'match1(2;15);match2(3;14);match3(4;13);match4(5;12);match5(6;11);match6(7;10);match7(8;9);match8(1;15);match9(2;13);match10(3;12);match11(4;11);match12(5;10);match13(6;9);match14(7;8);match15(1;14);match16(15;13);match17(2;11);match18(3;10);match19(4;9);match20(5;8);match21(6;7);match22(1;13);match23(14;12);match24(15;11);match25(2;9);match26(3;8);match27(4;7);match28(5;6);match29(1;12);match30(13;11);match31(14;10);match32(15;9);match33(2;7);match34(3;6);match35(4;5);match36(1;11);match37(12;10);match38(13;9);match39(14;8);match40(15;7);match41(2;5);match42(3;4);match43(1;10);match44(11;9);match45(12;8);match46(13;7);match47(14;6);match48(15;5);match49(2;3);match50(1;9);match51(10;8);match52(11;7);match53(12;6);match54(13;5);match55(14;4);match56(15;3);match57(1;8);match58(9;7);match59(10;6);match60(11;5);match61(12;4);match62(13;3);match63(14;2);match64(1;7);match65(8;6);match66(9;5);match67(10;4);match68(11;3);match69(12;2);match70(14;15);match71(1;6);match72(7;5);match73(8;4);match74(9;3);match75(10;2);match76(12;15);match77(13;14);match78(1;5);match79(6;4);match80(7;3);match81(8;2);match82(10;15);match83(11;14);match84(12;13);match85(1;4);match86(5;3);match87(6;2);match88(8;15);match89(9;14);match90(10;13);match91(11;12);match92(1;3);match93(4;2);match94(6;15);match95(7;14);match96(8;13);match97(9;12);match98(10;11);match99(1;2);match100(4;15);match101(5;14);match102(6;13);match103(7;12);match104(8;11);match105(9;10);'),
(15, 16, 'match1(1;2);match2(3;4);match3(5;6);match4(7;8);match5(9;10);match6(11;12);match7(13;14);match8(15;16);match9(1;3);match10(2;4);match11(5;7);match12(6;8);match13(9;11);match14(10;12);match15(13;15);match16(14;16);match17(1;4);match18(2;3);match19(5;8);match20(6;7);match21(9;12);match22(10;11);match23(13;16);match24(14;15);match25(1;5);match26(2;6);match27(3;7);match28(4;8);match29(9;13);match30(10;14);match31(11;15);match32(12;16);match33(1;6);match34(2;5);match35(3;8);match36(4;7);match37(9;14);match38(10;13);match39(11;16);match40(12;15);match41(1;7);match42(2;8);match43(3;5);match44(4;6);match45(9;15);match46(10;16);match47(11;13);match48(12;14);match49(1;8);match50(2;7);match51(3;6);match52(4;5);match53(9;16);match54(10;15);match55(11;14);match56(12;13);match57(1;9);match58(2;10);match59(3;11);match60(4;12);match61(5;13);match62(6;14);match63(7;15);match64(8;16);match65(1;10);match66(2;9);match67(3;12);match68(4;11);match69(5;14);match70(6;13);match71(7;16);match72(8;15);match73(1;11);match74(2;12);match75(3;9);match76(4;10);match77(5;15);match78(6;16);match79(7;13);match80(8;14);match81(1;12);match82(2;11);match83(3;10);match84(4;9);match85(5;16);match86(6;15);match87(7;14);match88(8;13);match89(1;13);match90(2;14);match91(3;15);match92(4;16);match93(5;9);match94(6;10);match95(7;11);match96(8;12);match97(1;14);match98(2;13);match99(3;16);match100(4;15);match101(5;10);match102(6;9);match103(7;12);match104(8;11);match105(1;15);match106(2;16);match107(3;13);match108(4;14);match109(5;11);match110(6;12);match111(7;9);match112(8;10);match113(1;16);match114(2;15);match115(3;14);match116(4;13);match117(5;12);match118(6;11);match119(7;10);match120(8;9);'),
(16, 17, 'match1(2;17);match2(3;16);match3(4;15);match4(5;14);match5(6;13);match6(7;12);match7(8;11);match8(9;10);match9(1;17);match10(2;15);match11(3;14);match12(4;13);match13(5;12);match14(6;11);match15(7;10);match16(8;9);match17(1;16);match18(17;15);match19(2;13);match20(3;12);match21(4;11);match22(5;10);match23(6;9);match24(7;8);match25(1;15);match26(16;14);match27(17;13);match28(2;11);match29(3;10);match30(4;9);match31(5;8);match32(6;7);match33(1;14);match34(15;13);match35(16;12);match36(17;11);match37(2;9);match38(3;8);match39(4;7);match40(5;6);match41(1;13);match42(14;12);match43(15;11);match44(16;10);match45(17;9);match46(2;7);match47(3;6);match48(4;5);match49(1;12);match50(13;11);match51(14;10);match52(15;9);match53(16;8);match54(17;7);match55(2;5);match56(3;4);match57(1;11);match58(12;10);match59(13;9);match60(14;8);match61(15;7);match62(16;6);match63(17;5);match64(2;3);match65(1;10);match66(11;9);match67(12;8);match68(13;7);match69(14;6);match70(15;5);match71(16;4);match72(17;3);match73(1;9);match74(10;8);match75(11;7);match76(12;6);match77(13;5);match78(14;4);match79(15;3);match80(16;2);match81(1;8);match82(9;7);match83(10;6);match84(11;5);match85(12;4);match86(13;3);match87(14;2);match88(16;17);match89(1;7);match90(8;6);match91(9;5);match92(10;4);match93(11;3);match94(12;2);match95(14;17);match96(15;16);match97(1;6);match98(7;5);match99(8;4);match100(9;3);match101(10;2);match102(12;17);match103(13;16);match104(14;15);match105(1;5);match106(6;4);match107(7;3);match108(8;2);match109(10;17);match110(11;16);match111(12;15);match112(13;14);match113(1;4);match114(5;3);match115(6;2);match116(8;17);match117(9;16);match118(10;15);match119(11;14);match120(12;13);match121(1;3);match122(4;2);match123(6;17);match124(7;16);match125(8;15);match126(9;14);match127(10;13);match128(11;12);match129(1;2);match130(4;17);match131(5;16);match132(6;15);match133(7;14);match134(8;13);match135(9;12);match136(10;11);'),
(17, 18, 'match1(1;18);match2(2;17);match3(3;16);match4(4;15);match5(5;14);match6(6;13);match7(7;12);match8(8;11);match9(9;10);match10(1;17);match11(18;16);match12(2;15);match13(3;14);match14(4;13);match15(5;12);match16(6;11);match17(7;10);match18(8;9);match19(1;16);match20(17;15);match21(18;14);match22(2;13);match23(3;12);match24(4;11);match25(5;10);match26(6;9);match27(7;8);match28(1;15);match29(16;14);match30(17;13);match31(18;12);match32(2;11);match33(3;10);match34(4;9);match35(5;8);match36(6;7);match37(1;14);match38(15;13);match39(16;12);match40(17;11);match41(18;10);match42(2;9);match43(3;8);match44(4;7);match45(5;6);match46(1;13);match47(14;12);match48(15;11);match49(16;10);match50(17;9);match51(18;8);match52(2;7);match53(3;6);match54(4;5);match55(1;12);match56(13;11);match57(14;10);match58(15;9);match59(16;8);match60(17;7);match61(18;6);match62(2;5);match63(3;4);match64(1;11);match65(12;10);match66(13;9);match67(14;8);match68(15;7);match69(16;6);match70(17;5);match71(18;4);match72(2;3);match73(1;10);match74(11;9);match75(12;8);match76(13;7);match77(14;6);match78(15;5);match79(16;4);match80(17;3);match81(18;2);match82(1;9);match83(10;8);match84(11;7);match85(12;6);match86(13;5);match87(14;4);match88(15;3);match89(16;2);match90(17;18);match91(1;8);match92(9;7);match93(10;6);match94(11;5);match95(12;4);match96(13;3);match97(14;2);match98(15;18);match99(16;17);match100(1;7);match101(8;6);match102(9;5);match103(10;4);match104(11;3);match105(12;2);match106(13;18);match107(14;17);match108(15;16);match109(1;6);match110(7;5);match111(8;4);match112(9;3);match113(10;2);match114(11;18);match115(12;17);match116(13;16);match117(14;15);match118(1;5);match119(6;4);match120(7;3);match121(8;2);match122(9;18);match123(10;17);match124(11;16);match125(12;15);match126(13;14);match127(1;4);match128(5;3);match129(6;2);match130(7;18);match131(8;17);match132(9;16);match133(10;15);match134(11;14);match135(12;13);match136(1;3);match137(4;2);match138(5;18);match139(6;17);match140(7;16);match141(8;15);match142(9;14);match143(10;13);match144(11;12);match145(1;2);match146(3;18);match147(4;17);match148(5;16);match149(6;15);match150(7;14);match151(8;13);match152(9;12);match153(10;11);'),
(18, 19, 'match1(2;19);match2(3;18);match3(4;17);match4(5;16);match5(6;15);match6(7;14);match7(8;13);match8(9;12);match9(10;11);match10(1;19);match11(2;17);match12(3;16);match13(4;15);match14(5;14);match15(6;13);match16(7;12);match17(8;11);match18(9;10);match19(1;18);match20(19;17);match21(2;15);match22(3;14);match23(4;13);match24(5;12);match25(6;11);match26(7;10);match27(8;9);match28(1;17);match29(18;16);match30(19;15);match31(2;13);match32(3;12);match33(4;11);match34(5;10);match35(6;9);match36(7;8);match37(1;16);match38(17;15);match39(18;14);match40(19;13);match41(2;11);match42(3;10);match43(4;9);match44(5;8);match45(6;7);match46(1;15);match47(16;14);match48(17;13);match49(18;12);match50(19;11);match51(2;9);match52(3;8);match53(4;7);match54(5;6);match55(1;14);match56(15;13);match57(16;12);match58(17;11);match59(18;10);match60(19;9);match61(2;7);match62(3;6);match63(4;5);match64(1;13);match65(14;12);match66(15;11);match67(16;10);match68(17;9);match69(18;8);match70(19;7);match71(2;5);match72(3;4);match73(1;12);match74(13;11);match75(14;10);match76(15;9);match77(16;8);match78(17;7);match79(18;6);match80(19;5);match81(2;3);match82(1;11);match83(12;10);match84(13;9);match85(14;8);match86(15;7);match87(16;6);match88(17;5);match89(18;4);match90(19;3);match91(1;10);match92(11;9);match93(12;8);match94(13;7);match95(14;6);match96(15;5);match97(16;4);match98(17;3);match99(18;2);match100(1;9);match101(10;8);match102(11;7);match103(12;6);match104(13;5);match105(14;4);match106(15;3);match107(16;2);match108(18;19);match109(1;8);match110(9;7);match111(10;6);match112(11;5);match113(12;4);match114(13;3);match115(14;2);match116(16;19);match117(17;18);match118(1;7);match119(8;6);match120(9;5);match121(10;4);match122(11;3);match123(12;2);match124(14;19);match125(15;18);match126(16;17);match127(1;6);match128(7;5);match129(8;4);match130(9;3);match131(10;2);match132(12;19);match133(13;18);match134(14;17);match135(15;16);match136(1;5);match137(6;4);match138(7;3);match139(8;2);match140(10;19);match141(11;18);match142(12;17);match143(13;16);match144(14;15);match145(1;4);match146(5;3);match147(6;2);match148(8;19);match149(9;18);match150(10;17);match151(11;16);match152(12;15);match153(13;14);match154(1;3);match155(4;2);match156(6;19);match157(7;18);match158(8;17);match159(9;16);match160(10;15);match161(11;14);match162(12;13);match163(1;2);match164(4;19);match165(5;18);match166(6;17);match167(7;16);match168(8;15);match169(9;14);match170(10;13);match171(11;12);'),
(19, 20, 'match1(1;20);match2(2;19);match3(3;18);match4(4;17);match5(5;16);match6(6;15);match7(7;14);match8(8;13);match9(9;12);match10(10;11);match11(1;19);match12(20;18);match13(2;17);match14(3;16);match15(4;15);match16(5;14);match17(6;13);match18(7;12);match19(8;11);match20(9;10);match21(1;18);match22(19;17);match23(20;16);match24(2;15);match25(3;14);match26(4;13);match27(5;12);match28(6;11);match29(7;10);match30(8;9);match31(1;17);match32(18;16);match33(19;15);match34(20;14);match35(2;13);match36(3;12);match37(4;11);match38(5;10);match39(6;9);match40(7;8);match41(1;16);match42(17;15);match43(18;14);match44(19;13);match45(20;12);match46(2;11);match47(3;10);match48(4;9);match49(5;8);match50(6;7);match51(1;15);match52(16;14);match53(17;13);match54(18;12);match55(19;11);match56(20;10);match57(2;9);match58(3;8);match59(4;7);match60(5;6);match61(1;14);match62(15;13);match63(16;12);match64(17;11);match65(18;10);match66(19;9);match67(20;8);match68(2;7);match69(3;6);match70(4;5);match71(1;13);match72(14;12);match73(15;11);match74(16;10);match75(17;9);match76(18;8);match77(19;7);match78(20;6);match79(2;5);match80(3;4);match81(1;12);match82(13;11);match83(14;10);match84(15;9);match85(16;8);match86(17;7);match87(18;6);match88(19;5);match89(20;4);match90(2;3);match91(1;11);match92(12;10);match93(13;9);match94(14;8);match95(15;7);match96(16;6);match97(17;5);match98(18;4);match99(19;3);match100(20;2);match101(1;10);match102(11;9);match103(12;8);match104(13;7);match105(14;6);match106(15;5);match107(16;4);match108(17;3);match109(18;2);match110(19;20);match111(1;9);match112(10;8);match113(11;7);match114(12;6);match115(13;5);match116(14;4);match117(15;3);match118(16;2);match119(17;20);match120(18;19);match121(1;8);match122(9;7);match123(10;6);match124(11;5);match125(12;4);match126(13;3);match127(14;2);match128(15;20);match129(16;19);match130(17;18);match131(1;7);match132(8;6);match133(9;5);match134(10;4);match135(11;3);match136(12;2);match137(13;20);match138(14;19);match139(15;18);match140(16;17);match141(1;6);match142(7;5);match143(8;4);match144(9;3);match145(10;2);match146(11;20);match147(12;19);match148(13;18);match149(14;17);match150(15;16);match151(1;5);match152(6;4);match153(7;3);match154(8;2);match155(9;20);match156(10;19);match157(11;18);match158(12;17);match159(13;16);match160(14;15);match161(1;4);match162(5;3);match163(6;2);match164(7;20);match165(8;19);match166(9;18);match167(10;17);match168(11;16);match169(12;15);match170(13;14);match171(1;3);match172(4;2);match173(5;20);match174(6;19);match175(7;18);match176(8;17);match177(9;16);match178(10;15);match179(11;14);match180(12;13);match181(1;2);match182(3;20);match183(4;19);match184(5;18);match185(6;17);match186(7;16);match187(8;15);match188(9;14);match189(10;13);match190(11;12);');

-- --------------------------------------------------------

--
-- Structure de la table `parametre`
--

CREATE TABLE `parametre` (
  `id` int NOT NULL,
  `id_tournoi` varchar(500) NOT NULL,
  `nbre_terrain_poule` int NOT NULL,
  `nbre_terrain_phasefinal` int NOT NULL,
  `temps_de_match` varchar(50) NOT NULL,
  `heure_debut_poule` varchar(50) NOT NULL,
  `heure_debut_phasefinal` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `troissets` int NOT NULL DEFAULT '3',
  `terrain_automatique` varchar(10) NOT NULL,
  `matchtermine` int DEFAULT '0',
  `tournoi_cacher` int NOT NULL DEFAULT '0',
  `tournoi_password` varchar(500) NOT NULL DEFAULT '',
  `timer` int NOT NULL DEFAULT '0',
  `qrcode` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `phases_finales`
--

CREATE TABLE `phases_finales` (
  `id` int NOT NULL,
  `id_tournoi` varchar(500) NOT NULL,
  `id_categorie` int DEFAULT NULL,
  `nom` varchar(100) NOT NULL DEFAULT 'Phase Finale',
  `type_bracket` varchar(30) NOT NULL,
  `nb_equipes` int NOT NULL,
  `nb_equipes_arrondi` int NOT NULL,
  `nb_rounds` int NOT NULL,
  `statut` varchar(20) DEFAULT 'en_attente',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `poule`
--

CREATE TABLE `poule` (
  `id` int NOT NULL,
  `id_tournoi` varchar(500) NOT NULL,
  `id_categorie` int NOT NULL,
  `id_poule` int NOT NULL,
  `nom` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `timer`
--

CREATE TABLE `timer` (
  `id` int NOT NULL,
  `id_tournoi` int DEFAULT NULL,
  `duration` int NOT NULL DEFAULT '0',
  `start_time` bigint DEFAULT NULL,
  `paused_at` int DEFAULT NULL,
  `status` enum('stopped','running','paused','finished') DEFAULT 'stopped',
  `sound_enabled` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tournoi`
--

CREATE TABLE `tournoi` (
  `id` int NOT NULL,
  `id_tournoi` varchar(500) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `user_uid` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_local`
--

CREATE TABLE `user_local` (
  `id` int NOT NULL,
  `user` varchar(500) NOT NULL,
  `password` varchar(500) NOT NULL,
  `user_uid` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `expire_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `user_local`
--

INSERT INTO `user_local` (`id`, `user`, `password`, `user_uid`, `expire_date`, `note`) VALUES
(1, 'local', '$2y$12$5eHbw0MMsynCnbl7ND5jBOtVMRn5nXWH9lRZBcFdodiqJDk.B74mu', '53b32f9d-b91e-436c-9933-eafce89a0091', '2037-07-29 14:23:20', 'local');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `equipe`
--
ALTER TABLE `equipe`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `equipes_phase_finale`
--
ALTER TABLE `equipes_phase_finale`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `matchs_phase_finale`
--
ALTER TABLE `matchs_phase_finale`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `match_poule`
--
ALTER TABLE `match_poule`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `ordre_match_poule`
--
ALTER TABLE `ordre_match_poule`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `parametre`
--
ALTER TABLE `parametre`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `phases_finales`
--
ALTER TABLE `phases_finales`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `poule`
--
ALTER TABLE `poule`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `timer`
--
ALTER TABLE `timer`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `tournoi`
--
ALTER TABLE `tournoi`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_local`
--
ALTER TABLE `user_local`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `equipe`
--
ALTER TABLE `equipe`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `equipes_phase_finale`
--
ALTER TABLE `equipes_phase_finale`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `matchs_phase_finale`
--
ALTER TABLE `matchs_phase_finale`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `match_poule`
--
ALTER TABLE `match_poule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ordre_match_poule`
--
ALTER TABLE `ordre_match_poule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `parametre`
--
ALTER TABLE `parametre`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `phases_finales`
--
ALTER TABLE `phases_finales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `poule`
--
ALTER TABLE `poule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `timer`
--
ALTER TABLE `timer`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tournoi`
--
ALTER TABLE `tournoi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_local`
--
ALTER TABLE `user_local`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
