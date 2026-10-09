<?php

namespace App\Services\Audit;

/**
 * Plantillas fijas en español para el texto humano de cada entrada de
 * auditoría (ver ESPECIFICACION_TECNICA.md M-03 §4.3). Cada módulo nuevo que
 * audita algo agrega su combinación "entity_type:action" acá, nunca arma el
 * string a mano en el Service.
 */
class AuditSummaries
{
    public static function for(string $entityType, string $action, array $data = []): string
    {
        return match ("{$entityType}:{$action}") {
            'workspace:created' => "Creó el workspace {$data['name']}",
            'workspace:updated' => "Editó el workspace {$data['name']}",
            'workspace:deleted' => "Eliminó el workspace {$data['name']}",
            'workspace_member:created' => "Se unió al workspace {$data['workspace_name']}",
            'workspace_member:deleted' => !empty($data['self'])
                ? "Abandonó el workspace {$data['workspace_name']}"
                : "Quitó a {$data['name']} del workspace",
            'workspace_invitation:created' => isset($data['email'])
                ? "Invitó a {$data['email']} al workspace"
                : 'Creó un link de invitación al workspace',
            'workspace_invitation:deleted' => 'Revocó una invitación',
            'category:created' => "Creó la categoría {$data['name']}",
            'category:updated' => "Editó la categoría {$data['name']}",
            'category:deleted' => "Eliminó la categoría {$data['name']}",
            'monthly_closing:created' => "Cierre automático de {$data['period']}",
            'monthly_closing:updated' => "Asignó el sobrante del cierre de {$data['period']}",
            'smart_suggestion:updated' => $data['action'] === 'accepted'
                ? "Convirtió en servicio la sugerencia: {$data['description']}"
                : "Descartó la sugerencia: {$data['description']}",
            'income_entry:created' => "Cargó un ingreso de {$data['amount']} ({$data['concept']})",
            'income_entry:updated' => "Editó un ingreso ({$data['concept']})",
            'income_entry:deleted' => "Eliminó un ingreso de {$data['amount']} ({$data['concept']})",
            'expense:created' => "Cargó un gasto de {$data['amount']}"
                .(isset($data['category']) ? " en {$data['category']}" : ''),
            'expense:updated' => 'Editó un gasto',
            'expense:deleted' => "Eliminó un gasto de {$data['amount']}",
            'service:created' => "Creó el servicio {$data['name']}",
            'service:updated' => "Editó el servicio {$data['name']}",
            'service:deleted' => "Eliminó el servicio {$data['name']}",
            'service_payment:paid' => "Marcó pagado {$data['name']} {$data['period']}",
            'service_payment:unpaid' => "Despagó {$data['name']} {$data['period']}",
            'installment:created' => "Creó la compra en cuotas {$data['description']}",
            'installment:updated' => "Editó la compra en cuotas {$data['description']}",
            'installment:deleted' => "Canceló la compra en cuotas {$data['description']}",
            'installment_payment:paid' => "Pagó la cuota {$data['number']} de {$data['description']}",
            'installment_payment:unpaid' => "Despagó la cuota {$data['number']} de {$data['description']}",
            'savings_movement:created' => ($data['type'] ?? null) === 'deposit'
                ? "Depositó {$data['amount']} al monedero"
                : "Retiró {$data['amount']} del monedero",
            'savings_goal:created' => "Creó la meta de ahorro {$data['name']}",
            'savings_goal:updated' => match ($data['reason'] ?? null) {
                'contribution' => "Aportó {$data['amount']} a la meta {$data['name']}",
                'withdrawal' => "Retiró {$data['amount']} de la meta {$data['name']}",
                'transfer' => "Transfirió {$data['amount']} del monedero a la meta {$data['name']}",
                default => "Editó la meta de ahorro {$data['name']}",
            },
            'savings_goal:deleted' => "Canceló la meta de ahorro {$data['name']}",
            'budget:created' => "Creó un presupuesto para {$data['category']}",
            'budget:updated' => "Editó el presupuesto de {$data['category']}",
            'budget:deleted' => 'Eliminó un presupuesto',
            'settlement_payment:created' =>
                "Registró un pago de {$data['amount']} de {$data['from_name']} a {$data['to_name']}",
            'settlement_payment:deleted' =>
                "Deshizo un pago de {$data['amount']} de {$data['from_name']} a {$data['to_name']}",
            default => match ($action) {
                'created' => "Creó {$entityType}",
                'updated' => "Editó {$entityType}",
                'deleted' => "Eliminó {$entityType}",
                default => "Modificó {$entityType}",
            },
        };
    }
}
