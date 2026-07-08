<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Service account
    |--------------------------------------------------------------------------
    |
    | Path to the Google Cloud service account JSON key file (with the Wallet
    | Object Issuer role, linked to your issuer account in the Google Pay &
    | Wallet Console) used to sign the "Add to Google Wallet" JWTs.
    |
    */
    'service_account' => env('GOOGLE_WALLET_SERVICE_ACCOUNT'),

    /*
    |--------------------------------------------------------------------------
    | Origins
    |--------------------------------------------------------------------------
    |
    | The origins (e.g. 'https://example.com') that are allowed to render the
    | save urls created by the SaveUrlFactory. Leave empty to allow the save
    | url to be opened from anywhere (e.g. from an email).
    |
    */
    'origins' => [],

    /*
    |--------------------------------------------------------------------------
    | Save/delete callbacks
    |--------------------------------------------------------------------------
    |
    | When enabled, the package exposes a POST '{route_prefix}/callback'
    | endpoint (route name 'google-wallet.callback') that verifies the signed
    | Google Wallet save/delete callbacks and dispatches PassSavedEvent /
    | PassDeletedEvent. Signature verification requires your issuer id. Set
    | 'environment' to 'test' to verify against Google's test root signing
    | keys instead of the production keys.
    |
    */
    'callback' => [
        'enabled' => env('GOOGLE_WALLET_CALLBACK_ENABLED', false),
        'issuer_id' => env('GOOGLE_WALLET_ISSUER_ID'),
        'environment' => 'production',
        'route_prefix' => '/google-wallet',
    ],
];
