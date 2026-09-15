<?php

namespace Tests\Unit;

use App\Http\Controllers\HRMS\PettyCashController;
use App\Models\PettyCashEntry;
use Tests\TestCase;

class PettyCashHouseKeepingMergedLedgerTest extends TestCase
{
    public function test_petty_cash_entry_has_category_and_category_label(): void
    {
        $pc = new PettyCashEntry();
        $pc->category = 'petty_cash';
        $this->assertEquals('Petty Cash', $pc->category_label);

        $hk = new PettyCashEntry();
        $hk->category = 'house_keeping';
        $this->assertEquals('House Keeping', $hk->category_label);

        // Default when empty/null
        $defaultEntry = new PettyCashEntry();
        $this->assertEquals('Petty Cash', $defaultEntry->category_label);
    }

    public function test_petty_cash_query_filters_by_category(): void
    {
        // 1. Unfiltered query
        $allSql = PettyCashEntry::query()->toSql();
        $this->assertStringNotContainsString('"category" = ?', $allSql);

        // 2. Petty cash filtered query
        $pcQuery = PettyCashEntry::query()->where('category', 'petty_cash');
        $this->assertStringContainsString('"category" = ?', $pcQuery->toSql());
        $this->assertContains('petty_cash', $pcQuery->getBindings());

        // 3. House keeping filtered query
        $hkQuery = PettyCashEntry::query()->where('category', 'house_keeping');
        $this->assertStringContainsString('"category" = ?', $hkQuery->toSql());
        $this->assertContains('house_keeping', $hkQuery->getBindings());
    }

    public function test_petty_cash_entry_fillable_includes_category(): void
    {
        $entry = new PettyCashEntry();
        $this->assertContains('category', $entry->getFillable());
    }

    public function test_controller_has_merged_ledger_methods(): void
    {
        $controller = new PettyCashController();
        $this->assertTrue(method_exists($controller, 'report'));
        $this->assertTrue(method_exists($controller, 'calculateReportData'));
        $this->assertTrue(method_exists($controller, 'store'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'destroy'));
        $this->assertTrue(method_exists($controller, 'exportExcel'));
        $this->assertTrue(method_exists($controller, 'exportPdf'));
    }
}
