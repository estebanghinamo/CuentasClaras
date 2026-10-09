<?php

namespace Tests\Feature\Reports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class WorkspaceExportTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'income_entries', 'expenses', 'monthly_closings', 'categories',
            'savings_wallet', 'workspace_invitations', 'workspace_users', 'workspaces',
            'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    public function test_export_returns_an_xlsx_with_five_sheets(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $date = now()->startOfMonth()->addDays(9)->format('Y-m-d');

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1000, 'concept' => 'Sueldo', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/export",
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(200);
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        file_put_contents($tempPath, $response->getContent());
        $spreadsheet = IOFactory::load($tempPath);
        unlink($tempPath);

        $this->assertSame(
            ['Gastos', 'Ingresos', 'Servicios', 'Cuotas', 'Cierres'],
            $spreadsheet->getSheetNames(),
        );

        $incomeSheet = $spreadsheet->getSheetByName('Ingresos');
        $this->assertSame('Concepto', $incomeSheet->getCell('C1')->getValue());
        $this->assertSame('Sueldo', $incomeSheet->getCell('C2')->getValue());
    }
}
