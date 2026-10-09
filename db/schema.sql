-- Cuentas Claras — esquema (MySQL)
-- Generado a partir de sistema.md §4 + ESPECIFICACION_TECNICA.md Apéndice A.
-- Orden de DROP: hijas -> padres. Orden de CREATE: padres -> hijas.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS failed_jobs;
DROP TABLE IF EXISTS push_devices;
DROP TABLE IF EXISTS notification_preferences;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS financial_health_scores;
DROP TABLE IF EXISTS smart_suggestions;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS settlement_payments;
DROP TABLE IF EXISTS workspace_sidebar_sections;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS savings_goal_movements;
DROP TABLE IF EXISTS savings_goals;
DROP TABLE IF EXISTS savings_movements;
DROP TABLE IF EXISTS savings_wallet;
DROP TABLE IF EXISTS monthly_closings;
DROP TABLE IF EXISTS installment_payments;
DROP TABLE IF EXISTS installments;
DROP TABLE IF EXISTS service_payments;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS expense_import_batches;
DROP TABLE IF EXISTS income_entries;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS workspace_invitations;
DROP TABLE IF EXISTS workspace_users;
DROP TABLE IF EXISTS workspaces;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS personal_access_tokens;
DROP TABLE IF EXISTS users;

-- Tablas de catálogo (reemplazan los ENUM de MySQL: los valores permitidos
-- viven en filas insertadas acá, no en la definición del tipo de columna)
DROP TABLE IF EXISTS workspace_types;
DROP TABLE IF EXISTS workspace_roles;
DROP TABLE IF EXISTS invitation_statuses;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS late_fee_types;
DROP TABLE IF EXISTS service_payment_statuses;
DROP TABLE IF EXISTS installment_statuses;
DROP TABLE IF EXISTS installment_payment_statuses;
DROP TABLE IF EXISTS closing_allocation_statuses;
DROP TABLE IF EXISTS closing_triggers;
DROP TABLE IF EXISTS savings_movement_types;
DROP TABLE IF EXISTS savings_movement_sources;
DROP TABLE IF EXISTS savings_goal_movement_types;
DROP TABLE IF EXISTS savings_goal_statuses;
DROP TABLE IF EXISTS budget_alert_levels;
DROP TABLE IF EXISTS audit_actions;
DROP TABLE IF EXISTS smart_suggestion_statuses;
DROP TABLE IF EXISTS push_platforms;
DROP TABLE IF EXISTS sidebar_sections;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  two_factor_secret TEXT NULL,
  two_factor_recovery_codes TEXT NULL,
  two_factor_confirmed_at TIMESTAMP NULL,
  email_verified_at TIMESTAMP NULL,
  locale VARCHAR(5) NOT NULL DEFAULT 'es',
  theme VARCHAR(10) NOT NULL DEFAULT 'system',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Requerida por Laravel Sanctum (auth vía Bearer token, ver ADR-002)
CREATE TABLE personal_access_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  abilities TEXT NULL,
  last_used_at TIMESTAMP NULL,
  expires_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB;

-- M-01: recuperación de contraseña
CREATE TABLE password_reset_tokens (
  email VARCHAR(150) PRIMARY KEY,
  token VARCHAR(64) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Tablas de catálogo (reemplazan los ENUM de MySQL - decisión del usuario,
-- 2026-09-17: los valores permitidos deben ser filas insertadas, consultables
-- desde ahí, no un tipo de columna cerrado). Todas usan el mismo código de
-- texto que ya circulaba como valor de ENUM, así que ningún Request/DTO/
-- frontend necesita cambiar: siguen mandando y recibiendo el mismo string.
-- ---------------------------------------------------------------------------

CREATE TABLE workspace_types (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO workspace_types (code, label) VALUES
  ('individual', 'Individual'),
  ('shared_joint', 'Compartido - fondo común'),
  ('shared_separate', 'Compartido - gastos separados'),
  ('shared_settlement', 'Gastos compartidos');

CREATE TABLE workspace_roles (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO workspace_roles (code, label) VALUES
  ('owner', 'Dueño'),
  ('member', 'Miembro');

CREATE TABLE invitation_statuses (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO invitation_statuses (code, label) VALUES
  ('pending', 'Pendiente'),
  ('accepted', 'Aceptada'),
  ('expired', 'Expirada'),
  ('revoked', 'Revocada');

CREATE TABLE payment_methods (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO payment_methods (code, label) VALUES
  ('cash', 'Efectivo'),
  ('debit', 'Débito'),
  ('credit', 'Crédito'),
  ('transfer', 'Transferencia'),
  ('wallet', 'Billetera virtual'),
  ('other', 'Otro');

CREATE TABLE late_fee_types (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO late_fee_types (code, label) VALUES
  ('percentage', 'Porcentaje'),
  ('fixed', 'Monto fijo');

CREATE TABLE service_payment_statuses (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO service_payment_statuses (code, label) VALUES
  ('pending', 'Pendiente'),
  ('paid', 'Pagado'),
  ('overdue', 'Vencido');

CREATE TABLE installment_statuses (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO installment_statuses (code, label) VALUES
  ('active', 'Activa'),
  ('completed', 'Completada'),
  ('cancelled', 'Cancelada');

CREATE TABLE installment_payment_statuses (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO installment_payment_statuses (code, label) VALUES
  ('pending', 'Pendiente'),
  ('paid', 'Pagada');

CREATE TABLE closing_allocation_statuses (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO closing_allocation_statuses (code, label) VALUES
  ('pending', 'Pendiente'),
  ('allocated', 'Asignado'),
  ('not_applicable', 'No aplica');

CREATE TABLE closing_triggers (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO closing_triggers (code, label) VALUES
  ('system', 'Automático'),
  ('manual', 'Manual');

CREATE TABLE savings_movement_types (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO savings_movement_types (code, label) VALUES
  ('deposit', 'Depósito'),
  ('withdraw', 'Retiro');

-- Compartida por savings_movements.source y savings_goal_movements.source:
-- mismo concepto de dominio (el movimiento lo disparó el usuario a mano o el
-- cierre mensual automático), no una coincidencia superficial de valores.
CREATE TABLE savings_movement_sources (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO savings_movement_sources (code, label) VALUES
  ('manual', 'Manual'),
  ('closing', 'Cierre mensual');

CREATE TABLE savings_goal_movement_types (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO savings_goal_movement_types (code, label) VALUES
  ('contribution', 'Aporte'),
  ('withdrawal', 'Retiro');

CREATE TABLE savings_goal_statuses (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO savings_goal_statuses (code, label) VALUES
  ('active', 'Activa'),
  ('completed', 'Completada'),
  ('cancelled', 'Cancelada');

CREATE TABLE budget_alert_levels (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO budget_alert_levels (code, label) VALUES
  ('none', 'Sin alerta'),
  ('warning', 'Advertencia'),
  ('reached', 'Alcanzado'),
  ('exceeded', 'Excedido');

CREATE TABLE audit_actions (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO audit_actions (code, label) VALUES
  ('created', 'Creado'),
  ('updated', 'Editado'),
  ('deleted', 'Eliminado'),
  ('paid', 'Pagado'),
  ('unpaid', 'Despagado');

CREATE TABLE smart_suggestion_statuses (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO smart_suggestion_statuses (code, label) VALUES
  ('pending', 'Pendiente'),
  ('accepted', 'Aceptada'),
  ('dismissed', 'Descartada');

CREATE TABLE push_platforms (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO push_platforms (code, label) VALUES
  ('android', 'Android'),
  ('ios', 'iOS'),
  ('web', 'Web');

-- Secciones OPCIONALES del sidebar del workspace, que cada usuario puede
-- agregar/sacar (ver workspace_sidebar_sections). Dashboard/Ingresos/Gastos/
-- Servicios/Cuotas son fijas y no pasan por acá; Configuración es owner-only
-- y tampoco pasa por acá (se sigue mostrando automático, sin preferencia).
CREATE TABLE sidebar_sections (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

INSERT INTO sidebar_sections (code, label) VALUES
  ('budgets', 'Presupuestos'),
  ('savings', 'Monedero'),
  ('goals', 'Metas'),
  ('members', 'Miembros'),
  ('activity', 'Actividad');

CREATE TABLE workspaces (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'ARS',
  type VARCHAR(20) NOT NULL DEFAULT 'individual',
  created_by BIGINT UNSIGNED NOT NULL,
  onboarding_completed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (type) REFERENCES workspace_types(code)
) ENGINE=InnoDB;

CREATE TABLE workspace_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'member',
  invited_at TIMESTAMP NULL,
  joined_at TIMESTAMP NULL,
  UNIQUE KEY uq_workspace_user (workspace_id, user_id),
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role) REFERENCES workspace_roles(code)
) ENGINE=InnoDB;

CREATE TABLE workspace_invitations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(64) NOT NULL UNIQUE,
  email VARCHAR(150) NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  accepted_by BIGINT UNSIGNED NULL,
  accepted_at TIMESTAMP NULL,
  expires_at TIMESTAMP NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (accepted_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (status) REFERENCES invitation_statuses(code)
) ENGINE=InnoDB;

-- Secciones opcionales del sidebar que un usuario agregó, por workspace (ver
-- sidebar_sections). Sin filas para un user_id+workspace_id = solo se ven las
-- fijas (Dashboard/Ingresos/Gastos/Servicios/Cuotas) + Configuración si es owner.
CREATE TABLE workspace_sidebar_sections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  section_code VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_workspace_user_section (workspace_id, user_id, section_code),
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (section_code) REFERENCES sidebar_sections(code)
) ENGINE=InnoDB;

CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  icon VARCHAR(50) NULL,
  color VARCHAR(20) NULL,
  is_default BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_category_name (workspace_id, name),
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- M-07: lotes de importación de gastos por CSV
CREATE TABLE expense_import_batches (
  id CHAR(36) PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  rows_imported INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  undone_at TIMESTAMP NULL,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE expenses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  import_batch_id CHAR(36) NULL,
  paid_by_user_id BIGINT UNSIGNED NULL,
  amount DECIMAL(14,2) NOT NULL,
  description VARCHAR(255) NULL,
  payment_method VARCHAR(20) NOT NULL DEFAULT 'other',
  date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (import_batch_id) REFERENCES expense_import_batches(id) ON DELETE SET NULL,
  FOREIGN KEY (paid_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (payment_method) REFERENCES payment_methods(code),
  INDEX idx_expense_date (workspace_id, date),
  INDEX idx_expense_category (workspace_id, category_id, date),
  INDEX idx_expense_user (workspace_id, user_id, date)
) ENGINE=InnoDB;

CREATE TABLE services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  due_day_start TINYINT UNSIGNED NOT NULL,
  due_day_end TINYINT UNSIGNED NULL,
  late_fee_type VARCHAR(20) NOT NULL DEFAULT 'fixed',
  late_fee_value DECIMAL(14,2) NOT NULL DEFAULT 0,
  is_estimated BOOLEAN NOT NULL DEFAULT FALSE,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (late_fee_type) REFERENCES late_fee_types(code)
) ENGINE=InnoDB;

CREATE TABLE service_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id BIGINT UNSIGNED NOT NULL,
  workspace_id BIGINT UNSIGNED NOT NULL,
  paid_by_user_id BIGINT UNSIGNED NULL,
  month TINYINT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  amount_paid DECIMAL(14,2) NULL,
  late_fee_applied DECIMAL(14,2) NOT NULL DEFAULT 0,
  paid_at TIMESTAMP NULL,
  was_late BOOLEAN NOT NULL DEFAULT FALSE,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  notes VARCHAR(255) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_service_period (service_id, month, year),
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (paid_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (status) REFERENCES service_payment_statuses(code),
  INDEX idx_service_payment_period (workspace_id, year, month, status)
) ENGINE=InnoDB;

CREATE TABLE installments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  total_amount DECIMAL(14,2) NOT NULL,
  installment_amount DECIMAL(14,2) NOT NULL,
  installments_count SMALLINT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (status) REFERENCES installment_statuses(code)
) ENGINE=InnoDB;

CREATE TABLE installment_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  installment_id BIGINT UNSIGNED NOT NULL,
  workspace_id BIGINT UNSIGNED NOT NULL,
  number SMALLINT UNSIGNED NOT NULL,
  month TINYINT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  paid_at TIMESTAMP NULL,
  UNIQUE KEY uq_installment_period (installment_id, month, year),
  FOREIGN KEY (installment_id) REFERENCES installments(id) ON DELETE CASCADE,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (status) REFERENCES installment_payment_statuses(code),
  INDEX idx_installment_payment_period (workspace_id, year, month, status)
) ENGINE=InnoDB;

-- Debe crearse antes que savings_movements y savings_goal_movements (la referencian)
CREATE TABLE monthly_closings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  month TINYINT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  total_income DECIMAL(14,2) NOT NULL,
  total_expenses DECIMAL(14,2) NOT NULL,
  total_services DECIMAL(14,2) NOT NULL,
  total_installments DECIMAL(14,2) NOT NULL,
  savings_generated DECIMAL(14,2) NOT NULL,
  remaining_amount DECIMAL(14,2) NOT NULL,
  allocated_to_wallet DECIMAL(14,2) NOT NULL DEFAULT 0,
  allocated_to_goals DECIMAL(14,2) NOT NULL DEFAULT 0,
  allocated_to_next_month DECIMAL(14,2) NOT NULL DEFAULT 0,
  allocation_status VARCHAR(20) NOT NULL,
  allocated_at TIMESTAMP NULL,
  breakdown_json JSON NULL,
  closed_by VARCHAR(20) NOT NULL DEFAULT 'system',
  closed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_closing_period (workspace_id, month, year),
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (allocation_status) REFERENCES closing_allocation_statuses(code),
  FOREIGN KEY (closed_by) REFERENCES closing_triggers(code)
) ENGINE=InnoDB;

-- Ingresos incrementales: cada fila es un ingreso (base o extra) dentro del mes.
-- Debe crearse despues de monthly_closings (closing_id la referencia).
CREATE TABLE income_entries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  concept VARCHAR(150) NOT NULL DEFAULT 'Ingreso',
  date DATE NOT NULL,
  month TINYINT UNSIGNED NOT NULL,
  source VARCHAR(20) NOT NULL DEFAULT 'manual',
  closing_id BIGINT UNSIGNED NULL,
  year SMALLINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (closing_id) REFERENCES monthly_closings(id) ON DELETE SET NULL,
  FOREIGN KEY (source) REFERENCES savings_movement_sources(code),
  INDEX idx_income_period (workspace_id, year, month)
) ENGINE=InnoDB;

CREATE TABLE savings_wallet (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL UNIQUE,
  balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE savings_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wallet_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  type VARCHAR(20) NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  balance_after DECIMAL(14,2) NOT NULL,
  source VARCHAR(20) NOT NULL DEFAULT 'manual',
  closing_id BIGINT UNSIGNED NULL,
  month TINYINT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (wallet_id) REFERENCES savings_wallet(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (closing_id) REFERENCES monthly_closings(id) ON DELETE SET NULL,
  FOREIGN KEY (type) REFERENCES savings_movement_types(code),
  FOREIGN KEY (source) REFERENCES savings_movement_sources(code)
) ENGINE=InnoDB;

CREATE TABLE savings_goals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  target_amount DECIMAL(14,2) NOT NULL,
  current_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  due_date DATE NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (status) REFERENCES savings_goal_statuses(code)
) ENGINE=InnoDB;

CREATE TABLE savings_goal_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  goal_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  type VARCHAR(20) NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  source VARCHAR(20) NOT NULL DEFAULT 'manual',
  closing_id BIGINT UNSIGNED NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (goal_id) REFERENCES savings_goals(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (closing_id) REFERENCES monthly_closings(id) ON DELETE SET NULL,
  FOREIGN KEY (type) REFERENCES savings_goal_movement_types(code),
  FOREIGN KEY (source) REFERENCES savings_movement_sources(code)
) ENGINE=InnoDB;

CREATE TABLE budgets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  month TINYINT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  limit_amount DECIMAL(14,2) NOT NULL,
  alert_level VARCHAR(20) NOT NULL DEFAULT 'none',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_budget_period (workspace_id, category_id, month, year),
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  FOREIGN KEY (alert_level) REFERENCES budget_alert_levels(code)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(20) NOT NULL,
  summary VARCHAR(255) NOT NULL,
  old_value JSON NULL,
  new_value JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (action) REFERENCES audit_actions(code),
  INDEX idx_audit_entity (entity_type, entity_id),
  INDEX idx_audit_workspace_date (workspace_id, created_at)
) ENGINE=InnoDB;

-- M-25: pago real registrado entre dos miembros para saldar una deuda de
-- liquidación de gastos compartidos. Sin columna de estado: la existencia de
-- la fila ES el estado "pagada"; deshacer es un DELETE físico auditado.
CREATE TABLE settlement_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  from_user_id BIGINT UNSIGNED NOT NULL,
  to_user_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  note VARCHAR(255) NULL,
  registered_by BIGINT UNSIGNED NOT NULL,
  paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_settlement_payment_workspace (workspace_id, paid_at)
) ENGINE=InnoDB;

CREATE TABLE smart_suggestions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  normalized_key VARCHAR(255) NOT NULL,
  avg_amount DECIMAL(14,2) NOT NULL,
  frequency_detected VARCHAR(50) NOT NULL,
  occurrences TINYINT UNSIGNED NOT NULL,
  last_seen_date DATE NOT NULL,
  suggested_due_day TINYINT UNSIGNED NOT NULL,
  sample_expense_ids JSON NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  resolved_by BIGINT UNSIGNED NULL,
  resolved_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_suggestion_key (workspace_id, normalized_key),
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
  FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (status) REFERENCES smart_suggestion_statuses(code)
) ENGINE=InnoDB;

CREATE TABLE financial_health_scores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  month TINYINT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NOT NULL,
  breakdown_json JSON NULL,
  UNIQUE KEY uq_health_period (workspace_id, month, year),
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  workspace_id BIGINT UNSIGNED NULL,
  type VARCHAR(50) NOT NULL,
  title VARCHAR(150) NOT NULL,
  body VARCHAR(500) NOT NULL,
  route VARCHAR(255) NULL,
  payload JSON NULL,
  read_at TIMESTAMP NULL,
  sent_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  INDEX idx_notification_user (user_id, read_at, created_at)
) ENGINE=InnoDB;

-- M-17: preferencias de notificación por usuario
CREATE TABLE notification_preferences (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  service_reminder_days JSON NULL,
  budget_alert_levels JSON NULL,
  channels JSON NULL,
  muted_types JSON NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- M-17: dispositivos registrados para push (FCM)
CREATE TABLE push_devices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  platform VARCHAR(20) NOT NULL,
  device_name VARCHAR(100) NULL,
  last_seen_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (platform) REFERENCES push_platforms(code)
) ENGINE=InnoDB;

-- M-23: cola de Laravel (estructura estándar, sin migrations)
CREATE TABLE failed_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid VARCHAR(255) NOT NULL UNIQUE,
  connection TEXT NOT NULL,
  queue TEXT NOT NULL,
  payload LONGTEXT NOT NULL,
  exception LONGTEXT NOT NULL,
  failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- procedimientos.
-- ============================================================================
-- STORED PROCEDURES
-- ============================================================================
-- Se agregan acá a medida que se implementa cada módulo (ver skills/database.md
-- y ESPECIFICACION_TECNICA.md §0.6). Cada uno como `DROP PROCEDURE IF EXISTS sp_x`
-- + `CREATE PROCEDURE sp_x(...)`, mismo nombre al editar (nunca crear una versión
-- paralela "sp_x_v2"). Esta sección se puede seleccionar y ejecutar sola en
-- Workbench sin tocar las tablas de arriba: no borra datos, solo redefine

--
-- Requiere DELIMITER porque los cuerpos tienen ';' internos. Al agregar SPs de
-- un módulo nuevo: sumar los DROP PROCEDURE arriba (con el ';' normal, antes de
-- DELIMITER $$) y el CREATE PROCEDURE nuevo entre DELIMITER $$ y DELIMITER ;.

-- ---------------------------------------------------------------------------
-- M-01 Autenticación y usuarios
-- ---------------------------------------------------------------------------
-- procedimientos almacenados.

DROP PROCEDURE IF EXISTS sp_user_create;
DROP PROCEDURE IF EXISTS sp_user_get_by_email;
DROP PROCEDURE IF EXISTS sp_user_get_by_id;
DROP PROCEDURE IF EXISTS sp_user_update_profile;
DROP PROCEDURE IF EXISTS sp_user_update_password;
DROP PROCEDURE IF EXISTS sp_user_set_two_factor;
DROP PROCEDURE IF EXISTS sp_user_clear_two_factor;
DROP PROCEDURE IF EXISTS sp_user_use_recovery_code;
DROP PROCEDURE IF EXISTS sp_password_reset_token_upsert;
DROP PROCEDURE IF EXISTS sp_password_reset_token_get;
DROP PROCEDURE IF EXISTS sp_password_reset_token_delete;

-- ---------------------------------------------------------------------------
-- M-02 Workspaces, membresías e invitaciones
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_workspace_create;
DROP PROCEDURE IF EXISTS sp_workspace_list_by_user;
DROP PROCEDURE IF EXISTS sp_workspace_get;
DROP PROCEDURE IF EXISTS sp_workspace_update;
DROP PROCEDURE IF EXISTS sp_workspace_delete;
DROP PROCEDURE IF EXISTS sp_workspace_member_get;
DROP PROCEDURE IF EXISTS sp_workspace_members_list;
DROP PROCEDURE IF EXISTS sp_workspace_member_remove;
DROP PROCEDURE IF EXISTS sp_workspace_mark_onboarding;
DROP PROCEDURE IF EXISTS sp_invitation_create;
DROP PROCEDURE IF EXISTS sp_invitation_list;
DROP PROCEDURE IF EXISTS sp_invitation_get_by_code;
DROP PROCEDURE IF EXISTS sp_invitation_accept;
DROP PROCEDURE IF EXISTS sp_invitation_revoke;
DROP PROCEDURE IF EXISTS sp_invitation_expire_stale;
DROP PROCEDURE IF EXISTS sp_invitation_list_by_email;

-- ---------------------------------------------------------------------------
-- M-03 Auditoría (mínimo necesario para que M-02 pueda auditar)
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_audit_log_create;
DROP PROCEDURE IF EXISTS sp_audit_log_list;

-- ---------------------------------------------------------------------------
-- M-04 Categorías
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_category_list;
DROP PROCEDURE IF EXISTS sp_category_get;
DROP PROCEDURE IF EXISTS sp_category_create;
DROP PROCEDURE IF EXISTS sp_category_update;
DROP PROCEDURE IF EXISTS sp_category_delete;

-- ---------------------------------------------------------------------------
-- Soporte transversal - ClosingGuard (usado desde M-05 en adelante)
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_monthly_closing_exists;

-- ---------------------------------------------------------------------------
-- M-05 Ingresos incrementales
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_income_entry_list;
DROP PROCEDURE IF EXISTS sp_income_entry_list_all;
DROP PROCEDURE IF EXISTS sp_income_entry_get;
DROP PROCEDURE IF EXISTS sp_income_entry_create;
DROP PROCEDURE IF EXISTS sp_income_entry_update;
DROP PROCEDURE IF EXISTS sp_income_entry_delete;

-- ---------------------------------------------------------------------------
-- M-06 Gastos
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_expense_list;
DROP PROCEDURE IF EXISTS sp_expense_get;
DROP PROCEDURE IF EXISTS sp_expense_create;
DROP PROCEDURE IF EXISTS sp_expense_update;
DROP PROCEDURE IF EXISTS sp_expense_delete;
DROP PROCEDURE IF EXISTS sp_expense_payment_methods;

-- ---------------------------------------------------------------------------
-- M-08 Servicios recurrentes y pagos mensuales
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_service_list;
DROP PROCEDURE IF EXISTS sp_service_get;
DROP PROCEDURE IF EXISTS sp_service_create;
DROP PROCEDURE IF EXISTS sp_service_update;
DROP PROCEDURE IF EXISTS sp_service_delete;
DROP PROCEDURE IF EXISTS sp_service_payment_ensure_period;
DROP PROCEDURE IF EXISTS sp_service_payment_list;
DROP PROCEDURE IF EXISTS sp_service_payment_list_all;
DROP PROCEDURE IF EXISTS sp_service_payment_get;
DROP PROCEDURE IF EXISTS sp_service_payment_pay;
DROP PROCEDURE IF EXISTS sp_service_payment_unpay;
DROP PROCEDURE IF EXISTS sp_service_payment_mark_overdue;
DROP PROCEDURE IF EXISTS sp_service_payment_list_overdue;
DROP PROCEDURE IF EXISTS sp_service_payment_due_soon;
DROP PROCEDURE IF EXISTS sp_installment_list;
DROP PROCEDURE IF EXISTS sp_installment_get;
DROP PROCEDURE IF EXISTS sp_installment_create;
DROP PROCEDURE IF EXISTS sp_installment_update;
DROP PROCEDURE IF EXISTS sp_installment_delete;
DROP PROCEDURE IF EXISTS sp_installment_payment_pay;
DROP PROCEDURE IF EXISTS sp_installment_payment_unpay;
DROP PROCEDURE IF EXISTS sp_installment_payments_by_period;
DROP PROCEDURE IF EXISTS sp_installment_payments_settle_period;
DROP PROCEDURE IF EXISTS sp_installment_payment_due_soon;

-- ---------------------------------------------------------------------------
-- M-10 Monedero de ahorro
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_savings_wallet_get;
DROP PROCEDURE IF EXISTS sp_savings_movement_create;
DROP PROCEDURE IF EXISTS sp_savings_movement_list;
DROP PROCEDURE IF EXISTS sp_savings_wallet_history;
DROP PROCEDURE IF EXISTS sp_savings_movement_totals_for_period;

-- ---------------------------------------------------------------------------
-- M-11 Metas de ahorro
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_savings_goal_list;
DROP PROCEDURE IF EXISTS sp_savings_goal_get;
DROP PROCEDURE IF EXISTS sp_savings_goal_create;
DROP PROCEDURE IF EXISTS sp_savings_goal_update;
DROP PROCEDURE IF EXISTS sp_savings_goal_cancel;
DROP PROCEDURE IF EXISTS sp_savings_goal_contribute;
DROP PROCEDURE IF EXISTS sp_savings_goal_movements;
DROP PROCEDURE IF EXISTS sp_savings_goal_totals_for_period;
DROP PROCEDURE IF EXISTS sp_savings_transfer_wallet_to_goal;

-- ---------------------------------------------------------------------------
-- M-12 Presupuestos y alertas
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_budget_list;
DROP PROCEDURE IF EXISTS sp_budget_upsert;
DROP PROCEDURE IF EXISTS sp_budget_delete;
DROP PROCEDURE IF EXISTS sp_budget_copy_from_period;
DROP PROCEDURE IF EXISTS sp_budget_progress;
DROP PROCEDURE IF EXISTS sp_budget_update_alert_level;

-- ---------------------------------------------------------------------------
-- M-17 Notificaciones (version minima: solo in-app, sin push/FCM ni
-- preferencias todavia - ver NotificationService)
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_notification_create;
DROP PROCEDURE IF EXISTS sp_notification_list;
DROP PROCEDURE IF EXISTS sp_notification_unread_count;
DROP PROCEDURE IF EXISTS sp_notification_mark_read;
DROP PROCEDURE IF EXISTS sp_notification_mark_all_read;
DROP PROCEDURE IF EXISTS sp_notification_preference_get;
DROP PROCEDURE IF EXISTS sp_notification_preference_upsert;
DROP PROCEDURE IF EXISTS sp_push_device_upsert;
DROP PROCEDURE IF EXISTS sp_push_device_delete;
DROP PROCEDURE IF EXISTS sp_push_device_list_by_users;

-- ---------------------------------------------------------------------------
-- M-14 Dashboard (version reducida: sin servicios/cuotas/presupuestos/cierre
-- mensual/health score, esos modulos todavia no existen - ver DashboardService)
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_dashboard_get;
DROP PROCEDURE IF EXISTS sp_dashboard_daily_spend;

-- ---------------------------------------------------------------------------
-- Sidebar personalizable (secciones opcionales por usuario+workspace)
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_sidebar_sections_list;
DROP PROCEDURE IF EXISTS sp_sidebar_sections_set;

-- ---------------------------------------------------------------------------
-- M-15 Health score financiero
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_health_score_inputs;
DROP PROCEDURE IF EXISTS sp_health_score_upsert;
DROP PROCEDURE IF EXISTS sp_health_score_get;
DROP PROCEDURE IF EXISTS sp_health_score_list;

-- ---------------------------------------------------------------------------
-- M-13 Cierre mensual congelado
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_monthly_closing_compute;
DROP PROCEDURE IF EXISTS sp_monthly_closing_create;
DROP PROCEDURE IF EXISTS sp_monthly_closing_get;
DROP PROCEDURE IF EXISTS sp_monthly_closing_list;
DROP PROCEDURE IF EXISTS sp_monthly_closing_allocate;
DROP PROCEDURE IF EXISTS sp_workspace_list_all;

-- ---------------------------------------------------------------------------
-- M-18 Recordatorios inteligentes (smart suggestions)
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_smart_suggestion_candidates;
DROP PROCEDURE IF EXISTS sp_smart_suggestion_upsert;
DROP PROCEDURE IF EXISTS sp_smart_suggestion_list;
DROP PROCEDURE IF EXISTS sp_smart_suggestion_get;
DROP PROCEDURE IF EXISTS sp_smart_suggestion_update_status;

-- ---------------------------------------------------------------------------
-- M-25 Gastos compartidos y liquidación entre miembros
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_settlement_snapshot_get;
DROP PROCEDURE IF EXISTS sp_settlement_payment_create;
DROP PROCEDURE IF EXISTS sp_settlement_payment_delete;

DELIMITER $$

CREATE PROCEDURE sp_user_create(IN p_json JSON)
BEGIN
  DECLARE v_name VARCHAR(150);
  DECLARE v_email VARCHAR(150);
  DECLARE v_password_hash VARCHAR(255);
  DECLARE v_locale VARCHAR(5);
  DECLARE v_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR 1062
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'DUPLICATE') AS result;
  END;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_name = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.name'));
  SET v_email = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.email'));
  SET v_password_hash = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.password_hash'));
  SET v_locale = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.locale'));

  START TRANSACTION;
  INSERT INTO users (name, email, password, locale)
  VALUES (v_name, v_email, v_password_hash, v_locale);
  SET v_id = LAST_INSERT_ID();
  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'id', v_id, 'name', v_name, 'email', v_email, 'locale', v_locale,
      'created_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_user_get_by_email(IN p_json JSON)
BEGIN
  DECLARE v_email VARCHAR(150);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_email = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.email'));

  IF NOT EXISTS (SELECT 1 FROM users WHERE email = v_email) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', id, 'name', name, 'email', email, 'password_hash', password, 'locale', locale, 'theme', theme,
        'two_factor_secret', two_factor_secret,
        'two_factor_confirmed_at', DATE_FORMAT(two_factor_confirmed_at, '%Y-%m-%dT%H:%i:%sZ'),
        'two_factor_recovery_codes', two_factor_recovery_codes,
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM users WHERE email = v_email;
  END IF;
END$$

CREATE PROCEDURE sp_user_get_by_id(IN p_json JSON)
BEGIN
  DECLARE v_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_id = CAST(JSON_EXTRACT(p_json, '$.id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM users WHERE id = v_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', id, 'name', name, 'email', email, 'locale', locale, 'theme', theme,
        'two_factor_secret', two_factor_secret,
        'two_factor_confirmed_at', DATE_FORMAT(two_factor_confirmed_at, '%Y-%m-%dT%H:%i:%sZ'),
        'two_factor_recovery_codes', two_factor_recovery_codes,
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM users WHERE id = v_id;
  END IF;
END$$

CREATE PROCEDURE sp_user_update_profile(IN p_json JSON)
BEGIN
  DECLARE v_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(150);
  DECLARE v_locale VARCHAR(5);
  DECLARE v_theme VARCHAR(10);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_id = CAST(JSON_EXTRACT(p_json, '$.id') AS UNSIGNED);
  SET v_name = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.name'));
  SET v_locale = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.locale'));
  SET v_theme = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.theme'));

  IF NOT EXISTS (SELECT 1 FROM users WHERE id = v_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    UPDATE users SET name = v_name, locale = v_locale, theme = v_theme WHERE id = v_id;
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', id, 'name', name, 'email', email, 'locale', locale, 'theme', theme,
        'two_factor_confirmed_at', DATE_FORMAT(two_factor_confirmed_at, '%Y-%m-%dT%H:%i:%sZ'),
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM users WHERE id = v_id;
  END IF;
END$$

CREATE PROCEDURE sp_user_update_password(IN p_json JSON)
BEGIN
  DECLARE v_id BIGINT UNSIGNED;
  DECLARE v_password_hash VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_id = CAST(JSON_EXTRACT(p_json, '$.id') AS UNSIGNED);
  SET v_password_hash = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.password_hash'));

  IF NOT EXISTS (SELECT 1 FROM users WHERE id = v_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    UPDATE users SET password = v_password_hash WHERE id = v_id;
    COMMIT;
    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_user_set_two_factor(IN p_json JSON)
BEGIN
  DECLARE v_id BIGINT UNSIGNED;
  DECLARE v_secret TEXT;
  DECLARE v_recovery_codes TEXT;
  DECLARE v_confirmed BOOLEAN;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_id = CAST(JSON_EXTRACT(p_json, '$.id') AS UNSIGNED);
  SET v_secret = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.two_factor_secret'));
  SET v_recovery_codes = IF(
    JSON_EXTRACT(p_json, '$.two_factor_recovery_codes') IS NULL
    OR JSON_TYPE(JSON_EXTRACT(p_json, '$.two_factor_recovery_codes')) = 'NULL',
    NULL,
    JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.two_factor_recovery_codes'))
  );
  SET v_confirmed = (JSON_EXTRACT(p_json, '$.confirmed') = CAST('true' AS JSON));

  IF NOT EXISTS (SELECT 1 FROM users WHERE id = v_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    IF v_confirmed THEN
      UPDATE users
      SET two_factor_secret = v_secret,
          two_factor_recovery_codes = v_recovery_codes,
          two_factor_confirmed_at = NOW()
      WHERE id = v_id;
    ELSE
      UPDATE users
      SET two_factor_secret = v_secret,
          two_factor_recovery_codes = v_recovery_codes,
          two_factor_confirmed_at = NULL
      WHERE id = v_id;
    END IF;
    COMMIT;
    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_user_clear_two_factor(IN p_json JSON)
BEGIN
  DECLARE v_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_id = CAST(JSON_EXTRACT(p_json, '$.id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM users WHERE id = v_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    UPDATE users
    SET two_factor_secret = NULL, two_factor_recovery_codes = NULL, two_factor_confirmed_at = NULL
    WHERE id = v_id;
    COMMIT;
    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_user_use_recovery_code(IN p_json JSON)
BEGIN
  DECLARE v_id BIGINT UNSIGNED;
  DECLARE v_codes TEXT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_id = CAST(JSON_EXTRACT(p_json, '$.id') AS UNSIGNED);
  SET v_codes = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.two_factor_recovery_codes'));

  IF NOT EXISTS (SELECT 1 FROM users WHERE id = v_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    UPDATE users SET two_factor_recovery_codes = v_codes WHERE id = v_id;
    COMMIT;
    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_password_reset_token_upsert(IN p_json JSON)
BEGIN
  DECLARE v_email VARCHAR(150);
  DECLARE v_token_hash VARCHAR(64);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_email = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.email'));
  SET v_token_hash = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.token_hash'));

  START TRANSACTION;
  INSERT INTO password_reset_tokens (email, token, created_at)
  VALUES (v_email, v_token_hash, NOW())
  ON DUPLICATE KEY UPDATE token = v_token_hash, created_at = NOW();
  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
END$$

CREATE PROCEDURE sp_password_reset_token_get(IN p_json JSON)
BEGIN
  DECLARE v_email VARCHAR(150);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_email = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.email'));

  IF NOT EXISTS (SELECT 1 FROM password_reset_tokens WHERE email = v_email) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'token_hash', token,
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM password_reset_tokens WHERE email = v_email;
  END IF;
END$$

CREATE PROCEDURE sp_password_reset_token_delete(IN p_json JSON)
BEGIN
  DECLARE v_email VARCHAR(150);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_email = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.email'));

  START TRANSACTION;
  DELETE FROM password_reset_tokens WHERE email = v_email;
  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
END$$

-- ---------------------------------------------------------------------------
-- M-02 Workspaces, membresías e invitaciones
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_workspace_create(IN p_json JSON)
BEGIN
  DECLARE v_name VARCHAR(150);
  DECLARE v_currency VARCHAR(10);
  DECLARE v_type VARCHAR(20);
  DECLARE v_created_by BIGINT UNSIGNED;
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_name = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.name'));
  SET v_currency = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.currency'));
  SET v_type = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.type'));
  SET v_created_by = CAST(JSON_EXTRACT(p_json, '$.created_by') AS UNSIGNED);

  START TRANSACTION;

  INSERT INTO workspaces (name, currency, type, created_by)
  VALUES (v_name, v_currency, v_type, v_created_by);
  SET v_workspace_id = LAST_INSERT_ID();

  INSERT INTO workspace_users (workspace_id, user_id, role, joined_at)
  VALUES (v_workspace_id, v_created_by, 'owner', NOW());

  INSERT INTO savings_wallet (workspace_id, balance)
  VALUES (v_workspace_id, 0);

  -- Sin categorías por defecto a proposito: el workspace arranca en 0 y el
  -- usuario carga las que necesita (decision del usuario, no sigue M-04 al pie
  -- de la letra en este punto - ver ERROR_LOG.md/PROJECT_STATE.json).

  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'id', v_workspace_id, 'name', v_name, 'currency', v_currency, 'type', v_type,
      'created_by', v_created_by, 'role', 'owner', 'members_count', 1,
      'onboarding_completed', FALSE,
      'created_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_workspace_list_by_user(IN p_json JSON)
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'id', t.id, 'name', t.name, 'currency', t.currency, 'type', t.type,
        'created_by', t.created_by, 'role', t.role, 'members_count', t.members_count,
        'onboarding_completed', (t.onboarding_completed_at IS NOT NULL),
        'created_at', t.created_at
      ))
      FROM (
        SELECT
          w.id, w.name, w.currency, w.type, w.created_by, wu.role, w.onboarding_completed_at,
          (SELECT COUNT(*) FROM workspace_users wu2 WHERE wu2.workspace_id = w.id) AS members_count,
          DATE_FORMAT(w.created_at, '%Y-%m-%dT%H:%i:%sZ') AS created_at
        FROM workspaces w
        JOIN workspace_users wu ON wu.workspace_id = w.id AND wu.user_id = v_user_id
        ORDER BY w.created_at
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_workspace_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM workspace_users WHERE workspace_id = v_workspace_id AND user_id = v_user_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', w.id, 'name', w.name, 'currency', w.currency, 'type', w.type,
        'created_by', w.created_by, 'role', wu.role,
        'members_count', (SELECT COUNT(*) FROM workspace_users wu2 WHERE wu2.workspace_id = w.id),
        'onboarding_completed', (w.onboarding_completed_at IS NOT NULL),
        'created_at', DATE_FORMAT(w.created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM workspaces w
    JOIN workspace_users wu ON wu.workspace_id = w.id AND wu.user_id = v_user_id
    WHERE w.id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_workspace_update(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(150);
  DECLARE v_currency VARCHAR(10);
  DECLARE v_type VARCHAR(20);
  DECLARE v_members_count INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_name = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.name'));
  SET v_currency = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.currency'));
  SET v_type = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.type'));
  SET v_members_count = (SELECT COUNT(*) FROM workspace_users WHERE workspace_id = v_workspace_id);

  IF NOT EXISTS (SELECT 1 FROM workspaces WHERE id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_type = 'individual' AND v_members_count > 1 THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSE
    START TRANSACTION;
    UPDATE workspaces SET name = v_name, currency = v_currency, type = v_type WHERE id = v_workspace_id;
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', id, 'name', name, 'currency', currency, 'type', type, 'created_by', created_by,
        'members_count', v_members_count,
        'onboarding_completed', (onboarding_completed_at IS NOT NULL),
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM workspaces WHERE id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_workspace_delete(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);

  START TRANSACTION;
  DELETE FROM workspaces WHERE id = v_workspace_id;
  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
END$$

CREATE PROCEDURE sp_workspace_member_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM workspace_users WHERE workspace_id = v_workspace_id AND user_id = v_user_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'workspace_id', w.id, 'user_id', wu.user_id, 'role', wu.role,
        'workspace_type', w.type, 'workspace_currency', w.currency, 'workspace_name', w.name
      )
    ) AS result
    FROM workspace_users wu
    JOIN workspaces w ON w.id = wu.workspace_id
    WHERE wu.workspace_id = v_workspace_id AND wu.user_id = v_user_id;
  END IF;
END$$

CREATE PROCEDURE sp_workspace_members_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'user_id', t.user_id, 'name', t.name, 'email', t.email, 'role', t.role, 'joined_at', t.joined_at
      ))
      FROM (
        SELECT
          u.id AS user_id, u.name, u.email, wu.role,
          IF(wu.joined_at IS NULL, NULL, DATE_FORMAT(wu.joined_at, '%Y-%m-%dT%H:%i:%sZ')) AS joined_at
        FROM workspace_users wu
        JOIN users u ON u.id = wu.user_id
        WHERE wu.workspace_id = v_workspace_id
        ORDER BY (wu.role = 'owner') DESC, wu.joined_at
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_workspace_member_remove(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_role VARCHAR(20);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_role = (SELECT role FROM workspace_users WHERE workspace_id = v_workspace_id AND user_id = v_user_id);

  IF v_role IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_role = 'owner' THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSE
    START TRANSACTION;
    DELETE FROM workspace_users WHERE workspace_id = v_workspace_id AND user_id = v_user_id;
    COMMIT;

    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_workspace_mark_onboarding(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM workspaces WHERE id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    UPDATE workspaces SET onboarding_completed_at = NOW() WHERE id = v_workspace_id;
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', id, 'name', name, 'currency', currency, 'type', type, 'created_by', created_by,
        'members_count', (SELECT COUNT(*) FROM workspace_users WHERE workspace_id = v_workspace_id),
        'onboarding_completed', TRUE,
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM workspaces WHERE id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_invitation_create(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_code VARCHAR(64);
  DECLARE v_email VARCHAR(150);
  DECLARE v_expires_at DATETIME;
  DECLARE v_created_by BIGINT UNSIGNED;
  DECLARE v_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR 1062
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'DUPLICATE') AS result;
  END;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_code = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.code'));
  SET v_email = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.email')) = 'NULL' OR JSON_EXTRACT(p_json, '$.email') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.email')));
  SET v_expires_at = STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.expires_at')), '%Y-%m-%d %H:%i:%s');
  SET v_created_by = CAST(JSON_EXTRACT(p_json, '$.created_by') AS UNSIGNED);

  START TRANSACTION;
  INSERT INTO workspace_invitations (workspace_id, code, email, created_by, expires_at, status)
  VALUES (v_workspace_id, v_code, v_email, v_created_by, v_expires_at, 'pending');
  SET v_id = LAST_INSERT_ID();
  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'id', v_id, 'code', v_code, 'email', v_email, 'status', 'pending',
      'expires_at', DATE_FORMAT(v_expires_at, '%Y-%m-%dT%H:%i:%sZ'),
      'created_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ'),
      'created_by_name', (SELECT name FROM users WHERE id = v_created_by)
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_invitation_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'id', t.id, 'code', t.code, 'email', t.email, 'status', t.status,
        'expires_at', t.expires_at, 'created_at', t.created_at, 'created_by_name', t.created_by_name
      ))
      FROM (
        SELECT
          wi.id, wi.code, wi.email, wi.status,
          DATE_FORMAT(wi.expires_at, '%Y-%m-%dT%H:%i:%sZ') AS expires_at,
          DATE_FORMAT(wi.created_at, '%Y-%m-%dT%H:%i:%sZ') AS created_at,
          u.name AS created_by_name
        FROM workspace_invitations wi
        JOIN users u ON u.id = wi.created_by
        WHERE wi.workspace_id = v_workspace_id
        ORDER BY wi.created_at DESC
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_invitation_get_by_code(IN p_json JSON)
BEGIN
  DECLARE v_code VARCHAR(64);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_code = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.code'));

  IF NOT EXISTS (SELECT 1 FROM workspace_invitations WHERE code = v_code) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', wi.id, 'workspace_id', wi.workspace_id, 'workspace_name', w.name, 'workspace_type', w.type,
        'email', wi.email, 'status', wi.status,
        'expires_at', DATE_FORMAT(wi.expires_at, '%Y-%m-%dT%H:%i:%sZ'),
        'created_by_name', u.name
      )
    ) AS result
    FROM workspace_invitations wi
    JOIN workspaces w ON w.id = wi.workspace_id
    JOIN users u ON u.id = wi.created_by
    WHERE wi.code = v_code;
  END IF;
END$$

CREATE PROCEDURE sp_invitation_accept(IN p_json JSON)
BEGIN
  DECLARE v_code VARCHAR(64);
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_now DATETIME;
  DECLARE v_inv_id BIGINT UNSIGNED;
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_status VARCHAR(20);
  DECLARE v_expires_at DATETIME;
  DECLARE v_inv_created_at DATETIME;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_code = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.code'));
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_now = STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.now')), '%Y-%m-%d %H:%i:%s');

  START TRANSACTION;

  SELECT id, workspace_id, status, expires_at, created_at
    INTO v_inv_id, v_workspace_id, v_status, v_expires_at, v_inv_created_at
    FROM workspace_invitations WHERE code = v_code FOR UPDATE;

  IF v_inv_id IS NULL OR v_status <> 'pending' OR v_expires_at < v_now THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSE
    IF NOT EXISTS (SELECT 1 FROM workspace_users WHERE workspace_id = v_workspace_id AND user_id = v_user_id) THEN
      INSERT INTO workspace_users (workspace_id, user_id, role, invited_at, joined_at)
      VALUES (v_workspace_id, v_user_id, 'member', v_inv_created_at, NOW());
    END IF;

    UPDATE workspace_invitations SET status = 'accepted', accepted_by = v_user_id, accepted_at = NOW()
      WHERE id = v_inv_id;

    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', w.id, 'name', w.name, 'currency', w.currency, 'type', w.type, 'created_by', w.created_by,
        'role', 'member',
        'members_count', (SELECT COUNT(*) FROM workspace_users WHERE workspace_id = w.id),
        'onboarding_completed', (w.onboarding_completed_at IS NOT NULL),
        'created_at', DATE_FORMAT(w.created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM workspaces w WHERE w.id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_invitation_revoke(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_invitation_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_invitation_id = CAST(JSON_EXTRACT(p_json, '$.invitation_id') AS UNSIGNED);

  START TRANSACTION;
  UPDATE workspace_invitations SET status = 'revoked'
    WHERE id = v_invitation_id AND workspace_id = v_workspace_id AND status = 'pending';

  IF ROW_COUNT() = 0 THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSE
    COMMIT;
    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_invitation_expire_stale(IN p_json JSON)
BEGIN
  DECLARE v_now DATETIME;
  DECLARE v_count INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_now = STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.now')), '%Y-%m-%d %H:%i:%s');

  START TRANSACTION;
  UPDATE workspace_invitations SET status = 'expired' WHERE status = 'pending' AND expires_at < v_now;
  SET v_count = ROW_COUNT();
  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('expired_count', v_count)) AS result;
END$$

CREATE PROCEDURE sp_invitation_list_by_email(IN p_json JSON)
BEGIN
  DECLARE v_email VARCHAR(150);
  DECLARE v_now DATETIME;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_email = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.email'));
  SET v_now = STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.now')), '%Y-%m-%d %H:%i:%s');

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'code', t.code, 'workspace_name', t.workspace_name, 'workspace_type', t.workspace_type,
        'created_by_name', t.created_by_name, 'email', t.email, 'expires_at', t.expires_at
      ))
      FROM (
        SELECT
          wi.code, w.name AS workspace_name, w.type AS workspace_type,
          u.name AS created_by_name, wi.email,
          DATE_FORMAT(wi.expires_at, '%Y-%m-%dT%H:%i:%sZ') AS expires_at
        FROM workspace_invitations wi
        JOIN workspaces w ON w.id = wi.workspace_id
        JOIN users u ON u.id = wi.created_by
        WHERE wi.email = v_email AND wi.status = 'pending' AND wi.expires_at >= v_now
        ORDER BY wi.created_at DESC
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_audit_log_create(IN p_json JSON)
BEGIN
  DECLARE v_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  START TRANSACTION;
  INSERT INTO audit_logs (
    workspace_id, user_id, entity_type, entity_id, action, summary,
    old_value, new_value, ip_address, user_agent
  )
  VALUES (
    CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED),
    CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED),
    JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.entity_type')),
    CAST(JSON_EXTRACT(p_json, '$.entity_id') AS UNSIGNED),
    JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.action')),
    JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.summary')),
    JSON_EXTRACT(p_json, '$.old_value'),
    JSON_EXTRACT(p_json, '$.new_value'),
    IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.ip_address'))),
    IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.user_agent')))
  );
  SET v_id = LAST_INSERT_ID();
  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('id', v_id)) AS result;
END$$

CREATE PROCEDURE sp_audit_log_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_page INT;
  DECLARE v_per_page INT;
  DECLARE v_entity_type VARCHAR(50);
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_offset INT;
  DECLARE v_total INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_page = CAST(JSON_EXTRACT(p_json, '$.page') AS UNSIGNED);
  SET v_per_page = CAST(JSON_EXTRACT(p_json, '$.per_page') AS UNSIGNED);
  SET v_entity_type = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.entity_type')) = 'NULL' OR JSON_EXTRACT(p_json, '$.entity_type') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.entity_type')));
  SET v_user_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.user_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.user_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED));
  SET v_offset = (v_page - 1) * v_per_page;

  SELECT COUNT(*) INTO v_total
  FROM audit_logs
  WHERE workspace_id = v_workspace_id
    AND (v_entity_type IS NULL OR entity_type = v_entity_type)
    AND (v_user_id IS NULL OR user_id = v_user_id);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'total', v_total,
      'items', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', t.id, 'user_id', t.user_id, 'user_name', t.user_name, 'entity_type', t.entity_type,
          'entity_id', t.entity_id, 'action', t.action, 'summary', t.summary,
          'old_value', t.old_value, 'new_value', t.new_value, 'created_at', t.created_at
        ))
        FROM (
          SELECT
            al.id, al.user_id, u.name AS user_name, al.entity_type, al.entity_id, al.action, al.summary,
            al.old_value, al.new_value,
            DATE_FORMAT(al.created_at, '%Y-%m-%dT%H:%i:%sZ') AS created_at
          FROM audit_logs al
          JOIN users u ON u.id = al.user_id
          WHERE al.workspace_id = v_workspace_id
            AND (v_entity_type IS NULL OR al.entity_type = v_entity_type)
            AND (v_user_id IS NULL OR al.user_id = v_user_id)
          ORDER BY al.created_at DESC
          LIMIT v_per_page OFFSET v_offset
        ) t
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

-- ---------------------------------------------------------------------------
-- M-04 Categorías
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_category_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'id', t.id, 'name', t.name, 'icon', t.icon, 'color', t.color,
        'is_default', (t.is_default = 1), 'expenses_count', t.expenses_count
      ))
      FROM (
        SELECT c.id, c.name, c.icon, c.color, c.is_default, COUNT(e.id) AS expenses_count
        FROM categories c
        LEFT JOIN expenses e ON e.category_id = c.id
        WHERE c.workspace_id = v_workspace_id
        GROUP BY c.id, c.name, c.icon, c.color, c.is_default
        ORDER BY c.name
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_category_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_category_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_category_id = CAST(JSON_EXTRACT(p_json, '$.category_id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM categories WHERE id = v_category_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', c.id, 'name', c.name, 'icon', c.icon, 'color', c.color, 'is_default', (c.is_default = 1),
        'expenses_count', (SELECT COUNT(*) FROM expenses e WHERE e.category_id = c.id)
      )
    ) AS result
    FROM categories c
    WHERE c.id = v_category_id AND c.workspace_id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_category_create(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(100);
  DECLARE v_icon VARCHAR(50);
  DECLARE v_color VARCHAR(20);
  DECLARE v_id BIGINT UNSIGNED;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR 1062
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'DUPLICATE') AS result;
  END;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_name = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.name'));
  SET v_icon = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.icon'));
  SET v_color = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.color'));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  START TRANSACTION;

  INSERT INTO categories (workspace_id, name, icon, color, is_default)
  VALUES (v_workspace_id, v_name, v_icon, v_color, FALSE);
  SET v_id = LAST_INSERT_ID();

  INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
  VALUES (
    v_workspace_id, v_audit_user_id, 'category', v_id, 'created', v_audit_summary,
    NULL, JSON_OBJECT('name', v_name, 'icon', v_icon, 'color', v_color),
    v_audit_ip, v_audit_ua
  );

  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'id', v_id, 'name', v_name, 'icon', v_icon, 'color', v_color,
      'is_default', FALSE, 'expenses_count', 0
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_category_update(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_category_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(100);
  DECLARE v_icon VARCHAR(50);
  DECLARE v_color VARCHAR(20);
  DECLARE v_is_default BOOLEAN;
  DECLARE v_old_name VARCHAR(100);
  DECLARE v_old_icon VARCHAR(50);
  DECLARE v_old_color VARCHAR(20);
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR 1062
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'DUPLICATE') AS result;
  END;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_category_id = CAST(JSON_EXTRACT(p_json, '$.category_id') AS UNSIGNED);
  SET v_name = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.name'));
  SET v_icon = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.icon'));
  SET v_color = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.color'));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  SELECT name, icon, color, is_default INTO v_old_name, v_old_icon, v_old_color, v_is_default
  FROM categories WHERE id = v_category_id AND workspace_id = v_workspace_id FOR UPDATE;

  IF v_old_name IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;

    UPDATE categories SET name = v_name, icon = v_icon, color = v_color
    WHERE id = v_category_id AND workspace_id = v_workspace_id;

    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
    VALUES (
      v_workspace_id, v_audit_user_id, 'category', v_category_id, 'updated', v_audit_summary,
      JSON_OBJECT('name', v_old_name, 'icon', v_old_icon, 'color', v_old_color),
      JSON_OBJECT('name', v_name, 'icon', v_icon, 'color', v_color),
      v_audit_ip, v_audit_ua
    );

    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', v_category_id, 'name', v_name, 'icon', v_icon, 'color', v_color,
        'is_default', (v_is_default = 1),
        'expenses_count', (SELECT COUNT(*) FROM expenses WHERE category_id = v_category_id)
      )
    ) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_category_delete(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_category_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(100);
  DECLARE v_icon VARCHAR(50);
  DECLARE v_color VARCHAR(20);
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_category_id = CAST(JSON_EXTRACT(p_json, '$.category_id') AS UNSIGNED);
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  SELECT name, icon, color INTO v_name, v_icon, v_color
  FROM categories WHERE id = v_category_id AND workspace_id = v_workspace_id FOR UPDATE;

  IF v_name IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;

    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
    VALUES (
      v_workspace_id, v_audit_user_id, 'category', v_category_id, 'deleted', v_audit_summary,
      JSON_OBJECT('name', v_name, 'icon', v_icon, 'color', v_color), NULL,
      v_audit_ip, v_audit_ua
    );

    DELETE FROM categories WHERE id = v_category_id AND workspace_id = v_workspace_id;

    COMMIT;

    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

-- ---------------------------------------------------------------------------
-- Soporte transversal - ClosingGuard (usado desde M-05 en adelante)
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_monthly_closing_exists(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT('exists', EXISTS(
      SELECT 1 FROM monthly_closings WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month
    ))
  ) AS result;
END$$

-- ---------------------------------------------------------------------------
-- M-05 Ingresos incrementales
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_income_entry_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'total', COALESCE((
        SELECT SUM(amount) FROM income_entries
        WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month
      ), 0),
      'entries', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', t.id, 'user_id', t.user_id, 'user_name', t.user_name, 'amount', t.amount,
          'concept', t.concept, 'date', t.date, 'year', t.year, 'month', t.month,
          'source', t.source, 'created_at', t.created_at
        ))
        FROM (
          SELECT
            ie.id, ie.user_id, u.name AS user_name, ie.amount, ie.concept,
            DATE_FORMAT(ie.date, '%Y-%m-%d') AS date, ie.year, ie.month, ie.source,
            DATE_FORMAT(ie.created_at, '%Y-%m-%dT%H:%i:%sZ') AS created_at
          FROM income_entries ie
          JOIN users u ON u.id = ie.user_id
          WHERE ie.workspace_id = v_workspace_id AND ie.year = v_year AND ie.month = v_month
          ORDER BY ie.date DESC, ie.id DESC
        ) t
      ), JSON_ARRAY()),
      'by_user', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT('user_id', g.user_id, 'user_name', g.user_name, 'total', g.total))
        FROM (
          SELECT ie.user_id, u.name AS user_name, SUM(ie.amount) AS total
          FROM income_entries ie
          JOIN users u ON u.id = ie.user_id
          WHERE ie.workspace_id = v_workspace_id AND ie.year = v_year AND ie.month = v_month
          GROUP BY ie.user_id, u.name
          ORDER BY total DESC
        ) g
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

-- Sin filtro de year/month: usado por el export unico del Dashboard (M-16),
-- trae TODO el historico. sp_income_entry_list sigue siendo el que usa la
-- pagina de Ingresos (requiere periodo puntual).
-- Depende de income_entries.source (agregada por la feature de bloqueo de
-- ingresos de cierre) - mergear esa rama antes de aplicar este SP.
CREATE PROCEDURE sp_income_entry_list_all(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'entries', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', t.id, 'user_id', t.user_id, 'user_name', t.user_name, 'amount', t.amount,
          'concept', t.concept, 'date', t.date, 'year', t.year, 'month', t.month,
          'source', t.source, 'created_at', t.created_at
        ))
        FROM (
          SELECT
            ie.id, ie.user_id, u.name AS user_name, ie.amount, ie.concept,
            DATE_FORMAT(ie.date, '%Y-%m-%d') AS date, ie.year, ie.month, ie.source,
            DATE_FORMAT(ie.created_at, '%Y-%m-%dT%H:%i:%sZ') AS created_at
          FROM income_entries ie
          JOIN users u ON u.id = ie.user_id
          WHERE ie.workspace_id = v_workspace_id
          ORDER BY ie.date DESC, ie.id DESC
        ) t
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_income_entry_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_income_entry_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_income_entry_id = CAST(JSON_EXTRACT(p_json, '$.income_entry_id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM income_entries WHERE id = v_income_entry_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', ie.id, 'user_id', ie.user_id, 'user_name', u.name, 'amount', ie.amount,
        'concept', ie.concept, 'date', DATE_FORMAT(ie.date, '%Y-%m-%d'), 'year', ie.year, 'month', ie.month,
        'source', ie.source, 'created_at', DATE_FORMAT(ie.created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM income_entries ie
    JOIN users u ON u.id = ie.user_id
    WHERE ie.id = v_income_entry_id AND ie.workspace_id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_income_entry_create(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_concept VARCHAR(150);
  DECLARE v_date DATE;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_amount = CAST(JSON_EXTRACT(p_json, '$.amount') AS DECIMAL(14,2));
  SET v_concept = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.concept'));
  SET v_date = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.date'));
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);

  START TRANSACTION;
  INSERT INTO income_entries (workspace_id, user_id, amount, concept, date, year, month)
  VALUES (v_workspace_id, v_user_id, v_amount, v_concept, v_date, v_year, v_month);
  SET v_id = LAST_INSERT_ID();
  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'id', v_id, 'user_id', v_user_id, 'user_name', (SELECT name FROM users WHERE id = v_user_id),
      'amount', v_amount, 'concept', v_concept, 'date', DATE_FORMAT(v_date, '%Y-%m-%d'),
      'year', v_year, 'month', v_month, 'created_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_income_entry_update(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_income_entry_id BIGINT UNSIGNED;
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_concept VARCHAR(150);
  DECLARE v_date DATE;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_income_entry_id = CAST(JSON_EXTRACT(p_json, '$.income_entry_id') AS UNSIGNED);
  SET v_amount = CAST(JSON_EXTRACT(p_json, '$.amount') AS DECIMAL(14,2));
  SET v_concept = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.concept'));
  SET v_date = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.date'));
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);

  SELECT user_id INTO v_user_id FROM income_entries WHERE id = v_income_entry_id AND workspace_id = v_workspace_id;

  IF v_user_id IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    UPDATE income_entries SET amount = v_amount, concept = v_concept, date = v_date, year = v_year, month = v_month
    WHERE id = v_income_entry_id AND workspace_id = v_workspace_id;
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', v_income_entry_id, 'user_id', v_user_id, 'user_name', (SELECT name FROM users WHERE id = v_user_id),
        'amount', v_amount, 'concept', v_concept, 'date', DATE_FORMAT(v_date, '%Y-%m-%d'),
        'year', v_year, 'month', v_month,
        'created_at', (SELECT DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ') FROM income_entries WHERE id = v_income_entry_id)
      )
    ) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_income_entry_delete(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_income_entry_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_income_entry_id = CAST(JSON_EXTRACT(p_json, '$.income_entry_id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM income_entries WHERE id = v_income_entry_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    DELETE FROM income_entries WHERE id = v_income_entry_id AND workspace_id = v_workspace_id;
    COMMIT;

    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

-- ---------------------------------------------------------------------------
-- M-06 Gastos
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_expense_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_page INT;
  DECLARE v_per_page INT;
  DECLARE v_offset INT;
  DECLARE v_date_from DATE;
  DECLARE v_date_to DATE;
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_payment_method VARCHAR(20);
  DECLARE v_amount_min DECIMAL(14,2);
  DECLARE v_amount_max DECIMAL(14,2);
  DECLARE v_search VARCHAR(120);
  DECLARE v_sort VARCHAR(20);
  DECLARE v_order VARCHAR(4);
  DECLARE v_category_ids JSON;
  DECLARE v_has_category_filter BOOLEAN;
  DECLARE v_total INT;
  DECLARE v_total_amount DECIMAL(14,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_page = CAST(JSON_EXTRACT(p_json, '$.page') AS UNSIGNED);
  SET v_per_page = CAST(JSON_EXTRACT(p_json, '$.per_page') AS UNSIGNED);
  SET v_offset = (v_page - 1) * v_per_page;
  SET v_date_from = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.date_from')) = 'NULL' OR JSON_EXTRACT(p_json, '$.date_from') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.date_from')));
  SET v_date_to = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.date_to')) = 'NULL' OR JSON_EXTRACT(p_json, '$.date_to') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.date_to')));
  SET v_user_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.user_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.user_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED));
  SET v_payment_method = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.payment_method')) = 'NULL' OR JSON_EXTRACT(p_json, '$.payment_method') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.payment_method')));
  SET v_amount_min = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.amount_min')) = 'NULL' OR JSON_EXTRACT(p_json, '$.amount_min') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.amount_min') AS DECIMAL(14,2)));
  SET v_amount_max = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.amount_max')) = 'NULL' OR JSON_EXTRACT(p_json, '$.amount_max') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.amount_max') AS DECIMAL(14,2)));
  SET v_search = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.search')) = 'NULL' OR JSON_EXTRACT(p_json, '$.search') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.search')));
  SET v_search = IF(v_search IS NULL, NULL, REPLACE(REPLACE(v_search, '%', '\\%'), '_', '\\_'));
  SET v_sort = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.sort'));
  SET v_order = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.order'));
  SET v_category_ids = JSON_EXTRACT(p_json, '$.category_ids');
  SET v_has_category_filter = (v_category_ids IS NOT NULL AND JSON_LENGTH(v_category_ids) > 0);

  SELECT COUNT(*), COALESCE(SUM(e.amount), 0) INTO v_total, v_total_amount
  FROM expenses e
  WHERE e.workspace_id = v_workspace_id
    AND (v_date_from IS NULL OR e.date >= v_date_from)
    AND (v_date_to IS NULL OR e.date <= v_date_to)
    AND (v_user_id IS NULL OR e.user_id = v_user_id)
    AND (v_payment_method IS NULL OR e.payment_method = v_payment_method)
    AND (v_amount_min IS NULL OR e.amount >= v_amount_min)
    AND (v_amount_max IS NULL OR e.amount <= v_amount_max)
    AND (v_search IS NULL OR e.description LIKE CONCAT('%', v_search, '%') ESCAPE '\\')
    AND (NOT v_has_category_filter OR JSON_CONTAINS(v_category_ids, CAST(IFNULL(e.category_id, 0) AS JSON)));

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'total', v_total,
      'total_amount', v_total_amount,
      'items', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', t.id, 'user_id', t.user_id, 'user_name', t.user_name,
          'category_id', t.category_id, 'category_name', t.category_name,
          'category_icon', t.category_icon, 'category_color', t.category_color,
          'amount', t.amount, 'description', t.description, 'payment_method', t.payment_method,
          'paid_by_user_id', t.paid_by_user_id, 'paid_by_user_name', t.paid_by_user_name,
          'date', t.date, 'created_at', t.created_at
        ))
        FROM (
          SELECT
            e.id, e.user_id, u.name AS user_name,
            e.category_id, c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
            e.amount, e.description, e.payment_method,
            e.paid_by_user_id, pu.name AS paid_by_user_name,
            DATE_FORMAT(e.date, '%Y-%m-%d') AS date,
            DATE_FORMAT(e.created_at, '%Y-%m-%dT%H:%i:%sZ') AS created_at
          FROM expenses e
          JOIN users u ON u.id = e.user_id
          LEFT JOIN categories c ON c.id = e.category_id
          LEFT JOIN users pu ON pu.id = e.paid_by_user_id
          WHERE e.workspace_id = v_workspace_id
            AND (v_date_from IS NULL OR e.date >= v_date_from)
            AND (v_date_to IS NULL OR e.date <= v_date_to)
            AND (v_user_id IS NULL OR e.user_id = v_user_id)
            AND (v_payment_method IS NULL OR e.payment_method = v_payment_method)
            AND (v_amount_min IS NULL OR e.amount >= v_amount_min)
            AND (v_amount_max IS NULL OR e.amount <= v_amount_max)
            AND (v_search IS NULL OR e.description LIKE CONCAT('%', v_search, '%') ESCAPE '\\')
            AND (NOT v_has_category_filter OR JSON_CONTAINS(v_category_ids, CAST(IFNULL(e.category_id, 0) AS JSON)))
          ORDER BY
            CASE WHEN v_sort = 'amount' AND v_order = 'asc' THEN e.amount END ASC,
            CASE WHEN v_sort = 'amount' AND v_order != 'asc' THEN e.amount END DESC,
            CASE WHEN v_sort = 'created_at' AND v_order = 'asc' THEN e.created_at END ASC,
            CASE WHEN v_sort = 'created_at' AND v_order != 'asc' THEN e.created_at END DESC,
            CASE WHEN (v_sort IS NULL OR v_sort NOT IN ('amount', 'created_at')) AND v_order = 'asc' THEN e.date END ASC,
            CASE WHEN (v_sort IS NULL OR v_sort NOT IN ('amount', 'created_at')) AND v_order != 'asc' THEN e.date END DESC,
            e.id DESC
          LIMIT v_per_page OFFSET v_offset
        ) t
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_expense_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_expense_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_expense_id = CAST(JSON_EXTRACT(p_json, '$.expense_id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM expenses WHERE id = v_expense_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', e.id, 'user_id', e.user_id, 'user_name', u.name,
        'category_id', e.category_id, 'category_name', c.name, 'category_icon', c.icon, 'category_color', c.color,
        'amount', e.amount, 'description', e.description, 'payment_method', e.payment_method,
        'paid_by_user_id', e.paid_by_user_id, 'paid_by_user_name', pu.name,
        'date', DATE_FORMAT(e.date, '%Y-%m-%d'), 'created_at', DATE_FORMAT(e.created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM expenses e
    JOIN users u ON u.id = e.user_id
    LEFT JOIN categories c ON c.id = e.category_id
    LEFT JOIN users pu ON pu.id = e.paid_by_user_id
    WHERE e.id = v_expense_id AND e.workspace_id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_expense_create(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_category_id BIGINT UNSIGNED;
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_description VARCHAR(255);
  DECLARE v_payment_method VARCHAR(20);
  DECLARE v_date DATE;
  DECLARE v_paid_by_user_id BIGINT UNSIGNED;
  DECLARE v_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_category_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.category_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.category_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.category_id') AS UNSIGNED));
  SET v_amount = CAST(JSON_EXTRACT(p_json, '$.amount') AS DECIMAL(14,2));
  SET v_description = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.description')) = 'NULL' OR JSON_EXTRACT(p_json, '$.description') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.description')));
  SET v_payment_method = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.payment_method'));
  SET v_date = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.date'));
  SET v_paid_by_user_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.paid_by_user_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.paid_by_user_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.paid_by_user_id') AS UNSIGNED));

  IF v_category_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categories WHERE id = v_category_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'CATEGORY_NOT_IN_WORKSPACE') AS result;
  ELSEIF v_paid_by_user_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM workspace_users WHERE user_id = v_paid_by_user_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'PAYER_NOT_IN_WORKSPACE') AS result;
  ELSE
    START TRANSACTION;
    INSERT INTO expenses (workspace_id, user_id, category_id, amount, description, payment_method, date, paid_by_user_id)
    VALUES (v_workspace_id, v_user_id, v_category_id, v_amount, v_description, v_payment_method, v_date, v_paid_by_user_id);
    SET v_id = LAST_INSERT_ID();
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', v_id, 'user_id', v_user_id, 'user_name', (SELECT name FROM users WHERE id = v_user_id),
        'category_id', v_category_id,
        'category_name', (SELECT name FROM categories WHERE id = v_category_id),
        'category_icon', (SELECT icon FROM categories WHERE id = v_category_id),
        'category_color', (SELECT color FROM categories WHERE id = v_category_id),
        'amount', v_amount, 'description', v_description, 'payment_method', v_payment_method,
        'paid_by_user_id', v_paid_by_user_id, 'paid_by_user_name', (SELECT name FROM users WHERE id = v_paid_by_user_id),
        'date', DATE_FORMAT(v_date, '%Y-%m-%d'), 'created_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_expense_update(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_expense_id BIGINT UNSIGNED;
  DECLARE v_category_id BIGINT UNSIGNED;
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_description VARCHAR(255);
  DECLARE v_payment_method VARCHAR(20);
  DECLARE v_date DATE;
  DECLARE v_paid_by_user_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_expense_id = CAST(JSON_EXTRACT(p_json, '$.expense_id') AS UNSIGNED);
  SET v_category_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.category_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.category_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.category_id') AS UNSIGNED));
  SET v_amount = CAST(JSON_EXTRACT(p_json, '$.amount') AS DECIMAL(14,2));
  SET v_description = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.description')) = 'NULL' OR JSON_EXTRACT(p_json, '$.description') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.description')));
  SET v_payment_method = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.payment_method'));
  SET v_date = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.date'));
  SET v_paid_by_user_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.paid_by_user_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.paid_by_user_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.paid_by_user_id') AS UNSIGNED));

  SELECT user_id INTO v_user_id FROM expenses WHERE id = v_expense_id AND workspace_id = v_workspace_id;

  IF v_user_id IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_category_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categories WHERE id = v_category_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'CATEGORY_NOT_IN_WORKSPACE') AS result;
  ELSEIF v_paid_by_user_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM workspace_users WHERE user_id = v_paid_by_user_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'PAYER_NOT_IN_WORKSPACE') AS result;
  ELSE
    START TRANSACTION;
    UPDATE expenses
    SET category_id = v_category_id, amount = v_amount, description = v_description,
        payment_method = v_payment_method, date = v_date, paid_by_user_id = v_paid_by_user_id
    WHERE id = v_expense_id AND workspace_id = v_workspace_id;
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', v_expense_id, 'user_id', v_user_id, 'user_name', (SELECT name FROM users WHERE id = v_user_id),
        'category_id', v_category_id,
        'category_name', (SELECT name FROM categories WHERE id = v_category_id),
        'category_icon', (SELECT icon FROM categories WHERE id = v_category_id),
        'category_color', (SELECT color FROM categories WHERE id = v_category_id),
        'amount', v_amount, 'description', v_description, 'payment_method', v_payment_method,
        'paid_by_user_id', v_paid_by_user_id, 'paid_by_user_name', (SELECT name FROM users WHERE id = v_paid_by_user_id),
        'date', DATE_FORMAT(v_date, '%Y-%m-%d'),
        'created_at', (SELECT DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ') FROM expenses WHERE id = v_expense_id)
      )
    ) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_expense_delete(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_expense_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_expense_id = CAST(JSON_EXTRACT(p_json, '$.expense_id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM expenses WHERE id = v_expense_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    DELETE FROM expenses WHERE id = v_expense_id AND workspace_id = v_workspace_id;
    COMMIT;

    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_expense_payment_methods(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(t.payment_method)
      FROM (
        SELECT DISTINCT payment_method FROM expenses WHERE workspace_id = v_workspace_id ORDER BY payment_method
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

-- ---------------------------------------------------------------------------
-- M-08 Servicios recurrentes y pagos mensuales
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_service_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_active VARCHAR(10);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_active = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.active'));

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'id', t.id, 'name', t.name, 'amount', t.amount, 'is_estimated', (t.is_estimated = 1),
        'due_day_start', t.due_day_start, 'due_day_end', t.due_day_end,
        'late_fee_type', t.late_fee_type, 'late_fee_value', t.late_fee_value,
        'active', (t.active = 1),
        'created_at', DATE_FORMAT(t.created_at, '%Y-%m-%dT%H:%i:%sZ')
      ))
      FROM (
        SELECT id, name, amount, is_estimated, due_day_start, due_day_end, late_fee_type, late_fee_value, active, created_at
        FROM services
        WHERE workspace_id = v_workspace_id
          AND (v_active = 'all' OR active = (v_active = 'true'))
        ORDER BY name
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_service_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_service_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_service_id = CAST(JSON_EXTRACT(p_json, '$.service_id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM services WHERE id = v_service_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', s.id, 'name', s.name, 'amount', s.amount, 'is_estimated', (s.is_estimated = 1),
        'due_day_start', s.due_day_start, 'due_day_end', s.due_day_end,
        'late_fee_type', s.late_fee_type, 'late_fee_value', s.late_fee_value,
        'active', (s.active = 1),
        'created_at', DATE_FORMAT(s.created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM services s
    WHERE s.id = v_service_id AND s.workspace_id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_service_create(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(150);
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_is_estimated BOOLEAN;
  DECLARE v_due_day_start TINYINT UNSIGNED;
  DECLARE v_due_day_end TINYINT UNSIGNED;
  DECLARE v_late_fee_type VARCHAR(10);
  DECLARE v_late_fee_value DECIMAL(14,2);
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_id BIGINT UNSIGNED;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_name = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.name'));
  SET v_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.amount')) AS DECIMAL(14,2));
  SET v_is_estimated = CAST(JSON_EXTRACT(p_json, '$.is_estimated') AS UNSIGNED);
  SET v_due_day_start = CAST(JSON_EXTRACT(p_json, '$.due_day_start') AS UNSIGNED);
  SET v_due_day_end = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.due_day_end')) = 'NULL' OR JSON_EXTRACT(p_json, '$.due_day_end') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.due_day_end') AS UNSIGNED));
  SET v_late_fee_type = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.late_fee_type'));
  SET v_late_fee_value = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.late_fee_value')) AS DECIMAL(14,2));
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  START TRANSACTION;

  INSERT INTO services (workspace_id, name, amount, due_day_start, due_day_end, late_fee_type, late_fee_value, is_estimated, active)
  VALUES (v_workspace_id, v_name, v_amount, v_due_day_start, v_due_day_end, v_late_fee_type, v_late_fee_value, v_is_estimated, TRUE);
  SET v_id = LAST_INSERT_ID();

  INSERT INTO service_payments (service_id, workspace_id, month, year, status)
  VALUES (v_id, v_workspace_id, v_month, v_year, 'pending');

  INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
  VALUES (
    v_workspace_id, v_audit_user_id, 'service', v_id, 'created', v_audit_summary,
    NULL, JSON_OBJECT(
      'name', v_name, 'amount', v_amount, 'is_estimated', (v_is_estimated = 1),
      'due_day_start', v_due_day_start, 'due_day_end', v_due_day_end,
      'late_fee_type', v_late_fee_type, 'late_fee_value', v_late_fee_value
    ),
    v_audit_ip, v_audit_ua
  );

  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'id', v_id, 'name', v_name, 'amount', v_amount, 'is_estimated', (v_is_estimated = 1),
      'due_day_start', v_due_day_start, 'due_day_end', v_due_day_end,
      'late_fee_type', v_late_fee_type, 'late_fee_value', v_late_fee_value, 'active', TRUE,
      'created_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_service_update(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_service_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(150);
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_is_estimated BOOLEAN;
  DECLARE v_due_day_start TINYINT UNSIGNED;
  DECLARE v_due_day_end TINYINT UNSIGNED;
  DECLARE v_late_fee_type VARCHAR(10);
  DECLARE v_late_fee_value DECIMAL(14,2);
  DECLARE v_active BOOLEAN;
  DECLARE v_old_name VARCHAR(150);
  DECLARE v_old_amount DECIMAL(14,2);
  DECLARE v_old_is_estimated BOOLEAN;
  DECLARE v_old_due_day_start TINYINT UNSIGNED;
  DECLARE v_old_due_day_end TINYINT UNSIGNED;
  DECLARE v_old_late_fee_type VARCHAR(10);
  DECLARE v_old_late_fee_value DECIMAL(14,2);
  DECLARE v_old_active BOOLEAN;
  DECLARE v_created_at TIMESTAMP;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_service_id = CAST(JSON_EXTRACT(p_json, '$.service_id') AS UNSIGNED);
  SET v_name = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.name'));
  SET v_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.amount')) AS DECIMAL(14,2));
  SET v_is_estimated = CAST(JSON_EXTRACT(p_json, '$.is_estimated') AS UNSIGNED);
  SET v_due_day_start = CAST(JSON_EXTRACT(p_json, '$.due_day_start') AS UNSIGNED);
  SET v_due_day_end = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.due_day_end')) = 'NULL' OR JSON_EXTRACT(p_json, '$.due_day_end') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.due_day_end') AS UNSIGNED));
  SET v_late_fee_type = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.late_fee_type'));
  SET v_late_fee_value = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.late_fee_value')) AS DECIMAL(14,2));
  SET v_active = CAST(JSON_EXTRACT(p_json, '$.active') AS UNSIGNED);
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  SELECT name, amount, is_estimated, due_day_start, due_day_end, late_fee_type, late_fee_value, active, created_at
  INTO v_old_name, v_old_amount, v_old_is_estimated, v_old_due_day_start, v_old_due_day_end, v_old_late_fee_type, v_old_late_fee_value, v_old_active, v_created_at
  FROM services WHERE id = v_service_id AND workspace_id = v_workspace_id FOR UPDATE;

  IF v_old_name IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;

    UPDATE services SET
      name = v_name, amount = v_amount, is_estimated = v_is_estimated,
      due_day_start = v_due_day_start, due_day_end = v_due_day_end,
      late_fee_type = v_late_fee_type, late_fee_value = v_late_fee_value, active = v_active
    WHERE id = v_service_id AND workspace_id = v_workspace_id;

    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
    VALUES (
      v_workspace_id, v_audit_user_id, 'service', v_service_id, 'updated', v_audit_summary,
      JSON_OBJECT(
        'name', v_old_name, 'amount', v_old_amount, 'is_estimated', (v_old_is_estimated = 1),
        'due_day_start', v_old_due_day_start, 'due_day_end', v_old_due_day_end,
        'late_fee_type', v_old_late_fee_type, 'late_fee_value', v_old_late_fee_value, 'active', (v_old_active = 1)
      ),
      JSON_OBJECT(
        'name', v_name, 'amount', v_amount, 'is_estimated', (v_is_estimated = 1),
        'due_day_start', v_due_day_start, 'due_day_end', v_due_day_end,
        'late_fee_type', v_late_fee_type, 'late_fee_value', v_late_fee_value, 'active', (v_active = 1)
      ),
      v_audit_ip, v_audit_ua
    );

    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', v_service_id, 'name', v_name, 'amount', v_amount, 'is_estimated', (v_is_estimated = 1),
        'due_day_start', v_due_day_start, 'due_day_end', v_due_day_end,
        'late_fee_type', v_late_fee_type, 'late_fee_value', v_late_fee_value, 'active', (v_active = 1),
        'created_at', DATE_FORMAT(v_created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_service_delete(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_service_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(150);
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_active BOOLEAN;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_service_id = CAST(JSON_EXTRACT(p_json, '$.service_id') AS UNSIGNED);
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  SELECT name, amount, active INTO v_name, v_amount, v_active
  FROM services WHERE id = v_service_id AND workspace_id = v_workspace_id FOR UPDATE;

  IF v_name IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;

    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
    VALUES (
      v_workspace_id, v_audit_user_id, 'service', v_service_id, 'deleted', v_audit_summary,
      JSON_OBJECT('name', v_name, 'amount', v_amount, 'active', (v_active = 1)), NULL,
      v_audit_ip, v_audit_ua
    );

    DELETE FROM services WHERE id = v_service_id AND workspace_id = v_workspace_id;

    COMMIT;

    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_service_payment_ensure_period(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_created_count INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);

  START TRANSACTION;

  INSERT IGNORE INTO service_payments (service_id, workspace_id, month, year, status)
  SELECT s.id, s.workspace_id, v_month, v_year, 'pending'
  FROM services s
  WHERE s.workspace_id = v_workspace_id AND s.active = TRUE;

  SET v_created_count = ROW_COUNT();

  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('created_count', v_created_count)) AS result;
END$$

CREATE PROCEDURE sp_service_payment_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_days_in_month INT;
  DECLARE v_total_expected DECIMAL(14,2);
  DECLARE v_total_paid DECIMAL(14,2);
  DECLARE v_pending_count INT;
  DECLARE v_overdue_count INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_days_in_month = DAY(LAST_DAY(STR_TO_DATE(CONCAT(v_year, '-', v_month, '-01'), '%Y-%m-%d')));

  SELECT
    COALESCE(SUM(s.amount), 0),
    COALESCE(SUM(CASE WHEN sp.status = 'paid' THEN sp.amount_paid ELSE 0 END), 0),
    COALESCE(SUM(CASE WHEN sp.status = 'pending' THEN 1 ELSE 0 END), 0),
    COALESCE(SUM(CASE WHEN sp.status = 'overdue' THEN 1 ELSE 0 END), 0)
  INTO v_total_expected, v_total_paid, v_pending_count, v_overdue_count
  FROM service_payments sp
  JOIN services s ON s.id = sp.service_id
  WHERE sp.workspace_id = v_workspace_id AND sp.year = v_year AND sp.month = v_month;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'total_expected', v_total_expected,
      'total_paid', v_total_paid,
      'pending_count', v_pending_count,
      'overdue_count', v_overdue_count,
      'items', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', t.id, 'service_id', t.service_id, 'service_name', t.service_name,
          'year', t.year, 'month', t.month, 'expected_amount', t.expected_amount,
          'due_date_start', DATE_FORMAT(t.due_date_start, '%Y-%m-%d'),
          'due_date_end', DATE_FORMAT(t.due_date_end, '%Y-%m-%d'),
          'status', t.status, 'amount_paid', t.amount_paid, 'late_fee_applied', t.late_fee_applied,
          'paid_at', DATE_FORMAT(t.paid_at, '%Y-%m-%dT%H:%i:%sZ'),
          'was_late', (t.was_late = 1),
          'paid_by_user_id', t.paid_by_user_id, 'paid_by_name', t.paid_by_name, 'notes', t.notes,
          'days_until_due', IF(t.status = 'paid', NULL, DATEDIFF(t.due_date_start, CURDATE()))
        ))
        FROM (
          SELECT
            sp.id, sp.service_id, s.name AS service_name, sp.year, sp.month, s.amount AS expected_amount,
            STR_TO_DATE(CONCAT(v_year, '-', v_month, '-', LEAST(s.due_day_start, v_days_in_month)), '%Y-%m-%d') AS due_date_start,
            STR_TO_DATE(CONCAT(v_year, '-', v_month, '-', LEAST(COALESCE(s.due_day_end, s.due_day_start), v_days_in_month)), '%Y-%m-%d') AS due_date_end,
            sp.status, sp.amount_paid, sp.late_fee_applied, sp.paid_at, sp.was_late,
            sp.paid_by_user_id, u.name AS paid_by_name, sp.notes
          FROM service_payments sp
          JOIN services s ON s.id = sp.service_id
          LEFT JOIN users u ON u.id = sp.paid_by_user_id
          WHERE sp.workspace_id = v_workspace_id AND sp.year = v_year AND sp.month = v_month
          ORDER BY LEAST(s.due_day_start, v_days_in_month) ASC, sp.id ASC
        ) t
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

-- Sin filtro de year/month: usado por el export unico del Dashboard (M-16),
-- trae TODO el historico. sp_service_payment_list sigue siendo el que usa
-- la pagina de Servicios (requiere periodo puntual). due_date_start/end se
-- calculan por fila usando el year/month propio de cada pago (no un unico
-- periodo global como en sp_service_payment_list).
CREATE PROCEDURE sp_service_payment_list_all(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'items', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', t.id, 'service_id', t.service_id, 'service_name', t.service_name,
          'year', t.year, 'month', t.month, 'expected_amount', t.expected_amount,
          'due_date_start', DATE_FORMAT(t.due_date_start, '%Y-%m-%d'),
          'due_date_end', DATE_FORMAT(t.due_date_end, '%Y-%m-%d'),
          'status', t.status, 'amount_paid', t.amount_paid, 'late_fee_applied', t.late_fee_applied,
          'paid_at', DATE_FORMAT(t.paid_at, '%Y-%m-%dT%H:%i:%sZ'),
          'was_late', (t.was_late = 1),
          'paid_by_user_id', t.paid_by_user_id, 'paid_by_name', t.paid_by_name, 'notes', t.notes
        ))
        FROM (
          SELECT
            sp.id, sp.service_id, s.name AS service_name, sp.year, sp.month, s.amount AS expected_amount,
            STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-',
              LEAST(s.due_day_start, DAY(LAST_DAY(STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-01'), '%Y-%m-%d'))))
            ), '%Y-%m-%d') AS due_date_start,
            STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-',
              LEAST(COALESCE(s.due_day_end, s.due_day_start), DAY(LAST_DAY(STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-01'), '%Y-%m-%d'))))
            ), '%Y-%m-%d') AS due_date_end,
            sp.status, sp.amount_paid, sp.late_fee_applied, sp.paid_at, sp.was_late,
            sp.paid_by_user_id, u.name AS paid_by_name, sp.notes
          FROM service_payments sp
          JOIN services s ON s.id = sp.service_id
          LEFT JOIN users u ON u.id = sp.paid_by_user_id
          WHERE sp.workspace_id = v_workspace_id
          ORDER BY sp.year DESC, sp.month DESC, sp.id ASC
        ) t
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_service_payment_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_service_payment_id BIGINT UNSIGNED;
  DECLARE v_days_in_month INT;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_service_payment_id = CAST(JSON_EXTRACT(p_json, '$.service_payment_id') AS UNSIGNED);

  SELECT year, month INTO v_year, v_month
  FROM service_payments WHERE id = v_service_payment_id AND workspace_id = v_workspace_id;

  IF v_year IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SET v_days_in_month = DAY(LAST_DAY(STR_TO_DATE(CONCAT(v_year, '-', v_month, '-01'), '%Y-%m-%d')));

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', t.id, 'service_id', t.service_id, 'service_name', t.service_name,
        'year', t.year, 'month', t.month, 'expected_amount', t.expected_amount,
        'due_date_start', DATE_FORMAT(t.due_date_start, '%Y-%m-%d'),
        'due_date_end', DATE_FORMAT(t.due_date_end, '%Y-%m-%d'),
        'status', t.status, 'amount_paid', t.amount_paid, 'late_fee_applied', t.late_fee_applied,
        'paid_at', DATE_FORMAT(t.paid_at, '%Y-%m-%dT%H:%i:%sZ'),
        'was_late', (t.was_late = 1),
        'paid_by_user_id', t.paid_by_user_id, 'paid_by_name', t.paid_by_name, 'notes', t.notes,
        'days_until_due', IF(t.status = 'paid', NULL, DATEDIFF(t.due_date_start, CURDATE())),
        'late_fee_type', t.late_fee_type, 'late_fee_value', t.late_fee_value, 'service_amount', t.expected_amount
      )
    ) AS result
    FROM (
      SELECT
        sp.id, sp.service_id, s.name AS service_name, sp.year, sp.month, s.amount AS expected_amount,
        STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-', LEAST(s.due_day_start, v_days_in_month)), '%Y-%m-%d') AS due_date_start,
        STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-', LEAST(COALESCE(s.due_day_end, s.due_day_start), v_days_in_month)), '%Y-%m-%d') AS due_date_end,
        sp.status, sp.amount_paid, sp.late_fee_applied, sp.paid_at, sp.was_late,
        sp.paid_by_user_id, u.name AS paid_by_name, sp.notes,
        s.late_fee_type, s.late_fee_value
      FROM service_payments sp
      JOIN services s ON s.id = sp.service_id
      LEFT JOIN users u ON u.id = sp.paid_by_user_id
      WHERE sp.id = v_service_payment_id AND sp.workspace_id = v_workspace_id
    ) t;
  END IF;
END$$

CREATE PROCEDURE sp_service_payment_pay(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_service_payment_id BIGINT UNSIGNED;
  DECLARE v_amount_paid DECIMAL(14,2);
  DECLARE v_late_fee_applied DECIMAL(14,2);
  DECLARE v_paid_at DATE;
  DECLARE v_was_late BOOLEAN;
  DECLARE v_paid_by_user_id BIGINT UNSIGNED;
  DECLARE v_notes VARCHAR(255);
  DECLARE v_status VARCHAR(20);
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_days_in_month INT;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_service_payment_id = CAST(JSON_EXTRACT(p_json, '$.service_payment_id') AS UNSIGNED);
  SET v_amount_paid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.amount_paid')) AS DECIMAL(14,2));
  SET v_late_fee_applied = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.late_fee_applied')) AS DECIMAL(14,2));
  SET v_paid_at = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.paid_at')) AS DATE);
  SET v_was_late = CAST(JSON_EXTRACT(p_json, '$.was_late') AS UNSIGNED);
  SET v_paid_by_user_id = CAST(JSON_EXTRACT(p_json, '$.paid_by_user_id') AS UNSIGNED);
  SET v_notes = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.notes')) = 'NULL' OR JSON_EXTRACT(p_json, '$.notes') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.notes')));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  -- START TRANSACTION antes del SELECT FOR UPDATE (a diferencia de otros SPs de
  -- este archivo) para que el lock de fila se mantenga hasta el COMMIT: dos
  -- pagos simultaneos del mismo service_payment deben serializarse de verdad.
  START TRANSACTION;

  SELECT status, year, month INTO v_status, v_year, v_month FROM service_payments
  WHERE id = v_service_payment_id AND workspace_id = v_workspace_id FOR UPDATE;

  IF v_status IS NULL THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_status = 'paid' THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSE
    UPDATE service_payments SET
      status = 'paid', amount_paid = v_amount_paid, late_fee_applied = v_late_fee_applied,
      paid_at = v_paid_at, was_late = v_was_late, paid_by_user_id = v_paid_by_user_id, notes = v_notes
    WHERE id = v_service_payment_id AND workspace_id = v_workspace_id;

    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
    VALUES (
      v_workspace_id, v_audit_user_id, 'service_payment', v_service_payment_id, 'paid', v_audit_summary,
      NULL,
      JSON_OBJECT('amount_paid', v_amount_paid, 'late_fee_applied', v_late_fee_applied, 'was_late', (v_was_late = 1), 'paid_at', v_paid_at),
      v_audit_ip, v_audit_ua
    );

    COMMIT;

    SET v_days_in_month = DAY(LAST_DAY(STR_TO_DATE(CONCAT(v_year, '-', v_month, '-01'), '%Y-%m-%d')));

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', t.id, 'service_id', t.service_id, 'service_name', t.service_name,
        'year', t.year, 'month', t.month, 'expected_amount', t.expected_amount,
        'due_date_start', DATE_FORMAT(t.due_date_start, '%Y-%m-%d'),
        'due_date_end', DATE_FORMAT(t.due_date_end, '%Y-%m-%d'),
        'status', t.status, 'amount_paid', t.amount_paid, 'late_fee_applied', t.late_fee_applied,
        'paid_at', DATE_FORMAT(t.paid_at, '%Y-%m-%dT%H:%i:%sZ'),
        'was_late', (t.was_late = 1),
        'paid_by_user_id', t.paid_by_user_id, 'paid_by_name', t.paid_by_name, 'notes', t.notes,
        'days_until_due', IF(t.status = 'paid', NULL, DATEDIFF(t.due_date_start, CURDATE()))
      )
    ) AS result
    FROM (
      SELECT
        sp.id, sp.service_id, s.name AS service_name, sp.year, sp.month, s.amount AS expected_amount,
        STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-', LEAST(s.due_day_start, v_days_in_month)), '%Y-%m-%d') AS due_date_start,
        STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-', LEAST(COALESCE(s.due_day_end, s.due_day_start), v_days_in_month)), '%Y-%m-%d') AS due_date_end,
        sp.status, sp.amount_paid, sp.late_fee_applied, sp.paid_at, sp.was_late,
        sp.paid_by_user_id, u.name AS paid_by_name, sp.notes
      FROM service_payments sp
      JOIN services s ON s.id = sp.service_id
      LEFT JOIN users u ON u.id = sp.paid_by_user_id
      WHERE sp.id = v_service_payment_id AND sp.workspace_id = v_workspace_id
    ) t;
  END IF;
END$$

CREATE PROCEDURE sp_service_payment_unpay(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_service_payment_id BIGINT UNSIGNED;
  DECLARE v_new_status VARCHAR(10);
  DECLARE v_status VARCHAR(20);
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_days_in_month INT;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_service_payment_id = CAST(JSON_EXTRACT(p_json, '$.service_payment_id') AS UNSIGNED);
  SET v_new_status = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.new_status'));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  START TRANSACTION;

  SELECT status, year, month INTO v_status, v_year, v_month FROM service_payments
  WHERE id = v_service_payment_id AND workspace_id = v_workspace_id FOR UPDATE;

  IF v_status IS NULL THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_status <> 'paid' THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSE
    UPDATE service_payments SET
      status = v_new_status, amount_paid = NULL, paid_at = NULL, was_late = FALSE,
      late_fee_applied = 0, paid_by_user_id = NULL
    WHERE id = v_service_payment_id AND workspace_id = v_workspace_id;

    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
    VALUES (
      v_workspace_id, v_audit_user_id, 'service_payment', v_service_payment_id, 'unpaid', v_audit_summary,
      NULL, JSON_OBJECT('status', v_new_status),
      v_audit_ip, v_audit_ua
    );

    COMMIT;

    SET v_days_in_month = DAY(LAST_DAY(STR_TO_DATE(CONCAT(v_year, '-', v_month, '-01'), '%Y-%m-%d')));

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', t.id, 'service_id', t.service_id, 'service_name', t.service_name,
        'year', t.year, 'month', t.month, 'expected_amount', t.expected_amount,
        'due_date_start', DATE_FORMAT(t.due_date_start, '%Y-%m-%d'),
        'due_date_end', DATE_FORMAT(t.due_date_end, '%Y-%m-%d'),
        'status', t.status, 'amount_paid', t.amount_paid, 'late_fee_applied', t.late_fee_applied,
        'paid_at', DATE_FORMAT(t.paid_at, '%Y-%m-%dT%H:%i:%sZ'),
        'was_late', (t.was_late = 1),
        'paid_by_user_id', t.paid_by_user_id, 'paid_by_name', t.paid_by_name, 'notes', t.notes,
        'days_until_due', DATEDIFF(t.due_date_start, CURDATE())
      )
    ) AS result
    FROM (
      SELECT
        sp.id, sp.service_id, s.name AS service_name, sp.year, sp.month, s.amount AS expected_amount,
        STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-', LEAST(s.due_day_start, v_days_in_month)), '%Y-%m-%d') AS due_date_start,
        STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-', LEAST(COALESCE(s.due_day_end, s.due_day_start), v_days_in_month)), '%Y-%m-%d') AS due_date_end,
        sp.status, sp.amount_paid, sp.late_fee_applied, sp.paid_at, sp.was_late,
        sp.paid_by_user_id, u.name AS paid_by_name, sp.notes
      FROM service_payments sp
      JOIN services s ON s.id = sp.service_id
      LEFT JOIN users u ON u.id = sp.paid_by_user_id
      WHERE sp.id = v_service_payment_id AND sp.workspace_id = v_workspace_id
    ) t;
  END IF;
END$$

CREATE PROCEDURE sp_service_payment_mark_overdue(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_today DATE;
  DECLARE v_updated_count INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.workspace_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.workspace_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED));
  SET v_today = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.today')) AS DATE);

  START TRANSACTION;

  UPDATE service_payments sp
  JOIN services s ON s.id = sp.service_id
  SET sp.status = 'overdue'
  WHERE sp.status = 'pending'
    AND (v_workspace_id IS NULL OR sp.workspace_id = v_workspace_id)
    AND STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-', LEAST(COALESCE(s.due_day_end, s.due_day_start), DAY(LAST_DAY(STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-01'), '%Y-%m-%d'))))), '%Y-%m-%d') < v_today;

  SET v_updated_count = ROW_COUNT();

  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('updated_count', v_updated_count)) AS result;
END$$

CREATE PROCEDURE sp_service_payment_list_overdue(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_updated_today_only TINYINT(1);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  -- Opcional (default false): filtra a las filas cuyo updated_at cae en el
  -- CURDATE() de MySQL. Lo usa ReminderService::sendOverdueDigest para saber
  -- que pasaron a 'overdue' HOY (recien lo hizo sp_service_payment_mark_overdue,
  -- que pisa updated_at via ON UPDATE CURRENT_TIMESTAMP) - antes ese filtro se
  -- hacia en PHP comparando contra CarbonImmutable::now(config('app.timezone')),
  -- que en un servidor con APP_TIMEZONE distinto de UTC (ej. America/Argentina/
  -- Buenos_Aires) y MySQL corriendo en UTC podia desalinearse cerca de la
  -- medianoche local: "hoy" en PHP y el CURDATE() que graba updated_at caian en
  -- dias distintos, y el digest de vencidos no notificaba a nadie ese dia.
  -- Filtrar con el mismo reloj (CURDATE() de MySQL) que graba la columna
  -- elimina esa comparacion cross-timezone.
  SET v_updated_today_only = COALESCE(CAST(JSON_EXTRACT(p_json, '$.updated_today_only') AS UNSIGNED), 0);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'id', t.id, 'service_id', t.service_id, 'service_name', t.service_name,
        'year', t.year, 'month', t.month, 'expected_amount', t.expected_amount,
        'due_date_start', DATE_FORMAT(t.due_date_start, '%Y-%m-%d'),
        'due_date_end', DATE_FORMAT(t.due_date_end, '%Y-%m-%d'),
        'status', t.status, 'amount_paid', t.amount_paid, 'late_fee_applied', t.late_fee_applied,
        'paid_at', DATE_FORMAT(t.paid_at, '%Y-%m-%dT%H:%i:%sZ'),
        'was_late', (t.was_late = 1),
        'paid_by_user_id', t.paid_by_user_id, 'paid_by_name', t.paid_by_name, 'notes', t.notes,
        'days_until_due', DATEDIFF(t.due_date_start, CURDATE()),
        'updated_at', DATE_FORMAT(t.updated_at, '%Y-%m-%dT%H:%i:%sZ')
      ))
      FROM (
        SELECT
          sp.id, sp.service_id, s.name AS service_name, sp.year, sp.month, s.amount AS expected_amount,
          STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-', LEAST(s.due_day_start, DAY(LAST_DAY(STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-01'), '%Y-%m-%d'))))), '%Y-%m-%d') AS due_date_start,
          STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-', LEAST(COALESCE(s.due_day_end, s.due_day_start), DAY(LAST_DAY(STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-01'), '%Y-%m-%d'))))), '%Y-%m-%d') AS due_date_end,
          sp.status, sp.amount_paid, sp.late_fee_applied, sp.paid_at, sp.was_late,
          sp.paid_by_user_id, u.name AS paid_by_name, sp.notes, sp.updated_at
        FROM service_payments sp
        JOIN services s ON s.id = sp.service_id
        LEFT JOIN users u ON u.id = sp.paid_by_user_id
        WHERE sp.workspace_id = v_workspace_id AND sp.status = 'overdue'
          AND (v_updated_today_only = 0 OR DATE(sp.updated_at) = CURDATE())
        ORDER BY sp.year DESC, sp.month DESC, sp.id DESC
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

-- M-17: servicios pendientes cuyo vencimiento (due_date_start, mismo calculo
-- que sp_service_payment_list_overdue) cae EXACTO en la fecha pedida - usado
-- por SendServiceRemindersJob, una vez por cada dia de anticipacion distinto
-- configurado en las preferencias de los usuarios (ej. hoy+3, hoy+1).
CREATE PROCEDURE sp_service_payment_due_soon(IN p_json JSON)
BEGIN
  DECLARE v_date DATE;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_date = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.date')) AS DATE);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'service_payment_id', t.id, 'workspace_id', t.workspace_id, 'service_id', t.service_id,
        'service_name', t.service_name, 'amount', t.expected_amount,
        'due_date', DATE_FORMAT(t.due_date_start, '%Y-%m-%d')
      ))
      FROM (
        SELECT
          sp.id, sp.workspace_id, sp.service_id, s.name AS service_name, s.amount AS expected_amount,
          STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-', LEAST(s.due_day_start, DAY(LAST_DAY(STR_TO_DATE(CONCAT(sp.year, '-', sp.month, '-01'), '%Y-%m-%d'))))), '%Y-%m-%d') AS due_date_start
        FROM service_payments sp
        JOIN services s ON s.id = sp.service_id
        WHERE sp.status = 'pending'
      ) t
      WHERE t.due_date_start = v_date
    ), JSON_ARRAY())
  ) AS result;
END$$

-- ---------------------------------------------------------------------------
-- M-14 Dashboard (version reducida)
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_installment_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_status VARCHAR(20);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_status = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.status')), 'active');

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'id', x.id, 'user_id', x.user_id, 'user_name', x.user_name,
        'description', x.description, 'category_id', x.category_id, 'category_name', x.category_name,
        'total_amount', x.total_amount, 'installments_count', x.installments_count,
        'installment_amount', x.installment_amount, 'start_date', x.start_date, 'status', x.status,
        'paid_count', x.paid_count, 'remaining_count', x.remaining_count, 'remaining_amount', x.remaining_amount,
        'next_due', IF(x.next_year IS NULL, NULL, JSON_OBJECT('year', x.next_year, 'month', x.next_month)),
        'can_edit', TRUE
      ))
      FROM (
        SELECT i.id, i.user_id, u.name AS user_name, i.description, i.category_id,
          c.name AS category_name, i.total_amount, i.installments_count, i.installment_amount,
          DATE_FORMAT(i.start_date, '%Y-%m-%d') AS start_date, i.status,
          COALESCE(SUM(ip.status = 'paid'), 0) AS paid_count,
          COALESCE(SUM(ip.status = 'pending'), 0) AS remaining_count,
          COALESCE(SUM(CASE WHEN ip.status = 'pending' THEN ip.amount ELSE 0 END), 0) AS remaining_amount,
          (SELECT ip_next.year FROM installment_payments ip_next WHERE ip_next.installment_id = i.id AND ip_next.status = 'pending' ORDER BY ip_next.year, ip_next.month LIMIT 1) AS next_year,
          (SELECT ip_next.month FROM installment_payments ip_next WHERE ip_next.installment_id = i.id AND ip_next.status = 'pending' ORDER BY ip_next.year, ip_next.month LIMIT 1) AS next_month
        FROM installments i
        JOIN users u ON u.id = i.user_id
        LEFT JOIN categories c ON c.id = i.category_id
        LEFT JOIN installment_payments ip ON ip.installment_id = i.id
        WHERE i.workspace_id = v_workspace_id AND (v_status = 'all' OR i.status = v_status)
        GROUP BY i.id, i.user_id, u.name, i.description, i.category_id, c.name, i.total_amount,
          i.installments_count, i.installment_amount, i.start_date, i.status
        ORDER BY i.status, i.start_date DESC, i.id DESC
      ) x
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_installment_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_installment_id BIGINT UNSIGNED;
  DECLARE v_exists BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_installment_id = CAST(JSON_EXTRACT(p_json, '$.installment_id') AS UNSIGNED);
  SELECT id INTO v_exists FROM installments WHERE id = v_installment_id AND workspace_id = v_workspace_id;

  IF v_exists IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', x.id, 'user_id', x.user_id, 'user_name', x.user_name, 'description', x.description,
        'category_id', x.category_id, 'category_name', x.category_name, 'total_amount', x.total_amount,
        'installments_count', x.installments_count, 'installment_amount', x.installment_amount,
        'start_date', x.start_date, 'status', x.status, 'paid_count', x.paid_count,
        'remaining_count', x.remaining_count, 'remaining_amount', x.remaining_amount,
        'next_due', IF(x.next_year IS NULL, NULL, JSON_OBJECT('year', x.next_year, 'month', x.next_month)),
        'can_edit', TRUE,
        'payments', COALESCE((SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', ip.id, 'number', ip.number, 'year', ip.year, 'month', ip.month,
          'amount', ip.amount, 'status', ip.status, 'paid_at', DATE_FORMAT(ip.paid_at, '%Y-%m-%dT%H:%i:%sZ')
        )) FROM installment_payments ip WHERE ip.installment_id = v_installment_id), JSON_ARRAY())
      )
    ) AS result
    FROM (
      SELECT i.id, i.user_id, u.name AS user_name, i.description, i.category_id, c.name AS category_name,
        i.total_amount, i.installments_count, i.installment_amount, DATE_FORMAT(i.start_date, '%Y-%m-%d') AS start_date,
        i.status, COALESCE(SUM(ip.status = 'paid'), 0) AS paid_count,
        COALESCE(SUM(ip.status = 'pending'), 0) AS remaining_count,
        COALESCE(SUM(CASE WHEN ip.status = 'pending' THEN ip.amount ELSE 0 END), 0) AS remaining_amount,
        (SELECT ip_next.year FROM installment_payments ip_next WHERE ip_next.installment_id = i.id AND ip_next.status = 'pending' ORDER BY ip_next.year, ip_next.month LIMIT 1) AS next_year,
        (SELECT ip_next.month FROM installment_payments ip_next WHERE ip_next.installment_id = i.id AND ip_next.status = 'pending' ORDER BY ip_next.year, ip_next.month LIMIT 1) AS next_month
      FROM installments i
      JOIN users u ON u.id = i.user_id
      LEFT JOIN categories c ON c.id = i.category_id
      LEFT JOIN installment_payments ip ON ip.installment_id = i.id
      WHERE i.id = v_installment_id AND i.workspace_id = v_workspace_id
      GROUP BY i.id, i.user_id, u.name, i.description, i.category_id, c.name, i.total_amount,
        i.installments_count, i.installment_amount, i.start_date, i.status
    ) x;
  END IF;
END$$

CREATE PROCEDURE sp_installment_create(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_category_id BIGINT UNSIGNED;
  DECLARE v_description VARCHAR(255);
  DECLARE v_total_amount DECIMAL(14,2);
  DECLARE v_installments_count SMALLINT UNSIGNED;
  DECLARE v_installment_amount DECIMAL(14,2);
  DECLARE v_start_date DATE;
  DECLARE v_installment_id BIGINT UNSIGNED;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_description = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.description'));
  SET v_total_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.total_amount')) AS DECIMAL(14,2));
  SET v_installments_count = CAST(JSON_EXTRACT(p_json, '$.installments_count') AS UNSIGNED);
  SET v_installment_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.installment_amount')) AS DECIMAL(14,2));
  SET v_start_date = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.start_date')) AS DATE);
  SET v_category_id = IF(JSON_EXTRACT(p_json, '$.category_id') IS NULL OR JSON_TYPE(JSON_EXTRACT(p_json, '$.category_id')) = 'NULL', NULL, CAST(JSON_EXTRACT(p_json, '$.category_id') AS UNSIGNED));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  IF v_category_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categories WHERE id = v_category_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'VALIDATION_ERROR') AS result;
  ELSE
    START TRANSACTION;
    INSERT INTO installments (workspace_id, user_id, category_id, description, total_amount, installment_amount, installments_count, start_date)
    VALUES (v_workspace_id, v_user_id, v_category_id, v_description, v_total_amount, v_installment_amount, v_installments_count, v_start_date);
    SET v_installment_id = LAST_INSERT_ID();
    INSERT INTO installment_payments (installment_id, workspace_id, number, month, year, amount, status)
      SELECT v_installment_id, v_workspace_id, number, month, year, amount, 'pending'
      FROM JSON_TABLE(JSON_EXTRACT(p_json, '$.payments'), '$[*]' COLUMNS (
        number SMALLINT UNSIGNED PATH '$.number', month TINYINT UNSIGNED PATH '$.month',
        year SMALLINT UNSIGNED PATH '$.year', amount DECIMAL(14,2) PATH '$.amount'
      )) payments;
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_audit_user_id, 'installment', v_installment_id, 'created', v_audit_summary, NULL,
        JSON_OBJECT('description', v_description, 'total_amount', v_total_amount, 'installments_count', v_installments_count, 'start_date', v_start_date), v_audit_ip, v_audit_ua);
    COMMIT;
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', x.id, 'user_id', x.user_id, 'user_name', x.user_name, 'description', x.description,
        'category_id', x.category_id, 'category_name', x.category_name, 'total_amount', x.total_amount,
        'installments_count', x.installments_count, 'installment_amount', x.installment_amount,
        'start_date', x.start_date, 'status', x.status, 'paid_count', x.paid_count,
        'remaining_count', x.remaining_count, 'remaining_amount', x.remaining_amount,
        'next_due', IF(x.next_year IS NULL, NULL, JSON_OBJECT('year', x.next_year, 'month', x.next_month)),
        'can_edit', TRUE,
        'payments', COALESCE((SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', ip.id, 'number', ip.number, 'year', ip.year, 'month', ip.month,
          'amount', ip.amount, 'status', ip.status, 'paid_at', DATE_FORMAT(ip.paid_at, '%Y-%m-%dT%H:%i:%sZ')
        )) FROM installment_payments ip WHERE ip.installment_id = v_installment_id), JSON_ARRAY())
      )
    ) AS result
    FROM (
      SELECT i.id, i.user_id, u.name AS user_name, i.description, i.category_id, c.name AS category_name,
        i.total_amount, i.installments_count, i.installment_amount, DATE_FORMAT(i.start_date, '%Y-%m-%d') AS start_date,
        i.status, COALESCE(SUM(ip.status = 'paid'), 0) AS paid_count,
        COALESCE(SUM(ip.status = 'pending'), 0) AS remaining_count,
        COALESCE(SUM(CASE WHEN ip.status = 'pending' THEN ip.amount ELSE 0 END), 0) AS remaining_amount,
        (SELECT ip_next.year FROM installment_payments ip_next WHERE ip_next.installment_id = i.id AND ip_next.status = 'pending' ORDER BY ip_next.year, ip_next.month LIMIT 1) AS next_year,
        (SELECT ip_next.month FROM installment_payments ip_next WHERE ip_next.installment_id = i.id AND ip_next.status = 'pending' ORDER BY ip_next.year, ip_next.month LIMIT 1) AS next_month
      FROM installments i
      JOIN users u ON u.id = i.user_id
      LEFT JOIN categories c ON c.id = i.category_id
      LEFT JOIN installment_payments ip ON ip.installment_id = i.id
      WHERE i.id = v_installment_id AND i.workspace_id = v_workspace_id
      GROUP BY i.id, i.user_id, u.name, i.description, i.category_id, c.name, i.total_amount,
        i.installments_count, i.installment_amount, i.start_date, i.status
    ) x;
  END IF;
END$$

CREATE PROCEDURE sp_installment_update(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_installment_id BIGINT UNSIGNED;
  DECLARE v_description VARCHAR(255);
  DECLARE v_category_id BIGINT UNSIGNED;
  DECLARE v_old_description VARCHAR(255);
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_installment_id = CAST(JSON_EXTRACT(p_json, '$.installment_id') AS UNSIGNED);
  SET v_description = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.description'));
  SET v_category_id = IF(JSON_EXTRACT(p_json, '$.category_id') IS NULL OR JSON_TYPE(JSON_EXTRACT(p_json, '$.category_id')) = 'NULL', NULL, CAST(JSON_EXTRACT(p_json, '$.category_id') AS UNSIGNED));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address'));
  SET v_audit_ua = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent'));

  SELECT description INTO v_old_description FROM installments WHERE id = v_installment_id AND workspace_id = v_workspace_id;
  IF v_old_description IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_category_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categories WHERE id = v_category_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'VALIDATION_ERROR') AS result;
  ELSE
    START TRANSACTION;
    UPDATE installments SET description = v_description, category_id = v_category_id WHERE id = v_installment_id AND workspace_id = v_workspace_id;
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_audit_user_id, 'installment', v_installment_id, 'updated', v_audit_summary,
        JSON_OBJECT('description', v_old_description), JSON_OBJECT('description', v_description), v_audit_ip, v_audit_ua);
    COMMIT;
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', x.id, 'user_id', x.user_id, 'user_name', x.user_name, 'description', x.description,
        'category_id', x.category_id, 'category_name', x.category_name, 'total_amount', x.total_amount,
        'installments_count', x.installments_count, 'installment_amount', x.installment_amount,
        'start_date', x.start_date, 'status', x.status, 'paid_count', x.paid_count,
        'remaining_count', x.remaining_count, 'remaining_amount', x.remaining_amount,
        'next_due', IF(x.next_year IS NULL, NULL, JSON_OBJECT('year', x.next_year, 'month', x.next_month)),
        'can_edit', TRUE,
        'payments', COALESCE((SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', ip.id, 'number', ip.number, 'year', ip.year, 'month', ip.month,
          'amount', ip.amount, 'status', ip.status, 'paid_at', DATE_FORMAT(ip.paid_at, '%Y-%m-%dT%H:%i:%sZ')
        )) FROM installment_payments ip WHERE ip.installment_id = v_installment_id), JSON_ARRAY())
      )
    ) AS result
    FROM (
      SELECT i.id, i.user_id, u.name AS user_name, i.description, i.category_id, c.name AS category_name,
        i.total_amount, i.installments_count, i.installment_amount, DATE_FORMAT(i.start_date, '%Y-%m-%d') AS start_date,
        i.status, COALESCE(SUM(ip.status = 'paid'), 0) AS paid_count,
        COALESCE(SUM(ip.status = 'pending'), 0) AS remaining_count,
        COALESCE(SUM(CASE WHEN ip.status = 'pending' THEN ip.amount ELSE 0 END), 0) AS remaining_amount,
        (SELECT ip_next.year FROM installment_payments ip_next WHERE ip_next.installment_id = i.id AND ip_next.status = 'pending' ORDER BY ip_next.year, ip_next.month LIMIT 1) AS next_year,
        (SELECT ip_next.month FROM installment_payments ip_next WHERE ip_next.installment_id = i.id AND ip_next.status = 'pending' ORDER BY ip_next.year, ip_next.month LIMIT 1) AS next_month
      FROM installments i
      JOIN users u ON u.id = i.user_id
      LEFT JOIN categories c ON c.id = i.category_id
      LEFT JOIN installment_payments ip ON ip.installment_id = i.id
      WHERE i.id = v_installment_id AND i.workspace_id = v_workspace_id
      GROUP BY i.id, i.user_id, u.name, i.description, i.category_id, c.name, i.total_amount,
        i.installments_count, i.installment_amount, i.start_date, i.status
    ) x;
  END IF;
END$$

CREATE PROCEDURE sp_installment_delete(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_installment_id BIGINT UNSIGNED;
  DECLARE v_mode VARCHAR(10);
  DECLARE v_status VARCHAR(20);
  DECLARE v_description VARCHAR(255);
  DECLARE v_paid_count INT;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_installment_id = CAST(JSON_EXTRACT(p_json, '$.installment_id') AS UNSIGNED);
  SET v_mode = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.mode'));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address'));
  SET v_audit_ua = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent'));
  SELECT status, description INTO v_status, v_description FROM installments WHERE id = v_installment_id AND workspace_id = v_workspace_id FOR UPDATE;
  SELECT COUNT(*) INTO v_paid_count FROM installment_payments WHERE installment_id = v_installment_id AND status = 'paid';

  IF v_status IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    IF v_mode = 'hard' THEN
      DELETE FROM installments WHERE id = v_installment_id AND workspace_id = v_workspace_id;
    ELSE
      UPDATE installments SET status = 'cancelled' WHERE id = v_installment_id AND workspace_id = v_workspace_id;
      DELETE FROM installment_payments WHERE installment_id = v_installment_id AND status = 'pending';
    END IF;
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_audit_user_id, 'installment', v_installment_id, 'deleted', v_audit_summary,
        JSON_OBJECT('description', v_description, 'status', v_status, 'paid_count', v_paid_count), JSON_OBJECT('mode', v_mode), v_audit_ip, v_audit_ua);
    COMMIT;
    SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('mode', v_mode)) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_installment_payment_pay(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_installment_id BIGINT UNSIGNED;
  DECLARE v_payment_id BIGINT UNSIGNED;
  DECLARE v_status VARCHAR(20);
  DECLARE v_paid_at DATE;
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_installment_id = CAST(JSON_EXTRACT(p_json, '$.installment_id') AS UNSIGNED);
  SET v_payment_id = CAST(JSON_EXTRACT(p_json, '$.payment_id') AS UNSIGNED);
  SET v_paid_at = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.paid_at')) AS DATE);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address'));
  SET v_audit_ua = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent'));
  START TRANSACTION;
  SELECT status INTO v_status FROM installment_payments WHERE id = v_payment_id AND installment_id = v_installment_id AND workspace_id = v_workspace_id FOR UPDATE;
  IF v_status IS NULL THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_status = 'paid' THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSE
    UPDATE installment_payments SET status = 'paid', paid_at = v_paid_at WHERE id = v_payment_id;
    IF NOT EXISTS (SELECT 1 FROM installment_payments WHERE installment_id = v_installment_id AND status = 'pending') THEN
      UPDATE installments SET status = 'completed' WHERE id = v_installment_id;
    END IF;
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_user_id, 'installment_payment', v_payment_id, 'paid', v_audit_summary, NULL, JSON_OBJECT('paid_at', v_paid_at), v_audit_ip, v_audit_ua);
    COMMIT;
    SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('id', ip.id, 'number', ip.number, 'year', ip.year, 'month', ip.month, 'amount', ip.amount, 'status', ip.status, 'paid_at', DATE_FORMAT(ip.paid_at, '%Y-%m-%dT%H:%i:%sZ'))) AS result
    FROM installment_payments ip WHERE ip.id = v_payment_id;
  END IF;
END$$

CREATE PROCEDURE sp_installment_payment_unpay(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_installment_id BIGINT UNSIGNED;
  DECLARE v_payment_id BIGINT UNSIGNED;
  DECLARE v_status VARCHAR(20);
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_installment_id = CAST(JSON_EXTRACT(p_json, '$.installment_id') AS UNSIGNED);
  SET v_payment_id = CAST(JSON_EXTRACT(p_json, '$.payment_id') AS UNSIGNED);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address'));
  SET v_audit_ua = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent'));
  START TRANSACTION;
  SELECT status INTO v_status FROM installment_payments WHERE id = v_payment_id AND installment_id = v_installment_id AND workspace_id = v_workspace_id FOR UPDATE;
  IF v_status IS NULL THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_status <> 'paid' THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSE
    UPDATE installment_payments SET status = 'pending', paid_at = NULL WHERE id = v_payment_id;
    UPDATE installments SET status = 'active' WHERE id = v_installment_id;
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_user_id, 'installment_payment', v_payment_id, 'unpaid', v_audit_summary, NULL, JSON_OBJECT('status', 'pending'), v_audit_ip, v_audit_ua);
    COMMIT;
    SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('id', ip.id, 'number', ip.number, 'year', ip.year, 'month', ip.month, 'amount', ip.amount, 'status', ip.status, 'paid_at', NULL)) AS result
    FROM installment_payments ip WHERE ip.id = v_payment_id;
  END IF;
END$$

CREATE PROCEDURE sp_installment_payments_by_period(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT(
    'total', COALESCE(SUM(ip.amount), 0),
    'total_paid', COALESCE(SUM(CASE WHEN ip.status = 'paid' THEN ip.amount ELSE 0 END), 0),
    'items', COALESCE(JSON_ARRAYAGG(JSON_OBJECT(
      'installment_id', i.id, 'description', i.description, 'number', ip.number,
      'installments_count', i.installments_count, 'amount', ip.amount, 'status', ip.status, 'user_id', i.user_id
    )), JSON_ARRAY())
  )) AS result
  FROM installment_payments ip JOIN installments i ON i.id = ip.installment_id
  WHERE ip.workspace_id = v_workspace_id AND ip.year = v_year AND ip.month = v_month AND i.status <> 'cancelled';
END$$

CREATE PROCEDURE sp_installment_payments_settle_period(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_updated INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  START TRANSACTION;
  UPDATE installment_payments SET status = 'paid', paid_at = NOW()
  WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month AND status = 'pending';
  SET v_updated = ROW_COUNT();
  UPDATE installments i SET status = 'completed'
  WHERE i.workspace_id = v_workspace_id AND i.status = 'active'
    AND NOT EXISTS (SELECT 1 FROM installment_payments ip WHERE ip.installment_id = i.id AND ip.status = 'pending');
  COMMIT;
  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('updated_count', v_updated)) AS result;
END$$

-- M-17: cuotas pendientes de un periodo, agrupadas por workspace (las cuotas
-- no tienen dia de vencimiento propio - P-24, se avisa una vez al inicio del
-- mes con el total en vez de por cada cuota individual como los servicios).
-- Usado por SendInstallmentRemindersJob el dia 1 de cada mes.
CREATE PROCEDURE sp_installment_payment_due_soon(IN p_json JSON)
BEGIN
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT('workspace_id', t.workspace_id, 'count', t.count, 'total', t.total))
      FROM (
        SELECT ip.workspace_id, COUNT(*) AS count, SUM(ip.amount) AS total
        FROM installment_payments ip
        JOIN installments i ON i.id = ip.installment_id
        WHERE ip.year = v_year AND ip.month = v_month AND ip.status = 'pending' AND i.status <> 'cancelled'
        GROUP BY ip.workspace_id
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_dashboard_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_date_from DATE;
  DECLARE v_date_to DATE;
  DECLARE v_total_income DECIMAL(14,2);
  DECLARE v_total_expenses DECIMAL(14,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_date_from = STR_TO_DATE(CONCAT(v_year, '-', v_month, '-01'), '%Y-%m-%d');
  SET v_date_to = LAST_DAY(v_date_from);

  SELECT COALESCE(SUM(amount), 0) INTO v_total_income
  FROM income_entries WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month;

  SELECT COALESCE(SUM(amount), 0) INTO v_total_expenses
  FROM expenses WHERE workspace_id = v_workspace_id AND date BETWEEN v_date_from AND v_date_to;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'total_income', v_total_income,
      'total_expenses', v_total_expenses,
      'available', v_total_income - v_total_expenses,
      'by_category', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'category_id', t.category_id, 'category_name', t.category_name,
          'category_color', t.category_color, 'category_icon', t.category_icon,
          'amount', t.amount,
          'pct', IF(v_total_expenses = 0, 0, ROUND(t.amount / v_total_expenses * 100, 1))
        ))
        FROM (
          SELECT
            e.category_id,
            COALESCE(c.name, 'Sin categoría') AS category_name,
            COALESCE(c.color, '#9E9E9E') AS category_color,
            COALESCE(c.icon, 'more_horiz') AS category_icon,
            SUM(e.amount) AS amount
          FROM expenses e
          LEFT JOIN categories c ON c.id = e.category_id
          WHERE e.workspace_id = v_workspace_id AND e.date BETWEEN v_date_from AND v_date_to
          GROUP BY e.category_id, category_name, category_color, category_icon
          ORDER BY amount DESC
        ) t
      ), JSON_ARRAY()),
      'by_user', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'user_id', g.user_id, 'user_name', g.user_name,
          'income', g.income, 'expenses', g.expenses, 'balance', g.income - g.expenses
        ))
        FROM (
          SELECT
            u.id AS user_id, u.name AS user_name,
            COALESCE((
              SELECT SUM(ie.amount) FROM income_entries ie
              WHERE ie.workspace_id = v_workspace_id AND ie.year = v_year AND ie.month = v_month AND ie.user_id = u.id
            ), 0) AS income,
            COALESCE((
              SELECT SUM(ex.amount) FROM expenses ex
              WHERE ex.workspace_id = v_workspace_id AND ex.date BETWEEN v_date_from AND v_date_to AND ex.user_id = u.id
            ), 0) AS expenses
          FROM users u
          JOIN workspace_users wu ON wu.user_id = u.id AND wu.workspace_id = v_workspace_id
        ) g
        WHERE g.income > 0 OR g.expenses > 0
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

-- M-14: gasto acumulado del mes hasta HOY (no hasta fin de mes) - usado para
-- la proyección de fin de mes (daily_average = expenses_to_date / dias_transcurridos).
CREATE PROCEDURE sp_dashboard_daily_spend(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_today DATE;
  DECLARE v_date_from DATE;
  DECLARE v_date_to DATE;
  DECLARE v_expenses_to_date DECIMAL(14,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_today = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.today'));
  SET v_date_from = STR_TO_DATE(CONCAT(v_year, '-', v_month, '-01'), '%Y-%m-%d');
  SET v_date_to = LEAST(LAST_DAY(v_date_from), v_today);

  SELECT COALESCE(SUM(amount), 0) INTO v_expenses_to_date
  FROM expenses WHERE workspace_id = v_workspace_id AND date BETWEEN v_date_from AND v_date_to;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT('expenses_to_date', v_expenses_to_date)
  ) AS result;
END$$

-- ---------------------------------------------------------------------------
-- M-10 Monedero de ahorro
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_savings_wallet_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_exists BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SELECT id INTO v_exists FROM savings_wallet WHERE workspace_id = v_workspace_id;

  IF v_exists IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', w.id, 'balance', w.balance,
        'total_deposited', COALESCE((SELECT SUM(amount) FROM savings_movements WHERE wallet_id = w.id AND type = 'deposit'), 0),
        'total_withdrawn', COALESCE((SELECT SUM(amount) FROM savings_movements WHERE wallet_id = w.id AND type = 'withdraw'), 0),
        'updated_at', DATE_FORMAT(w.updated_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM savings_wallet w
    WHERE w.workspace_id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_savings_movement_create(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_type VARCHAR(20);
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_note VARCHAR(255);
  DECLARE v_source VARCHAR(20);
  DECLARE v_closing_id BIGINT UNSIGNED;
  DECLARE v_wallet_id BIGINT UNSIGNED;
  DECLARE v_balance DECIMAL(14,2);
  DECLARE v_new_balance DECIMAL(14,2);
  DECLARE v_movement_id BIGINT UNSIGNED;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_user_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.user_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.user_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED));
  SET v_type = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.type'));
  SET v_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.amount')) AS DECIMAL(14,2));
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_note = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.note')) = 'NULL' OR JSON_EXTRACT(p_json, '$.note') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.note')));
  SET v_source = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.source')), 'manual');
  SET v_closing_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.closing_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.closing_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.closing_id') AS UNSIGNED));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  START TRANSACTION;
  SELECT id, balance INTO v_wallet_id, v_balance FROM savings_wallet WHERE workspace_id = v_workspace_id FOR UPDATE;

  IF v_wallet_id IS NULL THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_type = 'withdraw' AND v_amount > v_balance THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INSUFFICIENT_FUNDS') AS result;
  ELSE
    SET v_new_balance = IF(v_type = 'deposit', v_balance + v_amount, v_balance - v_amount);
    UPDATE savings_wallet SET balance = v_new_balance WHERE id = v_wallet_id;
    INSERT INTO savings_movements (wallet_id, user_id, type, amount, balance_after, source, closing_id, month, year, note)
      VALUES (v_wallet_id, v_user_id, v_type, v_amount, v_new_balance, v_source, v_closing_id, v_month, v_year, v_note);
    SET v_movement_id = LAST_INSERT_ID();
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_audit_user_id, 'savings_movement', v_movement_id, 'created', v_audit_summary,
        JSON_OBJECT('balance', v_balance), JSON_OBJECT('balance', v_new_balance, 'amount', v_amount, 'type', v_type), v_audit_ip, v_audit_ua);
    COMMIT;
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', v_movement_id, 'type', v_type, 'amount', v_amount, 'balance_after', v_new_balance,
        'year', v_year, 'month', v_month, 'note', v_note, 'source', v_source,
        'user_name', (SELECT name FROM users WHERE id = v_user_id),
        'created_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_savings_movement_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_page INT;
  DECLARE v_per_page INT;
  DECLARE v_offset INT;
  DECLARE v_type VARCHAR(20);
  DECLARE v_wallet_id BIGINT UNSIGNED;
  DECLARE v_total INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_page = CAST(JSON_EXTRACT(p_json, '$.page') AS UNSIGNED);
  SET v_per_page = CAST(JSON_EXTRACT(p_json, '$.per_page') AS UNSIGNED);
  SET v_offset = (v_page - 1) * v_per_page;
  SET v_type = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.type')) = 'NULL' OR JSON_EXTRACT(p_json, '$.type') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.type')));

  SELECT id INTO v_wallet_id FROM savings_wallet WHERE workspace_id = v_workspace_id;

  SELECT COUNT(*) INTO v_total FROM savings_movements
  WHERE wallet_id = v_wallet_id AND (v_type IS NULL OR type = v_type);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'total', v_total,
      'items', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', t.id, 'type', t.type, 'amount', t.amount, 'balance_after', t.balance_after,
          'year', t.year, 'month', t.month, 'note', t.note, 'source', t.source,
          'user_name', t.user_name, 'created_at', t.created_at
        ))
        FROM (
          SELECT m.id, m.type, m.amount, m.balance_after, m.year, m.month, m.note, m.source,
            u.name AS user_name,
            DATE_FORMAT(m.created_at, '%Y-%m-%dT%H:%i:%sZ') AS created_at
          FROM savings_movements m
          LEFT JOIN users u ON u.id = m.user_id
          WHERE m.wallet_id = v_wallet_id AND (v_type IS NULL OR m.type = v_type)
          ORDER BY m.created_at DESC, m.id DESC
          LIMIT v_per_page OFFSET v_offset
        ) t
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

-- Devuelve TODOS los movimientos ordenados cronologicamente (id/monto no
-- son muchos en un uso hogareno) - el calculo de "saldo al cierre de cada
-- uno de los ultimos N meses" (walk-forward, carry-over en meses sin
-- movimientos) se hace en SavingsWalletService::history(), no aca, para no
-- meter generacion de calendario en SQL.
CREATE PROCEDURE sp_savings_wallet_history(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_wallet_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SELECT id INTO v_wallet_id FROM savings_wallet WHERE workspace_id = v_workspace_id;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT('year', t.year, 'month', t.month, 'balance_after', t.balance_after))
      FROM (
        SELECT year, month, balance_after
        FROM savings_movements
        WHERE wallet_id = v_wallet_id
        ORDER BY year ASC, month ASC, created_at ASC, id ASC
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

-- Usado por DashboardService para restar del "Disponible" del mes lo que se
-- depositó al monedero (y sumar lo retirado) - decision explicita del usuario
-- (2026-09-19) de aplicar el "si" en el PENDIENTE P-15 de ESPECIFICACION_TECNICA.md.
CREATE PROCEDURE sp_savings_movement_totals_for_period(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_wallet_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SELECT id INTO v_wallet_id FROM savings_wallet WHERE workspace_id = v_workspace_id;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'deposited', COALESCE((
        SELECT SUM(amount) FROM savings_movements
        WHERE wallet_id = v_wallet_id AND year = v_year AND month = v_month AND type = 'deposit'
      ), 0),
      'withdrawn', COALESCE((
        SELECT SUM(amount) FROM savings_movements
        WHERE wallet_id = v_wallet_id AND year = v_year AND month = v_month AND type = 'withdraw'
      ), 0)
    )
  ) AS result;
END$$

-- ---------------------------------------------------------------------------
-- M-11 Metas de ahorro
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_savings_goal_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_status VARCHAR(20);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_status = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.status')), 'active');

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'id', t.id, 'name', t.name, 'target_amount', t.target_amount, 'current_amount', t.current_amount,
        'due_date', t.due_date, 'status', t.status, 'completed_at', t.completed_at, 'created_at', t.created_at
      ))
      FROM (
        SELECT id, name, target_amount, current_amount,
          DATE_FORMAT(due_date, '%Y-%m-%d') AS due_date, status,
          DATE_FORMAT(completed_at, '%Y-%m-%dT%H:%i:%sZ') AS completed_at,
          DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ') AS created_at
        FROM savings_goals
        WHERE workspace_id = v_workspace_id AND (v_status = 'all' OR status = v_status)
        ORDER BY status, due_date IS NULL, due_date ASC, id DESC
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_savings_goal_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_goal_id BIGINT UNSIGNED;
  DECLARE v_exists BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_goal_id = CAST(JSON_EXTRACT(p_json, '$.goal_id') AS UNSIGNED);
  SELECT id INTO v_exists FROM savings_goals WHERE id = v_goal_id AND workspace_id = v_workspace_id;

  IF v_exists IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', id, 'name', name, 'target_amount', target_amount, 'current_amount', current_amount,
        'due_date', DATE_FORMAT(due_date, '%Y-%m-%d'), 'status', status,
        'completed_at', DATE_FORMAT(completed_at, '%Y-%m-%dT%H:%i:%sZ'),
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM savings_goals WHERE id = v_goal_id AND workspace_id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_savings_goal_create(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(150);
  DECLARE v_target_amount DECIMAL(14,2);
  DECLARE v_due_date DATE;
  DECLARE v_goal_id BIGINT UNSIGNED;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_name = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.name'));
  SET v_target_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.target_amount')) AS DECIMAL(14,2));
  SET v_due_date = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.due_date')) = 'NULL' OR JSON_EXTRACT(p_json, '$.due_date') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.due_date')));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  START TRANSACTION;
  INSERT INTO savings_goals (workspace_id, name, target_amount, due_date)
    VALUES (v_workspace_id, v_name, v_target_amount, v_due_date);
  SET v_goal_id = LAST_INSERT_ID();
  INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
    VALUES (v_workspace_id, v_audit_user_id, 'savings_goal', v_goal_id, 'created', v_audit_summary, NULL,
      JSON_OBJECT('name', v_name, 'target_amount', v_target_amount, 'due_date', v_due_date), v_audit_ip, v_audit_ua);
  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'id', id, 'name', name, 'target_amount', target_amount, 'current_amount', current_amount,
      'due_date', DATE_FORMAT(due_date, '%Y-%m-%d'), 'status', status,
      'completed_at', DATE_FORMAT(completed_at, '%Y-%m-%dT%H:%i:%sZ'),
      'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
    )
  ) AS result
  FROM savings_goals WHERE id = v_goal_id;
END$$

CREATE PROCEDURE sp_savings_goal_update(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_goal_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(150);
  DECLARE v_target_amount DECIMAL(14,2);
  DECLARE v_due_date DATE;
  DECLARE v_current_amount DECIMAL(14,2);
  DECLARE v_old_name VARCHAR(150);
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_goal_id = CAST(JSON_EXTRACT(p_json, '$.goal_id') AS UNSIGNED);
  SET v_name = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.name'));
  SET v_target_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.target_amount')) AS DECIMAL(14,2));
  SET v_due_date = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.due_date')) = 'NULL' OR JSON_EXTRACT(p_json, '$.due_date') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.due_date')));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  SELECT name, current_amount INTO v_old_name, v_current_amount FROM savings_goals WHERE id = v_goal_id AND workspace_id = v_workspace_id;

  IF v_old_name IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_target_amount < v_current_amount THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSE
    START TRANSACTION;
    UPDATE savings_goals SET name = v_name, target_amount = v_target_amount, due_date = v_due_date WHERE id = v_goal_id;
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_audit_user_id, 'savings_goal', v_goal_id, 'updated', v_audit_summary,
        JSON_OBJECT('name', v_old_name), JSON_OBJECT('name', v_name), v_audit_ip, v_audit_ua);
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', id, 'name', name, 'target_amount', target_amount, 'current_amount', current_amount,
        'due_date', DATE_FORMAT(due_date, '%Y-%m-%d'), 'status', status,
        'completed_at', DATE_FORMAT(completed_at, '%Y-%m-%dT%H:%i:%sZ'),
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM savings_goals WHERE id = v_goal_id;
  END IF;
END$$

CREATE PROCEDURE sp_savings_goal_cancel(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_goal_id BIGINT UNSIGNED;
  DECLARE v_name VARCHAR(150);
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_goal_id = CAST(JSON_EXTRACT(p_json, '$.goal_id') AS UNSIGNED);
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  SELECT name INTO v_name FROM savings_goals WHERE id = v_goal_id AND workspace_id = v_workspace_id;

  IF v_name IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    UPDATE savings_goals SET status = 'cancelled' WHERE id = v_goal_id;
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_audit_user_id, 'savings_goal', v_goal_id, 'deleted', v_audit_summary, JSON_OBJECT('name', v_name), NULL, v_audit_ip, v_audit_ua);
    COMMIT;
    SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('id', v_goal_id)) AS result;
  END IF;
END$$

-- Nota sobre estados: la spec original dice "status<>'active' -> INVALID_STATE"
-- para CUALQUIER movimiento, pero el caso borde "un retiro deja la meta active
-- de nuevo si baja del objetivo" solo tiene sentido si un retiro SI se permite
-- sobre una meta 'completed' (si no, nunca podria volver a bajar). Se resuelve
-- asi: 'cancelled' bloquea todo; 'completed' bloquea solo 'contribution'.
CREATE PROCEDURE sp_savings_goal_contribute(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_goal_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_type VARCHAR(20);
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_source VARCHAR(20);
  DECLARE v_closing_id BIGINT UNSIGNED;
  DECLARE v_note VARCHAR(255);
  DECLARE v_status VARCHAR(20);
  DECLARE v_current_amount DECIMAL(14,2);
  DECLARE v_target_amount DECIMAL(14,2);
  DECLARE v_new_amount DECIMAL(14,2);
  DECLARE v_new_status VARCHAR(20);
  DECLARE v_movement_id BIGINT UNSIGNED;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_goal_id = CAST(JSON_EXTRACT(p_json, '$.goal_id') AS UNSIGNED);
  SET v_user_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.user_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.user_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED));
  SET v_type = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.type'));
  SET v_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.amount')) AS DECIMAL(14,2));
  SET v_source = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.source')), 'manual');
  SET v_closing_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.closing_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.closing_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.closing_id') AS UNSIGNED));
  SET v_note = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.note')) = 'NULL' OR JSON_EXTRACT(p_json, '$.note') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.note')));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  START TRANSACTION;
  SELECT status, current_amount, target_amount INTO v_status, v_current_amount, v_target_amount
    FROM savings_goals WHERE id = v_goal_id AND workspace_id = v_workspace_id FOR UPDATE;

  IF v_status IS NULL THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_status = 'cancelled' THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSEIF v_status = 'completed' AND v_type = 'contribution' THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSEIF v_type = 'withdrawal' AND v_amount > v_current_amount THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INSUFFICIENT_FUNDS') AS result;
  ELSE
    SET v_new_amount = IF(v_type = 'contribution', v_current_amount + v_amount, v_current_amount - v_amount);
    SET v_new_status = IF(v_new_amount >= v_target_amount, 'completed', 'active');
    UPDATE savings_goals
      SET current_amount = v_new_amount, status = v_new_status,
        completed_at = IF(v_new_status = 'completed', NOW(), NULL)
      WHERE id = v_goal_id;
    INSERT INTO savings_goal_movements (goal_id, user_id, type, amount, source, closing_id, note)
      VALUES (v_goal_id, v_user_id, v_type, v_amount, v_source, v_closing_id, v_note);
    SET v_movement_id = LAST_INSERT_ID();
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_audit_user_id, 'savings_goal', v_goal_id, 'updated', v_audit_summary,
        JSON_OBJECT('current_amount', v_current_amount), JSON_OBJECT('current_amount', v_new_amount, 'status', v_new_status), v_audit_ip, v_audit_ua);
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', id, 'name', name, 'target_amount', target_amount, 'current_amount', current_amount,
        'due_date', DATE_FORMAT(due_date, '%Y-%m-%d'), 'status', status,
        'completed_at', DATE_FORMAT(completed_at, '%Y-%m-%dT%H:%i:%sZ'),
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM savings_goals WHERE id = v_goal_id;
  END IF;
END$$

CREATE PROCEDURE sp_savings_goal_movements(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_goal_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_goal_id = CAST(JSON_EXTRACT(p_json, '$.goal_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'id', t.id, 'type', t.type, 'amount', t.amount, 'source', t.source, 'note', t.note,
        'user_name', t.user_name, 'created_at', t.created_at
      ))
      FROM (
        SELECT gm.id, gm.type, gm.amount, gm.source, gm.note, u.name AS user_name,
          DATE_FORMAT(gm.created_at, '%Y-%m-%dT%H:%i:%sZ') AS created_at
        FROM savings_goal_movements gm
        JOIN savings_goals g ON g.id = gm.goal_id
        LEFT JOIN users u ON u.id = gm.user_id
        WHERE gm.goal_id = v_goal_id AND g.workspace_id = v_workspace_id
        ORDER BY gm.created_at DESC, gm.id DESC
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_savings_goal_totals_for_period(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'contributed', COALESCE((
        SELECT SUM(gm.amount) FROM savings_goal_movements gm
        JOIN savings_goals g ON g.id = gm.goal_id
        WHERE g.workspace_id = v_workspace_id AND gm.type = 'contribution'
          AND YEAR(gm.created_at) = v_year AND MONTH(gm.created_at) = v_month
      ), 0),
      'withdrawn', COALESCE((
        SELECT SUM(gm.amount) FROM savings_goal_movements gm
        JOIN savings_goals g ON g.id = gm.goal_id
        WHERE g.workspace_id = v_workspace_id AND gm.type = 'withdrawal'
          AND YEAR(gm.created_at) = v_year AND MONTH(gm.created_at) = v_month
      ), 0)
    )
  ) AS result;
END$$

-- Transferencia atomica monedero -> meta (P-16): retira del wallet Y aporta a
-- la meta en UNA sola transaccion (evita el estado intermedio "se descontó
-- del monedero pero no se acreditó a la meta" de hacerlo en 2 llamadas).
CREATE PROCEDURE sp_savings_transfer_wallet_to_goal(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_goal_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_note VARCHAR(255);
  DECLARE v_wallet_id BIGINT UNSIGNED;
  DECLARE v_wallet_balance DECIMAL(14,2);
  DECLARE v_goal_status VARCHAR(20);
  DECLARE v_goal_current DECIMAL(14,2);
  DECLARE v_goal_target DECIMAL(14,2);
  DECLARE v_new_wallet_balance DECIMAL(14,2);
  DECLARE v_new_goal_amount DECIMAL(14,2);
  DECLARE v_new_goal_status VARCHAR(20);
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_goal_id = CAST(JSON_EXTRACT(p_json, '$.goal_id') AS UNSIGNED);
  SET v_user_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.user_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.user_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED));
  SET v_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.amount')) AS DECIMAL(14,2));
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_note = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.note')) = 'NULL' OR JSON_EXTRACT(p_json, '$.note') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.note')));
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  START TRANSACTION;
  SELECT id, balance INTO v_wallet_id, v_wallet_balance FROM savings_wallet WHERE workspace_id = v_workspace_id FOR UPDATE;
  SELECT status, current_amount, target_amount INTO v_goal_status, v_goal_current, v_goal_target
    FROM savings_goals WHERE id = v_goal_id AND workspace_id = v_workspace_id FOR UPDATE;

  IF v_wallet_id IS NULL OR v_goal_status IS NULL THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSEIF v_goal_status IN ('cancelled', 'completed') THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
  ELSEIF v_amount > v_wallet_balance THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INSUFFICIENT_FUNDS') AS result;
  ELSE
    SET v_new_wallet_balance = v_wallet_balance - v_amount;
    SET v_new_goal_amount = v_goal_current + v_amount;
    SET v_new_goal_status = IF(v_new_goal_amount >= v_goal_target, 'completed', 'active');

    UPDATE savings_wallet SET balance = v_new_wallet_balance WHERE id = v_wallet_id;
    INSERT INTO savings_movements (wallet_id, user_id, type, amount, balance_after, source, closing_id, month, year, note)
      VALUES (v_wallet_id, v_user_id, 'withdraw', v_amount, v_new_wallet_balance, 'manual', NULL, v_month, v_year, v_note);

    UPDATE savings_goals
      SET current_amount = v_new_goal_amount, status = v_new_goal_status,
        completed_at = IF(v_new_goal_status = 'completed', NOW(), NULL)
      WHERE id = v_goal_id;
    INSERT INTO savings_goal_movements (goal_id, user_id, type, amount, source, closing_id, note)
      VALUES (v_goal_id, v_user_id, 'contribution', v_amount, 'manual', NULL, v_note);

    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_audit_user_id, 'savings_goal', v_goal_id, 'updated', v_audit_summary,
        JSON_OBJECT('wallet_balance', v_wallet_balance, 'goal_amount', v_goal_current),
        JSON_OBJECT('wallet_balance', v_new_wallet_balance, 'goal_amount', v_new_goal_amount), v_audit_ip, v_audit_ua);
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'wallet_balance', v_new_wallet_balance,
        'goal', JSON_OBJECT(
          'id', g.id, 'name', g.name, 'target_amount', g.target_amount, 'current_amount', g.current_amount,
          'due_date', DATE_FORMAT(g.due_date, '%Y-%m-%d'), 'status', g.status,
          'completed_at', DATE_FORMAT(g.completed_at, '%Y-%m-%dT%H:%i:%sZ'),
          'created_at', DATE_FORMAT(g.created_at, '%Y-%m-%dT%H:%i:%sZ')
        )
      )
    ) AS result
    FROM savings_goals g WHERE g.id = v_goal_id;
  END IF;
END$$

-- ---------------------------------------------------------------------------
-- M-12 Presupuestos y alertas
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_budget_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_date_from DATE;
  DECLARE v_date_to DATE;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_date_from = STR_TO_DATE(CONCAT(v_year, '-', v_month, '-01'), '%Y-%m-%d');
  SET v_date_to = LAST_DAY(v_date_from);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'budgets', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', t.id, 'category_id', t.category_id, 'category_name', t.category_name,
          'category_icon', t.category_icon, 'category_color', t.category_color,
          'year', t.year, 'month', t.month, 'limit_amount', t.limit_amount,
          'spent_amount', t.spent_amount, 'alert_level', t.alert_level
        ))
        FROM (
          SELECT
            b.id, b.category_id, c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
            b.year, b.month, b.limit_amount, b.alert_level,
            COALESCE((
              SELECT SUM(e.amount) FROM expenses e
              WHERE e.workspace_id = v_workspace_id AND e.category_id = b.category_id
                AND e.date BETWEEN v_date_from AND v_date_to
            ), 0) AS spent_amount
          FROM budgets b
          JOIN categories c ON c.id = b.category_id
          WHERE b.workspace_id = v_workspace_id AND b.year = v_year AND b.month = v_month
          ORDER BY c.name
        ) t
      ), JSON_ARRAY()),
      'categories_without_budget', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT('id', u.id, 'name', u.name, 'spent_amount', u.spent_amount))
        FROM (
          SELECT c.id, c.name, SUM(e.amount) AS spent_amount
          FROM expenses e
          JOIN categories c ON c.id = e.category_id
          WHERE e.workspace_id = v_workspace_id AND e.date BETWEEN v_date_from AND v_date_to
            AND NOT EXISTS (
              SELECT 1 FROM budgets b
              WHERE b.workspace_id = v_workspace_id AND b.category_id = e.category_id
                AND b.year = v_year AND b.month = v_month
            )
          GROUP BY c.id, c.name
          ORDER BY spent_amount DESC
        ) u
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_budget_upsert(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_category_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_limit_amount DECIMAL(14,2);
  DECLARE v_budget_id BIGINT UNSIGNED;
  DECLARE v_created BOOLEAN;
  DECLARE v_previous_limit DECIMAL(14,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_category_id = CAST(JSON_EXTRACT(p_json, '$.category_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_limit_amount = CAST(JSON_EXTRACT(p_json, '$.limit_amount') AS DECIMAL(14,2));

  IF NOT EXISTS (SELECT 1 FROM categories WHERE id = v_category_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'CATEGORY_NOT_IN_WORKSPACE') AS result;
  ELSE
    START TRANSACTION;
    SELECT id, limit_amount INTO v_budget_id, v_previous_limit FROM budgets
      WHERE workspace_id = v_workspace_id AND category_id = v_category_id AND year = v_year AND month = v_month
      FOR UPDATE;

    IF v_budget_id IS NULL THEN
      SET v_created = TRUE;
      INSERT INTO budgets (workspace_id, category_id, year, month, limit_amount, alert_level)
        VALUES (v_workspace_id, v_category_id, v_year, v_month, v_limit_amount, 'none');
      SET v_budget_id = LAST_INSERT_ID();
    ELSE
      SET v_created = FALSE;
      UPDATE budgets SET
        limit_amount = v_limit_amount,
        alert_level = IF(v_limit_amount <> v_previous_limit, 'none', alert_level)
        WHERE id = v_budget_id;
    END IF;
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'created', v_created,
        'budget', JSON_OBJECT(
          'id', b.id, 'category_id', b.category_id, 'category_name', c.name,
          'category_icon', c.icon, 'category_color', c.color,
          'year', b.year, 'month', b.month, 'limit_amount', b.limit_amount,
          'spent_amount', COALESCE((
            SELECT SUM(e.amount) FROM expenses e
            WHERE e.workspace_id = v_workspace_id AND e.category_id = b.category_id
              AND e.date BETWEEN STR_TO_DATE(CONCAT(b.year, '-', b.month, '-01'), '%Y-%m-%d')
                AND LAST_DAY(STR_TO_DATE(CONCAT(b.year, '-', b.month, '-01'), '%Y-%m-%d'))
          ), 0),
          'alert_level', b.alert_level
        )
      )
    ) AS result
    FROM budgets b JOIN categories c ON c.id = b.category_id WHERE b.id = v_budget_id;
  END IF;
END$$

CREATE PROCEDURE sp_budget_delete(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_budget_id BIGINT UNSIGNED;
  DECLARE v_exists BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_budget_id = CAST(JSON_EXTRACT(p_json, '$.budget_id') AS UNSIGNED);

  SELECT id INTO v_exists FROM budgets WHERE id = v_budget_id AND workspace_id = v_workspace_id;

  IF v_exists IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    DELETE FROM budgets WHERE id = v_budget_id;
    COMMIT;

    SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('id', v_budget_id)) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_budget_copy_from_period(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_from_year SMALLINT UNSIGNED;
  DECLARE v_from_month TINYINT UNSIGNED;
  DECLARE v_to_year SMALLINT UNSIGNED;
  DECLARE v_to_month TINYINT UNSIGNED;
  DECLARE v_overwrite BOOLEAN;
  DECLARE v_before BIGINT UNSIGNED;
  DECLARE v_after BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_from_year = CAST(JSON_EXTRACT(p_json, '$.from_year') AS UNSIGNED);
  SET v_from_month = CAST(JSON_EXTRACT(p_json, '$.from_month') AS UNSIGNED);
  SET v_to_year = CAST(JSON_EXTRACT(p_json, '$.to_year') AS UNSIGNED);
  SET v_to_month = CAST(JSON_EXTRACT(p_json, '$.to_month') AS UNSIGNED);
  SET v_overwrite = (CAST(JSON_EXTRACT(p_json, '$.overwrite') AS UNSIGNED) = 1);

  SELECT COUNT(*) INTO v_before FROM budgets WHERE workspace_id = v_workspace_id AND year = v_to_year AND month = v_to_month;

  START TRANSACTION;
  IF v_overwrite THEN
    INSERT INTO budgets (workspace_id, category_id, year, month, limit_amount, alert_level)
      SELECT workspace_id, category_id, v_to_year, v_to_month, limit_amount, 'none'
      FROM budgets WHERE workspace_id = v_workspace_id AND year = v_from_year AND month = v_from_month
      ON DUPLICATE KEY UPDATE limit_amount = VALUES(limit_amount), alert_level = 'none';
  ELSE
    INSERT IGNORE INTO budgets (workspace_id, category_id, year, month, limit_amount, alert_level)
      SELECT workspace_id, category_id, v_to_year, v_to_month, limit_amount, 'none'
      FROM budgets WHERE workspace_id = v_workspace_id AND year = v_from_year AND month = v_from_month;
  END IF;
  SELECT COUNT(*) INTO v_after FROM budgets WHERE workspace_id = v_workspace_id AND year = v_to_year AND month = v_to_month;
  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('copied_count',
    IF(v_overwrite,
      (SELECT COUNT(*) FROM budgets WHERE workspace_id = v_workspace_id AND year = v_from_year AND month = v_from_month),
      v_after - v_before
    )
  )) AS result;
END$$

CREATE PROCEDURE sp_budget_progress(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_category_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_budget_id BIGINT UNSIGNED;
  DECLARE v_limit_amount DECIMAL(14,2);
  DECLARE v_alert_level VARCHAR(20);
  DECLARE v_date_from DATE;
  DECLARE v_date_to DATE;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_category_id = CAST(JSON_EXTRACT(p_json, '$.category_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_date_from = STR_TO_DATE(CONCAT(v_year, '-', v_month, '-01'), '%Y-%m-%d');
  SET v_date_to = LAST_DAY(v_date_from);

  SELECT id, limit_amount, alert_level INTO v_budget_id, v_limit_amount, v_alert_level
    FROM budgets WHERE workspace_id = v_workspace_id AND category_id = v_category_id AND year = v_year AND month = v_month;

  IF v_budget_id IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'budget_id', v_budget_id,
        'limit_amount', v_limit_amount,
        'alert_level', v_alert_level,
        'spent_amount', COALESCE((
          SELECT SUM(e.amount) FROM expenses e
          WHERE e.workspace_id = v_workspace_id AND e.category_id = v_category_id
            AND e.date BETWEEN v_date_from AND v_date_to
        ), 0)
      )
    ) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_budget_update_alert_level(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_budget_id BIGINT UNSIGNED;
  DECLARE v_alert_level VARCHAR(20);
  DECLARE v_exists BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_budget_id = CAST(JSON_EXTRACT(p_json, '$.budget_id') AS UNSIGNED);
  SET v_alert_level = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.alert_level'));

  SELECT id INTO v_exists FROM budgets WHERE id = v_budget_id AND workspace_id = v_workspace_id;

  IF v_exists IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    UPDATE budgets SET alert_level = v_alert_level WHERE id = v_budget_id;
    COMMIT;

    SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('id', v_budget_id, 'alert_level', v_alert_level)) AS result;
  END IF;
END$$

-- ---------------------------------------------------------------------------
-- M-17 Notificaciones (version minima in-app)
-- ---------------------------------------------------------------------------

CREATE PROCEDURE sp_notification_create(IN p_json JSON)
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_type VARCHAR(50);
  DECLARE v_title VARCHAR(150);
  DECLARE v_body VARCHAR(500);
  DECLARE v_route VARCHAR(255);
  DECLARE v_payload JSON;
  DECLARE v_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_workspace_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.workspace_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.workspace_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED));
  SET v_type = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.type'));
  SET v_title = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.title'));
  SET v_body = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.body'));
  SET v_route = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.route')) = 'NULL' OR JSON_EXTRACT(p_json, '$.route') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.route')));
  SET v_payload = JSON_EXTRACT(p_json, '$.payload');

  START TRANSACTION;
  INSERT INTO notifications (user_id, workspace_id, type, title, body, route, payload, sent_at)
    VALUES (v_user_id, v_workspace_id, v_type, v_title, v_body, v_route, v_payload, NOW());
  SET v_id = LAST_INSERT_ID();
  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'id', v_id, 'type', v_type, 'title', v_title, 'body', v_body, 'route', v_route,
      'workspace_id', v_workspace_id, 'payload', v_payload, 'read_at', NULL,
      'created_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ')
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_notification_list(IN p_json JSON)
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_page INT;
  DECLARE v_per_page INT;
  DECLARE v_offset INT;
  DECLARE v_unread_only BOOLEAN;
  DECLARE v_total INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_page = COALESCE(CAST(JSON_EXTRACT(p_json, '$.page') AS UNSIGNED), 1);
  SET v_per_page = COALESCE(CAST(JSON_EXTRACT(p_json, '$.per_page') AS UNSIGNED), 20);
  SET v_offset = (v_page - 1) * v_per_page;
  SET v_unread_only = COALESCE(CAST(JSON_EXTRACT(p_json, '$.unread_only') AS UNSIGNED), 0);

  SELECT COUNT(*) INTO v_total FROM notifications
  WHERE user_id = v_user_id AND (v_unread_only = 0 OR read_at IS NULL);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'total', v_total,
      'items', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', t.id, 'type', t.type, 'title', t.title, 'body', t.body, 'route', t.route,
          'workspace_id', t.workspace_id, 'payload', t.payload,
          'read_at', DATE_FORMAT(t.read_at, '%Y-%m-%dT%H:%i:%sZ'),
          'created_at', DATE_FORMAT(t.created_at, '%Y-%m-%dT%H:%i:%sZ')
        ))
        FROM (
          SELECT id, type, title, body, route, workspace_id, payload, read_at, created_at
          FROM notifications
          WHERE user_id = v_user_id AND (v_unread_only = 0 OR read_at IS NULL)
          ORDER BY created_at DESC
          LIMIT v_per_page OFFSET v_offset
        ) t
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_notification_unread_count(IN p_json JSON)
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_count BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SELECT COUNT(*) INTO v_count FROM notifications WHERE user_id = v_user_id AND read_at IS NULL;

  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('count', v_count)) AS result;
END$$

CREATE PROCEDURE sp_notification_mark_read(IN p_json JSON)
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_notification_id BIGINT UNSIGNED;
  DECLARE v_exists BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_notification_id = CAST(JSON_EXTRACT(p_json, '$.notification_id') AS UNSIGNED);

  SELECT id INTO v_exists FROM notifications WHERE id = v_notification_id AND user_id = v_user_id;

  IF v_exists IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    UPDATE notifications SET read_at = NOW() WHERE id = v_notification_id AND read_at IS NULL;
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', t.id, 'type', t.type, 'title', t.title, 'body', t.body, 'route', t.route,
        'workspace_id', t.workspace_id, 'payload', t.payload,
        'read_at', DATE_FORMAT(t.read_at, '%Y-%m-%dT%H:%i:%sZ'),
        'created_at', DATE_FORMAT(t.created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM notifications t WHERE t.id = v_notification_id;
  END IF;
END$$

CREATE PROCEDURE sp_notification_mark_all_read(IN p_json JSON)
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_updated INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);

  START TRANSACTION;
  UPDATE notifications SET read_at = NOW() WHERE user_id = v_user_id AND read_at IS NULL;
  SET v_updated = ROW_COUNT();
  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('updated_count', v_updated)) AS result;
END$$

-- M-17: preferencias de notificación. Sin fila propia todavía -> defaults
-- (mismos que documenta ESPECIFICACION_TECNICA.md): recordatorios a 3 y 1
-- día, alerta de presupuesto en warning/reached/exceeded, in_app+push
-- activados y email desactivado, nada silenciado.
CREATE PROCEDURE sp_notification_preference_get(IN p_json JSON)
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'service_reminder_days', COALESCE((SELECT service_reminder_days FROM notification_preferences WHERE user_id = v_user_id), JSON_ARRAY(3, 1)),
      'budget_alert_levels', COALESCE((SELECT budget_alert_levels FROM notification_preferences WHERE user_id = v_user_id), JSON_ARRAY('warning', 'reached', 'exceeded')),
      'channels', COALESCE((SELECT channels FROM notification_preferences WHERE user_id = v_user_id), JSON_OBJECT('in_app', TRUE, 'push', TRUE, 'email', FALSE)),
      'muted_types', COALESCE((SELECT muted_types FROM notification_preferences WHERE user_id = v_user_id), JSON_ARRAY())
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_notification_preference_upsert(IN p_json JSON)
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_service_reminder_days JSON;
  DECLARE v_budget_alert_levels JSON;
  DECLARE v_channels JSON;
  DECLARE v_muted_types JSON;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_service_reminder_days = JSON_EXTRACT(p_json, '$.service_reminder_days');
  SET v_budget_alert_levels = JSON_EXTRACT(p_json, '$.budget_alert_levels');
  SET v_channels = JSON_EXTRACT(p_json, '$.channels');
  SET v_muted_types = JSON_EXTRACT(p_json, '$.muted_types');

  START TRANSACTION;
  INSERT INTO notification_preferences (user_id, service_reminder_days, budget_alert_levels, channels, muted_types)
    VALUES (v_user_id, v_service_reminder_days, v_budget_alert_levels, v_channels, v_muted_types)
    ON DUPLICATE KEY UPDATE
      service_reminder_days = v_service_reminder_days,
      budget_alert_levels = v_budget_alert_levels,
      channels = v_channels,
      muted_types = v_muted_types;
  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'service_reminder_days', v_service_reminder_days,
      'budget_alert_levels', v_budget_alert_levels,
      'channels', v_channels,
      'muted_types', v_muted_types
    )
  ) AS result;
END$$

-- M-17: dispositivos push (FCM). El token es UNIQUE a nivel tabla: si el
-- mismo token vuelve a registrarse con otro user_id (dispositivo compartido,
-- o el usuario cerro sesion y otro entro en el mismo celular), se reasigna al
-- nuevo dueño en vez de fallar por duplicado.
CREATE PROCEDURE sp_push_device_upsert(IN p_json JSON)
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_token VARCHAR(255);
  DECLARE v_platform VARCHAR(20);
  DECLARE v_device_name VARCHAR(100);
  DECLARE v_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_token = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.token'));
  SET v_platform = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.platform'));
  SET v_device_name = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.device_name')) = 'NULL' OR JSON_EXTRACT(p_json, '$.device_name') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.device_name')));

  IF NOT EXISTS (SELECT 1 FROM push_platforms WHERE code = v_platform) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_PLATFORM') AS result;
  ELSE
    START TRANSACTION;
    INSERT INTO push_devices (user_id, token, platform, device_name, last_seen_at)
      VALUES (v_user_id, v_token, v_platform, v_device_name, NOW())
      ON DUPLICATE KEY UPDATE
        user_id = v_user_id, platform = v_platform, device_name = v_device_name, last_seen_at = NOW();
    SET v_id = (SELECT id FROM push_devices WHERE token = v_token);
    COMMIT;

    SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('id', v_id)) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_push_device_delete(IN p_json JSON)
BEGIN
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_token VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_token = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.token'));

  START TRANSACTION;
  DELETE FROM push_devices WHERE token = v_token AND user_id = v_user_id;
  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT()) AS result;
END$$

-- Usado por el NotificationDispatcher: dado un set de user_id (miembros de un
-- workspace, o un solo usuario), trae todos sus tokens activos para mandar
-- el push por FCM. Tambien acepta borrar un token puntual cuando FCM
-- responde que ya no existe (dispositivo desinstalo la app).
CREATE PROCEDURE sp_push_device_list_by_users(IN p_json JSON)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT('user_id', pd.user_id, 'token', pd.token, 'platform', pd.platform))
      FROM push_devices pd
      WHERE pd.user_id IN (
        SELECT ids.user_id
        FROM JSON_TABLE(JSON_EXTRACT(p_json, '$.user_ids'), '$[*]' COLUMNS (user_id BIGINT UNSIGNED PATH '$')) ids
      )
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_sidebar_sections_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'sections', COALESCE((
        SELECT JSON_ARRAYAGG(section_code)
        FROM workspace_sidebar_sections
        WHERE workspace_id = v_workspace_id AND user_id = v_user_id
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_sidebar_sections_set(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);

  START TRANSACTION;

  DELETE FROM workspace_sidebar_sections WHERE workspace_id = v_workspace_id AND user_id = v_user_id;

  INSERT INTO workspace_sidebar_sections (workspace_id, user_id, section_code)
    SELECT v_workspace_id, v_user_id, sections.code
    FROM JSON_TABLE(JSON_EXTRACT(p_json, '$.sections'), '$[*]' COLUMNS (code VARCHAR(20) PATH '$')) sections;

  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'sections', COALESCE((
        SELECT JSON_ARRAYAGG(section_code)
        FROM workspace_sidebar_sections
        WHERE workspace_id = v_workspace_id AND user_id = v_user_id
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_health_score_inputs(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_total_income DECIMAL(14,2);
  DECLARE v_total_expenses DECIMAL(14,2);
  DECLARE v_total_installments DECIMAL(14,2);
  DECLARE v_savings_in_month DECIMAL(14,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);

  SET v_total_income = COALESCE((
    SELECT SUM(amount) FROM income_entries WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month
  ), 0);
  SET v_total_expenses = COALESCE((
    SELECT SUM(amount) FROM expenses WHERE workspace_id = v_workspace_id AND YEAR(date) = v_year AND MONTH(date) = v_month
  ), 0);
  SET v_total_installments = COALESCE((
    SELECT SUM(amount) FROM installment_payments
    WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month AND status IN ('pending', 'paid')
  ), 0);

  -- source='closing': la fila ya trae el periodo exacto (savings_movements via
  -- year/month propios seteados por sp_monthly_closing_allocate; savings_goal_movements
  -- no tiene year/month, se une via closing_id -> monthly_closings). Para el mes en
  -- vivo (sin cierre todavia) esto da 0 naturalmente, no hace falta un IF aparte.
  SET v_savings_in_month =
    COALESCE((
      SELECT SUM(sm.amount) FROM savings_movements sm
      JOIN savings_wallet sw ON sw.id = sm.wallet_id
      WHERE sw.workspace_id = v_workspace_id AND sm.source = 'closing' AND sm.type = 'deposit'
        AND sm.year = v_year AND sm.month = v_month
    ), 0)
    + COALESCE((
      SELECT SUM(sgm.amount) FROM savings_goal_movements sgm
      JOIN monthly_closings mc ON mc.id = sgm.closing_id
      WHERE mc.workspace_id = v_workspace_id AND mc.year = v_year AND mc.month = v_month
        AND sgm.source = 'closing' AND sgm.type = 'contribution'
    ), 0)
    + COALESCE((
      SELECT SUM(sm.amount) FROM savings_movements sm
      JOIN savings_wallet sw ON sw.id = sm.wallet_id
      WHERE sw.workspace_id = v_workspace_id AND sm.source = 'manual' AND sm.type = 'deposit'
        AND sm.year = v_year AND sm.month = v_month
    ), 0);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'total_income', v_total_income,
      'total_expenses', v_total_expenses,
      'total_installments', v_total_installments,
      'savings_in_month', v_savings_in_month,
      'budgets', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'limit_amount', b.limit_amount,
          'spent_amount', COALESCE((
            SELECT SUM(e.amount) FROM expenses e
            WHERE e.workspace_id = v_workspace_id AND e.category_id = b.category_id
              AND YEAR(e.date) = v_year AND MONTH(e.date) = v_month
          ), 0)
        ))
        FROM budgets b WHERE b.workspace_id = v_workspace_id AND b.year = v_year AND b.month = v_month
      ), JSON_ARRAY()),
      'services', JSON_OBJECT(
        'paid_on_time', (SELECT COUNT(*) FROM service_payments WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month AND status = 'paid' AND was_late = 0),
        'paid_late', (SELECT COUNT(*) FROM service_payments WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month AND status = 'paid' AND was_late = 1),
        'overdue', (SELECT COUNT(*) FROM service_payments WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month AND status = 'overdue')
      )
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_health_score_upsert(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_score TINYINT UNSIGNED;
  DECLARE v_breakdown JSON;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_score = CAST(JSON_EXTRACT(p_json, '$.score') AS UNSIGNED);
  SET v_breakdown = JSON_EXTRACT(p_json, '$.breakdown_json');

  START TRANSACTION;

  INSERT INTO financial_health_scores (workspace_id, year, month, score, breakdown_json)
    VALUES (v_workspace_id, v_year, v_month, v_score, v_breakdown)
    ON DUPLICATE KEY UPDATE score = v_score, breakdown_json = v_breakdown;

  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT('year', v_year, 'month', v_month, 'score', v_score, 'breakdown', v_breakdown)
  ) AS result;
END$$

CREATE PROCEDURE sp_health_score_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM financial_health_scores WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT('year', year, 'month', month, 'score', score, 'breakdown', breakdown_json)
    ) AS result
    FROM financial_health_scores WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month;
  END IF;
END$$

CREATE PROCEDURE sp_health_score_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_months INT;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_months = CAST(JSON_EXTRACT(p_json, '$.months') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT('year', t.year, 'month', t.month, 'score', t.score, 'breakdown', t.breakdown_json))
      FROM (
        SELECT year, month, score, breakdown_json FROM financial_health_scores
        WHERE workspace_id = v_workspace_id
        ORDER BY year DESC, month DESC
        LIMIT v_months
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_workspace_list_all(IN p_json JSON)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((SELECT JSON_ARRAYAGG(JSON_OBJECT('id', id)) FROM workspaces), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_monthly_closing_compute(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_date_from DATE;
  DECLARE v_date_to DATE;
  DECLARE v_total_income DECIMAL(14,2);
  DECLARE v_total_expenses DECIMAL(14,2);
  DECLARE v_total_services DECIMAL(14,2);
  DECLARE v_total_installments DECIMAL(14,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_date_from = STR_TO_DATE(CONCAT(v_year, '-', v_month, '-01'), '%Y-%m-%d');
  SET v_date_to = LAST_DAY(v_date_from);

  SET v_total_income = COALESCE((SELECT SUM(amount) FROM income_entries WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month), 0);
  SET v_total_expenses = COALESCE((SELECT SUM(amount) FROM expenses WHERE workspace_id = v_workspace_id AND date BETWEEN v_date_from AND v_date_to), 0);
  SET v_total_services = COALESCE((SELECT SUM(amount_paid) FROM service_payments WHERE workspace_id = v_workspace_id AND status = 'paid' AND YEAR(paid_at) = v_year AND MONTH(paid_at) = v_month), 0);
  SET v_total_installments = COALESCE((SELECT SUM(amount) FROM installment_payments WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month AND status IN ('pending', 'paid')), 0);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'total_income', v_total_income,
      'total_expenses', v_total_expenses,
      'total_services', v_total_services,
      'total_installments', v_total_installments,
      'breakdown', JSON_OBJECT(
        'by_category', COALESCE((
          SELECT JSON_ARRAYAGG(JSON_OBJECT(
            'category_id', t.category_id, 'category_name', t.category_name, 'amount', t.amount,
            'pct', IF(v_total_expenses = 0, 0, ROUND(t.amount / v_total_expenses * 100, 1))
          ))
          FROM (
            SELECT e.category_id, COALESCE(c.name, 'Sin categoría') AS category_name, SUM(e.amount) AS amount
            FROM expenses e LEFT JOIN categories c ON c.id = e.category_id
            WHERE e.workspace_id = v_workspace_id AND e.date BETWEEN v_date_from AND v_date_to
            GROUP BY e.category_id, category_name
            ORDER BY amount DESC
          ) t
        ), JSON_ARRAY()),
        'by_user', COALESCE((
          SELECT JSON_ARRAYAGG(JSON_OBJECT(
            'user_id', g.user_id, 'user_name', g.user_name,
            'income', g.income, 'expenses', g.expenses, 'installments', g.installments
          ))
          FROM (
            SELECT u.id AS user_id, u.name AS user_name,
              COALESCE((SELECT SUM(ie.amount) FROM income_entries ie WHERE ie.workspace_id = v_workspace_id AND ie.year = v_year AND ie.month = v_month AND ie.user_id = u.id), 0) AS income,
              COALESCE((SELECT SUM(ex.amount) FROM expenses ex WHERE ex.workspace_id = v_workspace_id AND ex.date BETWEEN v_date_from AND v_date_to AND ex.user_id = u.id), 0) AS expenses,
              COALESCE((SELECT SUM(ip.amount) FROM installment_payments ip JOIN installments i ON i.id = ip.installment_id WHERE i.workspace_id = v_workspace_id AND ip.year = v_year AND ip.month = v_month AND ip.status IN ('pending', 'paid') AND i.user_id = u.id), 0) AS installments
            FROM users u JOIN workspace_users wu ON wu.user_id = u.id AND wu.workspace_id = v_workspace_id
          ) g
          WHERE g.income > 0 OR g.expenses > 0 OR g.installments > 0
        ), JSON_ARRAY()),
        'services', COALESCE((
          SELECT JSON_ARRAYAGG(JSON_OBJECT(
            'service_id', s.id, 'name', s.name, 'status', sp.status,
            'amount_paid', sp.amount_paid, 'late_fee_applied', sp.late_fee_applied, 'was_late', (sp.was_late = 1)
          ))
          FROM service_payments sp JOIN services s ON s.id = sp.service_id
          WHERE sp.workspace_id = v_workspace_id AND sp.year = v_year AND sp.month = v_month
        ), JSON_ARRAY()),
        'installments', COALESCE((
          SELECT JSON_ARRAYAGG(JSON_OBJECT(
            'installment_id', i.id, 'description', i.description, 'number', ip.number,
            'installments_count', i.installments_count, 'amount', ip.amount
          ))
          FROM installment_payments ip JOIN installments i ON i.id = ip.installment_id
          WHERE ip.workspace_id = v_workspace_id AND ip.year = v_year AND ip.month = v_month AND ip.status IN ('pending', 'paid')
        ), JSON_ARRAY()),
        'budgets', COALESCE((
          SELECT JSON_ARRAYAGG(JSON_OBJECT(
            'category_id', b.category_id, 'category_name', c.name, 'limit_amount', b.limit_amount,
            'spent_amount', COALESCE((
              SELECT SUM(e.amount) FROM expenses e
              WHERE e.workspace_id = v_workspace_id AND e.category_id = b.category_id AND e.date BETWEEN v_date_from AND v_date_to
            ), 0),
            'pct', IF(b.limit_amount = 0, 0, ROUND(COALESCE((
              SELECT SUM(e.amount) FROM expenses e
              WHERE e.workspace_id = v_workspace_id AND e.category_id = b.category_id AND e.date BETWEEN v_date_from AND v_date_to
            ), 0) / b.limit_amount * 100, 1))
          ))
          FROM budgets b
          JOIN categories c ON c.id = b.category_id
          WHERE b.workspace_id = v_workspace_id AND b.year = v_year AND b.month = v_month
        ), JSON_ARRAY()),
        'expenses_count', (SELECT COUNT(*) FROM expenses WHERE workspace_id = v_workspace_id AND date BETWEEN v_date_from AND v_date_to),
        'income_entries_count', (SELECT COUNT(*) FROM income_entries WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month)
      )
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_monthly_closing_create(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_total_income DECIMAL(14,2);
  DECLARE v_total_expenses DECIMAL(14,2);
  DECLARE v_total_services DECIMAL(14,2);
  DECLARE v_total_installments DECIMAL(14,2);
  DECLARE v_remaining DECIMAL(14,2);
  DECLARE v_breakdown JSON;
  DECLARE v_closed_by VARCHAR(20);
  DECLARE v_allocation_status VARCHAR(20);
  DECLARE v_closing_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR 1062
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'DUPLICATE') AS result;
  END;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_total_income = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.total_income')) AS DECIMAL(14,2));
  SET v_total_expenses = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.total_expenses')) AS DECIMAL(14,2));
  SET v_total_services = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.total_services')) AS DECIMAL(14,2));
  SET v_total_installments = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.total_installments')) AS DECIMAL(14,2));
  SET v_remaining = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.remaining_amount')) AS DECIMAL(14,2));
  SET v_breakdown = JSON_EXTRACT(p_json, '$.breakdown_json');
  SET v_closed_by = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.closed_by')), 'system');
  SET v_allocation_status = IF(v_remaining > 0, 'pending', 'not_applicable');

  START TRANSACTION;

  INSERT INTO monthly_closings (
    workspace_id, month, year, total_income, total_expenses, total_services, total_installments,
    savings_generated, remaining_amount, allocation_status, breakdown_json, closed_by
  ) VALUES (
    v_workspace_id, v_month, v_year, v_total_income, v_total_expenses, v_total_services, v_total_installments,
    0, v_remaining, v_allocation_status, v_breakdown, v_closed_by
  );
  SET v_closing_id = LAST_INSERT_ID();

  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'id', v_closing_id, 'year', v_year, 'month', v_month,
      'total_income', v_total_income, 'total_expenses', v_total_expenses,
      'total_services', v_total_services, 'total_installments', v_total_installments,
      'remaining_amount', v_remaining, 'savings_generated', 0,
      'allocated_to_wallet', 0, 'allocated_to_goals', 0,
      'allocation_status', v_allocation_status, 'allocated_at', NULL,
      'breakdown', v_breakdown, 'closed_by', v_closed_by,
      'closed_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sZ'), 'health_score', NULL
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_monthly_closing_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_next_year SMALLINT UNSIGNED;
  DECLARE v_next_month TINYINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_year = CAST(JSON_EXTRACT(p_json, '$.year') AS UNSIGNED);
  SET v_month = CAST(JSON_EXTRACT(p_json, '$.month') AS UNSIGNED);
  SET v_next_year = IF(v_month = 12, v_year + 1, v_year);
  SET v_next_month = IF(v_month = 12, 1, v_month + 1);

  IF NOT EXISTS (SELECT 1 FROM monthly_closings WHERE workspace_id = v_workspace_id AND year = v_year AND month = v_month) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', mc.id, 'year', mc.year, 'month', mc.month,
        'total_income', mc.total_income, 'total_expenses', mc.total_expenses,
        'total_services', mc.total_services, 'total_installments', mc.total_installments,
        'remaining_amount', mc.remaining_amount, 'savings_generated', mc.savings_generated,
        'allocated_to_wallet', mc.allocated_to_wallet, 'allocated_to_goals', mc.allocated_to_goals,
        'allocated_to_next_month', mc.allocated_to_next_month,
        'allocation_status', mc.allocation_status,
        'allocated_at', DATE_FORMAT(mc.allocated_at, '%Y-%m-%dT%H:%i:%sZ'),
        'breakdown', mc.breakdown_json, 'closed_by', mc.closed_by,
        'closed_at', DATE_FORMAT(mc.closed_at, '%Y-%m-%dT%H:%i:%sZ'),
        'health_score', hs.score,
        'next_month_open', (NOT EXISTS (
          SELECT 1 FROM monthly_closings nmc
          WHERE nmc.workspace_id = v_workspace_id AND nmc.year = v_next_year AND nmc.month = v_next_month
        ))
      )
    ) AS result
    FROM monthly_closings mc
    LEFT JOIN financial_health_scores hs ON hs.workspace_id = mc.workspace_id AND hs.year = mc.year AND hs.month = mc.month
    WHERE mc.workspace_id = v_workspace_id AND mc.year = v_year AND mc.month = v_month;
  END IF;
END$$

CREATE PROCEDURE sp_monthly_closing_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'id', t.id, 'year', t.year, 'month', t.month,
        'total_income', t.total_income, 'total_expenses', t.total_expenses,
        'total_services', t.total_services, 'total_installments', t.total_installments,
        'remaining_amount', t.remaining_amount, 'savings_generated', t.savings_generated,
        'allocated_to_wallet', t.allocated_to_wallet, 'allocated_to_goals', t.allocated_to_goals,
        'allocated_to_next_month', t.allocated_to_next_month,
        'allocation_status', t.allocation_status,
        'allocated_at', DATE_FORMAT(t.allocated_at, '%Y-%m-%dT%H:%i:%sZ'),
        'closed_by', t.closed_by, 'closed_at', DATE_FORMAT(t.closed_at, '%Y-%m-%dT%H:%i:%sZ'),
        'health_score', t.score
      ))
      FROM (
        SELECT mc.*, hs.score
        FROM monthly_closings mc
        LEFT JOIN financial_health_scores hs ON hs.workspace_id = mc.workspace_id AND hs.year = mc.year AND hs.month = mc.month
        WHERE mc.workspace_id = v_workspace_id
        ORDER BY mc.year DESC, mc.month DESC
      ) t
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_monthly_closing_allocate(IN p_json JSON)
main_block: BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_closing_id BIGINT UNSIGNED;
  DECLARE v_user_id BIGINT UNSIGNED;
  DECLARE v_to_wallet DECIMAL(14,2);
  DECLARE v_to_next_month DECIMAL(14,2);
  DECLARE v_goals_json JSON;
  DECLARE v_goals_count INT;
  DECLARE v_i INT;
  DECLARE v_goal_id BIGINT UNSIGNED;
  DECLARE v_goal_amount DECIMAL(14,2);
  DECLARE v_goals_sum DECIMAL(14,2);
  DECLARE v_allocation_status VARCHAR(20);
  DECLARE v_remaining DECIMAL(14,2);
  DECLARE v_allocated_wallet DECIMAL(14,2);
  DECLARE v_allocated_goals DECIMAL(14,2);
  DECLARE v_allocated_next_month DECIMAL(14,2);
  DECLARE v_unallocated DECIMAL(14,2);
  DECLARE v_year SMALLINT UNSIGNED;
  DECLARE v_month TINYINT UNSIGNED;
  DECLARE v_next_year SMALLINT UNSIGNED;
  DECLARE v_next_month TINYINT UNSIGNED;
  DECLARE v_wallet_id BIGINT UNSIGNED;
  DECLARE v_wallet_balance DECIMAL(14,2);
  DECLARE v_new_wallet_balance DECIMAL(14,2);
  DECLARE v_goal_status VARCHAR(20);
  DECLARE v_goal_current DECIMAL(14,2);
  DECLARE v_goal_target DECIMAL(14,2);
  DECLARE v_goal_new_amount DECIMAL(14,2);
  DECLARE v_goal_new_status VARCHAR(20);
  DECLARE v_new_allocated_wallet DECIMAL(14,2);
  DECLARE v_new_allocated_goals DECIMAL(14,2);
  DECLARE v_new_allocated_next_month DECIMAL(14,2);
  DECLARE v_new_alloc_status VARCHAR(20);
  DECLARE v_note VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_closing_id = CAST(JSON_EXTRACT(p_json, '$.closing_id') AS UNSIGNED);
  SET v_user_id = CAST(JSON_EXTRACT(p_json, '$.user_id') AS UNSIGNED);
  SET v_to_wallet = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.to_wallet')) AS DECIMAL(14,2));
  SET v_to_next_month = COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.to_next_month')) AS DECIMAL(14,2)), 0);
  SET v_goals_json = COALESCE(JSON_EXTRACT(p_json, '$.to_goals'), JSON_ARRAY());
  SET v_goals_count = JSON_LENGTH(v_goals_json);

  START TRANSACTION;

  SELECT allocation_status, remaining_amount, allocated_to_wallet, allocated_to_goals, allocated_to_next_month, year, month
    INTO v_allocation_status, v_remaining, v_allocated_wallet, v_allocated_goals, v_allocated_next_month, v_year, v_month
    FROM monthly_closings WHERE id = v_closing_id AND workspace_id = v_workspace_id FOR UPDATE;

  IF v_allocation_status IS NULL THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
    LEAVE main_block;
  END IF;

  IF v_allocation_status = 'not_applicable' THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
    LEAVE main_block;
  END IF;

  SET v_next_year = IF(v_month = 12, v_year + 1, v_year);
  SET v_next_month = IF(v_month = 12, 1, v_month + 1);

  IF v_to_next_month > 0 AND EXISTS (
    SELECT 1 FROM monthly_closings WHERE workspace_id = v_workspace_id AND year = v_next_year AND month = v_next_month
  ) THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NEXT_MONTH_CLOSED') AS result;
    LEAVE main_block;
  END IF;

  SET v_i = 0;
  SET v_goals_sum = 0;
  WHILE v_i < v_goals_count DO
    SET v_goals_sum = v_goals_sum + CAST(JSON_UNQUOTE(JSON_EXTRACT(v_goals_json, CONCAT('$[', v_i, '].amount'))) AS DECIMAL(14,2));
    SET v_i = v_i + 1;
  END WHILE;

  SET v_unallocated = GREATEST(v_remaining, 0) - v_allocated_wallet - v_allocated_goals - v_allocated_next_month;

  IF (v_to_wallet + v_goals_sum + v_to_next_month) > v_unallocated THEN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INSUFFICIENT_FUNDS') AS result;
    LEAVE main_block;
  END IF;

  -- Validar TODAS las metas antes de mutar nada (existencia + activa).
  SET v_i = 0;
  WHILE v_i < v_goals_count DO
    SET v_goal_id = CAST(JSON_EXTRACT(v_goals_json, CONCAT('$[', v_i, '].goal_id')) AS UNSIGNED);
    SET v_goal_status = NULL;
    SELECT status INTO v_goal_status FROM savings_goals WHERE id = v_goal_id AND workspace_id = v_workspace_id FOR UPDATE;
    IF v_goal_status IS NULL OR v_goal_status <> 'active' THEN
      ROLLBACK;
      SELECT JSON_OBJECT('success', FALSE, 'error', 'INVALID_STATE') AS result;
      LEAVE main_block;
    END IF;
    SET v_i = v_i + 1;
  END WHILE;

  SET v_note = CONCAT('Sobrante del cierre ', LPAD(v_month, 2, '0'), '/', v_year);

  IF v_to_wallet > 0 THEN
    SELECT id, balance INTO v_wallet_id, v_wallet_balance FROM savings_wallet WHERE workspace_id = v_workspace_id FOR UPDATE;
    SET v_new_wallet_balance = v_wallet_balance + v_to_wallet;
    UPDATE savings_wallet SET balance = v_new_wallet_balance WHERE id = v_wallet_id;
    INSERT INTO savings_movements (wallet_id, user_id, type, amount, balance_after, source, closing_id, month, year, note)
      VALUES (v_wallet_id, v_user_id, 'deposit', v_to_wallet, v_new_wallet_balance, 'closing', v_closing_id, v_month, v_year, v_note);
  END IF;

  IF v_to_next_month > 0 THEN
    INSERT INTO income_entries (workspace_id, user_id, amount, concept, date, year, month, source, closing_id)
      VALUES (v_workspace_id, v_user_id, v_to_next_month, v_note,
        STR_TO_DATE(CONCAT(v_next_year, '-', v_next_month, '-01'), '%Y-%m-%d'), v_next_year, v_next_month,
        'closing', v_closing_id);
  END IF;

  SET v_i = 0;
  WHILE v_i < v_goals_count DO
    SET v_goal_id = CAST(JSON_EXTRACT(v_goals_json, CONCAT('$[', v_i, '].goal_id')) AS UNSIGNED);
    SET v_goal_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(v_goals_json, CONCAT('$[', v_i, '].amount'))) AS DECIMAL(14,2));

    SELECT current_amount, target_amount INTO v_goal_current, v_goal_target FROM savings_goals WHERE id = v_goal_id;
    SET v_goal_new_amount = v_goal_current + v_goal_amount;
    SET v_goal_new_status = IF(v_goal_new_amount >= v_goal_target, 'completed', 'active');
    UPDATE savings_goals SET current_amount = v_goal_new_amount, status = v_goal_new_status,
      completed_at = IF(v_goal_new_status = 'completed', NOW(), NULL)
      WHERE id = v_goal_id;
    INSERT INTO savings_goal_movements (goal_id, user_id, type, amount, source, closing_id, note)
      VALUES (v_goal_id, v_user_id, 'contribution', v_goal_amount, 'closing', v_closing_id, v_note);

    SET v_i = v_i + 1;
  END WHILE;

  SET v_new_allocated_wallet = v_allocated_wallet + v_to_wallet;
  SET v_new_allocated_goals = v_allocated_goals + v_goals_sum;
  SET v_new_allocated_next_month = v_allocated_next_month + v_to_next_month;
  SET v_new_alloc_status = IF(
    (GREATEST(v_remaining, 0) - v_new_allocated_wallet - v_new_allocated_goals - v_new_allocated_next_month) <= 0,
    'allocated', 'pending'
  );

  UPDATE monthly_closings SET
    allocated_to_wallet = v_new_allocated_wallet,
    allocated_to_goals = v_new_allocated_goals,
    allocated_to_next_month = v_new_allocated_next_month,
    savings_generated = v_new_allocated_wallet + v_new_allocated_goals,
    allocation_status = v_new_alloc_status,
    allocated_at = NOW()
    WHERE id = v_closing_id;

  INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value)
    VALUES (v_workspace_id, v_user_id, 'monthly_closing', v_closing_id, 'updated',
      CONCAT('Asignó el sobrante del cierre ', LPAD(v_month, 2, '0'), '/', v_year),
      JSON_OBJECT('allocated_to_wallet', v_allocated_wallet, 'allocated_to_goals', v_allocated_goals, 'allocated_to_next_month', v_allocated_next_month),
      JSON_OBJECT('allocated_to_wallet', v_new_allocated_wallet, 'allocated_to_goals', v_new_allocated_goals, 'allocated_to_next_month', v_new_allocated_next_month));

  COMMIT;

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'id', mc.id, 'year', mc.year, 'month', mc.month,
      'total_income', mc.total_income, 'total_expenses', mc.total_expenses,
      'total_services', mc.total_services, 'total_installments', mc.total_installments,
      'remaining_amount', mc.remaining_amount, 'savings_generated', mc.savings_generated,
      'allocated_to_wallet', mc.allocated_to_wallet, 'allocated_to_goals', mc.allocated_to_goals,
      'allocated_to_next_month', mc.allocated_to_next_month,
      'allocation_status', mc.allocation_status,
      'allocated_at', DATE_FORMAT(mc.allocated_at, '%Y-%m-%dT%H:%i:%sZ'),
      'closed_by', mc.closed_by, 'closed_at', DATE_FORMAT(mc.closed_at, '%Y-%m-%dT%H:%i:%sZ'),
      'next_month_open', (NOT EXISTS (
        SELECT 1 FROM monthly_closings nmc
        WHERE nmc.workspace_id = v_workspace_id AND nmc.year = v_next_year AND nmc.month = v_next_month
      ))
    )
  ) AS result
  FROM monthly_closings mc WHERE mc.id = v_closing_id;
END$$

CREATE PROCEDURE sp_smart_suggestion_candidates(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_date_from DATE;
  DECLARE v_min_occurrences INT;
  DECLARE v_tolerance_pct DECIMAL(5,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_date_from = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.date_from')) AS DATE);
  SET v_min_occurrences = CAST(JSON_EXTRACT(p_json, '$.min_occurrences') AS UNSIGNED);
  SET v_tolerance_pct = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.tolerance_pct')) AS DECIMAL(5,2));

  -- "description" = la mas RECIENTE del grupo (simplificacion de "la mas
  -- frecuente" del spec original - equivalente en la practica, mucho mas
  -- simple en SQL puro). Separador \u0001 en el GROUP_CONCAT para que una
  -- descripcion con comas no rompa el SUBSTRING_INDEX.
  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'normalized_key', t.normalized_key,
        'description', t.description,
        'avg_amount', t.avg_amount,
        'occurrences', t.months_count,
        'last_seen_date', t.last_seen_date,
        'suggested_due_day', t.avg_day,
        'sample_expense_ids', t.sample_expense_ids
      ))
      FROM (
        SELECT
          LOWER(TRIM(REGEXP_REPLACE(e.description, '[0-9#*/.-]+', ''))) AS normalized_key,
          SUBSTRING_INDEX(GROUP_CONCAT(e.description ORDER BY e.date DESC SEPARATOR '\u0001'), '\u0001', 1) AS description,
          ROUND(AVG(e.amount), 2) AS avg_amount,
          MIN(e.amount) AS min_amount,
          MAX(e.amount) AS max_amount,
          COUNT(DISTINCT DATE_FORMAT(e.date, '%Y-%m')) AS months_count,
          ROUND(AVG(DAY(e.date))) AS avg_day,
          MAX(e.date) AS last_seen_date,
          CAST(CONCAT('[', SUBSTRING_INDEX(GROUP_CONCAT(e.id ORDER BY e.date DESC), ',', 5), ']') AS JSON) AS sample_expense_ids
        FROM expenses e
        WHERE e.workspace_id = v_workspace_id AND e.date >= v_date_from
          AND e.description IS NOT NULL
          AND CHAR_LENGTH(LOWER(TRIM(REGEXP_REPLACE(e.description, '[0-9#*/.-]+', '')))) >= 3
        GROUP BY normalized_key
        HAVING months_count >= v_min_occurrences
          AND (avg_amount = 0 OR (max_amount - min_amount) / avg_amount * 100 <= v_tolerance_pct)
      ) t
      WHERE NOT EXISTS (
        SELECT 1 FROM services s WHERE s.workspace_id = v_workspace_id
          AND LOWER(TRIM(REGEXP_REPLACE(s.name, '[0-9#*/.-]+', ''))) = t.normalized_key
      )
      AND NOT EXISTS (
        SELECT 1 FROM smart_suggestions ss WHERE ss.workspace_id = v_workspace_id
          AND ss.normalized_key = t.normalized_key AND ss.status IN ('accepted', 'dismissed')
      )
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_smart_suggestion_upsert(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_normalized_key VARCHAR(255);
  DECLARE v_description VARCHAR(255);
  DECLARE v_avg_amount DECIMAL(14,2);
  DECLARE v_occurrences TINYINT UNSIGNED;
  DECLARE v_last_seen_date DATE;
  DECLARE v_suggested_due_day TINYINT UNSIGNED;
  DECLARE v_sample_expense_ids JSON;
  DECLARE v_existing_id BIGINT UNSIGNED;
  DECLARE v_existing_status VARCHAR(20);
  DECLARE v_id BIGINT UNSIGNED;
  DECLARE v_created BOOLEAN DEFAULT FALSE;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_normalized_key = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.normalized_key'));
  SET v_description = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.description'));
  SET v_avg_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.avg_amount')) AS DECIMAL(14,2));
  SET v_occurrences = CAST(JSON_EXTRACT(p_json, '$.occurrences') AS UNSIGNED);
  SET v_last_seen_date = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.last_seen_date')) AS DATE);
  SET v_suggested_due_day = CAST(JSON_EXTRACT(p_json, '$.suggested_due_day') AS UNSIGNED);
  SET v_sample_expense_ids = JSON_EXTRACT(p_json, '$.sample_expense_ids');

  START TRANSACTION;

  SET v_existing_id = NULL;
  SELECT id, status INTO v_existing_id, v_existing_status FROM smart_suggestions
    WHERE workspace_id = v_workspace_id AND normalized_key = v_normalized_key FOR UPDATE;

  IF v_existing_id IS NULL THEN
    INSERT INTO smart_suggestions (workspace_id, description, normalized_key, avg_amount, frequency_detected, occurrences, last_seen_date, suggested_due_day, sample_expense_ids, status)
      VALUES (v_workspace_id, v_description, v_normalized_key, v_avg_amount, 'monthly', v_occurrences, v_last_seen_date, v_suggested_due_day, v_sample_expense_ids, 'pending');
    SET v_id = LAST_INSERT_ID();
    SET v_created = TRUE;
  ELSEIF v_existing_status = 'pending' THEN
    UPDATE smart_suggestions SET description = v_description, avg_amount = v_avg_amount, occurrences = v_occurrences,
      last_seen_date = v_last_seen_date, suggested_due_day = v_suggested_due_day, sample_expense_ids = v_sample_expense_ids
      WHERE id = v_existing_id;
    SET v_id = v_existing_id;
    SET v_created = FALSE;
  ELSE
    SET v_id = v_existing_id;
    SET v_created = FALSE;
  END IF;

  COMMIT;

  SELECT JSON_OBJECT('success', TRUE, 'data', JSON_OBJECT('id', v_id, 'created', v_created)) AS result;
END$$

CREATE PROCEDURE sp_smart_suggestion_list(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_status VARCHAR(20);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_status = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.status'));

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', COALESCE((
      SELECT JSON_ARRAYAGG(JSON_OBJECT(
        'id', id, 'description', description, 'avg_amount', avg_amount,
        'frequency_detected', frequency_detected, 'occurrences', occurrences,
        'last_seen_date', last_seen_date, 'suggested_due_day', suggested_due_day,
        'status', status, 'service_id', service_id,
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      ))
      FROM smart_suggestions
      WHERE workspace_id = v_workspace_id AND (v_status = 'all' OR status = v_status)
      ORDER BY created_at DESC
    ), JSON_ARRAY())
  ) AS result;
END$$

CREATE PROCEDURE sp_smart_suggestion_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_suggestion_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_suggestion_id = CAST(JSON_EXTRACT(p_json, '$.suggestion_id') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM smart_suggestions WHERE id = v_suggestion_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', id, 'description', description, 'avg_amount', avg_amount,
        'frequency_detected', frequency_detected, 'occurrences', occurrences,
        'last_seen_date', last_seen_date, 'suggested_due_day', suggested_due_day,
        'status', status, 'service_id', service_id,
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM smart_suggestions WHERE id = v_suggestion_id AND workspace_id = v_workspace_id;
  END IF;
END$$

CREATE PROCEDURE sp_smart_suggestion_update_status(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_suggestion_id BIGINT UNSIGNED;
  DECLARE v_status VARCHAR(20);
  DECLARE v_service_id BIGINT UNSIGNED;
  DECLARE v_resolved_by BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_suggestion_id = CAST(JSON_EXTRACT(p_json, '$.suggestion_id') AS UNSIGNED);
  SET v_status = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.status'));
  SET v_service_id = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.service_id')) = 'NULL' OR JSON_EXTRACT(p_json, '$.service_id') IS NULL, NULL, CAST(JSON_EXTRACT(p_json, '$.service_id') AS UNSIGNED));
  SET v_resolved_by = CAST(JSON_EXTRACT(p_json, '$.resolved_by') AS UNSIGNED);

  IF NOT EXISTS (SELECT 1 FROM smart_suggestions WHERE id = v_suggestion_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    UPDATE smart_suggestions SET status = v_status, service_id = v_service_id, resolved_by = v_resolved_by, resolved_at = NOW()
      WHERE id = v_suggestion_id AND workspace_id = v_workspace_id;
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', id, 'description', description, 'avg_amount', avg_amount,
        'frequency_detected', frequency_detected, 'occurrences', occurrences,
        'last_seen_date', last_seen_date, 'suggested_due_day', suggested_due_day,
        'status', status, 'service_id', service_id,
        'created_at', DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ')
      )
    ) AS result
    FROM smart_suggestions WHERE id = v_suggestion_id AND workspace_id = v_workspace_id;
  END IF;
END$$

-- ---------------------------------------------------------------------------
-- M-25 Gastos compartidos y liquidación entre miembros
-- ---------------------------------------------------------------------------

-- Sin cálculo acá adentro (balances/liquidación sugerida son 100% de
-- App\Services\Settlement\SettlementCalculator, clase pura en PHP): este SP
-- solo junta los 3 insumos crudos que esa clase necesita. payer_user_id ya
-- viene resuelto con COALESCE (integridad de lectura, no regla de negocio).
CREATE PROCEDURE sp_settlement_snapshot_get(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);

  SELECT JSON_OBJECT(
    'success', TRUE,
    'data', JSON_OBJECT(
      'members', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT('user_id', wu.user_id, 'name', u.name))
        FROM workspace_users wu
        JOIN users u ON u.id = wu.user_id
        WHERE wu.workspace_id = v_workspace_id
      ), JSON_ARRAY()),
      'expenses', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT('amount', e.amount, 'payer_user_id', COALESCE(e.paid_by_user_id, e.user_id)))
        FROM expenses e
        WHERE e.workspace_id = v_workspace_id
      ), JSON_ARRAY()),
      'settlement_payments', COALESCE((
        SELECT JSON_ARRAYAGG(JSON_OBJECT(
          'id', sp.id, 'from_user_id', sp.from_user_id, 'from_user_name', fu.name,
          'to_user_id', sp.to_user_id, 'to_user_name', tu.name,
          'amount', sp.amount, 'note', sp.note,
          'registered_by', sp.registered_by, 'registered_by_name', ru.name,
          'paid_at', DATE_FORMAT(sp.paid_at, '%Y-%m-%dT%H:%i:%sZ'),
          'created_at', DATE_FORMAT(sp.created_at, '%Y-%m-%dT%H:%i:%sZ')
        ))
        FROM settlement_payments sp
        JOIN users fu ON fu.id = sp.from_user_id
        JOIN users tu ON tu.id = sp.to_user_id
        JOIN users ru ON ru.id = sp.registered_by
        WHERE sp.workspace_id = v_workspace_id
        ORDER BY sp.paid_at DESC
      ), JSON_ARRAY())
    )
  ) AS result;
END$$

CREATE PROCEDURE sp_settlement_payment_create(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_from_user_id BIGINT UNSIGNED;
  DECLARE v_to_user_id BIGINT UNSIGNED;
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_note VARCHAR(255);
  DECLARE v_registered_by BIGINT UNSIGNED;
  DECLARE v_id BIGINT UNSIGNED;
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_from_user_id = CAST(JSON_EXTRACT(p_json, '$.from_user_id') AS UNSIGNED);
  SET v_to_user_id = CAST(JSON_EXTRACT(p_json, '$.to_user_id') AS UNSIGNED);
  SET v_amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.amount')) AS DECIMAL(14,2));
  SET v_note = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.note')) = 'NULL' OR JSON_EXTRACT(p_json, '$.note') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.note')));
  SET v_registered_by = CAST(JSON_EXTRACT(p_json, '$.registered_by') AS UNSIGNED);
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  IF NOT EXISTS (SELECT 1 FROM workspace_users WHERE user_id = v_from_user_id AND workspace_id = v_workspace_id)
     OR NOT EXISTS (SELECT 1 FROM workspace_users WHERE user_id = v_to_user_id AND workspace_id = v_workspace_id) THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    INSERT INTO settlement_payments (workspace_id, from_user_id, to_user_id, amount, note, registered_by)
    VALUES (v_workspace_id, v_from_user_id, v_to_user_id, v_amount, v_note, v_registered_by);
    SET v_id = LAST_INSERT_ID();
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_audit_user_id, 'settlement_payment', v_id, 'created', v_audit_summary, NULL,
        JSON_OBJECT('from_user_id', v_from_user_id, 'to_user_id', v_to_user_id, 'amount', v_amount), v_audit_ip, v_audit_ua);
    COMMIT;

    SELECT JSON_OBJECT(
      'success', TRUE,
      'data', JSON_OBJECT(
        'id', v_id,
        'from_user_id', v_from_user_id, 'from_user_name', (SELECT name FROM users WHERE id = v_from_user_id),
        'to_user_id', v_to_user_id, 'to_user_name', (SELECT name FROM users WHERE id = v_to_user_id),
        'amount', v_amount, 'note', v_note,
        'registered_by', v_registered_by, 'registered_by_name', (SELECT name FROM users WHERE id = v_registered_by),
        'paid_at', (SELECT DATE_FORMAT(paid_at, '%Y-%m-%dT%H:%i:%sZ') FROM settlement_payments WHERE id = v_id),
        'created_at', (SELECT DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ') FROM settlement_payments WHERE id = v_id)
      )
    ) AS result;
  END IF;
END$$

CREATE PROCEDURE sp_settlement_payment_delete(IN p_json JSON)
BEGIN
  DECLARE v_workspace_id BIGINT UNSIGNED;
  DECLARE v_settlement_payment_id BIGINT UNSIGNED;
  DECLARE v_from_user_id BIGINT UNSIGNED;
  DECLARE v_to_user_id BIGINT UNSIGNED;
  DECLARE v_amount DECIMAL(14,2);
  DECLARE v_audit_user_id BIGINT UNSIGNED;
  DECLARE v_audit_summary VARCHAR(255);
  DECLARE v_audit_ip VARCHAR(45);
  DECLARE v_audit_ua VARCHAR(255);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SELECT JSON_OBJECT('success', FALSE, 'error', 'INTERNAL') AS result;
  END;

  SET v_workspace_id = CAST(JSON_EXTRACT(p_json, '$.workspace_id') AS UNSIGNED);
  SET v_settlement_payment_id = CAST(JSON_EXTRACT(p_json, '$.settlement_payment_id') AS UNSIGNED);
  SET v_audit_user_id = CAST(JSON_EXTRACT(p_json, '$.audit.user_id') AS UNSIGNED);
  SET v_audit_summary = JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.summary'));
  SET v_audit_ip = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.ip_address')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.ip_address') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.ip_address')));
  SET v_audit_ua = IF(JSON_TYPE(JSON_EXTRACT(p_json, '$.audit.user_agent')) = 'NULL' OR JSON_EXTRACT(p_json, '$.audit.user_agent') IS NULL, NULL, JSON_UNQUOTE(JSON_EXTRACT(p_json, '$.audit.user_agent')));

  SELECT from_user_id, to_user_id, amount INTO v_from_user_id, v_to_user_id, v_amount
    FROM settlement_payments WHERE id = v_settlement_payment_id AND workspace_id = v_workspace_id;

  IF v_from_user_id IS NULL THEN
    SELECT JSON_OBJECT('success', FALSE, 'error', 'NOT_FOUND') AS result;
  ELSE
    START TRANSACTION;
    DELETE FROM settlement_payments WHERE id = v_settlement_payment_id AND workspace_id = v_workspace_id;
    INSERT INTO audit_logs (workspace_id, user_id, entity_type, entity_id, action, summary, old_value, new_value, ip_address, user_agent)
      VALUES (v_workspace_id, v_audit_user_id, 'settlement_payment', v_settlement_payment_id, 'deleted', v_audit_summary,
        JSON_OBJECT('from_user_id', v_from_user_id, 'to_user_id', v_to_user_id, 'amount', v_amount), NULL, v_audit_ip, v_audit_ua);
    COMMIT;

    SELECT JSON_OBJECT('success', TRUE, 'data', NULL) AS result;
  END IF;
END$$

DELIMITER ;
