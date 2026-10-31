<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\Process\Process;

#[Description('Audits database migrations against the latest Git stable release tag (production baseline) to guarantee backward compatibility, catch immutable migration file violations, and enforce safe expand-contract schema evolution.')]
class AuditMigrationCompatibility extends Tool
{
    protected string $name = 'audit_migration_compatibility';

    protected string $title = 'Audit Migration Compatibility';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tag' => $schema->string()
                ->description('Specific baseline Git tag to compare against (e.g. "v1.2.3"). If omitted, auto-detects the latest stable SemVer tag.'),
            'strict' => $schema->boolean()
                ->description('Whether to enforce strict check on working directory modifications. Defaults to true.'),
            'scope' => $schema->string()
                ->enum(['all', 'tag_integrity', 'backward_compatibility'])
                ->description('Filter validation scope: "all", "tag_integrity", or "backward_compatibility". Defaults to "all".'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $customTag = (string) $request->get('tag', '');
        $strict = (bool) $request->get('strict', true);
        $scope = (string) $request->get('scope', 'all');

        $basePath = base_path();
        $baselineTag = $customTag !== '' ? $customTag : $this->getLatestStableTag($basePath);

        if (! $baselineTag) {
            return Response::json([
                'status' => 'warning',
                'summary' => 'No stable SemVer Git tag found (e.g. vX.Y.Z). Baseline check skipped.',
                'baseline_tag' => null,
                'violations' => [],
            ]);
        }

        $taggedMigrations = $this->getTaggedMigrations($basePath, $baselineTag);
        $violations = [];
        $unreleasedMigrations = [];

        // 1. Tag Integrity Check: Ensure tagged migrations are not modified or deleted
        if (in_array($scope, ['all', 'tag_integrity'], true)) {
            $tagViolations = $this->checkTagIntegrity($basePath, $baselineTag, $taggedMigrations, $strict);
            $violations = array_merge($violations, $tagViolations);
        }

        // 2. Backward Compatibility & Unreleased Migrations Inspection
        if (in_array($scope, ['all', 'backward_compatibility'], true)) {
            $unreleasedMigrations = $this->getUnreleasedMigrations($basePath, $baselineTag);
            $compatViolations = $this->checkBackwardCompatibility($basePath, $unreleasedMigrations);
            $violations = array_merge($violations, $compatViolations);
        }

        $status = empty($violations) ? 'pass' : (
            collect($violations)->contains('severity', 'critical') ? 'violation' : 'warning'
        );

        return Response::json([
            'status' => $status,
            'summary' => sprintf(
                'Baseline tag [%s]: Checked %d tagged migrations and %d unreleased migrations. Found %d violations.',
                $baselineTag,
                count($taggedMigrations),
                count($unreleasedMigrations),
                count($violations)
            ),
            'baseline_tag' => $baselineTag,
            'total_tagged_migrations' => count($taggedMigrations),
            'total_unreleased_migrations' => count($unreleasedMigrations),
            'violations_count' => count($violations),
            'violations' => $violations,
            'unreleased_migrations' => $unreleasedMigrations,
            'guidelines' => [
                'rule' => 'Rule 08: Database Schema, Reversible Migrations & Git-Tag Production Baseline',
                'recommendation' => 'Never edit or delete migrations released in production tags. For schema changes, create a new migration: php artisan make:migration <name>',
            ],
        ]);
    }

    /**
     * Resolves the latest stable SemVer tag (e.g. v1.2.3, ignoring -beta, -test, -rc).
     */
    protected function getLatestStableTag(string $basePath): ?string
    {
        $process = new Process(['git', 'tag', '-l', 'v*.*.*', '--sort=-v:refname'], $basePath);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $lines = array_filter(explode("\n", trim($process->getOutput())));
        foreach ($lines as $tag) {
            $trimmed = trim($tag);
            if (preg_match('/^v\d+\.\d+\.\d+$/', $trimmed)) {
                return $trimmed;
            }
        }

        return null;
    }

    /**
     * Retrieves all migration file paths present in the specified Git tag.
     *
     * @return array<int, string>
     */
    protected function getTaggedMigrations(string $basePath, string $tag): array
    {
        $process = new Process(['git', 'ls-tree', '-r', '--name-only', $tag, '--', 'database/migrations'], $basePath);
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $lines = array_filter(explode("\n", trim($process->getOutput())));

        return array_values(array_filter($lines, fn ($path) => str_ends_with($path, '.php')));
    }

    /**
     * Detects modified or deleted migration files that exist in the baseline tag.
     *
     * @param  array<int, string>  $taggedMigrations
     * @return array<int, array{file: string, severity: string, type: string, message: string, suggestion: string}>
     */
    protected function checkTagIntegrity(string $basePath, string $tag, array $taggedMigrations, bool $strict): array
    {
        $violations = [];
        $taggedLookup = array_flip($taggedMigrations);

        // Check difference between baseline tag and HEAD
        $diffProcess = new Process(['git', 'diff', '--name-status', $tag, 'HEAD', '--', 'database/migrations'], $basePath);
        $diffProcess->run();

        if ($diffProcess->isSuccessful()) {
            $lines = array_filter(explode("\n", trim($diffProcess->getOutput())));
            foreach ($lines as $line) {
                if (preg_match('/^([MDR])\s+(.+)$/', trim($line), $match)) {
                    $status = $match[1];
                    $file = $match[2];

                    if (isset($taggedLookup[$file])) {
                        $action = $status === 'D' ? 'deleted' : ($status === 'M' ? 'modified' : 'renamed');
                        $violations[] = [
                            'file' => $file,
                            'severity' => 'critical',
                            'type' => 'tag_immutability_violation',
                            'message' => "Migration file was {$action} after being released in baseline tag [{$tag}].",
                            'suggestion' => 'Restore the original migration file. Any schema adjustments must be made in a NEW migration file created with: php artisan make:migration <descriptive_name>',
                        ];
                    }
                }
            }
        }

        // Check uncommitted changes in working tree
        if ($strict) {
            $statusProcess = new Process(['git', 'status', '--porcelain', '--', 'database/migrations'], $basePath);
            $statusProcess->run();

            if ($statusProcess->isSuccessful()) {
                $statusLines = array_filter(explode("\n", trim($statusProcess->getOutput())));
                foreach ($statusLines as $line) {
                    $code = substr(trim($line), 0, 2);
                    $file = trim(substr(trim($line), 2));

                    if (isset($taggedLookup[$file]) && (str_contains($code, 'M') || str_contains($code, 'D'))) {
                        // Avoid duplicate if already reported in diff
                        if (! collect($violations)->contains('file', $file)) {
                            $violations[] = [
                                'file' => $file,
                                'severity' => 'critical',
                                'type' => 'working_tree_immutability_violation',
                                'message' => "Uncommitted changes detected on migration file released in baseline tag [{$tag}].",
                                'suggestion' => 'Discard modifications with `git checkout -- '.$file.'` and create a NEW migration file for current schema changes.',
                            ];
                        }
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Lists migrations introduced after the baseline tag.
     *
     * @return array<int, string>
     */
    protected function getUnreleasedMigrations(string $basePath, string $tag): array
    {
        $process = new Process(['git', 'diff', '--name-only', '--diff-filter=A', $tag, 'HEAD', '--', 'database/migrations'], $basePath);
        $process->run();

        $committedNew = [];
        if ($process->isSuccessful()) {
            $committedNew = array_values(array_filter(
                explode("\n", trim($process->getOutput())),
                fn ($path) => str_ends_with($path, '.php')
            ));
        }

        // Untracked or added in working directory
        $untrackedProcess = new Process(['git', 'status', '--porcelain', '--', 'database/migrations'], $basePath);
        $untrackedProcess->run();

        $workingNew = [];
        if ($untrackedProcess->isSuccessful()) {
            $lines = array_filter(explode("\n", trim($untrackedProcess->getOutput())));
            foreach ($lines as $line) {
                if (preg_match('/^\?\?\s+(.+)$/', trim($line), $m) || preg_match('/^A\s+(.+)$/', trim($line), $m)) {
                    if (str_ends_with($m[1], '.php')) {
                        $workingNew[] = $m[1];
                    }
                }
            }
        }

        return array_values(array_unique(array_merge($committedNew, $workingNew)));
    }

    /**
     * Inspects unreleased migration files for backward compatibility best practices.
     *
     * @param  array<int, string>  $migrations
     * @return array<int, array{file: string, severity: string, type: string, message: string, suggestion: string}>
     */
    protected function checkBackwardCompatibility(string $basePath, array $migrations): array
    {
        $violations = [];

        foreach ($migrations as $relPath) {
            $fullPath = $basePath.'/'.$relPath;
            if (! is_file($fullPath)) {
                continue;
            }

            $content = (string) file_get_contents($fullPath);

            // 1. Check for empty down() method
            if (preg_match('/public\s+function\s+down\s*\(\s*\)\s*:\s*void\s*\{\s*\}/', $content) ||
                preg_match('/public\s+function\s+down\s*\(\s*\)\s*\{\s*\}/', $content)) {
                $violations[] = [
                    'file' => $relPath,
                    'severity' => 'critical',
                    'type' => 'irreversible_down_method',
                    'message' => 'Empty down() method detected. Absolute Rollback Symmetry requires every up() operation to have an exact reverse in down().',
                    'suggestion' => 'Implement the exact reverse DDL/DML operations in down() in LIFO order.',
                ];
            }

            // 2. Check for missing SQLite guard on postgres-specific operations
            $hasPostgresFeatures = str_contains($content, 'fullText') || str_contains($content, 'fulltext') || str_contains($content, 'GIN') || str_contains($content, 'gin');
            $hasSqliteGuard = str_contains($content, "getDriverName() !== 'sqlite'") || str_contains($content, 'getDriverName() != "sqlite"');

            if ($hasPostgresFeatures && ! $hasSqliteGuard) {
                $violations[] = [
                    'file' => $relPath,
                    'severity' => 'warning',
                    'type' => 'missing_sqlite_driver_guard',
                    'message' => 'PostgreSQL-specific indexing/syntax detected without SQLite driver guard. This will cause SQLite test suites to fail.',
                    'suggestion' => 'Wrap postgres-specific DDL inside: if (DB::getDriverName() !== \'sqlite\') { ... } in both up() and down().',
                ];
            }

            // 3. Check for seeder execution inside migration
            if (preg_match('/(new\s+\w+Seeder|\bArtisan::call\([\'"]db:seed)/', $content)) {
                $violations[] = [
                    'file' => $relPath,
                    'severity' => 'critical',
                    'type' => 'seeder_called_in_migration',
                    'message' => 'Production Seeder execution detected inside migration file.',
                    'suggestion' => 'Remove seeder calls from migrations. Migrations run during RefreshDatabase in tests; seeders cause unique constraint collisions.',
                ];
            }

            // 4. Check for destructive dropColumn without phased expand-contract
            if (preg_match('/->dropColumn\(/', $content) && ! str_contains($content, 'phased') && ! str_contains($content, 'deprecated')) {
                $violations[] = [
                    'file' => $relPath,
                    'severity' => 'info',
                    'type' => 'destructive_column_drop',
                    'message' => 'Column drop detected. Verify this adheres to the Expand-and-Contract phased migration pattern.',
                    'suggestion' => 'Ensure old columns are only dropped in Phase 3 after all application code and mobile POS clients no longer reference them.',
                ];
            }
        }

        return $violations;
    }
}
