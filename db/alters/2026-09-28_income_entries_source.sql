-- Distingue un income_entry generado por sp_monthly_closing_allocate (sobrante
-- asignado como ingreso del mes siguiente, P-19) de uno cargado a mano - mismo
-- patron ya usado en savings_movements/savings_goal_movements. Reusa la tabla
-- de catalogo savings_movement_sources (manual/closing), ya tiene los codigos
-- que hacen falta. No destructivo, agrega columnas con DEFAULT - no afecta
-- filas existentes (backfill puntual de los registros ya cargados en
-- 2026-09-28_income_entries_source_backfill.sql, aparte).
ALTER TABLE income_entries
  ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'manual' AFTER month,
  ADD COLUMN closing_id BIGINT UNSIGNED NULL AFTER source,
  ADD FOREIGN KEY (closing_id) REFERENCES monthly_closings(id) ON DELETE SET NULL,
  ADD FOREIGN KEY (source) REFERENCES savings_movement_sources(code);
