<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ReflectionClass;

#[Description('Traces a route or URI to its full-stack implementation: Controller, Form Request rules, Injected Services, and Inertia Vue components.')]
class TraceFeatureStack extends Tool
{
    protected string $name = 'trace_feature_stack';

    protected string $title = 'Trace Feature Stack';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'identifier' => $schema->string()
                ->description('Route name (e.g., "app.inventory.items.index") or URL path (e.g., "/app/inventory/items").'),
            'method' => $schema->string()
                ->enum(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'ANY'])
                ->description('HTTP method filter when matching by URL path. Defaults to "ANY".'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $identifier = (string) $request->get('identifier', '');
        $method = strtoupper((string) $request->get('method', 'ANY'));

        if (empty($identifier)) {
            return Response::json([
                'error' => 'Identifier is required. Provide a route name or URI path.',
            ]);
        }

        $matchedRoute = $this->findRoute($identifier, $method);

        if (! $matchedRoute) {
            return Response::json([
                'error' => "No route found matching identifier [{$identifier}] with method [{$method}].",
            ]);
        }

        $stack = $this->analyzeRoute($matchedRoute);

        return Response::json($stack);
    }

    /**
     * Find route by name or URI path.
     */
    protected function findRoute(string $identifier, string $method): ?LaravelRoute
    {
        // 1. Try finding by route name first
        $routeByName = Route::getRoutes()->getByName($identifier);
        if ($routeByName) {
            return $routeByName;
        }

        // 2. Try matching by URI path
        $cleanUri = trim($identifier, '/');
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->uri() === $cleanUri || $route->uri() === $identifier) {
                if ($method === 'ANY' || in_array($method, $route->methods(), true)) {
                    return $route;
                }
            }
        }

        // 3. Fallback: partial regex match
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (Str::is($cleanUri, $route->uri()) || Str::is($identifier, $route->getName() ?? '')) {
                return $route;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function analyzeRoute(LaravelRoute $route): array
    {
        $action = $route->getAction();
        $controllerAction = $action['controller'] ?? null;

        $stack = [
            'route' => [
                'name' => $route->getName(),
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'middleware' => $route->middleware(),
                'rbac_permissions' => $this->extractPermissions($route->middleware()),
                'plan_features' => $this->extractPlanFeatures($route->middleware()),
            ],
            'controller' => null,
            'form_request' => null,
            'injected_services' => [],
            'frontend' => [
                'inertia_page' => null,
                'page_file' => null,
                'page_exists' => false,
                'sibling_components' => [],
            ],
        ];

        if (! is_string($controllerAction) || ! str_contains($controllerAction, '@')) {
            $stack['controller'] = [
                'raw_action' => is_string($controllerAction) ? $controllerAction : 'Closure or Invokable',
            ];

            return $stack;
        }

        [$controllerClass, $methodName] = explode('@', $controllerAction);

        if (! class_exists($controllerClass)) {
            $stack['controller'] = [
                'class' => $controllerClass,
                'method' => $methodName,
                'error' => 'Class not found',
            ];

            return $stack;
        }

        $reflectionClass = new ReflectionClass($controllerClass);
        $fileName = $reflectionClass->getFileName();

        $stack['controller'] = [
            'class' => $controllerClass,
            'method' => $methodName,
            'file' => $fileName ? str_replace(base_path().'/', '', $fileName) : null,
        ];

        // Analyze constructor dependencies (Injected Services)
        $constructor = $reflectionClass->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                if ($type && ! $type->isBuiltin()) {
                    $className = (string) $type;
                    if (str_contains($className, 'Service') || str_contains($className, 'App\\Services\\')) {
                        $stack['injected_services'][] = [
                            'name' => $param->getName(),
                            'class' => $className,
                        ];
                    }
                }
            }
        }

        // Analyze Method parameters for FormRequest
        if ($reflectionClass->hasMethod($methodName)) {
            $methodReflection = $reflectionClass->getMethod($methodName);
            foreach ($methodReflection->getParameters() as $param) {
                $type = $param->getType();
                if ($type && ! $type->isBuiltin()) {
                    $className = (string) $type;
                    if (is_subclass_of($className, FormRequest::class)) {
                        $stack['form_request'] = $this->analyzeFormRequest($className);
                    }
                }
            }

            // Inspect method source code for Inertia::render
            if ($fileName && file_exists($fileName)) {
                $fileContent = (string) file_get_contents($fileName);
                if (preg_match("/Inertia::render\s*\(\s*['\"]([^'\"]+)['\"]/", $fileContent, $matches)) {
                    $inertiaComponent = $matches[1];
                    $stack['frontend']['inertia_page'] = $inertiaComponent;

                    $vueFilePath = resource_path('js/Pages/'.$inertiaComponent.'.vue');
                    $stack['frontend']['page_file'] = str_replace(base_path().'/', '', $vueFilePath);
                    $stack['frontend']['page_exists'] = file_exists($vueFilePath);

                    // Scan sibling components
                    $componentDir = dirname($vueFilePath).'/Components';
                    if (is_dir($componentDir)) {
                        $siblings = glob($componentDir.'/*.vue') ?: [];
                        $stack['frontend']['sibling_components'] = array_map(
                            fn (string $path) => str_replace(base_path().'/', '', $path),
                            $siblings
                        );
                    }
                }
            }
        }

        return $stack;
    }

    /**
     * @param  array<int, string>  $middleware
     * @return array<int, string>
     */
    protected function extractPermissions(array $middleware): array
    {
        $permissions = [];
        foreach ($middleware as $m) {
            if (str_starts_with($m, 'permission:')) {
                $permissions[] = substr($m, strlen('permission:'));
            }
        }

        return $permissions;
    }

    /**
     * @param  array<int, string>  $middleware
     * @return array<int, string>
     */
    protected function extractPlanFeatures(array $middleware): array
    {
        $features = [];
        foreach ($middleware as $m) {
            if (str_starts_with($m, 'plan.feature:')) {
                $features[] = substr($m, strlen('plan.feature:'));
            }
        }

        return $features;
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     * @return array<string, mixed>
     */
    protected function analyzeFormRequest(string $requestClass): array
    {
        $ref = new ReflectionClass($requestClass);
        $fileName = $ref->getFileName();

        $rules = [];
        try {
            if ($ref->hasMethod('rules')) {
                $rulesMethod = $ref->getMethod('rules');
                if ($rulesMethod->getNumberOfRequiredParameters() === 0) {
                    $instance = new $requestClass;
                    $rules = $instance->rules();
                }
            }
        } catch (\Throwable) {
            $rules = ['note' => 'Could not dynamically instantiate FormRequest rules.'];
        }

        return [
            'class' => $requestClass,
            'file' => $fileName ? str_replace(base_path().'/', '', $fileName) : null,
            'rules' => $rules,
        ];
    }
}
