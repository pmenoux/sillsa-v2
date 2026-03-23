-- ============================================================
-- SILL SA — sillsa.ch v2
-- Schema MySQL — Migration depuis WordPress
-- 2026-03-05
-- ============================================================
-- Moteur : MariaDB 10.6 (Infomaniak)
-- Charset : utf8mb4 / utf8mb4_unicode_ci
-- Préfixe : sill_
-- ============================================================

-- -----------------------------------------------------------
-- 1. CONFIGURATION GÉNÉRALE
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données initiales
INSERT INTO sill_settings (setting_key, setting_value) VALUES
('site_name',        'SILL SA'),
('site_tagline',     'Société Immobilière Lausannoise pour le Logement SA'),
('site_url',         'https://sillsa.ch'),
('contact_address',  'Rue Haldimand 17, 1003 Lausanne'),
('contact_email',    'info@sillsa.ch'),
('capital_social',   '52500000'),
('date_creation',    '2009'),
('meta_description', 'La SILL SA promeut des logements à loyers modérés à Lausanne, dans le respect du développement durable.');

-- -----------------------------------------------------------
-- 2. PAGES STATIQUES
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_pages (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug          VARCHAR(120) NOT NULL UNIQUE,
    title         VARCHAR(255) NOT NULL,
    content       LONGTEXT,
    meta_title    VARCHAR(255),
    meta_desc     VARCHAR(320),
    sort_order    SMALLINT UNSIGNED DEFAULT 0,
    is_active     TINYINT(1) DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 3. PORTEFEUILLE IMMOBILIER
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_immeubles (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug          VARCHAR(120) NOT NULL UNIQUE,
    nom           VARCHAR(255) NOT NULL,
    chapeau       VARCHAR(500),
    description   TEXT,
    details       LONGTEXT,
    adresse       VARCHAR(255),
    latitude      DECIMAL(9,6),
    longitude     DECIMAL(9,6),
    image_id      INT UNSIGNED,
    categorie     ENUM('subventionne','controle','libre','mixte','ppe','etudiant') DEFAULT 'mixte',
    nb_logements  SMALLINT UNSIGNED,
    annee_livraison YEAR,
    label_energie VARCHAR(50),
    sort_order    SMALLINT UNSIGNED DEFAULT 0,
    is_active     TINYINT(1) DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_categorie (categorie),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 4. TIMELINE — Chronologie SILL
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_timeline (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug            VARCHAR(180) NOT NULL UNIQUE,
    title           VARCHAR(255) NOT NULL,
    event_date      DATE NOT NULL,
    event_year      YEAR GENERATED ALWAYS AS (YEAR(event_date)) STORED,
    category        ENUM(
                        'gouvernance',
                        'projets_emblematiques',
                        'strategie_financiere',
                        'concours_architecturaux',
                        'minergie_innovation',
                        'densification_durable',
                        'collaborations_publiques',
                        'logement_etudiant',
                        'metamorphose',
                        'developpement_durable',
                        'livraisons_emblematiques',
                        'gouvernance_evolutive',
                        'innovation_sociale',
                        'politique_fonciere',
                        'communication_transparence'
                    ) NOT NULL,
    description     TEXT,
    image_id        INT UNSIGNED,
    link_url        VARCHAR(500),
    sort_order      SMALLINT UNSIGNED DEFAULT 0,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (event_date),
    INDEX idx_year (event_year),
    INDEX idx_category (category),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 5. ACTUALITÉS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_actualites (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug          VARCHAR(120) NOT NULL UNIQUE,
    title         VARCHAR(255) NOT NULL,
    chapeau       VARCHAR(500),
    description   TEXT,
    details       LONGTEXT,
    image_id      INT UNSIGNED,
    adresse       VARCHAR(255),
    published_at  DATE,
    is_active     TINYINT(1) DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_published (published_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 6. MÉDIAS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_medias (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename      VARCHAR(255) NOT NULL,
    filepath      VARCHAR(500) NOT NULL,
    alt_text      VARCHAR(255),
    mime_type     VARCHAR(50) DEFAULT 'image/jpeg',
    width         SMALLINT UNSIGNED,
    height        SMALLINT UNSIGNED,
    filesize      INT UNSIGNED,
    credit        VARCHAR(255) DEFAULT 'SILL SA / Pierre Menoux',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 7. PUBLICATIONS (Rapports annuels, PDF)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_publications (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug          VARCHAR(120) NOT NULL UNIQUE,
    title         VARCHAR(255) NOT NULL,
    annee         YEAR NOT NULL,
    type          ENUM('rapport_annuel','communique','esg','autre') DEFAULT 'rapport_annuel',
    pdf_path      VARCHAR(500),
    cover_image_id INT UNSIGNED,
    description   TEXT,
    is_active     TINYINT(1) DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_annee (annee DESC),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 8. KPI PUBLICS (affichage page d'accueil / dashboard)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_kpi (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kpi_key       VARCHAR(80) NOT NULL UNIQUE,
    label         VARCHAR(120) NOT NULL,
    value_num     DECIMAL(15,2),
    value_text    VARCHAR(120),
    unit          VARCHAR(30),
    icon          VARCHAR(50),
    category      ENUM('patrimoine','finance','social','environnement','gouvernance','marche','energie') DEFAULT 'patrimoine',
    sort_order    SMALLINT UNSIGNED DEFAULT 0,
    is_public     TINYINT(1) DEFAULT 1,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KPI initiaux (valeurs publiques connues)
INSERT INTO sill_kpi (kpi_key, label, value_num, unit, icon, category, sort_order) VALUES
('capital_social',     'Capital social',           52.5,   'M CHF', 'banknotes',    'finance',        1),
('nb_logements',       'Logements gérés',          735,    NULL,    'building',     'patrimoine',     2),
('nb_immeubles',       'Immeubles',                10,     NULL,    'buildings',    'patrimoine',     3),
('valeur_dcf',         'Valeur patrimoniale DCF',  327,    'M CHF', 'chart-line',   'finance',        4),
('annee_creation',     'Année de création',         NULL,  NULL,    'calendar',     'gouvernance',    5);

UPDATE sill_kpi SET value_text = '2009' WHERE kpi_key = 'annee_creation';

-- -----------------------------------------------------------
-- 9. MENU NAVIGATION
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_menu (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id     INT UNSIGNED DEFAULT 0,
    label         VARCHAR(120) NOT NULL,
    target_type   ENUM('page','url','section') DEFAULT 'page',
    target_value  VARCHAR(255),
    sort_order    SMALLINT UNSIGNED DEFAULT 0,
    is_active     TINYINT(1) DEFAULT 1,
    INDEX idx_parent (parent_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu principal (structure actuelle WordPress)
INSERT INTO sill_menu (id, parent_id, label, target_type, target_value, sort_order) VALUES
(1,  0, 'Accueil',          'page', 'accueil',          1),
(2,  0, 'A propos',         'page', 'a-propos',         2),
(3,  2, 'La Société',       'page', 'a-propos',         1),
(4,  2, 'Le CA',            'page', 'a-propos-2',       2),
(5,  2, 'L''organisation',  'page', 'lorganisation',    3),
(6,  2, 'Aspects sociétaux','page', 'aspects-societaux',4),
(7,  2, 'Environnement',    'page', 'durabilite',       5),
(8,  0, 'Portefeuille',     'section','portefeuille',    3),
(9,  0, 'Publications',     'page', 'publications',     4),
(10, 0, 'Location',         'page', 'location',         5);

-- -----------------------------------------------------------
-- 10. MEMBRES CA (Conseil d'administration)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_membres_ca (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(120) NOT NULL,
    prenom        VARCHAR(80) NOT NULL,
    fonction      VARCHAR(255) NOT NULL,
    bio           TEXT,
    photo_id      INT UNSIGNED,
    sort_order    SMALLINT UNSIGNED DEFAULT 0,
    is_active     TINYINT(1) DEFAULT 1,
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 11. UTILISATEURS ADMIN
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sill_users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name  VARCHAR(120),
    email         VARCHAR(180),
    role          ENUM('admin','editor') DEFAULT 'editor',
    last_login    TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

