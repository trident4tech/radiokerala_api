<?php
//config.auth.php

return [
    'defaults' => [
        'guard' => 'check_Jwt_Token_and_ACL_Checking',
        'passwords' => 'users',

    ],

    'guards' => [
        'check_Jwt_Token_and_ACL_Checking' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => \App\User::class
        ],
    ]
];
