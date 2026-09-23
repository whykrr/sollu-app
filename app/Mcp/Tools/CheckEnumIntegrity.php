<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\RoleTemplateEnum;
use App\Models\Cockpit\Feature;
use App\Support\Enums\FrontendEnumProvider;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ReflectionClass;

#[Description('Verifies Enum integrity, zero-orphan permissions, role template mappings, frontend enum exposure, and SaaS feature alignment.')]
class CheckEnumIntegrity extends Tool
{
    protected string $name = 'check_enum_integrity';

    protected string $title = 'Check Enum Integrity';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()
                ->enum(['all', 'permissions', 'frontend', 'features'])
                ->description('Filter validation scope: "all", "permissions", "frontend", or "features". Defaults to "all".'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $scope = (string) $request->get('scope', 'all');

        $report = [
            'status' => 'pass',
            'summary' => [],
            'details' => [],
        ];

        $hasErrors = false;
        $hasWarnings = false;

        // 1. Check Permissions & Zero-Orphan Policy
        if (in_array($scope, ['all', 'permissions'], true)) {
            $permissionResult = $this->checkPermissions();
            $report['details']['permissions'] = $permissionResult;
            if ($permissionResult['status'] === 'error') {
                $hasErrors = true;
            } elseif ($permissionResult['status'] === 'warning') {
                $hasWarnings = true;
            }
            $report['summary']['permissions'] = $permissionResult['summary'];
        }

        // 2. Check Frontend Enum Provider exposure
        if (in_array($scope, ['all', 'frontend'], true)) {
            $frontendResult = $this->checkFrontendEnums();
            $report['details']['frontend_enums'] = $frontendResult;
            if ($frontendResult['status'] === 'error') {
                $hasErrors = true;
            } elseif ($frontendResult['status'] === 'warning') {
                $hasWarnings = true;
            }
            $report['summary']['frontend_enums'] = $frontendResult['summary'];
        }

        // 3. Check SaaS Feature Enums vs DB Features table
        if (in_array($scope, ['all', 'features'], true)) {
            $featureResult = $this->checkFeatures();
            $report['details']['features'] = $featureResult;
            if ($featureResult['status'] === 'error') {
                $hasErrors = true;
            } elseif ($featureResult['status'] === 'warning') {
                $hasWarnings = true;
            }
            $report['summary']['features'] = $featureResult['summary'];
        }

        $report['status'] = $hasErrors ? 'error' : ($hasWarnings ? 'warning' : 'pass');

        return Response::json($report);
    }

    /**
     * @return array{status: string, summary: string, total_permissions: int, orphan_permissions: array<int, string>, missing_metadata: array<int, array<string, string>>}
     */
    protected function checkPermissions(): array
    {
        $allPermissions = PermissionEnum::cases();
        $total = count($allPermissions);

        // Collect all permissions assigned across all RoleTemplateEnum cases
        $assignedPermissions = [];
        foreach (RoleTemplateEnum::cases() as $template) {
            foreach ($template->permissions() as $perm) {
                $assignedPermissions[$perm] = true;
            }
        }

        $orphans = [];
        $missingMetadata = [];

        foreach ($allPermissions as $perm) {
            $value = $perm->value;

            // Check if permission is orphaned (not in any template)
            if (! isset($assignedPermissions[$value])) {
                $orphans[] = $value;
            }

            // Check metadata completeness
            $missing = [];
            if (empty($perm->label())) {
                $missing[] = 'label';
            }
            if (empty($perm->group())) {
                $missing[] = 'group';
            }
            if (empty($perm->groupLabel())) {
                $missing[] = 'groupLabel';
            }

            if (! empty($missing)) {
                $missingMetadata[] = [
                    'permission' => $value,
                    'missing_fields' => implode(', ', $missing),
                ];
            }
        }

        $status = 'pass';
        if (! empty($orphans) || ! empty($missingMetadata)) {
            $status = ! empty($missingMetadata) ? 'error' : 'warning';
        }

        $summary = sprintf(
            '%d permissions checked. %d orphans found, %d metadata errors.',
            $total,
            count($orphans),
            count($missingMetadata)
        );

        return [
            'status' => $status,
            'summary' => $summary,
            'total_permissions' => $total,
            'orphan_permissions' => $orphans,
            'missing_metadata' => $missingMetadata,
        ];
    }

    /**
     * @return array{status: string, summary: string, total_discovered_enums: int, registered_frontend_enums: int, unregistered_enums: array<int, string>}
     */
    protected function checkFrontendEnums(): array
    {
        $reflection = new ReflectionClass(FrontendEnumProvider::class);
        $property = $reflection->getProperty('frontendEnums');
        $property->setAccessible(true);
        /** @var array<class-string> $registeredList */
        $registeredList = $property->getValue();

        $registeredMap = array_flip($registeredList);

        // Scan app/Enums directory
        $enumsDir = app_path('Enums');
        $allEnumFiles = glob($enumsDir.'/*.php') ?: [];
        $unregistered = [];
        $totalDiscovered = 0;

        foreach ($allEnumFiles as $filePath) {
            $className = 'App\\Enums\\'.basename($filePath, '.php');
            if (enum_exists($className)) {
                $totalDiscovered++;
                if (! isset($registeredMap[$className])) {
                    $unregistered[] = $className;
                }
            }
        }

        $summary = sprintf(
            '%d enums discovered, %d registered in FrontendEnumProvider, %d not exposed.',
            $totalDiscovered,
            count($registeredList),
            count($unregistered)
        );

        return [
            'status' => 'pass',
            'summary' => $summary,
            'total_discovered_enums' => $totalDiscovered,
            'registered_frontend_enums' => count($registeredList),
            'unregistered_enums' => $unregistered,
        ];
    }

    /**
     * @return array{status: string, summary: string, total_feature_enums: int, missing_in_db: array<int, string>, extra_in_db: array<int, string>}
     */
    protected function checkFeatures(): array
    {
        $featureCases = array_map(fn (FeatureEnum $case) => $case->value, FeatureEnum::cases());
        $total = count($featureCases);

        $missingInDb = [];
        $extraInDb = [];

        try {
            if (Schema::hasTable('features')) {
                $dbFeatures = Feature::pluck('key')->all();
                $dbFeatureMap = array_flip($dbFeatures);
                $enumFeatureMap = array_flip($featureCases);

                foreach ($featureCases as $key) {
                    if (! isset($dbFeatureMap[$key])) {
                        $missingInDb[] = $key;
                    }
                }

                foreach ($dbFeatures as $key) {
                    if (! isset($enumFeatureMap[$key])) {
                        $extraInDb[] = $key;
                    }
                }
            }
        } catch (\Throwable $e) {
            return [
                'status' => 'info',
                'summary' => "Database connection unavailable ({$e->getMessage()}). Skipped database table comparison.",
                'total_feature_enums' => $total,
                'missing_in_db' => [],
                'extra_in_db' => [],
            ];
        }

        $status = (! empty($missingInDb) || ! empty($extraInDb)) ? 'warning' : 'pass';
        $summary = sprintf(
            '%d FeatureEnum cases checked against database features table. %d missing in DB, %d extra in DB.',
            $total,
            count($missingInDb),
            count($extraInDb)
        );

        return [
            'status' => $status,
            'summary' => $summary,
            'total_feature_enums' => $total,
            'missing_in_db' => $missingInDb,
            'extra_in_db' => $extraInDb,
        ];
    }
}
