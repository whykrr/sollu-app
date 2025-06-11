<?php


if (! function_exists('generateBreadcrumbs')) {
    function generateBreadcrumbs($routeName)
    {
        $breadcrumbs = [
            'main' => [
                ['label' => 'Dashboard', 'url' => route('main')],
            ],

            // 'users.index' => [
            //     ['label' => 'Users', 'url' => '#'],
            // ],
            // 'users.create' => [
            //     ['label' => 'Users', 'url' => route('admin.users.index')],
            //     ['label' => 'Add User', 'url' => '#'],
            // ],
            // 'users.edit' => [
            //     ['label' => 'Users', 'url' => route('admin.users.index')],
            //     ['label' => 'Detail User', 'url' => '#'],
            // ],

            // 'languages.index' => [
            //     ['label' => 'Languages', 'url' => '#'],
            // ],
            // 'languages.create' => [
            //     ['label' => 'Languages', 'url' => route('admin.languages.index')],
            //     ['label' => 'Add Language', 'url' => '#'],
            // ],
            // 'languages.edit' => [
            //     ['label' => 'Languages', 'url' => route('admin.languages.index')],
            //     ['label' => 'Add Language', 'url' => '#'],
            // ],

            // 'content-types.index' => [
            //     ['label' => 'Content Type', 'url' => '#'],
            // ],
            // 'content-types.create' => [
            //     ['label' => 'Content Type', 'url' => route('admin.content-types.index')],
            //     ['label' => 'Add Content Type', 'url' => '#'],
            // ],
            // 'content-types.edit' => [
            //     ['label' => 'Content Type', 'url' => route('admin.content-types.index')],
            //     ['label' => 'Detail Content Type', 'url' => '#'],
            // ],

            // 'contents.index' => [
            //     ['label' => 'Content', 'url' => '#'],
            // ],

            // 'contents.listed' => [
            //     ['label' => 'Content', 'url' => '#'],
            // ],

            // 'contents.create' => [
            //     ['label' => 'Content', 'url' => '#'],
            //     ['label' => 'Create Content', 'url' => '#'],
            // ],

            // 'contents.edit' => [
            //     ['label' => 'Content', 'url' => '#'],
            //     ['label' => 'Edit Content', 'url' => '#'],
            // ],

            // 'message.index' => [
            //     ['label' => 'Inbox', 'url' => '#'],
            // ],


            // 'message.show' => [
            //     ['label' => 'Inbox', 'url' => route('admin.message.index')],
            //     ['label' => 'Detail Message', 'url' => '#'],
            // ],

            // 'settings.index' => [
            //     ['label' => 'Settings', 'url' => '#'],
            // ],
        ];

        $routeName = str_replace('admin.', '', $routeName);

        return $breadcrumbs[$routeName] ?? $breadcrumbs['main'];
    }
}
