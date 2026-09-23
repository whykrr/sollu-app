<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\AuditTenantIsolation;
use App\Mcp\Tools\CheckEnumIntegrity;
use App\Mcp\Tools\InspectInventoryState;
use App\Mcp\Tools\LintAgentRules;
use App\Mcp\Tools\TraceFeatureStack;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Sollu Project Server')]
#[Version('1.0.0')]
#[Instructions('This MCP server provides deep architectural inspection, multi-tenant auditing, full-stack route tracing, enum integrity checks, inventory ledger analysis, and rule compliance linting for the Sollu POS/ERP codebase.')]
class SolluProjectServer extends Server
{
    /**
     * @var array<int, class-string>
     */
    protected array $tools = [
        CheckEnumIntegrity::class,
        TraceFeatureStack::class,
        AuditTenantIsolation::class,
        InspectInventoryState::class,
        LintAgentRules::class,
    ];

    /**
     * @var array<int, class-string>
     */
    protected array $resources = [
        //
    ];

    /**
     * @var array<int, class-string>
     */
    protected array $prompts = [
        //
    ];
}
