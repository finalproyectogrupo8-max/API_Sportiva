<?php

/*
|--------------------------------------------------------------------------
| Acceso a la documentación de la API (/api/docs)
|--------------------------------------------------------------------------
|
| La política por entorno vive en App\Http\Middleware\ApiDocsAccess:
| local abierto, staging con HTTP Basic (estas credenciales) y el resto 404.
| Si en staging falta alguna de las dos, la documentación responde 404.
| Nunca versionar valores reales: en staging se cargan en Laravel Cloud.
|
*/

return [

    'username' => env('API_DOCS_USERNAME'),

    'password' => env('API_DOCS_PASSWORD'),

];
