<?php

use Attla\Support\Envir;

return [
    'flow' => [
        'route' => '__sign',
        'server' => 'http://localhost',
        'callback' => 'http://localhost/_auth?i={id}&n={name}&p={image}',
        'remember' => 2628000, // 5 years in minutes
    ],

    /**
     * Alphabet base seed to create a unique dictionary
     *
     * @var int|string
     */
    'seed' => null,

    /**
     * Encryption secret key
     *
     * @var string
     */
    'key' => Envir::get('app.key', Envir::get('APP_KEY')),
];
