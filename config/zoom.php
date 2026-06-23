<?php

return [
    'api_base_url' => env('ZOOM_API_BASE_URL', 'https://api.zoom.us/v2'),
    'oauth_url' => env('ZOOM_OAUTH_URL', 'https://zoom.us/oauth/token'),
    'default_timezone' => env('ZOOM_DEFAULT_TIMEZONE', config('app.timezone', 'UTC')),
];
