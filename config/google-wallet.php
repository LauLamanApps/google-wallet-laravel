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
];
