<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;

class SwaggerController extends Controller
{
    public function index()
    {
        if (!app()->environment('local', 'development', 'testing')) {
            abort(404);
        }

        $yamlUrl = route('docs.openapi');

        return response()->make(<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sollu POS API Documentation - Swagger UI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
    <style>
      html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
      *, *:before, *:after { box-sizing: inherit; }
      body { margin:0; background: #fafafa; }
    </style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" charset="UTF-8"> </script>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js" charset="UTF-8"> </script>
<script>
window.onload = function() {
  const ui = SwaggerUIBundle({
    url: "{$yamlUrl}",
    dom_id: '#swagger-ui',
    deepLinking: true,
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIStandalonePreset
    ],
    plugins: [
      SwaggerUIBundle.plugins.DownloadUrl
    ],
    layout: "StandaloneLayout"
  });
  window.ui = ui;
};
</script>
</body>
</html>
HTML
        , 200, ['Content-Type' => 'text/html']);
    }

    public function yaml()
    {
        if (!app()->environment('local', 'development', 'testing')) {
            abort(404);
        }

        $path = base_path('docs/openapi.yaml');
        if (!file_exists($path)) {
            abort(404, 'File OpenAPI specification tidak ditemukan.');
        }

        return response()->file($path, [
            'Content-Type' => 'text/yaml'
        ]);
    }
}
