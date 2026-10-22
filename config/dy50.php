<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Token compartido con la Raspberry
    |--------------------------------------------------------------------------
    | Se lee del archivo .env (DY50_API_TOKEN=...). Si queda vacío, la API
    | rechaza todas las peticiones (falla cerrado).
    */
    'token' => env('DY50_API_TOKEN'),

];
