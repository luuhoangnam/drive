# API

## Accept file upload (validate,...)
```php
$file = Drive::accept($input_name);
```

## Validation
```php
// Set up validation rules in config file use laravel validation rules
return [
    ...
    'rules' => [
        'max:2048', // Kilobytes
    ],
    ...
];
```

Throws `ValidationException` when failed validation.

## Save file (without editing)
```php
$nomal = $file->save();
$large = $file->save('-large'); // With file name suffix
```

## Edit image file via method chaining
```php
// Recipe: $file-><intervention/image method>($parameters)
$small = $file->crop($width, $height, $x, $y)
              ->blur($percent)
              ->save('-small');
```

## Edit image use profile declare in `drive.profiles` config
```php
$avatar = $file->profile('avatar')->save();
```

## Auto edit file with file type
```php
// For example: apply `avatar` profile with every uploaded images.
// Edit in `drive` config
return [
    ...
    'default_profiles' => [
        'image' => ['avatar'],
        'video' => [],
    ],
    ...
];
```