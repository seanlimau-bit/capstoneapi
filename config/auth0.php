<?php

return [
    // Auth0 domain, as provided by Auth0
    'domain'        => env('AUTH0_DOMAIN'),

    // Your Auth0 client ID
    'clientId'      => env('AUTH0_CLIENT_ID'),

    // Your Auth0 client secret
    'clientSecret'  => env('AUTH0_CLIENT_SECRET'),

    // URL to redirect to after login
    'redirectUri'   => env('AUTH0_REDIRECT_URI'),

    // Identifier for the API configured in Auth0
    'audience'      => env('AUTH0_AUDIENCE'),

    // Scopes requested for the tokens, separated by space
    'scope'         => 'openid profile email',
];
