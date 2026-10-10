-- Armory Falter Webshop – MySQL 8 Schema (Kerntabellen)
-- Stand 10.10.2026. In Laravel als Migrations umzusetzen; dieses DDL ist die Referenz.
-- Konventionen: utf8mb4_0900_ai_ci, BIGINT UNSIGNED IDs, Geldbeträge in Cent (INT), Zeitstempel UTC.
-- Sensible Felder (Dokumentnummern, Seriennummern) werden von Laravel verschlüsselt (encrypted cast)
-- und zusätzlich als HMAC-Blind-Index gespeichert, damit exakte Suche möglich bleibt.

SET NAMES utf8mb4;
SET default_storage_engine = InnoDB;

-- ---------------------------------------------------------------
-- 1. Benutzer, Rollen, Sicherheit
-- ---------------------------------------------------------------
CREATE TABLE users (
  id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_number        VARCHAR(20)  NOT NULL UNIQUE,
  customer_type          ENUM('private','company','authority','dealer') NOT NULL DEFAULT 'private',
  salutation             ENUM('herr','frau','divers','none') NOT NULL DEFAULT 'none',
  first_name             VARCHAR(100) NOT NULL,
  last_name              VARCHAR(100) NOT NULL,
  company_name           VARCHAR(200) NULL,
  vat_id                 VARCHAR(20)  NULL,
  email                  VARCHAR(255) NOT NULL UNIQUE,
  email_verified_at      TIMESTAMP NULL,
  phone                  VARCHAR(40)  NULL,
  birth_date             DATE NULL,                         -- Pflicht vor Kauf altersbeschränkter Ware
  age_verified_at        TIMESTAMP NULL,
  age_verification_method ENUM('document_review','postident','eid','in_store') NULL,
  password               VARCHAR(255) NOT NULL,
  two_factor_secret      TEXT NULL,                         -- encrypted
  two_factor_recovery_codes TEXT NULL,                      -- encrypted
  two_factor_confirmed_at TIMESTAMP NULL,
  is_active              BOOLEAN NOT NULL DEFAULT TRUE,
  locked_until           TIMESTAMP NULL,
  last_login_at          TIMESTAMP NULL,
  remember_token         VARCHAR(100) NULL,
  created_at             TIMESTAMP NULL,
  updated_at             TIMESTAMP NULL,
  deleted_at             TIMESTAMP NULL,                    -- Anonymisierung statt Hard-Delete (Aufbewahrungspflichten)
  INDEX idx_users_name (last_name, first_name)
);

-- Rollen/Rechte: spatie/laravel-permission erzeugt roles, permissions, model_has_roles,
-- model_has_permissions, role_has_permissions (siehe 07-rollen-rechte.md).

CREATE TABLE addresses (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       BIGINT UNSIGNED NOT NULL,
  type          ENUM('billing','shipping') NOT NULL,
  is_default    BOOLEAN NOT NULL DEFAULT FALSE,
  company_name  VARCHAR(200) NULL,
  first_name    VARCHAR(100) NOT NULL,
  last_name     VARCHAR(100) NOT NULL,
  street        VARCHAR(200) NOT NULL,
  house_number  VARCHAR(20)  NOT NULL,
  address_extra VARCHAR(200) NULL,
  postal_code   VARCHAR(10)  NOT NULL,
  city          VARCHAR(100) NOT NULL,
  country_code  CHAR(2)      NOT NULL DEFAULT 'DE',
  created_at    TIMESTAMP NULL,
  updated_at    TIMESTAMP NULL,
  CONSTRAINT fk_addr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_addr_user (user_id, type)
);

CREATE TABLE audit_logs (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id      BIGINT UNSIGNED NULL,                       -- NULL = System/Job
  actor_type    ENUM('user','admin','system','api') NOT NULL,
  action        VARCHAR(100) NOT NULL,                      -- z. B. document.approved, order.released
  subject_type  VARCHAR(100) NULL,
  subject_id    BIGINT UNSIGNED NULL,
  old_values    JSON NULL,
  new_values    JSON NULL,
  ip_address    VARBINARY(16) NULL,
  user_agent    VARCHAR(255) NULL,
  request_id    CHAR(26) NULL,
  prev_hash     CHAR(64) NULL,                              -- Hash-Kette → Manipulationserkennung
  hash          CHAR(64) NOT NULL,
  created_at    TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  INDEX idx_audit_subject (subject_type, subject_id),
  INDEX idx_audit_actor (actor_id, created_at),
  INDEX idx_audit_action (action, created_at)
);  -- App-DB-User hat nur INSERT/SELECT auf diese Tabelle

CREATE TABLE consent_records (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consent_id    CHAR(36) NOT NULL,                          -- pseudonyme ID aus Cookie
  user_id       BIGINT UNSIGNED NULL,
  categories    JSON NOT NULL,                              -- {"necessary":true,"statistics":false,...}
  banner_version VARCHAR(20) NOT NULL,
  ip_hash       CHAR(64) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_consent (consent_id)
);

-- ---------------------------------------------------------------
-- 2. Erwerbsrecht: Rechtsklassen, Dokumente, Berechtigungen
-- ---------------------------------------------------------------
CREATE TABLE legal_classes (
  id                    SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code                  VARCHAR(40) NOT NULL UNIQUE,        -- siehe 10-waffenrecht-compliance.md
  name                  VARCHAR(120) NOT NULL,
  min_age               TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0, 18, 21 (25 = Sonderfall, s. Doku)
  requires_entitlement  BOOLEAN NOT NULL DEFAULT FALSE,
  accepted_entitlements JSON NULL,                          -- z. B. ["wbk_voreintrag","jagdschein_langwaffe"]
  shipping_mode         ENUM('standard','age_check','weapon_personal','pickup_only','no_sale_online') NOT NULL,
  allowed_payment_codes JSON NOT NULL,                      -- z. B. ["prepayment","cash_pickup","psp_weapon"]
  requires_serial       BOOLEAN NOT NULL DEFAULT FALSE,     -- Waffenhandelsbuch / NWR-Meldung
  notice_text           TEXT NULL,                          -- Hinweis auf Produktseite
  created_at            TIMESTAMP NULL,
  updated_at            TIMESTAMP NULL
);

CREATE TABLE customer_documents (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id           BIGINT UNSIGNED NOT NULL,
  type              ENUM('wbk_green','wbk_yellow','wbk_red','hunting_license','id_card','passport',
                         'ammo_permit','dealer_license','authority_certificate','other') NOT NULL,
  document_number   TEXT NULL,                              -- encrypted
  document_number_bidx CHAR(64) NULL,                       -- HMAC blind index
  issuing_authority VARCHAR(200) NULL,
  issued_at         DATE NULL,
  valid_until       DATE NULL,                              -- Jagdschein: Ende Jagdjahr (31.03.)
  storage_path      VARCHAR(255) NOT NULL,                  -- privater, verschlüsselter Bucket
  mime_type         VARCHAR(60) NOT NULL,                   -- application/pdf, image/jpeg, image/png
  file_size         INT UNSIGNED NOT NULL,
  sha256            CHAR(64) NOT NULL,
  status            ENUM('uploaded','precheck_passed','precheck_flagged','approved','rejected','expired','revoked')
                    NOT NULL DEFAULT 'uploaded',
  precheck_result   JSON NULL,                              -- OCR-Felder, Namensabgleich, Ablauf, Bildqualität
  reviewed_by       BIGINT UNSIGNED NULL,
  reviewed_at       TIMESTAMP NULL,
  rejection_reason  VARCHAR(500) NULL,
  delete_after      DATE NULL,                              -- Löschkonzept
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  CONSTRAINT fk_doc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_doc_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id),
  INDEX idx_doc_status (status, created_at),
  INDEX idx_doc_user (user_id, type, status)
);

-- Aus geprüften Dokumenten abgeleitete, konkrete Erwerbsberechtigungen.
CREATE TABLE customer_entitlements (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          BIGINT UNSIGNED NOT NULL,
  document_id      BIGINT UNSIGNED NOT NULL,
  type             ENUM('wbk_voreintrag','wbk_yellow_sport','wbk_red_collector','hunting_license_longgun',
                        'hunting_license_shortgun_ammo','ammo_permit','dealer','authority') NOT NULL,
  weapon_kind      ENUM('kurzwaffe','langwaffe','any') NULL,
  caliber_id       BIGINT UNSIGNED NULL,                    -- Munitionserwerb / Voreintrag kaliberbezogen
  quantity_total   SMALLINT UNSIGNED NULL,                  -- z. B. Voreintrag für 1 Waffe
  quantity_used    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  valid_from       DATE NULL,
  valid_until      DATE NULL,                               -- Voreintrag: 1 Jahr gültig
  status           ENUM('active','consumed','expired','revoked') NOT NULL DEFAULT 'active',
  created_by       BIGINT UNSIGNED NOT NULL,                -- immer ein Mensch
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  CONSTRAINT fk_ent_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ent_doc  FOREIGN KEY (document_id) REFERENCES customer_documents(id),
  INDEX idx_ent_lookup (user_id, status, type, valid_until)
);

-- ---------------------------------------------------------------
-- 3. Katalog
-- ---------------------------------------------------------------
CREATE TABLE manufacturers (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(120) NOT NULL,
  slug           VARCHAR(140) NOT NULL UNIQUE,
  logo_path      VARCHAR(255) NULL,
  hero_media_path VARCHAR(255) NULL,
  country_code   CHAR(2) NULL,
  founded_year   SMALLINT NULL,
  intro          TEXT NULL,
  content        MEDIUMTEXT NULL,                           -- SEO-Langtext (Blocks/JSON oder HTML)
  is_featured    BOOLEAN NOT NULL DEFAULT FALSE,
  sort_order     INT NOT NULL DEFAULT 0,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL
);

CREATE TABLE categories (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id      BIGINT UNSIGNED NULL,
  name           VARCHAR(120) NOT NULL,
  slug           VARCHAR(140) NOT NULL,
  path           VARCHAR(500) NOT NULL UNIQUE,              -- materialisierter Pfad: kurzwaffen/pistolen
  description    TEXT NULL,
  content_bottom MEDIUMTEXT NULL,                           -- SEO-Text unter Listing
  image_path     VARCHAR(255) NULL,
  default_legal_class_id SMALLINT UNSIGNED NULL,
  is_active      BOOLEAN NOT NULL DEFAULT TRUE,
  sort_order     INT NOT NULL DEFAULT 0,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id) REFERENCES categories(id),
  CONSTRAINT fk_cat_legal FOREIGN KEY (default_legal_class_id) REFERENCES legal_classes(id),
  UNIQUE KEY uq_cat_slug (parent_id, slug)
);

CREATE TABLE calibers (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80) NOT NULL,                         -- .308 Win.
  slug        VARCHAR(80) NOT NULL UNIQUE,                  -- 308-win
  aliases     JSON NULL,                                    -- ["7,62x51","7.62x51 NATO"]
  kind        ENUM('rifle','handgun','shotgun','rimfire','airgun','other') NOT NULL,
  is_large_bore BOOLEAN NOT NULL DEFAULT FALSE,             -- relevant für 21+/25-Regeln Sportschützen
  content     MEDIUMTEXT NULL
);

CREATE TABLE tax_rates (
  id     SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name   VARCHAR(40) NOT NULL,
  rate   DECIMAL(5,2) NOT NULL                              -- 19.00 / 7.00
);

CREATE TABLE products (
  id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku                VARCHAR(40) NOT NULL UNIQUE,           -- Artikelnummer
  ean                VARCHAR(14) NULL,
  manufacturer_part_number VARCHAR(60) NULL,
  name               VARCHAR(200) NOT NULL,
  slug               VARCHAR(220) NOT NULL UNIQUE,
  manufacturer_id    BIGINT UNSIGNED NULL,
  caliber_id         BIGINT UNSIGNED NULL,
  legal_class_id     SMALLINT UNSIGNED NOT NULL,
  tax_rate_id        SMALLINT UNSIGNED NOT NULL,
  product_type       ENUM('weapon','ammo','optic','knife','accessory','reloading','apparel','service','voucher') NOT NULL,
  weapon_kind        ENUM('kurzwaffe','langwaffe') NULL,
  condition_code     ENUM('new','used','demo','like_new') NOT NULL DEFAULT 'new',
  condition_notes    TEXT NULL,                             -- bei Gebrauchtwaffen: Zustandsbericht
  is_unique_item     BOOLEAN NOT NULL DEFAULT FALSE,        -- Einzelstück (Gebrauchtware)
  usage_hunting      BOOLEAN NOT NULL DEFAULT FALSE,
  usage_sport        BOOLEAN NOT NULL DEFAULT FALSE,
  usage_defense_authority BOOLEAN NOT NULL DEFAULT FALSE,   -- nur für Behördenkunden sichtbar
  price_gross        INT UNSIGNED NOT NULL,                 -- Cent
  special_price_gross INT UNSIGNED NULL,
  special_from       DATETIME NULL,
  special_to         DATETIME NULL,
  lowest_price_30d   INT UNSIGNED NULL,                     -- PAngV § 11: niedrigster Preis 30 Tage vor Reduzierung
  uvp_gross          INT UNSIGNED NULL,
  stock_quantity     INT NOT NULL DEFAULT 0,                -- Summe, bei Varianten aggregiert
  stock_reserved     INT NOT NULL DEFAULT 0,
  availability       ENUM('in_stock','low_stock','on_order','preorder','out_of_stock','in_store_only') NOT NULL,
  delivery_time_days VARCHAR(20) NULL,
  weight_grams       INT UNSIGNED NULL,
  short_description  TEXT NULL,
  description        MEDIUMTEXT NULL,
  video_url          VARCHAR(255) NULL,                     -- selbst gehostet (HLS) oder YouTube-nocookie nach Consent
  status             ENUM('draft','active','hidden','archived') NOT NULL DEFAULT 'draft',
  published_at       TIMESTAMP NULL,
  created_at         TIMESTAMP NULL,
  updated_at         TIMESTAMP NULL,
  CONSTRAINT fk_prod_manu FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id),
  CONSTRAINT fk_prod_cal  FOREIGN KEY (caliber_id) REFERENCES calibers(id),
  CONSTRAINT fk_prod_legal FOREIGN KEY (legal_class_id) REFERENCES legal_classes(id),
  CONSTRAINT fk_prod_tax  FOREIGN KEY (tax_rate_id) REFERENCES tax_rates(id),
  INDEX idx_prod_listing (status, product_type, availability),
  INDEX idx_prod_manu (manufacturer_id, status),
  INDEX idx_prod_cal (caliber_id, status),
  FULLTEXT INDEX ft_prod (name, short_description)          -- Fallback; Primärsuche Meilisearch
);

CREATE TABLE product_category (
  product_id  BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  is_primary  BOOLEAN NOT NULL DEFAULT FALSE,               -- für Breadcrumb
  PRIMARY KEY (product_id, category_id),
  CONSTRAINT fk_pc_p FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pc_c FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  INDEX idx_pc_cat (category_id)
);

CREATE TABLE product_variants (                             -- z. B. Bekleidung Größe/Farbe, Munition Packungsgröße
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id     BIGINT UNSIGNED NOT NULL,
  sku            VARCHAR(50) NOT NULL UNIQUE,
  options        JSON NOT NULL,                             -- {"groesse":"L","farbe":"oliv"}
  price_gross    INT UNSIGNED NULL,                         -- NULL = Produktpreis
  stock_quantity INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_var_p FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE product_media (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  BIGINT UNSIGNED NOT NULL,
  type        ENUM('image','video','360') NOT NULL,
  path        VARCHAR(255) NOT NULL,
  alt_text    VARCHAR(255) NOT NULL,
  width       SMALLINT UNSIGNED NULL,
  height      SMALLINT UNSIGNED NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_media_p FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_media_p (product_id, sort_order)
);

CREATE TABLE product_documents (                            -- Downloadbereich, Anleitungen, Datenblätter
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  BIGINT UNSIGNED NOT NULL,
  type        ENUM('manual','datasheet','certificate','exploded_view','other') NOT NULL,
  title       VARCHAR(200) NOT NULL,
  path        VARCHAR(255) NOT NULL,
  language    CHAR(2) NOT NULL DEFAULT 'de',
  visibility  ENUM('public','customers','buyers') NOT NULL DEFAULT 'public',
  CONSTRAINT fk_pdoc_p FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE attribute_definitions (                        -- Technische Daten
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(60) NOT NULL UNIQUE,                  -- lauflaenge_mm, magazinkapazitaet, vergroesserung
  name        VARCHAR(120) NOT NULL,
  unit        VARCHAR(20) NULL,
  data_type   ENUM('text','number','bool','select') NOT NULL,
  is_filterable BOOLEAN NOT NULL DEFAULT FALSE,
  group_name  VARCHAR(80) NULL                              -- „Lauf“, „Abzug“, „Optik“
);

CREATE TABLE product_attribute_values (
  product_id    BIGINT UNSIGNED NOT NULL,
  attribute_id  BIGINT UNSIGNED NOT NULL,
  value_text    VARCHAR(255) NULL,
  value_number  DECIMAL(12,3) NULL,
  PRIMARY KEY (product_id, attribute_id),
  CONSTRAINT fk_pav_p FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pav_a FOREIGN KEY (attribute_id) REFERENCES attribute_definitions(id),
  INDEX idx_pav_num (attribute_id, value_number)
);

-- Seriennummern / Waffenhandelsbuch (§ 23 WaffG)
CREATE TABLE weapon_items (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id        BIGINT UNSIGNED NOT NULL,
  serial_number     TEXT NOT NULL,                          -- encrypted
  serial_bidx       CHAR(64) NOT NULL,
  nwr_id            VARCHAR(40) NULL,                       -- NWR-Waffen-ID
  status            ENUM('in_stock','reserved','sold','consignment','returned','transferred') NOT NULL,
  acquired_from     VARCHAR(255) NULL,
  acquired_at       DATE NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  CONSTRAINT fk_wi_p FOREIGN KEY (product_id) REFERENCES products(id),
  UNIQUE KEY uq_serial (serial_bidx)
);

CREATE TABLE weapon_book_entries (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  weapon_item_id   BIGINT UNSIGNED NOT NULL,
  direction        ENUM('in','out') NOT NULL,
  counterparty     JSON NOT NULL,                           -- Name, Anschrift, Erlaubnis-Nr. (verschlüsselt im JSON)
  permit_reference VARCHAR(120) NULL,
  order_id         BIGINT UNSIGNED NULL,
  nwr_reported_at  TIMESTAMP NULL,
  nwr_reference    VARCHAR(80) NULL,
  booked_by        BIGINT UNSIGNED NOT NULL,
  booked_at        TIMESTAMP NOT NULL,
  CONSTRAINT fk_wbe_item FOREIGN KEY (weapon_item_id) REFERENCES weapon_items(id)
);  -- unveränderbar: Korrekturen nur als Gegenbuchung

-- ---------------------------------------------------------------
-- 4. Merkliste, Warenkorb
-- ---------------------------------------------------------------
CREATE TABLE wishlists (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL DEFAULT 'Favoriten',
  is_default  BOOLEAN NOT NULL DEFAULT FALSE,
  share_token CHAR(32) NULL UNIQUE,
  created_at  TIMESTAMP NULL,
  CONSTRAINT fk_wl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE wishlist_items (
  wishlist_id BIGINT UNSIGNED NOT NULL,
  product_id  BIGINT UNSIGNED NOT NULL,
  notify_price_drop BOOLEAN NOT NULL DEFAULT FALSE,
  notify_back_in_stock BOOLEAN NOT NULL DEFAULT FALSE,
  added_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (wishlist_id, product_id),
  CONSTRAINT fk_wli_wl FOREIGN KEY (wishlist_id) REFERENCES wishlists(id) ON DELETE CASCADE,
  CONSTRAINT fk_wli_p FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE carts (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token       CHAR(36) NOT NULL UNIQUE,                     -- Gast-Warenkorb
  user_id     BIGINT UNSIGNED NULL,
  expires_at  TIMESTAMP NOT NULL,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE cart_items (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id     BIGINT UNSIGNED NOT NULL,
  product_id  BIGINT UNSIGNED NOT NULL,
  variant_id  BIGINT UNSIGNED NULL,
  quantity    INT UNSIGNED NOT NULL,
  CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_ci_p FOREIGN KEY (product_id) REFERENCES products(id),
  UNIQUE KEY uq_cart_line (cart_id, product_id, variant_id)
);

-- ---------------------------------------------------------------
-- 5. Bestellungen, Zahlung, Versand, Rechnungen
-- ---------------------------------------------------------------
CREATE TABLE orders (
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number        VARCHAR(20) NOT NULL UNIQUE,          -- AF-2026-000123
  user_id             BIGINT UNSIGNED NOT NULL,             -- kein Gastkauf für altersbeschränkte Ware
  status              ENUM('pending_payment','pending_compliance','compliance_hold','paid','processing',
                           'ready_for_pickup','shipped','delivered','completed','cancelled','refunded') NOT NULL,
  compliance_status   ENUM('not_required','pending','approved','rejected') NOT NULL,
  billing_address     JSON NOT NULL,                        -- Snapshot
  shipping_address    JSON NULL,
  shipping_method_code VARCHAR(40) NOT NULL,
  payment_method_code VARCHAR(40) NOT NULL,
  subtotal_gross      INT UNSIGNED NOT NULL,
  shipping_gross      INT UNSIGNED NOT NULL,
  discount_gross      INT UNSIGNED NOT NULL DEFAULT 0,
  total_gross         INT UNSIGNED NOT NULL,
  total_tax           INT UNSIGNED NOT NULL,
  currency            CHAR(3) NOT NULL DEFAULT 'EUR',
  customer_note       TEXT NULL,
  terms_accepted_at   TIMESTAMP NOT NULL,
  legal_declaration_accepted_at TIMESTAMP NULL,             -- „Ich bin erwerbsberechtigt …“
  ip_address          VARBINARY(16) NULL,
  idempotency_key     CHAR(36) NOT NULL UNIQUE,
  placed_at           TIMESTAMP NOT NULL,
  created_at          TIMESTAMP NULL,
  updated_at          TIMESTAMP NULL,
  CONSTRAINT fk_ord_user FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_ord_user (user_id, placed_at),
  INDEX idx_ord_status (status, placed_at)
);

CREATE TABLE order_items (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id         BIGINT UNSIGNED NOT NULL,
  product_id       BIGINT UNSIGNED NOT NULL,
  variant_id       BIGINT UNSIGNED NULL,
  weapon_item_id   BIGINT UNSIGNED NULL,                    -- zugeordnete Seriennummer
  sku              VARCHAR(50) NOT NULL,
  name             VARCHAR(200) NOT NULL,
  legal_class_code VARCHAR(40) NOT NULL,                    -- Snapshot
  quantity         INT UNSIGNED NOT NULL,
  unit_price_gross INT UNSIGNED NOT NULL,
  tax_rate         DECIMAL(5,2) NOT NULL,
  line_total_gross INT UNSIGNED NOT NULL,
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_p FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_oi_wi FOREIGN KEY (weapon_item_id) REFERENCES weapon_items(id)
);

CREATE TABLE order_compliance_checks (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id         BIGINT UNSIGNED NOT NULL,
  order_item_id    BIGINT UNSIGNED NULL,
  check_type       ENUM('age','entitlement','quantity','original_document','address_match','manual') NOT NULL,
  entitlement_id   BIGINT UNSIGNED NULL,
  result           ENUM('passed','failed','pending') NOT NULL,
  details          JSON NULL,
  checked_by       BIGINT UNSIGNED NULL,                    -- NULL = automatische Vorprüfung
  checked_at       TIMESTAMP NOT NULL,
  CONSTRAINT fk_occ_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_occ_ent FOREIGN KEY (entitlement_id) REFERENCES customer_entitlements(id)
);

CREATE TABLE order_status_history (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    BIGINT UNSIGNED NOT NULL,
  from_status VARCHAR(40) NULL,
  to_status   VARCHAR(40) NOT NULL,
  comment     TEXT NULL,
  notify_customer BOOLEAN NOT NULL DEFAULT FALSE,
  changed_by  BIGINT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_osh_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE payments (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id          BIGINT UNSIGNED NOT NULL,
  provider          VARCHAR(40) NOT NULL,                   -- paypal, klarna, psp_weapon, prepayment, cash
  provider_reference VARCHAR(120) NULL,
  status            ENUM('initiated','authorized','captured','failed','refunded','partially_refunded','cancelled') NOT NULL,
  amount            INT UNSIGNED NOT NULL,
  raw_response      JSON NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  CONSTRAINT fk_pay_order FOREIGN KEY (order_id) REFERENCES orders(id),
  UNIQUE KEY uq_provider_ref (provider, provider_reference)
);

CREATE TABLE shipping_methods (
  id                SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code              VARCHAR(40) NOT NULL UNIQUE,            -- dhl_standard, dhl_age_check, weapon_personal_overnight, pickup
  name              VARCHAR(120) NOT NULL,
  carrier           VARCHAR(40) NULL,
  allowed_shipping_modes JSON NOT NULL,                     -- welche legal_classes.shipping_mode bedient werden
  price_gross       INT UNSIGNED NOT NULL,
  free_from_gross   INT UNSIGNED NULL,
  is_active         BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE shipments (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id         BIGINT UNSIGNED NOT NULL,
  shipping_method_id SMALLINT UNSIGNED NOT NULL,
  carrier          VARCHAR(40) NULL,
  tracking_number  VARCHAR(60) NULL,
  label_path       VARCHAR(255) NULL,
  status           ENUM('created','label_printed','handed_over','in_transit','out_for_delivery','delivered',
                        'failed_attempt','returned','picked_up') NOT NULL,
  shipped_at       TIMESTAMP NULL,
  delivered_at     TIMESTAMP NULL,
  last_tracking_sync_at TIMESTAMP NULL,
  CONSTRAINT fk_ship_order FOREIGN KEY (order_id) REFERENCES orders(id),
  CONSTRAINT fk_ship_m FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id),
  INDEX idx_ship_tracking (tracking_number)
);

CREATE TABLE shipment_events (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shipment_id  BIGINT UNSIGNED NOT NULL,
  status       VARCHAR(60) NOT NULL,
  description  VARCHAR(255) NULL,
  location     VARCHAR(120) NULL,
  occurred_at  TIMESTAMP NOT NULL,
  CONSTRAINT fk_se_ship FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
);

CREATE TABLE invoices (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id        BIGINT UNSIGNED NOT NULL,
  invoice_number  VARCHAR(30) NOT NULL UNIQUE,              -- fortlaufend, lückenlos (GoBD)
  type            ENUM('invoice','credit_note','cancellation') NOT NULL,
  pdf_path        VARCHAR(255) NOT NULL,
  xrechnung_path  VARCHAR(255) NULL,                        -- E-Rechnung (B2B/Behörden: XRechnung/ZUGFeRD)
  total_gross     INT NOT NULL,
  issued_at       TIMESTAMP NOT NULL,
  CONSTRAINT fk_inv_order FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- ---------------------------------------------------------------
-- 6. Services: Verkaufsservice (egun), Ankauf, Werkstatt
-- ---------------------------------------------------------------
CREATE TABLE trade_requests (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference       VARCHAR(20) NOT NULL UNIQUE,              -- VS-2026-0012 / AK-2026-0045
  type            ENUM('consignment','buyback') NOT NULL,   -- Verkaufsservice (egun) / Ankauf
  user_id         BIGINT UNSIGNED NULL,
  contact         JSON NOT NULL,                            -- Name, Anschrift, Telefon, E-Mail
  weapon_type     VARCHAR(80) NULL,
  manufacturer_id BIGINT UNSIGNED NULL,
  manufacturer_free VARCHAR(120) NULL,
  model           VARCHAR(160) NOT NULL,
  caliber_id      BIGINT UNSIGNED NULL,
  caliber_free    VARCHAR(80) NULL,
  condition_code  ENUM('new','like_new','very_good','good','used','defective') NOT NULL,
  desired_price   INT UNSIGNED NULL,
  description     TEXT NULL,
  status          ENUM('new','in_review','offer_sent','accepted','declined','item_received','listed',
                       'sold','paid_out','closed') NOT NULL DEFAULT 'new',
  offer_price     INT UNSIGNED NULL,
  egun_listing_id VARCHAR(60) NULL,
  advisory_pdf_path VARCHAR(255) NULL,                      -- PDF für Beratung
  assigned_to     BIGINT UNSIGNED NULL,
  privacy_accepted_at TIMESTAMP NOT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  INDEX idx_tr_status (type, status, created_at)
);

CREATE TABLE trade_request_media (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  trade_request_id BIGINT UNSIGNED NOT NULL,
  path             VARCHAR(255) NOT NULL,
  sort_order       TINYINT UNSIGNED NOT NULL,               -- max. 20 (App-Validierung)
  CONSTRAINT fk_trm FOREIGN KEY (trade_request_id) REFERENCES trade_requests(id) ON DELETE CASCADE
);

CREATE TABLE workshop_services (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug             VARCHAR(80) NOT NULL UNIQUE,
  name             VARCHAR(120) NOT NULL,
  description      MEDIUMTEXT NULL,
  price_from_gross INT UNSIGNED NULL,
  duration_minutes SMALLINT UNSIGNED NOT NULL,              -- Annahmetermin, nicht Bearbeitungsdauer
  requires_weapon_dropoff BOOLEAN NOT NULL DEFAULT TRUE,
  is_bookable      BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE appointments (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type             ENUM('workshop','consultation','pickup','range') NOT NULL,
  workshop_service_id BIGINT UNSIGNED NULL,
  user_id          BIGINT UNSIGNED NULL,
  contact          JSON NOT NULL,
  starts_at        DATETIME NOT NULL,
  ends_at          DATETIME NOT NULL,
  resource         VARCHAR(60) NOT NULL,                    -- Mitarbeiter/Werkbank
  status           ENUM('requested','confirmed','checked_in','in_progress','done','no_show','cancelled') NOT NULL,
  weapon_description VARCHAR(255) NULL,
  notes            TEXT NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  UNIQUE KEY uq_slot (resource, starts_at),
  INDEX idx_appt_time (starts_at, status)
);

-- ---------------------------------------------------------------
-- 7. Inhalte: CMS, Blog, FAQ, SEO
-- ---------------------------------------------------------------
CREATE TABLE pages (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(160) NOT NULL UNIQUE,
  title        VARCHAR(200) NOT NULL,
  blocks       JSON NOT NULL,                               -- Block-Editor (Hero, Text, Bild, FAQ, CTA …)
  template     VARCHAR(40) NOT NULL DEFAULT 'default',
  status       ENUM('draft','published') NOT NULL,
  published_at TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL
);

CREATE TABLE authors (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      BIGINT UNSIGNED NULL,
  name         VARCHAR(120) NOT NULL,
  slug         VARCHAR(140) NOT NULL UNIQUE,
  role_title   VARCHAR(120) NULL,                           -- „Büchsenmachermeister“
  bio          TEXT NULL,
  avatar_path  VARCHAR(255) NULL,
  same_as      JSON NULL                                    -- Profil-Links für Schema.org Person
);

CREATE TABLE blog_categories (
  id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(80) NOT NULL,
  slug  VARCHAR(80) NOT NULL UNIQUE,
  description TEXT NULL
);

CREATE TABLE blog_posts (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  blog_category_id BIGINT UNSIGNED NOT NULL,
  author_id        BIGINT UNSIGNED NOT NULL,
  title            VARCHAR(200) NOT NULL,
  slug             VARCHAR(220) NOT NULL UNIQUE,
  excerpt          TEXT NULL,
  blocks           JSON NOT NULL,
  cover_path       VARCHAR(255) NULL,
  reading_minutes  TINYINT UNSIGNED NULL,
  comments_enabled BOOLEAN NOT NULL DEFAULT TRUE,
  status           ENUM('draft','review','scheduled','published') NOT NULL,
  published_at     TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  CONSTRAINT fk_bp_cat FOREIGN KEY (blog_category_id) REFERENCES blog_categories(id),
  CONSTRAINT fk_bp_author FOREIGN KEY (author_id) REFERENCES authors(id),
  INDEX idx_bp_pub (status, published_at)
);

CREATE TABLE blog_post_product (                            -- im Artikel erwähnte Produkte
  blog_post_id BIGINT UNSIGNED NOT NULL,
  product_id   BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (blog_post_id, product_id)
);

CREATE TABLE comments (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  blog_post_id  BIGINT UNSIGNED NOT NULL,
  parent_id     BIGINT UNSIGNED NULL,
  user_id       BIGINT UNSIGNED NULL,
  author_name   VARCHAR(80) NOT NULL,
  body          TEXT NOT NULL,
  status        ENUM('pending','approved','spam','rejected') NOT NULL DEFAULT 'pending',
  spam_score    DECIMAL(4,3) NULL,
  ip_hash       CHAR(64) NULL,
  created_at    TIMESTAMP NULL,
  CONSTRAINT fk_cm_post FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
  INDEX idx_cm_status (status, created_at)
);

CREATE TABLE faqs (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  faqable_type VARCHAR(100) NULL,                           -- global, Produkt, Kategorie, Hersteller, Blogpost
  faqable_id   BIGINT UNSIGNED NULL,
  group_name   VARCHAR(80) NULL,
  question     VARCHAR(300) NOT NULL,
  answer       TEXT NOT NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  INDEX idx_faq_owner (faqable_type, faqable_id)
);

CREATE TABLE seo_meta (                                     -- polymorph für alle indexierbaren Entitäten
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seoable_type   VARCHAR(100) NOT NULL,
  seoable_id     BIGINT UNSIGNED NOT NULL,
  meta_title     VARCHAR(70) NULL,
  meta_description VARCHAR(170) NULL,
  canonical_url  VARCHAR(255) NULL,
  robots         VARCHAR(40) NULL,                          -- index,follow
  og_title       VARCHAR(100) NULL,
  og_description VARCHAR(200) NULL,
  og_image_path  VARCHAR(255) NULL,
  focus_keyword  VARCHAR(100) NULL,
  UNIQUE KEY uq_seo (seoable_type, seoable_id)
);

CREATE TABLE redirects (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_path    VARCHAR(500) NOT NULL UNIQUE,
  to_path      VARCHAR(500) NOT NULL,
  status_code  SMALLINT NOT NULL DEFAULT 301,
  hits         INT UNSIGNED NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NULL
);

-- ---------------------------------------------------------------
-- 8. Newsletter
-- ---------------------------------------------------------------
CREATE TABLE newsletter_subscribers (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email            VARCHAR(255) NOT NULL UNIQUE,
  user_id          BIGINT UNSIGNED NULL,
  first_name       VARCHAR(100) NULL,
  interests        JSON NULL,                               -- ["jagd","sport","optik","sammler"]
  status           ENUM('pending','confirmed','unsubscribed','bounced','complained') NOT NULL,
  confirm_token    CHAR(64) NULL,
  opt_in_ip_hash   CHAR(64) NULL,
  opt_in_at        TIMESTAMP NULL,
  confirmed_at     TIMESTAMP NULL,                          -- Nachweis Double Opt-in
  confirmed_ip_hash CHAR(64) NULL,
  unsubscribed_at  TIMESTAMP NULL,
  source           VARCHAR(60) NULL,                        -- footer, checkout, blog, popup
  INDEX idx_nl_status (status)
);

CREATE TABLE newsletter_segments (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  rules      JSON NOT NULL                                  -- z. B. {"interests":["jagd"],"bought_category":"optiken"}
);

CREATE TABLE newsletter_campaigns (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(160) NOT NULL,
  subject      VARCHAR(200) NOT NULL,
  preheader    VARCHAR(200) NULL,
  segment_id   BIGINT UNSIGNED NULL,
  content      JSON NOT NULL,
  status       ENUM('draft','scheduled','sending','sent','cancelled') NOT NULL,
  scheduled_at TIMESTAMP NULL,
  sent_at      TIMESTAMP NULL,
  CONSTRAINT fk_nc_seg FOREIGN KEY (segment_id) REFERENCES newsletter_segments(id)
);

CREATE TABLE newsletter_events (                            -- Tracking (nur bei Einwilligung, s. Datenschutz)
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id   BIGINT UNSIGNED NOT NULL,
  subscriber_id BIGINT UNSIGNED NOT NULL,
  event         ENUM('sent','delivered','open','click','bounce','unsubscribe','complaint') NOT NULL,
  url           VARCHAR(500) NULL,
  occurred_at   TIMESTAMP NOT NULL,
  INDEX idx_ne (campaign_id, event)
);

-- ---------------------------------------------------------------
-- 9. Stammdaten-Beispiele
-- ---------------------------------------------------------------
INSERT INTO tax_rates (name, rate) VALUES ('Regelsatz', 19.00), ('Ermäßigt', 7.00);

INSERT INTO legal_classes (code, name, min_age, requires_entitlement, accepted_entitlements, shipping_mode, allowed_payment_codes, requires_serial) VALUES
('free_general',      'Frei verkäuflich',                         0,  FALSE, NULL, 'standard',        '["paypal","klarna","card","prepayment","cash_pickup","psp_weapon"]', FALSE),
('free_18',           'Frei ab 18 (z. B. Luftgewehr F, SRS, Messer)', 18, FALSE, NULL, 'age_check',    '["prepayment","cash_pickup","psp_weapon"]', FALSE),
('wbk_shortgun',      'Kurzwaffe – Erwerbserlaubnis nötig',      18, TRUE,  '["wbk_voreintrag","wbk_red_collector","dealer","authority"]', 'weapon_personal', '["prepayment","cash_pickup","psp_weapon"]', TRUE),
('wbk_longgun',       'Langwaffe – Erwerbserlaubnis nötig',      18, TRUE,  '["wbk_voreintrag","wbk_yellow_sport","hunting_license_longgun","wbk_red_collector","dealer","authority"]', 'weapon_personal', '["prepayment","cash_pickup","psp_weapon"]', TRUE),
('ammo',              'Munition – Erwerbsberechtigung nötig',    18, TRUE,  '["ammo_permit","hunting_license_longgun","hunting_license_shortgun_ammo","dealer","authority"]', 'weapon_personal', '["prepayment","cash_pickup","psp_weapon"]', FALSE),
('essential_part',    'Wesentliches Teil / Schalldämpfer',       18, TRUE,  '["wbk_voreintrag","hunting_license_longgun","dealer","authority"]', 'weapon_personal', '["prepayment","cash_pickup","psp_weapon"]', TRUE),
('pickup_only',       'Nur Abholung im Geschäft',                18, FALSE, NULL, 'pickup_only',     '["prepayment","cash_pickup"]', FALSE);
