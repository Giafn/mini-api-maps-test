<?php

namespace App\Http\Controllers;

class DocsController extends Controller
{
    public function openapi()
    {
        $path = base_path('openapi/openapi.yaml');
        if (! file_exists($path)) {
            return response('OpenAPI file not found', 404);
        }

        return response()->file($path, ['Content-Type' => 'text/vnd.yaml']);
    }

    public function ui()
    {
        $url = url('/api/docs/openapi.yaml');
        $html = <<<HTML
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>API Docs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/redoc@next/bundles/redoc.standalone.js"></script>
  </head>
  <body>
    <redoc spec-url="$url"></redoc>
  </body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
