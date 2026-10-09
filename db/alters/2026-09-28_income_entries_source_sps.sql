-- Agrega 'source' a los SPs de lectura de income_entries y puebla
-- source/closing_id en el INSERT de sp_monthly_closing_allocate (mismo
-- patron que ya usa para savings_movements/savings_goal_movements en
-- este mismo SP). Requiere que
-- db/alters/2026-09-28_income_entries_source.sql ya se haya aplicado.
-- Ejecutar manualmente (division de trabajo: la IA no toca
-- CREATE/DROP PROCEDURE).

DROP PROCEDURE IF EXISTS sp_income_entry_list;
DROP PROCEDURE IF EXISTS sp_income_entry_get;
DROP PROCEDURE IF EXISTS sp_monthly_closing_allocate;

DELIMITER $$

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

DELIMITER ;
