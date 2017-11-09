# Listing library for Laravel
 
Makes it easy to list a resource

# Installation

Require this package in your `composer.json` and update composer. Run the following command:
```php
composer require tsfcorp/lister
```

After updating composer, the service provider will automatically be registered and enabled, along with the facade, using Auto-Discovery


Next step is to run the artisan command to bring the config into your project

```php
php artisan vendor:publish --provider="TsfCorp\Lister\ListerServiceProvider"
```

Update `config/lister.php`

# Usage Instructions

`Lister` library can be added to your method signature, this way it will be resolved by DI container or it can be instatiated like this:
```php
$lister = new Lister(request(), DB::connection());
```

Query settings must be specified in this format:
```php
$query_settings = [
    'fields' => "users.*",

    'body' => "FROM users {filters}",

    'filters' => [
        "users.id IN (1,2,3)",
        "users.name LIKE '%{filter_name}%'",
    ],

    'sortables' => [
        'name' => 'asc',
    ],
];

$listing = $lister->make($query_settings)->get();
```

* `{filters}` keyword must be specified in the `body` parameter, so it can be replaced with the conditions specified.
* each item from `filters` param, will be added to `WHERE` clause. If the condition has a parameter specified in curly braces, we'll search for that parameter in the request and replace the parameter with value found.


If using remembered filters and also for query string cleanup, the following are needed at the top of the method used:
```php
if ($remembered = $lister->rememberFilters()) return redirect($remembered);
if ($clean_query_string = $lister->cleanQueryString()) return redirect($clean_query_string);
```

