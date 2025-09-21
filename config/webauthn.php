<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Relying Party
    |--------------------------------------------------------------------------
    |
    | We will use your application information to inform the device who is the
    | relying party. While only the name is enough, you can further set the
    | a custom domain as ID and even an icon image data encoded as BASE64.
    |
    */

  

    /*
    |--------------------------------------------------------------------------
    | Origins
    |--------------------------------------------------------------------------
    |
    | By default, only your application domain is used as a valid origin for
    | all ceremonies. If you are using your app as a backend for an app or
    | UI you may set additional origins to check against the ceremonies.
    |
    | For multiple origins, separate them using comma, like `foo,bar`.
    */

    'origins' => env('WEBAUTHN_ORIGINS'),



    'assertion' => [
        'user_verification' => 'required', // Hello prompt on sign-in
    ],
    'relying_party' => [
        'id'   => env('WEBAUTHN_ID', parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost'),
        'name' => env('WEBAUTHN_NAME', config('app.name')),
    ],

    'attestation' => [
        'authenticator_selection' => [
            'authenticator_attachment' => 'platform', // prefer Windows Hello
            'resident_key'             => 'required', // discoverable passkey
            'user_verification'        => 'required',
        ],
        'attestation' => 'none',
    ],

  



    /*
    |--------------------------------------------------------------------------
    | Challenge configuration
    |--------------------------------------------------------------------------
    |
    | When making challenges your application needs to push at least 16 bytes
    | of randomness. Since we need to later check them, we'll also store the
    | bytes for a small amount of time inside this current request session.
    |
    | @see https://www.w3.org/TR/webauthn-2/#sctn-cryptographic-challenges
    |
    */

    'challenge' => [
        'bytes' => 16,
        'timeout' => 60,
        'key' => '_webauthn',
    ],
];
