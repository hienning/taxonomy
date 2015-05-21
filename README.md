# Taxonomy

A taxonomy inspired by Drupal for Laravel 5.


## Install

```
composer require hienning/taxonomy
```

load the service in config/app.php:

```php
/*
 * Application Service Providers...
 */
'App\Providers\AppServiceProvider',
'App\Providers\BusServiceProvider',
'App\Providers\ConfigServiceProvider',
'App\Providers\EventServiceProvider',
'App\Providers\RouteServiceProvider',

'Hienning\Taxonomy\ServiceProvider',
...
```

## Usage

### 1. Create the data table

```
artisan taxonomy:create-table [name]
```

where the 'name' is the table name which being create; or use 'taxonomy'
by leave this argument empty.

### 2. Inherit your model from Hienning\Taxonomy\Model

```php
class Taxonomy extends \Hienning\Taxonomy\Model
{
    ...
};

```

