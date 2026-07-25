-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 192.168.3.70
-- Généré le : sam. 18 juil. 2026 à 09:56
-- Version du serveur : 9.7.0
-- Version de PHP : 8.4.22

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
  `id_tournoi` int NOT NULL,
  `id_categorie` int NOT NULL,
  `nom` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `equipe`
--

CREATE TABLE `equipe` (
  `id` int NOT NULL,
  `id_tournoi` int NOT NULL,
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
  `id_tournoi` int NOT NULL,
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
  `id_tournoi` int NOT NULL,
  `id_phase_finale` int NOT NULL,
  `round` int NOT NULL,
  `sub_group` int NOT NULL,
  `match_num` int NOT NULL,
  `match_code` varchar(50) NOT NULL,
  `source_team1` varchar(100) DEFAULT NULL,
  `source_team2` varchar(100) DEFAULT NULL,
  `equipe1_id` int DEFAULT NULL,
  `equipe2_id` int DEFAULT NULL,
  `score1` int DEFAULT NULL,
  `score2` int DEFAULT NULL,
  `winner_equipe_id` int DEFAULT NULL,
  `loser_equipe_id` int DEFAULT NULL,
  `classement_min` int DEFAULT NULL,
  `classement_max` int DEFAULT NULL,
  `statut` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `statut_match` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'planifie',
  `terrain` varchar(50) DEFAULT NULL,
  `date_maj` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `match_poule`
--

CREATE TABLE `match_poule` (
  `id` int NOT NULL,
  `id_tournoi` int NOT NULL,
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
  `heure_debut` varchar(500) NOT NULL,
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

-- --------------------------------------------------------

--
-- Structure de la table `parametre`
--

CREATE TABLE `parametre` (
  `id` int NOT NULL,
  `id_tournoi` int NOT NULL,
  `nbre_terrain_poule` int NOT NULL,
  `nbre_terrain_phasefinal` int NOT NULL,
  `temps_de_match` varchar(50) NOT NULL,
  `heure_debut_poule` varchar(50) NOT NULL,
  `heure_debut_phasefinal` varchar(50) NOT NULL,
  `troissets` int NOT NULL DEFAULT '3',
  `terrain_automatique` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `phases_finales`
--

CREATE TABLE `phases_finales` (
  `id` int NOT NULL,
  `id_tournoi` int NOT NULL,
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
  `id_tournoi` int NOT NULL,
  `id_categorie` int NOT NULL,
  `id_poule` int NOT NULL,
  `nom` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tournoi`
--

CREATE TABLE `tournoi` (
  `id` int NOT NULL,
  `id_tournoi` int NOT NULL,
  `nom` varchar(50) NOT NULL,
  `uid` varchar(500) NOT NULL,
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
  `uid` varchar(500) NOT NULL,
  `expire_date` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `user`, `password`, `uid`, `expire_date`) VALUES
(2, 'william', '$2y$12$NrLmJnMfCnSL3U8W8YRZiu5yGLS1.k56wzp8hIWIiuFzylbemN.76', '611f0860-4ea6-4fd2-8691-6000c78b8571', '2030-07-09 08:45:50'),
(3, 'admin', '$2y$12$XGjXCwzOPSZrZsAzZH4yD.XJaHC4qWicCOs3Jdw0W89uHYXjlV4vW', '87e756b7-1d6a-42c3-94ec-9ccc55f8d318', '2030-07-09 08:45:52');

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
-- AUTO_INCREMENT pour la table `tournoi`
--
ALTER TABLE `tournoi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
