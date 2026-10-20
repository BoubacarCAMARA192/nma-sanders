-- =====================================================================
-- Plateforme NMA Sanders - Gestion et suivi des commandes de farine
-- Base de donnees : nma_sanders (MySQL / MariaDB)
-- Encodage : utf8mb4 / InnoDB
-- =====================================================================

DROP DATABASE IF EXISTS nma_sanders;
CREATE DATABASE nma_sanders CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nma_sanders;

-- ---------------------------------------------------------------------
-- 1. UTILISATEUR  (comptes : boulanger, gestionnaire, administrateur)
-- ---------------------------------------------------------------------
CREATE TABLE utilisateur (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom                 VARCHAR(150)        NOT NULL,
    email               VARCHAR(190)        NOT NULL UNIQUE,
    telephone           VARCHAR(30)         DEFAULT NULL,
    mot_de_passe        VARCHAR(255)        NOT NULL,           -- hachage bcrypt
    role                ENUM('boulanger','gestionnaire','administrateur') NOT NULL DEFAULT 'boulanger',
    actif               TINYINT(1)          NOT NULL DEFAULT 0, -- activation par l'administrateur
    double_verification TINYINT(1)          NOT NULL DEFAULT 0, -- 2e verification (gestionnaire/admin)
    code_securite       VARCHAR(255)        DEFAULT NULL,       -- code 2nd facteur (hache)
    tentatives_connexion TINYINT UNSIGNED   NOT NULL DEFAULT 0,
    bloque_jusqua       DATETIME            DEFAULT NULL,
    reset_token         VARCHAR(64)         DEFAULT NULL,
    reset_expire_le     DATETIME            DEFAULT NULL,
    derniere_connexion  DATETIME            DEFAULT NULL,
    cree_le             DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    maj_le              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role (role, actif)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. BOULANGER  (client : artisan ou boulangerie)
-- ---------------------------------------------------------------------
CREATE TABLE boulanger (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id      INT UNSIGNED        NOT NULL UNIQUE,
    nom_boulangerie     VARCHAR(190)        NOT NULL,
    adresse             VARCHAR(255)        NOT NULL,
    ville               VARCHAR(100)        NOT NULL DEFAULT 'Dakar',
    zone_livraison      VARCHAR(100)        DEFAULT NULL,
    cree_le             DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_boulanger_user FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. PRODUIT  (catalogue de la farine et derives)
-- ---------------------------------------------------------------------
CREATE TABLE produit (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference           VARCHAR(50)         NOT NULL UNIQUE,
    libelle             VARCHAR(150)        NOT NULL,
    description         VARCHAR(255)        DEFAULT NULL,
    image               VARCHAR(255)        DEFAULT NULL,
    unite               VARCHAR(30)         NOT NULL DEFAULT 'sac de 50 kg',
    prix_unitaire       DECIMAL(12,2)       DEFAULT NULL,       -- optionnel
    stock_disponible    INT                 NOT NULL DEFAULT 0,
    seuil_alerte        INT                 NOT NULL DEFAULT 20,
    actif               TINYINT(1)          NOT NULL DEFAULT 1,
    cree_le             DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    maj_le              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_produit_actif (actif)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. COMMANDE
-- ---------------------------------------------------------------------
CREATE TABLE commande (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference               VARCHAR(20)     NOT NULL UNIQUE,
    boulanger_id            INT UNSIGNED    NOT NULL,
    gestionnaire_id         INT UNSIGNED    DEFAULT NULL,
    date_commande           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_livraison_souhaitee DATE           NOT NULL,
    adresse_livraison       VARCHAR(255)    NOT NULL,
    statut                  ENUM('recue','validee','en_preparation','en_livraison','livree','refusee','annulee')
                                            NOT NULL DEFAULT 'recue',
    commentaire             TEXT            DEFAULT NULL,
    mode_paiement           VARCHAR(30)     DEFAULT NULL,
    motif_refus             VARCHAR(255)    DEFAULT NULL,
    cree_le                 DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    maj_le                  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cmd_boulanger FOREIGN KEY (boulanger_id)
        REFERENCES boulanger(id) ON DELETE RESTRICT,
    CONSTRAINT fk_cmd_gestionnaire FOREIGN KEY (gestionnaire_id)
        REFERENCES utilisateur(id) ON DELETE SET NULL,
    INDEX idx_cmd_statut (statut),
    INDEX idx_cmd_date (date_commande),
    INDEX idx_cmd_boulanger (boulanger_id, statut)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. LIGNE_COMMANDE
-- ---------------------------------------------------------------------
CREATE TABLE ligne_commande (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id         INT UNSIGNED        NOT NULL,
    produit_id          INT UNSIGNED        NOT NULL,
    quantite            INT UNSIGNED        NOT NULL,           -- entier >= 1
    prix_unitaire       DECIMAL(12,2)       DEFAULT NULL,
    CONSTRAINT fk_ligne_commande FOREIGN KEY (commande_id)
        REFERENCES commande(id) ON DELETE CASCADE,
    CONSTRAINT fk_ligne_produit FOREIGN KEY (produit_id)
        REFERENCES produit(id) ON DELETE RESTRICT,
    CONSTRAINT chk_quantite CHECK (quantite >= 1),
    UNIQUE KEY uk_cmd_produit (commande_id, produit_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. LIVRAISON
-- ---------------------------------------------------------------------
CREATE TABLE livraison (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id         INT UNSIGNED        NOT NULL UNIQUE,
    date_livraison      DATE                NOT NULL,
    livreur             VARCHAR(150)        DEFAULT NULL,
    vehicule            VARCHAR(100)        DEFAULT NULL,
    etat                ENUM('planifiee','en_cours','livree','echouee') NOT NULL DEFAULT 'planifiee',
    commentaire         VARCHAR(255)        DEFAULT NULL,
    cree_le             DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    maj_le              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_livraison_commande FOREIGN KEY (commande_id)
        REFERENCES commande(id) ON DELETE CASCADE,
    INDEX idx_livraison_date (date_livraison, etat)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. HISTORIQUE_STATUT  (tracabilite horodatee et attribuee)
-- ---------------------------------------------------------------------
CREATE TABLE historique_statut (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id         INT UNSIGNED        NOT NULL,
    statut              ENUM('recue','validee','en_preparation','en_livraison','livree','refusee','annulee') NOT NULL,
    date_heure          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    utilisateur_id      INT UNSIGNED        DEFAULT NULL,
    motif               VARCHAR(255)        DEFAULT NULL,
    CONSTRAINT fk_hist_commande FOREIGN KEY (commande_id)
        REFERENCES commande(id) ON DELETE CASCADE,
    CONSTRAINT fk_hist_user FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON DELETE SET NULL,
    INDEX idx_hist_commande (commande_id, date_heure)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. NOTIFICATION  (in-app : gestionnaire et boulanger)
-- ---------------------------------------------------------------------
CREATE TABLE notification (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id      INT UNSIGNED        NOT NULL,
    titre               VARCHAR(150)        NOT NULL,
    message             VARCHAR(500)        NOT NULL,
    lien                VARCHAR(255)        DEFAULT NULL,
    type                ENUM('info','succes','alerte','danger') NOT NULL DEFAULT 'info',
    lu                  TINYINT(1)          NOT NULL DEFAULT 0,
    cree_le             DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON DELETE CASCADE,
    INDEX idx_notif_user (utilisateur_id, lu)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. JOURNAL_OPERATION  (audit : identifiant unique par operation)
-- ---------------------------------------------------------------------
CREATE TABLE journal_operation (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id      INT UNSIGNED        DEFAULT NULL,
    action              VARCHAR(80)         NOT NULL,
    entite              VARCHAR(50)         DEFAULT NULL,
    entite_id           INT UNSIGNED        DEFAULT NULL,
    details             VARCHAR(500)        DEFAULT NULL,
    adresse_ip          VARCHAR(45)         DEFAULT NULL,
    date_heure          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_journal_user FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id) ON DELETE SET NULL,
    INDEX idx_journal_date (date_heure)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. PARAMETRE  (reglages applicatifs)
-- ---------------------------------------------------------------------
CREATE TABLE parametre (
    cle                 VARCHAR(50)         PRIMARY KEY,
    valeur              VARCHAR(255)        NOT NULL,
    libelle             VARCHAR(190)        NOT NULL
) ENGINE=InnoDB;

-- =====================================================================
-- DONNEES INITIALES
-- =====================================================================

-- Parametres ------------------------------------------------------
INSERT INTO parametre (cle, valeur, libelle) VALUES
('delai_minimum_heures', '24',              'Delai minimum entre la commande et la livraison (heures)'),
('max_tentatives_connexion', '5',           'Nombre de tentatives de connexion avant blocage'),
('duree_blocage_minutes', '15',             'Duree du blocage apres echecs repetes (minutes)'),
('session_duree_minutes', '45',             'Duree d''inactivite avant expiration de la session (minutes)'),
('jours_historique_par_defaut', '90',       'Profondeur par defaut de l''historique affiche (jours)');

-- Utilisateurs ----------------------------------------------------
-- Mots de passe de demonstration (a changer des la mise en ligne) :
--   administrateur : Admin@2026
--   gestionnaire   : Gestion@2026
--   boulanger      : Boulanger@2026
--   client 1       : Client1@2026
--   client 2       : Client2@2026
INSERT INTO utilisateur (nom, email, telephone, mot_de_passe, role, actif, double_verification, code_securite) VALUES
('Administrateur NMA', 'admin@nmasanders.sn',        '+221 33 839 00 00', '$2y$10$GWt.FvL0dLPMtbeTYWSCa.xzr9LBUCv/dvUqPx.OOrbL0cG5C7XLe', 'administrateur', 1, 0, NULL),
('Moussa Ndiaye',      'gestionnaire@nmasanders.sn', '+221 77 123 45 67', '$2y$10$6643pMK.dvzQHm1n5nkRSO64JGfSeaCPxvBA6NuFAKVNweoLIVPfu', 'gestionnaire',   1, 0, NULL),
('Boulangerie Demo',   'boulanger@demo.sn',          '+221 76 987 65 43', '$2y$10$jtGFqmr/cOJ5DP98/fXpiORu96y3KYiwO6BcEIOYMRXF/ZOvU/oay',
  'boulanger', 1, 0, NULL),
('Aissatou Fall',      'client1@demo.sn',            '+221 77 801 23 45', '$2y$10$2vTPzRVyr6BB4.GsaOneS.EksMNgztvuz0vq9mB3BYzkXJV6uSjO.', 'boulanger', 1, 0, NULL),
('Omar Sow',           'client2@demo.sn',            '+221 78 902 34 56', '$2y$10$QZng4SuDjEjkborDdIJVQen85X8EYwX5JuwFLuArhhz9K7r.rSvne', 'boulanger', 1, 0, NULL);

INSERT INTO boulanger (utilisateur_id, nom_boulangerie, adresse, ville, zone_livraison) VALUES
(3, 'Boulangerie Demo',      'Rue 12 x Avenue Bourguiba', 'Dakar', 'Dakar Plateau'),
(4, 'Boulangerie du Marche', 'Cite Fass, rue 22',         'Dakar', 'Fass / Colobane'),
(5, 'Patisserie Teranga',    'Liberte 6, villa 41',       'Dakar', 'Liberte 6');

-- Catalogue produits : les 4 farines NMA Sanders -------------------
INSERT INTO produit (reference, libelle, description, image, unite, prix_unitaire, stock_disponible, seuil_alerte) VALUES
('FAR-T55',  'Farine Boulangère',           'Farine de blé tendre T55 complémentée, idéale pour le pain courant.',      'assets/img/produits/farine-boulangere.png',           'sac de 50 kg', 15200.00, 480, 60),
('FAR-AMEL', 'Farine Boulangère Améliorée', 'Farine T55 enrichie en fer et acide folique, pour les professionnels.',    'assets/img/produits/farine-boulangere-amelioree.png', 'sac de 50 kg', 15700.00, 400, 60),
('FAR-TAPA', 'Farine Tapalapa',             'Farine de blé T80 spécialement conçue pour le pain tapalapa.',            'assets/img/produits/farine-tapalapa.png',             'sac de 50 kg', 15200.00, 300, 50),
('FAR-PAT',  'Farine Pâtissière Type 45',   'Farine Farima T45 extra-fine pour pâtisserie, viennoiserie et pains industriels.', 'assets/img/produits/farine-patissiere.png',   'sac de 50 kg', 26000.00, 120, 30);
