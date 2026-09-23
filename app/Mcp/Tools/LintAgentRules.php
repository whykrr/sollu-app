<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

#[Description('Lints files against the project\'s .agents/rules/ guidelines (no shadows in MainPage, casts() method in Laravel 11 models, useFormDirtyGuard, Form component usage).')]
class LintAgentRules extends Tool
{
    protected string $name = 'lint_agent_rules';

    protected string $title = 'Lint Agent Rules';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('Relative path to file or directory to lint (e.g. "app/Models/Inventory" or "resources/js/Pages/App/Inventory"). If omitted, lints common directories.'),
            'ruleset' => $schema->string()
                ->enum(['all', 'backend', 'frontend'])
                ->description('Filter ruleset: "all", "backend", or "frontend". Defaults to "all".'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $pathInput = (string) $request->get('path', '');
        $ruleset = (string) $request->get('ruleset', 'all');

        $files = $this->collectFiles($pathInput, $ruleset);

        $violations = [];
        $totalFilesChecked = 0;

        foreach ($files as $filePath) {
            $totalFilesChecked++;
            $fileViolations = $this->lintFile($filePath, $ruleset);
            if (! empty($fileViolations)) {
                $violations[$filePath] = $fileViolations;
            }
        }

        $totalViolations = array_sum(array_map('count', $violations));

        return Response::json([
            'status' => $totalViolations === 0 ? 'pass' : 'warning',
            'summary' => sprintf(
                '%d files checked. Found %d rule violations across %d files.',
                $totalFilesChecked,
                $totalViolations,
                count($violations)
            ),
            'total_files_checked' => $totalFilesChecked,
            'total_violations' => $totalViolations,
            'violations_by_file' => $violations,
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function collectFiles(string $pathInput, string $ruleset): array
    {
        $base = base_path();

        if (! empty($pathInput)) {
            $fullPath = str_starts_with($pathInput, '/') ? $pathInput : $base.'/'.$pathInput;
            if (is_file($fullPath)) {
                return [$fullPath];
            }
            if (is_dir($fullPath)) {
                return $this->scanDirectory($fullPath, $ruleset);
            }
        }

        $files = [];
        if (in_array($ruleset, ['all', 'backend'], true)) {
            $files = array_merge($files, $this->scanDirectory($base.'/app', 'backend'));
        }
        if (in_array($ruleset, ['all', 'frontend'], true)) {
            $files = array_merge($files, $this->scanDirectory($base.'/resources/js/Pages/App', 'frontend'));
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    protected function scanDirectory(string $dir, string $ruleset): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $ext = $file->getExtension();
            if ($ruleset === 'backend' && $ext === 'php') {
                $files[] = $file->getPathname();
            } elseif ($ruleset === 'frontend' && in_array($ext, ['vue', 'js', 'ts'], true)) {
                $files[] = $file->getPathname();
            } elseif ($ruleset === 'all' && in_array($ext, ['php', 'vue', 'js', 'ts'], true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * @return array<int, array{line: int, rule: string, severity: string, message: string, suggestion: string}>
     */
    protected function lintFile(string $filePath, string $ruleset): array
    {
        $content = (string) file_get_contents($filePath);
        $lines = explode("\n", $content);
        $violations = [];
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $relPath = str_replace(base_path().'/', '', $filePath);

        if ($ext === 'php') {
            // Rule 06: Laravel 11 Model casts method vs $casts property
            if (str_contains($filePath, '/Models/')) {
                foreach ($lines as $i => $line) {
                    if (preg_match('/protected\s+\$casts\s*=/', $line)) {
                        $violations[] = [
                            'line' => $i + 1,
                            'rule' => 'Rule 06: Laravel 11 Eloquent Model Casts',
                            'severity' => 'warning',
                            'message' => 'Deprecated protected $casts property detected.',
                            'suggestion' => 'Replace with protected function casts(): array { return [...]; } method.',
                        ];
                    }
                }
            }
        }

        if ($ext === 'vue') {
            $isMainPage = str_contains($content, '<MainPage') || str_contains($content, 'MainPage');
            $isOverlayOrToast = str_contains($filePath, 'Toast') || str_contains($filePath, 'Modal') || str_contains($filePath, 'Dropdown');

            foreach ($lines as $i => $line) {
                // Rule 05: Flat Minimalist - No shadows inside MainPage
                if ($isMainPage && ! $isOverlayOrToast) {
                    if (preg_match('/\b(shadow|shadow-sm|shadow-md|shadow-lg|shadow-xl|shadow-2xl|shadow-xs)\b/', $line, $match)) {
                        // Skip comments
                        if (! str_starts_with(trim($line), '<!--') && ! str_starts_with(trim($line), '//')) {
                            $violations[] = [
                                'line' => $i + 1,
                                'rule' => 'Rule 05: Flat Minimalist & Larangan Shadow',
                                'severity' => 'warning',
                                'message' => "Shadow class [{$match[1]}] detected in MainPage component.",
                                'suggestion' => 'Remove shadow class. Use subtle border (border-slate-200) and solid background (bg-white / bg-slate-50).',
                            ];
                        }
                    }
                }

                // Rule 05 / 01: Raw HTML form tags
                if (preg_match('/<input\s+(?![^>]*type=[\'"]hidden[\'"])/', $line)) {
                    $violations[] = [
                        'line' => $i + 1,
                        'rule' => 'Rule 05: Komponen Form Terpusat',
                        'severity' => 'info',
                        'message' => 'Raw <input> tag detected.',
                        'suggestion' => 'Use centralized form components like TextField, NumberField, or AsyncSelectField from @/Components/Form/.',
                    ];
                }

                // Rule 05 / 11: Bypassing useFormDirtyGuard on cancel
                if (preg_match('/@click=["\']popUpStore\.close\(\)["\']/', $line)) {
                    $violations[] = [
                        'line' => $i + 1,
                        'rule' => 'Rule 05: useFormDirtyGuard Form Protection',
                        'severity' => 'warning',
                        'message' => 'Direct popUpStore.close() call detected on template click.',
                        'suggestion' => 'Use handleCancel from useFormDirtyGuard to prevent losing unsaved form changes.',
                    ];
                }
            }
        }

        return $violations;
    }
}
