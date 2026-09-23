<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\SolluProjectServer;
use App\Mcp\Tools\AuditTenantIsolation;
use App\Mcp\Tools\CheckEnumIntegrity;
use App\Mcp\Tools\InspectInventoryState;
use App\Mcp\Tools\LintAgentRules;
use App\Mcp\Tools\TraceFeatureStack;
use Tests\TestCase;

class SolluProjectServerTest extends TestCase
{
    /**
     * Test check_enum_integrity executes and returns valid JSON report.
     */
    public function test_check_enum_integrity_tool_executes_successfully(): void
    {
        $response = SolluProjectServer::tool(CheckEnumIntegrity::class, ['scope' => 'all']);

        $response->assertOk();
        $response->assertSee('permissions');
        $response->assertSee('frontend_enums');
    }

    /**
     * Test trace_feature_stack resolves routes to controller, form request, and vue page.
     */
    public function test_trace_feature_stack_resolves_route_details(): void
    {
        $response = SolluProjectServer::tool(TraceFeatureStack::class, [
            'identifier' => 'inventories.stocks.index',
        ]);

        $response->assertOk();
        $response->assertSee('StockController');
        $response->assertSee('inventories/stocks');
    }

    /**
     * Test audit_tenant_isolation verifies business_id and outlet_id partitioning.
     */
    public function test_audit_tenant_isolation_audits_models(): void
    {
        $response = SolluProjectServer::tool(AuditTenantIsolation::class, [
            'target' => 'InventoryBalance',
        ]);

        $response->assertOk();
        $response->assertSee('InventoryBalance');
        $response->assertSee('has_business_id');
    }

    /**
     * Test inspect_inventory_state returns error when item is not found.
     */
    public function test_inspect_inventory_state_handles_missing_item(): void
    {
        $response = SolluProjectServer::tool(InspectInventoryState::class, [
            'item_id' => 'non-existent-sku-xyz-123',
        ]);

        $response->assertOk();
        $response->assertSee('error');
    }

    /**
     * Test lint_agent_rules audits files against codebase rules.
     */
    public function test_lint_agent_rules_executes_successfully(): void
    {
        $response = SolluProjectServer::tool(LintAgentRules::class, [
            'ruleset' => 'backend',
        ]);

        $response->assertOk();
        $response->assertSee('total_files_checked');
    }
}
