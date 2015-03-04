# Getting Started

Provide an _elegant way_ to interact with upload & process uploaded file feature.

**Note**: The package is only support Laravel 5

# Installation

**Step 1**: Install package
```bash
composer require namest/drive
```

**Step 2**: Register service provider in your `config/app.php`
```php
return [
    ...
    'providers' => [
        ...
        'Namest\Drive\DriveServiceProvider',
    ],
    ...
];
```

**Step 3**: Publish package configs. Open your terminal and type:
```bash
php artisan vendor:publish --provider="Namest\Drive\DriveServiceProvider"
```

**Step 4**: Edit your appropriate configurations in `config/drive.php`:

**Step 6**: Read API below and start _happy_

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
        // Whatever you want
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

## Auto edit file by profile for particular upload type
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