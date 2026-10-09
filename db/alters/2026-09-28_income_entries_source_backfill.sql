-- Backfill de uso unico: marca como source='closing' + closing_id el income_entry
-- de sobrante que ya existia ANTES de agregar la columna (2026-09-28_income_entries_source.sql).
-- Los nuevos se crean bien de aca en mas via sp_monthly_closing_allocate actualizado.
-- Verificado a mano contra cuentas_claras real antes de aplicar: 1 sola fila matchea
-- (id=14, workspace_id=1, "Sobrante del cierre 08/2026", monto identico al
-- allocated_to_next_month del cierre 08/2026 de ese workspace, closing_id=8).
UPDATE income_entries ie
JOIN monthly_closings mc
  ON mc.workspace_id = ie.workspace_id
  AND CONCAT('Sobrante del cierre ', LPAD(mc.month, 2, '0'), '/', mc.year) = ie.concept
SET ie.source = 'closing', ie.closing_id = mc.id
WHERE ie.source = 'manual';
