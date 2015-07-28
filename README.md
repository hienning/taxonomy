# Taxonomy

A taxonomy inspired by Drupal for Laravel 5. Lots of sql queries in this codebase were copied from Mike Hillyer's [post](http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/).


## Installation

```
composer require hienning/taxonomy
```

load the service in your config/app.php:

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

## How to use it

### Preparation

#### 1. Create the data table

```
artisan taxonomy:create-table [name]
```

where the 'name' is the table name which being created; or use 'taxonomy'
by leaving this argument blank.

#### 2. Inherit your model from Hienning\Taxonomy\Model

```php
class Taxonomy extends \Hienning\Taxonomy\Model
{
    ...
};

```


To be continue ... ;-)
