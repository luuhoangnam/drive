# API

```php
try {
    $file = Drive::accept('upload_file');
    $small = $file->crop(...)
                  ->lighten(...)
                  ->store('small');
                  
    $medium = $file->crop(...)
                   ->lighten(...)
                   ->store('medium');
                   
    $large = $file->crop(...)
                  ->lighten(...)
                  ->store('large');
} catch (\Exception $e) {
    // Handle exception
}
```