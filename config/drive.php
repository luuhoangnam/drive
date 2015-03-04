<?php

return [
    'location'         => 'files', // Absolutely to filesystem.disks.local.root config
    'temporary'        => 'temp', // Same as above but its temporary directory
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
            'type'     => 'image',
            // Steps
            'crop'     => [100, 100, 25, 25], // width, height, x, y
            'blur' => [200], // height
        ],
    ],
];
