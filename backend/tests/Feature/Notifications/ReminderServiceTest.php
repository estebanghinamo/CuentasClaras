<?php

namespace Tests\Feature\Notifications;

use App\Services\Notifications\ReminderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class ReminderServiceTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'notifications', 'notification_preferences', 'service_payments', 'services',
            'installment_payments', 'installments', 'monthly_closings', 'categories', 'savings_wallet',
            'workspace_invitations', 'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function createService(array $ctx, int $dueDay, array $overrides = []): void
    {
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/services", array_merge([
            'name' => 'Netflix', 'amount' => 5500, 'is_estimated' => false,
            'due_day_start' => $dueDay, 'due_day_end' => $dueDay,
            'late_fee_type' => 'fixed', 'late_fee_value' => 0,
        ], $overrides), ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);
    }

    public function test_service_reminder_notifies_members_when_due_in_three_days(): void
    {
        $frozenNow = Carbon::create(2026, 6, 10, 12, 0, 0, config('app.timezone'));
        Carbon::setTestNow($frozenNow);

        try {
            $ctx = $this->createWorkspaceAsOwner();
            $auth = ['Authorization' => "Bearer {$ctx['token']}"];
            // vence el 13/06, hoy es 10/06 -> "vence en 3 dias".
            $this->createService($ctx, 13);

            $this->getJson('/api/notifications/unread-count', $auth)->assertJsonPath('data.count', 0);

            app(ReminderService::class)->sendServiceReminders();

            $this->getJson('/api/notifications/unread-count', $auth)->assertJsonPath('data.count', 1);
            $notification = $this->getJson('/api/notifications', $auth)->json('data.0');
            $this->assertSame('service_due_soon', $notification['type']);
            $this->assertSame(3, $notification['payload']['days']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_service_reminder_does_not_notify_when_due_date_is_outside_reminder_days(): void
    {
        $frozenNow = Carbon::create(2026, 6, 10, 12, 0, 0, config('app.timezone'));
        Carbon::setTestNow($frozenNow);

        try {
            $ctx = $this->createWorkspaceAsOwner();
            $auth = ['Authorization' => "Bearer {$ctx['token']}"];
            // vence el 25/06, muy lejos de {0,1,3} dias desde hoy.
            $this->createService($ctx, 25);

            app(ReminderService::class)->sendServiceReminders();

            $this->getJson('/api/notifications/unread-count', $auth)->assertJsonPath('data.count', 0);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_installment_reminder_notifies_pending_total_for_the_current_month(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/installments", [
            'description' => 'Heladera', 'total_amount' => 300, 'installments_count' => 3,
            'start_date' => now()->startOfMonth()->format('Y-m-d'), 'category_id' => null,
        ], $auth)->assertStatus(201);

        app(ReminderService::class)->sendInstallmentReminders();

        $this->getJson('/api/notifications/unread-count', $auth)->assertJsonPath('data.count', 1);
        $notification = $this->getJson('/api/notifications', $auth)->json('data.0');
        $this->assertSame('installment_due_soon', $notification['type']);
        $this->assertSame(1, $notification['payload']['count']);
    }

    public function test_overdue_digest_notifies_members_for_services_that_became_overdue_today(): void
    {
        // Sin Carbon::setTestNow() a propósito acá: sp_service_payment_mark_overdue
        // marca updated_at con el reloj REAL de MySQL (columna ON UPDATE
        // CURRENT_TIMESTAMP, no depende del NOW() de PHP), así que el digest
        // solo puede probarse contra la fecha real del sistema, no una simulada.
        //
        // El service_payment se crea para el mes actual con due_day_start = 1
        // y despues se mueve al mes ANTERIOR completo por UPDATE directo -
        // así la fecha de vencimiento queda inequívocamente en el pasado, sin
        // depender del "día del mes de ayer" (frágil: si hoy es el día 1, ayer
        // es un día de OTRO mes, y ese mismo número de día dentro del mes
        // actual puede no haber pasado todavía - bug real que causaba
        // flakiness real en CI, ver ERROR_LOG.md).
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->createService($ctx, 1);

        $previousMonth = now()->subMonthNoOverflow();
        DB::table('service_payments')
            ->where('workspace_id', $ctx['workspace_id'])
            ->update(['year' => $previousMonth->year, 'month' => $previousMonth->month]);

        app(ReminderService::class)->sendOverdueDigest();

        $this->getJson('/api/notifications/unread-count', $auth)->assertJsonPath('data.count', 1);
        $notification = $this->getJson('/api/notifications', $auth)->json('data.0');
        $this->assertSame('service_overdue', $notification['type']);
    }
}
