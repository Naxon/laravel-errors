# Laravel Errors


[![GitHub release](https://img.shields.io/github/release/naxon/laravel-errors.svg)](https://github.com/Naxon/laravel-errors)
[![StyleCI](https://github.styleci.io/repos/134062048/shield?branch=master)](https://github.styleci.io/repos/134062048)
[![Github All Releases](https://img.shields.io/github/downloads/naxon/laravel-errors/total.svg)](https://github.com/Naxon/laravel-errors)

This package lets you load errors the same way you load translations from your filesystem with multilang support, as simple as:

```php
error('validation.empty');
```

## Installation

Install the package through composer:

```bash
composer require naxon/laravel-errors:^1.0.0
```

Then publish the config file:

```bash
php artisan vendor:publish --provider="Naxon\Errors\ErrorsServiceProvider" --tag="config"
```

And finally, create the errors and languages folder under your resource folder:

```bash
├── resources
│   ├── errors
│   │   ├── en
|   |   |   |── validation.php
│   │   ├── he
|   |   |   |── validation.php
```

## Configuration

After you published the `config/errors.php` file, you may edit the errors path:

```php
return [

    /**
     * The path of the errors folder
     */
    'path' => resource_path('errors'),

];
```

## Credits

- [Daniel Naxon](https://github.com/naxon)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.