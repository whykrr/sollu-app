<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\SolluProjectServer;
use App\Mcp\Tools\AuditMigrationCompatibility;
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

    /**
     * Test lint_agent_rules detects hardcoded route redirects in App controllers.
     */
    public function test_lint_agent_rules_detects_hardcoded_controller_redirect(): void
    {
        $response = SolluProjectServer::tool(LintAgentRules::class, [
            'path' => 'app/Http/Controllers/App/Employee/EmployeeController.php',
            'ruleset' => 'backend',
        ]);

        $response->assertOk();
        $response->assertSee('Rule 05: Anti-Reset Filter Invariant on Redirect');
    }

    /**
     * Test lint_agent_rules verifies CreateEdit components use form.defaults() with useFormDirtyGuard.
     */
    public function test_lint_agent_rules_passes_on_well_formed_create_edit_components(): void
    {
        $response = SolluProjectServer::tool(LintAgentRules::class, [
            'path' => 'resources/js/Pages/App/Master/Product/CreateEdit.vue',
            'ruleset' => 'frontend',
        ]);

        $response->assertOk();
        $response->assertDontSee('useFormDirtyGuard used without form.defaults() call');
    }

    /**
     * Test lint_agent_rules verifies form submit button dirty state guard.
     */
    public function test_lint_agent_rules_verifies_submit_button_dirty_state_guard(): void
    {
        $response = SolluProjectServer::tool(LintAgentRules::class, [
            'path' => 'resources/js/Pages/App/Master/Product/CreateEdit.vue',
            'ruleset' => 'frontend',
        ]);

        $response->assertOk();
        $response->assertDontSee('Submit button on form without dirty-state guard detected');
    }

    /**
     * Test lint_agent_rules detects submit button without dirty state guard on candidate form.
     */
    public function test_lint_agent_rules_detects_submit_button_without_dirty_state_guard(): void
    {
        $dummyPath = resource_path('js/Pages/App/DummyTestForm.vue');
        file_put_contents($dummyPath, <<<'VUE'
<template>
    <form @submit.prevent="submit">
        <button type="submit" :disabled="form.processing">Simpan</button>
    </form>
</template>
<script setup>
import { useForm } from '@inertiajs/vue3'
const form = useForm({ name: '' })
</script>
VUE
        );

        try {
            $response = SolluProjectServer::tool(LintAgentRules::class, [
                'path' => 'resources/js/Pages/App/DummyTestForm.vue',
                'ruleset' => 'frontend',
            ]);

            $response->assertOk();
            $response->assertSee('Rule 04: Submit Button Dirty State Guard');
            $response->assertSee('Submit button on form without dirty-state guard detected');
        } finally {
            if (file_exists($dummyPath)) {
                unlink($dummyPath);
            }
        }
    }

    /**
     * Test audit_migration_compatibility verifies migrations against Git baseline tag.
     */
    public function test_audit_migration_compatibility_tool_executes_successfully(): void
    {
        $response = SolluProjectServer::tool(AuditMigrationCompatibility::class, [
            'scope' => 'all',
            'strict' => false,
        ]);

        $response->assertOk();
        $response->assertSee('baseline_tag');
        $response->assertSee('violations');
    }
}
