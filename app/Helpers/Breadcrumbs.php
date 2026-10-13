<?php

use App\Support\Breadcrumbs\BreadcrumbManager;

if (! function_exists('generateBreadcrumbs')) {
    /**
     * @param  mixed  $routeName
     * @return array<int, array{label: string, url: ?string}>
     */
    function generateBreadcrumbs($routeName = null, string $scope = 'app')
    {
        return (new BreadcrumbManager)->generate($routeName, $scope);
    }
}
