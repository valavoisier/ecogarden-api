-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 14 mars 2026 à 10:34
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ecogarden`
--

-- --------------------------------------------------------

--
-- Structure de la table `conseil`
--

CREATE TABLE `conseil` (
  `id` int(11) NOT NULL,
  `contenu` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `conseil`
--

INSERT INTO `conseil` (`id`, `contenu`) VALUES
(29, 'Plantez vos bulbes de printemps en terre bien drainée.'),
(30, 'Taillez vos rosiers pour favoriser une belle floraison.'),
(31, 'Arrosez vos plantes tôt le matin pour limiter l\'évaporation.'),
(32, 'Récoltez vos courgettes régulièrement pour stimuler la production.'),
(33, 'Protégez vos cultures du gel avec un voile d\'hivernage.'),
(34, 'Semez vos tomates en intérieur avant les Saints de Glace.'),
(35, 'Retournez votre compost pour l\'aérer.'),
(36, 'Cueillez pommes et poires à maturité, stockez au frais.'),
(37, 'Paillez le sol pour conserver l’humidité et limiter les mauvaises herbes.'),
(38, 'Éclaircissez vos semis pour permettre aux plants de bien se développer.'),
(39, 'Installez un récupérateur d’eau de pluie pour arroser durablement.'),
(40, 'Surveillez l’apparition des pucerons et utilisez du savon noir si nécessaire.'),
(41, 'Aérez la serre pour éviter l’excès d’humidité et les maladies.'),
(42, 'Buttez les pommes de terre pour favoriser la production.'),
(43, 'Taillez la lavande après la floraison pour la garder compacte.'),
(44, 'Nettoyez vos outils de jardin pour éviter la propagation de maladies.'),
(45, 'Plantez les arbres fruitiers à racines nues.'),
(46, 'Semez les engrais verts pour enrichir naturellement le sol.'),
(47, 'Protégez les jeunes plants du soleil avec un voile d’ombrage.'),
(48, 'Divisez les vivaces pour les rajeunir et les multiplier.'),
(49, 'Apportez du compost mûr pour nourrir le sol avant les plantations.'),
(50, 'Installez des tuteurs pour soutenir les plantes hautes.'),
(51, 'Ramassez les feuilles mortes pour éviter les maladies cryptogamiques.'),
(52, 'Installez des pièges à limaces pour protéger vos jeunes plants.');

-- --------------------------------------------------------

--
-- Structure de la table `conseil_mois`
--

CREATE TABLE `conseil_mois` (
  `conseil_id` int(11) NOT NULL,
  `mois_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `conseil_mois`
--

INSERT INTO `conseil_mois` (`conseil_id`, `mois_id`) VALUES
(29, 22),
(29, 23),
(30, 14),
(30, 15),
(31, 18),
(31, 19),
(31, 20),
(32, 18),
(32, 19),
(32, 20),
(32, 21),
(33, 13),
(33, 14),
(33, 23),
(33, 24),
(34, 15),
(34, 16),
(35, 15),
(35, 21),
(36, 21),
(36, 22),
(37, 17),
(37, 18),
(37, 19),
(37, 20),
(38, 16),
(38, 17),
(39, 15),
(39, 16),
(39, 17),
(40, 16),
(40, 17),
(40, 18),
(41, 16),
(41, 17),
(41, 18),
(41, 19),
(42, 17),
(42, 18),
(43, 20),
(43, 21),
(44, 22),
(44, 23),
(44, 24),
(45, 13),
(45, 23),
(45, 24),
(46, 21),
(46, 22),
(47, 19),
(47, 20),
(48, 15),
(48, 16),
(49, 14),
(49, 15),
(50, 16),
(50, 17),
(50, 18),
(51, 22),
(51, 23),
(52, 16),
(52, 17),
(52, 18);

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260303161916', '2026-03-03 17:20:01', 52),
('DoctrineMigrations\\Version20260303163414', '2026-03-03 17:34:33', 40),
('DoctrineMigrations\\Version20260303163923', '2026-03-03 17:39:43', 46),
('DoctrineMigrations\\Version20260313202355', '2026-03-13 21:26:18', 37),
('DoctrineMigrations\\Version20260313213201', '2026-03-13 22:41:16', 446),
('DoctrineMigrations\\Version20260313215032', '2026-03-13 22:56:18', 18),
('DoctrineMigrations\\Version20260313220719', '2026-03-13 23:09:30', 18),
('DoctrineMigrations\\Version20260313223146', '2026-03-13 23:33:16', 43);

-- --------------------------------------------------------

--
-- Structure de la table `mois`
--

CREATE TABLE `mois` (
  `id` int(11) NOT NULL,
  `numero` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `mois`
--

INSERT INTO `mois` (`id`, `numero`) VALUES
(13, 1),
(14, 2),
(15, 3),
(16, 4),
(17, 5),
(18, 6),
(19, 7),
(20, 8),
(21, 9),
(22, 10),
(23, 11),
(24, 12);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `roles`, `password`, `city`) VALUES
(8, 'user@ecogarden.com', '[\"ROLE_USER\"]', '$2y$13$FfOI9d5yGpfus0uMFr4cYOd0mtMJOfB2tdff5IRBTis8xKo.jATJm', 'Paris'),
(9, 'admin@ecogarden.com', '[\"ROLE_ADMIN\"]', '$2y$13$v/50SfA3tSI/YtRGf5tPh.d1ZRWysg2BZCqcmq1szFR09yQvOikBy', 'Laon');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `conseil`
--
ALTER TABLE `conseil`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `conseil_mois`
--
ALTER TABLE `conseil_mois`
  ADD PRIMARY KEY (`conseil_id`,`mois_id`),
  ADD KEY `IDX_5591B2C3668A3E03` (`conseil_id`),
  ADD KEY `IDX_5591B2C3FA0749B8` (`mois_id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `mois`
--
ALTER TABLE `mois`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `conseil`
--
ALTER TABLE `conseil`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT pour la table `mois`
--
ALTER TABLE `mois`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `conseil_mois`
--
ALTER TABLE `conseil_mois`
  ADD CONSTRAINT `FK_5591B2C3668A3E03` FOREIGN KEY (`conseil_id`) REFERENCES `conseil` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_5591B2C3FA0749B8` FOREIGN KEY (`mois_id`) REFERENCES `mois` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
