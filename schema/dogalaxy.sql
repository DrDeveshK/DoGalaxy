-- Do Galaxy — product schema (MySQL 8 / utf8mb4)
-- Shared identity + one module per vertical. Prefix dg_.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS dg_users (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name          VARCHAR(120) NOT NULL,
  phone         VARCHAR(20)  NULL,
  role          VARCHAR(32)  NOT NULL DEFAULT 'member',
  status        ENUM('pending','active','suspended') NOT NULL DEFAULT 'active',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY ix_users_role (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_audit (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NULL,
  entity     VARCHAR(40) NOT NULL,
  entity_id  BIGINT UNSIGNED NULL,
  action     VARCHAR(40) NOT NULL,
  meta       TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_audit_entity (entity, entity_id),
  KEY ix_audit_user (user_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES dg_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_enquiries (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  product    VARCHAR(32) NOT NULL,
  target_id  BIGINT UNSIGNED NULL,
  user_id    BIGINT UNSIGNED NULL,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  phone      VARCHAR(20)  NULL,
  intent     VARCHAR(40)  NOT NULL DEFAULT 'general',
  message    TEXT NOT NULL,
  status     ENUM('new','open','closed') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_enq_product (product, target_id, status),
  CONSTRAINT fk_enq_user FOREIGN KEY (user_id) REFERENCES dg_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 01 Do Udyog
CREATE TABLE IF NOT EXISTS dg_businesses (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id       BIGINT UNSIGNED NOT NULL,
  legal_name     VARCHAR(180) NOT NULL,
  industry       VARCHAR(60)  NOT NULL,
  city           VARCHAR(80)  NOT NULL,
  state          VARCHAR(80)  NULL,
  gstin          VARCHAR(15)  NULL,
  udyam_no       VARCHAR(30)  NULL,
  pan            VARCHAR(10)  NULL,
  employees      VARCHAR(20)  NULL,
  year_started   SMALLINT UNSIGNED NULL,
  website        VARCHAR(190) NULL,
  about          TEXT NULL,
  verify_status  ENUM('draft','pending','verified','rejected') NOT NULL DEFAULT 'pending',
  completeness   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_biz_owner (owner_id),
  KEY ix_biz_dir (verify_status, industry, city),
  UNIQUE KEY uq_biz_gstin (gstin),
  CONSTRAINT fk_biz_owner FOREIGN KEY (owner_id) REFERENCES dg_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_compliance (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  code        VARCHAR(32) NOT NULL,
  done        TINYINT(1) NOT NULL DEFAULT 0,
  note        VARCHAR(190) NULL,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_comp (business_id, code),
  CONSTRAINT fk_comp_biz FOREIGN KEY (business_id) REFERENCES dg_businesses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 02 Do Vishram
CREATE TABLE IF NOT EXISTS dg_stays (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  host_id       BIGINT UNSIGNED NOT NULL,
  title         VARCHAR(180) NOT NULL,
  stay_type     VARCHAR(40) NOT NULL,
  city          VARCHAR(80) NOT NULL,
  price_night   DECIMAL(10,2) NULL,
  max_guests    SMALLINT UNSIGNED NULL,
  about         TEXT NULL,
  verify_status ENUM('draft','pending','verified','rejected') NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_stay_dir (verify_status, city, stay_type),
  CONSTRAINT fk_stay_host FOREIGN KEY (host_id) REFERENCES dg_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_stay_requests (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  stay_id    BIGINT UNSIGNED NOT NULL,
  guest_id   BIGINT UNSIGNED NULL,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  checkin    DATE NOT NULL,
  checkout   DATE NOT NULL,
  guests     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  message    TEXT NULL,
  status     ENUM('new','accepted','declined') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_sreq (stay_id, status),
  CONSTRAINT fk_sreq_stay FOREIGN KEY (stay_id) REFERENCES dg_stays (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 03 Do Rojgar
CREATE TABLE IF NOT EXISTS dg_jobs (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employer_id   BIGINT UNSIGNED NOT NULL,
  title         VARCHAR(180) NOT NULL,
  job_type      VARCHAR(40) NOT NULL,
  city          VARCHAR(80) NOT NULL,
  pay           VARCHAR(80) NULL,
  description   TEXT NULL,
  status        ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_job_dir (status, city, job_type),
  CONSTRAINT fk_job_emp FOREIGN KEY (employer_id) REFERENCES dg_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_applications (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id     BIGINT UNSIGNED NOT NULL,
  seeker_id  BIGINT UNSIGNED NULL,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  phone      VARCHAR(20) NULL,
  experience VARCHAR(80) NULL,
  message    TEXT NOT NULL,
  status     ENUM('new','shortlisted','rejected','hired') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_apply (job_id, email),
  CONSTRAINT fk_app_job FOREIGN KEY (job_id) REFERENCES dg_jobs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 04 Do Swagat
CREATE TABLE IF NOT EXISTS dg_venues (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  partner_id    BIGINT UNSIGNED NOT NULL,
  title         VARCHAR(180) NOT NULL,
  kind          VARCHAR(40) NOT NULL,
  city          VARCHAR(80) NOT NULL,
  capacity      INT UNSIGNED NULL,
  about         TEXT NULL,
  verify_status ENUM('draft','pending','verified','rejected') NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_venue_dir (verify_status, city, kind),
  CONSTRAINT fk_venue_partner FOREIGN KEY (partner_id) REFERENCES dg_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_event_requests (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id   BIGINT UNSIGNED NOT NULL,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  event_date DATE NOT NULL,
  guests     INT UNSIGNED NULL,
  event_type VARCHAR(80) NULL,
  message    TEXT NOT NULL,
  status     ENUM('new','quoted','confirmed','declined') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_ereq_venue FOREIGN KEY (venue_id) REFERENCES dg_venues (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 05 Do Rishta (21+)
CREATE TABLE IF NOT EXISTS dg_profiles (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       BIGINT UNSIGNED NOT NULL,
  display_name  VARCHAR(120) NOT NULL,
  birth_date    DATE NOT NULL,
  city          VARCHAR(80) NOT NULL,
  community     VARCHAR(80) NULL,
  about         TEXT NULL,
  verify_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_prof_user (user_id),
  KEY ix_prof_dir (verify_status, city),
  CONSTRAINT fk_prof_user FOREIGN KEY (user_id) REFERENCES dg_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_interests (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  from_user_id BIGINT UNSIGNED NOT NULL,
  to_profile_id BIGINT UNSIGNED NOT NULL,
  note         VARCHAR(500) NOT NULL,
  status       ENUM('new','seen','declined') NOT NULL DEFAULT 'new',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_interest (from_user_id, to_profile_id),
  CONSTRAINT fk_int_from FOREIGN KEY (from_user_id) REFERENCES dg_users (id) ON DELETE CASCADE,
  CONSTRAINT fk_int_to FOREIGN KEY (to_profile_id) REFERENCES dg_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 06 Do Bajar
CREATE TABLE IF NOT EXISTS dg_listings (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  seller_id     BIGINT UNSIGNED NOT NULL,
  title         VARCHAR(180) NOT NULL,
  category      VARCHAR(40) NOT NULL,
  city          VARCHAR(80) NOT NULL,
  price         DECIMAL(10,2) NULL,
  about         TEXT NULL,
  status        ENUM('pending','live','sold','hidden') NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_list_dir (status, category, city),
  CONSTRAINT fk_list_seller FOREIGN KEY (seller_id) REFERENCES dg_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_order_requests (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id BIGINT UNSIGNED NOT NULL,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  phone      VARCHAR(20) NULL,
  qty        INT UNSIGNED NOT NULL DEFAULT 1,
  message    TEXT NOT NULL,
  status     ENUM('new','accepted','declined') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_oreq_list FOREIGN KEY (listing_id) REFERENCES dg_listings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 07 Do Aaram
CREATE TABLE IF NOT EXISTS dg_services (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider_id   BIGINT UNSIGNED NOT NULL,
  title         VARCHAR(180) NOT NULL,
  category      VARCHAR(40) NOT NULL,
  city          VARCHAR(80) NOT NULL,
  rate          VARCHAR(80) NULL,
  about         TEXT NULL,
  status        ENUM('pending','live','hidden') NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_svc_dir (status, category, city),
  CONSTRAINT fk_svc_prov FOREIGN KEY (provider_id) REFERENCES dg_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_service_requests (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  service_id BIGINT UNSIGNED NOT NULL,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  phone      VARCHAR(20) NULL,
  when_date  DATE NULL,
  message    TEXT NOT NULL,
  status     ENUM('new','accepted','declined') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_svcreq_svc FOREIGN KEY (service_id) REFERENCES dg_services (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 08 Do Nirman
CREATE TABLE IF NOT EXISTS dg_contractors (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id      BIGINT UNSIGNED NOT NULL,
  legal_name    VARCHAR(180) NOT NULL,
  trade         VARCHAR(60) NOT NULL,
  city          VARCHAR(80) NOT NULL,
  about         TEXT NULL,
  verify_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_con_dir (verify_status, trade, city),
  CONSTRAINT fk_con_owner FOREIGN KEY (owner_id) REFERENCES dg_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_project_leads (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  contractor_id BIGINT UNSIGNED NOT NULL,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  phone         VARCHAR(20) NULL,
  site_city     VARCHAR(80) NULL,
  message       TEXT NOT NULL,
  status        ENUM('new','quoted','closed') NOT NULL DEFAULT 'new',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_plead_con FOREIGN KEY (contractor_id) REFERENCES dg_contractors (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 09 Do Vyapaar
CREATE TABLE IF NOT EXISTS dg_suppliers (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id      BIGINT UNSIGNED NOT NULL,
  legal_name    VARCHAR(180) NOT NULL,
  category      VARCHAR(60) NOT NULL,
  city          VARCHAR(80) NOT NULL,
  about         TEXT NULL,
  verify_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_sup_dir (verify_status, category, city),
  CONSTRAINT fk_sup_owner FOREIGN KEY (owner_id) REFERENCES dg_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dg_rfqs (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  supplier_id BIGINT UNSIGNED NOT NULL,
  name        VARCHAR(120) NOT NULL,
  email       VARCHAR(190) NOT NULL,
  phone       VARCHAR(20) NULL,
  item        VARCHAR(180) NOT NULL,
  qty         VARCHAR(40) NULL,
  message     TEXT NOT NULL,
  status      ENUM('new','quoted','closed') NOT NULL DEFAULT 'new',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_rfq_sup FOREIGN KEY (supplier_id) REFERENCES dg_suppliers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MyDoApp journeys
CREATE TABLE IF NOT EXISTS dg_journeys (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NOT NULL,
  path       VARCHAR(32) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_journey_user (user_id, created_at),
  CONSTRAINT fk_journey_user FOREIGN KEY (user_id) REFERENCES dg_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
