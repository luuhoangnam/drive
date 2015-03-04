<?php

return [
    'location'         => [
        'temporary' => 'tmp',
        'storage'   => 'files', // Absolutely to filesystem.disks.local.root config
        'public'    => 'files', // Absolutely to public path.
    ],
    'structure'        => '{year}/{month}/{name}.{ext}', // Variables: year, month, name, ext
    // Validation rules
    'rules'            => [
        'max:2048', // Kilobytes
    ],
    // Default processing profiles
    'default_profiles' => [
        'image' => ['avatar'], // profiles
        'video' => [],
    ],
    // Profile name cannot be 'editing' because it's reserved word
    'profiles'         => [
        'avatar' => [
            'type' => 'image',
            // Steps
            'crop' => [100, 100, 25, 25], // width, height, x, y
            'blur' => [20], // percent
        ],
    ],
];
