{{--
    Renderer "swagger" de Scramble (config/scramble.php, renderers.swagger).
    Swagger UI se carga desde jsDelivr con versión fija e integridad SRI: si el archivo
    servido cambia, el browser lo bloquea. Para actualizar: docs/decisions.md.
--}}
@php
    $swagger = $config->renderer();
    $version = $swagger->get('version');
    $cdn = "https://cdn.jsdelivr.net/npm/swagger-ui-dist@{$version}";
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $config->get('ui.title') ?? config('app.name').' API' }}</title>
    <link rel="stylesheet" href="{{ $cdn }}/swagger-ui.css" integrity="{{ $swagger->get('integrity.css') }}" crossorigin="anonymous">
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="{{ $cdn }}/swagger-ui-bundle.js" integrity="{{ $swagger->get('integrity.js') }}" crossorigin="anonymous"></script>
    <script nonce="{{ request()->attributes->get(\App\Http\Middleware\ApiDocsAccess::CSP_NONCE_ATTRIBUTE) }}">
        window.addEventListener('load', function () {
            window.ui = SwaggerUIBundle({
                // Ruta relativa: el contrato siempre se pide al mismo origen que la página.
                url: @json(route('api-docs.json', absolute: false)),
                dom_id: '#swagger-ui',
                deepLinking: true,
                tryItOutEnabled: true,
                persistAuthorization: false,
                // Sin validador online: por defecto Swagger UI manda la URL del contrato a swagger.io.
                validatorUrl: null,
            });
        });
    </script>
</body>
</html>
