<?php

// Constantes de negocio de Cuentas Claras (ver ESPECIFICACION_TECNICA.md §0.17).
// Los valores marcados como "default propuesto" en el Apéndice C viven acá,
// aislados, para poder cambiarlos sin tocar código.

return [
    // 24 horas (pedido del usuario, 2026-09-17: antes 60 min, la sesión se sentía muy corta)
    'access_token_ttl_minutes' => (int) env('AUTH_ACCESS_TOKEN_TTL', 1440),
    'refresh_token_ttl_minutes' => (int) env('AUTH_REFRESH_TOKEN_TTL', 43200), // 30 días
    'two_factor_challenge_ttl_minutes' => 5,
    'password_reset_ttl_minutes' => 60,
    'invitation_ttl_days' => 7,
    'invitation_code_length' => 10,
    'max_refresh_tokens_per_user' => 10,

    'default_service_reminder_days' => [3, 1],
    'default_budget_alert_thresholds' => [80, 100],

    'csv_import_max_rows' => 2000,
    'csv_import_max_bytes' => 2 * 1024 * 1024,

    'smart_suggestions_lookback_months' => 4,
    'smart_suggestions_min_occurrences' => 3,
    'smart_suggestions_amount_tolerance_pct' => 15,

    'health_score_weights' => [
        'budgets' => 30,
        'savings' => 30,
        'services_on_time' => 20,
        'installments_load' => 20,
    ],

    'frontend_url' => env('FRONTEND_URL'),
];
