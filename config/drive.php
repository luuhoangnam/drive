<?php

return [
    'location'  => 'files', // Absolutely to filesystem.disks.local.root config
    'structure' => '{year}/{month}/{name}.{ext}', // Variables: year, month, name, ext
    // Validation rules
    'rules'     => [
        'max:2048', // Kilobytes
    ],
    'profiles'  => [
        'avatar' => [
            'type'     => 'image',
            // Steps
            'crop'     => [100, 100, 25, 25], // width, height, x, y
            'heighten' => [200], // height
        ],
    ],
];
