-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 192.168.3.70
-- Généré le : dim. 04 oct. 2026 à 17:06
-- Version du serveur : 26.7.0
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
  `date_maj` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dernier_modifiant` varchar(500) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `match_ordre`
--

CREATE TABLE `match_ordre` (
  `id` int NOT NULL,
  `id_tournoi` int NOT NULL,
  `ordre` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `match_ordre`
--

INSERT INTO `match_ordre` (`id`, `id_tournoi`, `ordre`) VALUES
(460, 5, '[\"P_1\",\"P_2\",\"P_3\",\"P_4\",\"P_5\",\"P_6\",\"P_7\",\"P_8\",\"P_9\",\"P_10\",\"P_11\",\"P_12\",\"P_13\",\"P_14\",\"P_15\",\"P_16\",\"P_17\",\"P_18\",\"P_19\",\"P_20\",\"P_21\",\"P_22\",\"P_23\",\"P_24\",\"P_25\",\"P_26\",\"P_27\",\"P_28\",\"P_29\",\"P_30\",\"P_31\",\"P_32\",\"P_33\",\"P_34\",\"P_35\",\"P_36\",\"P_37\",\"P_38\",\"P_39\",\"P_40\",\"P_41\",\"P_42\",\"P_43\",\"P_44\",\"P_45\",\"P_46\",\"P_47\",\"P_48\",\"P_49\",\"P_50\",\"P_51\",\"P_52\",\"P_53\",\"P_54\",\"P_55\",\"P_56\",\"P_57\",\"P_58\",\"P_59\",\"P_60\",\"P_61\",\"P_62\",\"P_63\",\"P_64\",\"P_65\",\"P_66\",\"P_67\",\"P_68\",\"P_69\",\"P_70\",\"P_71\",\"P_72\",\"P_73\",\"P_74\",\"P_75\",\"P_76\",\"P_77\",\"P_78\",\"P_79\",\"P_80\",\"P_81\",\"P_82\",\"P_83\",\"P_84\",\"P_85\",\"P_86\",\"P_87\",\"P_88\",\"P_89\",\"P_90\",\"P_91\",\"P_92\",\"P_93\",\"P_94\",\"P_95\",\"P_96\",\"P_97\",\"P_98\",\"P_99\",\"P_100\",\"P_101\",\"P_102\",\"P_103\",\"P_104\",\"P_105\",\"P_106\",\"P_107\",\"P_108\",\"P_109\",\"P_110\",\"P_111\",\"P_112\",\"P_113\",\"P_114\",\"P_115\",\"P_116\",\"P_117\",\"P_118\",\"P_119\",\"P_120\",\"F_12\",\"F_11\",\"F_10\",\"F_9\",\"F_8\",\"F_7\",\"F_6\",\"F_5\",\"F_4\",\"F_3\",\"F_2\",\"F_1\",\"F_24\",\"F_23\",\"F_22\",\"F_21\",\"F_20\",\"F_19\",\"F_18\",\"F_17\",\"F_16\",\"F_15\",\"F_14\",\"F_13\",\"F_36\",\"F_35\",\"F_34\",\"F_33\",\"F_32\",\"F_31\",\"F_30\",\"F_29\",\"F_28\",\"F_27\",\"F_26\",\"F_25\",\"F_48\",\"F_47\",\"F_46\",\"F_45\",\"F_44\",\"F_43\",\"F_42\",\"F_41\",\"F_40\",\"F_39\",\"F_38\",\"F_37\",\"F_60\",\"F_59\",\"F_58\",\"F_57\",\"F_56\",\"F_55\",\"F_54\",\"F_53\",\"F_52\",\"F_51\",\"F_50\",\"F_72\",\"F_71\",\"F_70\",\"F_69\",\"F_68\",\"F_67\",\"F_66\",\"F_65\",\"F_64\",\"F_63\",\"F_62\",\"F_49\",\"F_61\"]');

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
  `id_equipe_3` int DEFAULT NULL,
  `id_equipe_4` int DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'planifie',
  `score_equipe_1` varchar(50) NOT NULL DEFAULT '0*0*0',
  `score_equipe_2` varchar(50) NOT NULL DEFAULT '0*0*0',
  `heure_debut` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `heure_fin` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ordre_affichage` int NOT NULL DEFAULT '0',
  `dernier_modifiant` varchar(500) NOT NULL DEFAULT '',
  `numero_tour` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ompn.old`
--

CREATE TABLE `ompn.old` (
  `id` int NOT NULL,
  `quoi` varchar(50) NOT NULL DEFAULT '',
  `data` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `ompn.old`
--

INSERT INTO `ompn.old` (`id`, `quoi`, `data`) VALUES
(1, 'color', '255,255,255;204,0,204;255,255,255;0,102,0;255,255,255;153,102,0;255,255,255;0,102,204;255,255,255;153,51,51;0,0,0;211,211,211;0,0,0;0,204,204;0,0,0;204,204,255;0,0,0;255,153,0;0,0,0;0,204,0;255,255,255;204,0,204;255,255,255;0,102,0;255,255,255;153,102,0;255,255,255;0,102,204;255,255,255;153,51,51;0,0,0;211,211,211;0,0,0;0,204,204;0,0,0;204,204,255;0,0,0;255,153,0;0,0,0;0,204,0;255,255,255;204,0,204;255,255,255;0,102,0;255,255,255;153,102,0;255,255,255;0,102,204;255,255,255;153,51,51;0,0,0;211,211,211;0,0,0;0,204,204;0,0,0;204,204,255;0,0,0;255,153,0;0,0,0;0,204,0;255,255,255;204,0,204;255,255,255;0,102,0;255,255,255;153,102,0;255,255,255;0,102,204;255,255,255;153,51,51;0,0,0;211,211,211;0,0,0;0,204,204;0,0,0;204,204,255;0,0,0;255,153,0;0,0,0;0,204,0'),
(2, '2', '1;2'),
(3, '3', '1;2;1;3;2;3'),
(4, '4', '1;2;3;4;1;3;2;4;1;4;2;3'),
(5, '5', '1;2;3;4;1;3;2;5;1;4;3;5;1;5;2;4;2;3;4;5'),
(6, '6', '1;2;3;4;5;6;1;3;2;5;4;6;1;6;2;3;4;5;1;4;2;6;3;5;1;5;2;4;3;6'),
(7, '7', '1;2;3;4;5;6;1;3;5;4;6;7;1;4;2;5;7;3;1;5;6;3;2;7;3;5;2;6;4;7;1;6;2;4;5;7;1;7;2;3;4;6'),
(8, '8', '1;2;3;4;5;6;7;8;1;3;2;4;5;7;6;8;1;4;2;3;5;8;6;7;1;5;2;6;3;7;4;8;1;6;2;5;3;8;4;7;1;7;2;8;3;5;4;6;1;8;2;7;3;6;4;5'),
(9, '9', '1;2;3;4;5;6;7;8;1;9;2;3;4;5;6;7;1;8;2;4;5;7;6;9;1;3;2;9;4;6;8;5;1;7;2;6;3;5;8;9;4;9;2;5;3;7;6;8;1;4;2;7;3;8;5;9;1;6;3;9;2;8;4;7;1;5;7;9;6;3;4;8'),
(10, '10', '1;2;3;4;5;6;7;8;9;10;6;4;2;10;5;8;7;9;1;3;3;5;8;1;10;7;6;9;2;4;8;10;6;3;9;1;2;5;4;7;2;9;10;5;6;8;4;1;7;3;3;10;5;4;7;2;9;8;1;6;10;1;5;7;8;3;2;6;4;9;3;9;6;7;1;5;10;4;8;2;1;7;2;3;9;5;4;8;6;10'),
(11, '11', '1;2;7;8;4;5;10;11;2;3;8;9;5;6;3;1;9;7;6;4;2;5;8;11;1;4;7;10;5;3;11;9;1;6;4;2;10;8;3;6;5;1;11;7;3;4;9;10;6;2;1;7;3;9;10;4;8;2;5;11;1;8;9;2;3;10;4;11;6;7;9;1;2;10;11;3;7;5;6;8;10;1;11;2;4;7;8;5;6;9;11;1;7;3;4;8;9;5;6;10;2;7;8;3;4;9;10;5;6;11'),
(12, '12', '1;2;3;4;5;6;7;8;9;10;11;12;1;4;3;6;5;8;7;10;9;12;11;2;1;6;3;8;5;10;7;12;9;2;11;4;1;8;3;10;5;12;7;2;9;4;11;6;1;10;3;12;5;2;7;4;9;6;11;8;1;12;3;2;5;4;7;6;9;8;11;10;1;3;5;7;9;11;2;4;6;8;10;12;1;5;3;9;7;11;2;6;4;10;8;12;1;7;3;11;5;9;2;8;4;12;6;10;1;9;3;7;5;11;2;10;4;8;6;12;1;11;3;5;7;9;2;12;4;6;8;10'),
(13, '13', '2;13;3;12;4;11;5;10;6;9;7;8;1;13;2;11;3;10;4;9;5;8;6;7;1;12;13;11;2;9;3;8;4;7;5;6;1;11;12;10;13;9;2;7;3;6;4;5;1;10;11;9;12;8;13;7;2;5;3;4;1;9;10;8;11;7;12;6;13;5;2;3;1;8;9;7;10;6;11;5;12;4;13;3;1;7;8;6;9;5;10;4;11;3;12;2;1;6;7;5;8;4;9;3;10;2;12;13;1;5;6;4;7;3;8;2;10;13;11;12;1;4;5;3;6;2;8;13;9;12;10;11;1;3;4;2;6;13;7;12;8;11;9;10;1;2;4;13;5;12;6;11;7;10;8;9'),
(14, '14', '1;14;2;13;3;12;4;11;5;10;6;9;7;8;1;13;14;12;2;11;3;10;4;9;5;8;6;7;1;12;13;11;14;10;2;9;3;8;4;7;5;6;1;11;12;10;13;9;14;8;2;7;3;6;4;5;1;10;11;9;12;8;13;7;14;6;2;5;3;4;1;9;10;8;11;7;12;6;13;5;14;4;2;3;1;8;9;7;10;6;11;5;12;4;13;3;14;2;1;7;8;6;9;5;10;4;11;3;12;2;13;14;1;6;7;5;8;4;9;3;10;2;11;14;12;13;1;5;6;4;7;3;8;2;9;14;10;13;11;12;1;4;5;3;6;2;7;14;8;13;9;12;10;11;1;3;4;2;5;14;6;13;7;12;8;11;9;10;1;2;3;14;4;13;5;12;6;11;7;10;8;9'),
(15, '15', '2;15;3;14;4;13;5;12;6;11;7;10;8;9;1;15;2;13;3;12;4;11;5;10;6;9;7;8;1;14;15;13;2;11;3;10;4;9;5;8;6;7;1;13;14;12;15;11;2;9;3;8;4;7;5;6;1;12;13;11;14;10;15;9;2;7;3;6;4;5;1;11;12;10;13;9;14;8;15;7;2;5;3;4;1;10;11;9;12;8;13;7;14;6;15;5;2;3;1;9;10;8;11;7;12;6;13;5;14;4;15;3;1;8;9;7;10;6;11;5;12;4;13;3;14;2;1;7;8;6;9;5;10;4;11;3;12;2;14;15;1;6;7;5;8;4;9;3;10;2;12;15;13;14;1;5;6;4;7;3;8;2;10;15;11;14;12;13;1;4;5;3;6;2;8;15;9;14;10;13;11;12;1;3;4;2;6;15;7;14;8;13;9;12;10;11;1;2;4;15;5;14;6;13;7;12;8;11;9;10'),
(16, '16', '1;2;3;4;5;6;7;8;9;10;11;12;13;14;15;16;1;3;2;4;5;7;6;8;9;11;10;12;13;15;14;16;1;4;2;3;5;8;6;7;9;12;10;11;13;16;14;15;1;5;2;6;3;7;4;8;9;13;10;14;11;15;12;16;1;6;2;5;3;8;4;7;9;14;10;13;11;16;12;15;1;7;2;8;3;5;4;6;9;15;10;16;11;13;12;14;1;8;2;7;3;6;4;5;9;16;10;15;11;14;12;13;1;9;2;10;3;11;4;12;5;13;6;14;7;15;8;16;1;10;2;9;3;12;4;11;5;14;6;13;7;16;8;15;1;11;2;12;3;9;4;10;5;15;6;16;7;13;8;14;1;12;2;11;3;10;4;9;5;16;6;15;7;14;8;13;1;13;2;14;3;15;4;16;5;9;6;10;7;11;8;12;1;14;2;13;3;16;4;15;5;10;6;9;7;12;8;11;1;15;2;16;3;13;4;14;5;11;6;12;7;9;8;10;1;16;2;15;3;14;4;13;5;12;6;11;7;10;8;9'),
(17, '17', '2;17;3;16;4;15;5;14;6;13;7;12;8;11;9;10;1;17;2;15;3;14;4;13;5;12;6;11;7;10;8;9;1;16;17;15;2;13;3;12;4;11;5;10;6;9;7;8;1;15;16;14;17;13;2;11;3;10;4;9;5;8;6;7;1;14;15;13;16;12;17;11;2;9;3;8;4;7;5;6;1;13;14;12;15;11;16;10;17;9;2;7;3;6;4;5;1;12;13;11;14;10;15;9;16;8;17;7;2;5;3;4;1;11;12;10;13;9;14;8;15;7;16;6;17;5;2;3;1;10;11;9;12;8;13;7;14;6;15;5;16;4;17;3;1;9;10;8;11;7;12;6;13;5;14;4;15;3;16;2;1;8;9;7;10;6;11;5;12;4;13;3;14;2;16;17;1;7;8;6;9;5;10;4;11;3;12;2;14;17;15;16;1;6;7;5;8;4;9;3;10;2;12;17;13;16;14;15;1;5;6;4;7;3;8;2;10;17;11;16;12;15;13;14;1;4;5;3;6;2;8;17;9;16;10;15;11;14;12;13;1;3;4;2;6;17;7;16;8;15;9;14;10;13;11;12;1;2;4;17;5;16;6;15;7;14;8;13;9;12;10;11'),
(18, '18', '1;18;2;17;3;16;4;15;5;14;6;13;7;12;8;11;9;10;1;17;18;16;2;15;3;14;4;13;5;12;6;11;7;10;8;9;1;16;17;15;18;14;2;13;3;12;4;11;5;10;6;9;7;8;1;15;16;14;17;13;18;12;2;11;3;10;4;9;5;8;6;7;1;14;15;13;16;12;17;11;18;10;2;9;3;8;4;7;5;6;1;13;14;12;15;11;16;10;17;9;18;8;2;7;3;6;4;5;1;12;13;11;14;10;15;9;16;8;17;7;18;6;2;5;3;4;1;11;12;10;13;9;14;8;15;7;16;6;17;5;18;4;2;3;1;10;11;9;12;8;13;7;14;6;15;5;16;4;17;3;18;2;1;9;10;8;11;7;12;6;13;5;14;4;15;3;16;2;17;18;1;8;9;7;10;6;11;5;12;4;13;3;14;2;15;18;16;17;1;7;8;6;9;5;10;4;11;3;12;2;13;18;14;17;15;16;1;6;7;5;8;4;9;3;10;2;11;18;12;17;13;16;14;15;1;5;6;4;7;3;8;2;9;18;10;17;11;16;12;15;13;14;1;4;5;3;6;2;7;18;8;17;9;16;10;15;11;14;12;13;1;3;4;2;5;18;6;17;7;16;8;15;9;14;10;13;11;12;1;2;3;18;4;17;5;16;6;15;7;14;8;13;9;12;10;11'),
(19, '19', '2;19;3;18;4;17;5;16;6;15;7;14;8;13;9;12;10;11;1;19;2;17;3;16;4;15;5;14;6;13;7;12;8;11;9;10;1;18;19;17;2;15;3;14;4;13;5;12;6;11;7;10;8;9;1;17;18;16;19;15;2;13;3;12;4;11;5;10;6;9;7;8;1;16;17;15;18;14;19;13;2;11;3;10;4;9;5;8;6;7;1;15;16;14;17;13;18;12;19;11;2;9;3;8;4;7;5;6;1;14;15;13;16;12;17;11;18;10;19;9;2;7;3;6;4;5;1;13;14;12;15;11;16;10;17;9;18;8;19;7;2;5;3;4;1;12;13;11;14;10;15;9;16;8;17;7;18;6;19;5;2;3;1;11;12;10;13;9;14;8;15;7;16;6;17;5;18;4;19;3;1;10;11;9;12;8;13;7;14;6;15;5;16;4;17;3;18;2;1;9;10;8;11;7;12;6;13;5;14;4;15;3;16;2;18;19;1;8;9;7;10;6;11;5;12;4;13;3;14;2;16;19;17;18;1;7;8;6;9;5;10;4;11;3;12;2;14;19;15;18;16;17;1;6;7;5;8;4;9;3;10;2;12;19;13;18;14;17;15;16;1;5;6;4;7;3;8;2;10;19;11;18;12;17;13;16;14;15;1;4;5;3;6;2;8;19;9;18;10;17;11;16;12;15;13;14;1;3;4;2;6;19;7;18;8;17;9;16;10;15;11;14;12;13;1;2;4;19;5;18;6;17;7;16;8;15;9;14;10;13;11;12'),
(20, '20', '1;20;2;19;3;18;4;17;5;16;6;15;7;14;8;13;9;12;10;11;1;19;20;18;2;17;3;16;4;15;5;14;6;13;7;12;8;11;9;10;1;18;19;17;20;16;2;15;3;14;4;13;5;12;6;11;7;10;8;9;1;17;18;16;19;15;20;14;2;13;3;12;4;11;5;10;6;9;7;8;1;16;17;15;18;14;19;13;20;12;2;11;3;10;4;9;5;8;6;7;1;15;16;14;17;13;18;12;19;11;20;10;2;9;3;8;4;7;5;6;1;14;15;13;16;12;17;11;18;10;19;9;20;8;2;7;3;6;4;5;1;13;14;12;15;11;16;10;17;9;18;8;19;7;20;6;2;5;3;4;1;12;13;11;14;10;15;9;16;8;17;7;18;6;19;5;20;4;2;3;1;11;12;10;13;9;14;8;15;7;16;6;17;5;18;4;19;3;20;2;1;10;11;9;12;8;13;7;14;6;15;5;16;4;17;3;18;2;19;20;1;9;10;8;11;7;12;6;13;5;14;4;15;3;16;2;17;20;18;19;1;8;9;7;10;6;11;5;12;4;13;3;14;2;15;20;16;19;17;18;1;7;8;6;9;5;10;4;11;3;12;2;13;20;14;19;15;18;16;17;1;6;7;5;8;4;9;3;10;2;11;20;12;19;13;18;14;17;15;16;1;5;6;4;7;3;8;2;9;20;10;19;11;18;12;17;13;16;14;15;1;4;5;3;6;2;7;20;8;19;9;18;10;17;11;16;12;15;13;14;1;3;4;2;5;20;6;19;7;18;8;17;9;16;10;15;11;14;12;13;1;2;3;20;4;19;5;18;6;17;7;16;8;15;9;14;10;13;11;12');

-- --------------------------------------------------------

--
-- Structure de la table `ordre_match_poule`
--

CREATE TABLE `ordre_match_poule` (
  `id` int NOT NULL,
  `nbre_equipe` int NOT NULL,
  `ordre` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `qrcode` int NOT NULL DEFAULT '1',
  `scoring_password` varchar(500) DEFAULT NULL,
  `scoring_matchtermine` int NOT NULL DEFAULT '0',
  `tournoi_salade` int NOT NULL DEFAULT '0'
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
-- Structure de la table `preference`
--

CREATE TABLE `preference` (
  `id` int NOT NULL,
  `user` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `largeur` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
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
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int NOT NULL,
  `user` varchar(500) NOT NULL,
  `password` varchar(500) NOT NULL,
  `user_uid` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `expire_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note` varchar(500) DEFAULT NULL
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
-- Index pour la table `match_ordre`
--
ALTER TABLE `match_ordre`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `match_poule`
--
ALTER TABLE `match_poule`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `ompn.old`
--
ALTER TABLE `ompn.old`
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
-- Index pour la table `preference`
--
ALTER TABLE `preference`
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
-- Index pour la table `user`
--
ALTER TABLE `user`
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
-- AUTO_INCREMENT pour la table `match_ordre`
--
ALTER TABLE `match_ordre`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=511;

--
-- AUTO_INCREMENT pour la table `match_poule`
--
ALTER TABLE `match_poule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ompn.old`
--
ALTER TABLE `ompn.old`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `ordre_match_poule`
--
ALTER TABLE `ordre_match_poule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT pour la table `preference`
--
ALTER TABLE `preference`
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
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
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
