<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

#[Description('Audits Eloquent models to verify multi-tenant isolation (business_id and outlet_id partition keys), relations, and soft deletes compliance.')]
class AuditTenantIsolation extends Tool
{
    protected string $name = 'audit_tenant_isolation';

    protected string $title = 'Audit Tenant Isolation';

    /**
     * Models that are globally shared across the entire system and not tenant-scoped.
     *
     * @var array<int, string>
     */
    protected array $globalSystemModels = [
        'Uom',
        'User',
        'BusinessType',
        'BusinessCategory',
        'Country',
        'City',
        'Province',
        'PostalCode',
        'SystemFeature',
        'Feature',
        'SubscriptionPlan',
        'SubscriptionModule',
        'SubscriptionAddon',
        'Permission',
        'Role',
        'PersonalAccessToken',
    ];

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'target' => $schema->string()
                ->description('Specific model name (e.g. "InventoryBalance"), module ("Inventory"), or "all" to audit all models. Defaults to "all".'),
            'strict' => $schema->boolean()
                ->description('Whether to enforce strict soft-delete audit on operational models. Defaults to true.'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $target = (string) $request->get('target', 'all');
        $strict = (bool) $request->get('strict', true);

        $models = $this->discoverModels($target);

        $auditResults = [
            'status' => 'pass',
            'total_models_audited' => count($models),
            'tenant_scoped_count' => 0,
            'system_global_count' => 0,
            'issues_count' => 0,
            'issues' => [],
            'models' => [],
        ];

        foreach ($models as $modelClass) {
            $analysis = $this->auditModel($modelClass, $strict);
            $auditResults['models'][] = $analysis;

            if ($analysis['is_global_system']) {
                $auditResults['system_global_count']++;
            } else {
                $auditResults['tenant_scoped_count']++;
            }

            if (! empty($analysis['issues'])) {
                $auditResults['issues_count'] += count($analysis['issues']);
                $auditResults['issues'][] = [
                    'model' => $modelClass,
                    'issues' => $analysis['issues'],
                ];
            }
        }

        $auditResults['status'] = $auditResults['issues_count'] > 0 ? 'warning' : 'pass';

        return Response::json($auditResults);
    }

    /**
     * Discover Eloquent model classes.
     *
     * @return array<int, class-string<Model>>
     */
    protected function discoverModels(string $target): array
    {
        $modelsPath = app_path('Models');
        $models = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modelsPath)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace([$modelsPath.'/', '.php'], '', $file->getPathname());
            $className = 'App\\Models\\'.str_replace('/', '\\', $relativePath);

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            $baseName = class_basename($className);

            if ($target === 'all') {
                $models[] = $className;
            } elseif ($target === $baseName || $target === $className) {
                $models[] = $className;
            } elseif (str_contains(strtolower($relativePath), strtolower($target))) {
                $models[] = $className;
            }
        }

        return $models;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, mixed>
     */
    protected function auditModel(string $modelClass, bool $strict): array
    {
        $baseName = class_basename($modelClass);
        $isGlobal = in_array($baseName, $this->globalSystemModels, true)
            || str_starts_with($modelClass, 'App\\Models\\Cockpit\\');

        $issues = [];

        try {
            /** @var Model $instance */
            $instance = new $modelClass;
            $table = $instance->getTable();
            $tableExists = Schema::hasTable($table);

            $hasBusinessId = $tableExists && Schema::hasColumn($table, 'business_id');
            $hasOutletId = $tableExists && Schema::hasColumn($table, 'outlet_id');
            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);

            $reflection = new ReflectionClass($modelClass);
            $hasBusinessRelation = $reflection->hasMethod('business');
            $hasOutletRelation = $reflection->hasMethod('outlet');

            if (! $isGlobal) {
                if ($tableExists && ! $hasBusinessId && ! in_array($baseName, ['Business', 'Outlet', 'BusinessSetting', 'BusinessSubscription', 'BusinessInvoice'])) {
                    $issues[] = "Table [{$table}] is missing tenant partition key [business_id].";
                }

                if ($tableExists && $hasBusinessId && ! $hasBusinessRelation) {
                    $issues[] = 'Model has [business_id] column but missing business() Eloquent relation.';
                }

                if ($strict && $tableExists && ! $usesSoftDeletes && Schema::hasColumn($table, 'deleted_at')) {
                    $issues[] = "Table [{$table}] has deleted_at column but model does not use SoftDeletes trait.";
                }
            }

            return [
                'model' => $modelClass,
                'table' => $table,
                'table_exists' => $tableExists,
                'is_global_system' => $isGlobal,
                'has_business_id' => $hasBusinessId,
                'has_outlet_id' => $hasOutletId,
                'uses_soft_deletes' => $usesSoftDeletes,
                'has_business_relation' => $hasBusinessRelation,
                'has_outlet_relation' => $hasOutletRelation,
                'issues' => $issues,
            ];
        } catch (\Throwable $e) {
            return [
                'model' => $modelClass,
                'error' => $e->getMessage(),
                'issues' => ["Failed to inspect model: {$e->getMessage()}"],
            ];
        }
    }
}
