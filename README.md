# Netflex Database Driver for Laravel

This package provides a database driver for Laravel that allows you to use the Netflex API as a database backend for your Laravel application.

## Table of contents

* [Motivation / Why?](#motivation--why)
    * [Installation](#installation)
    * [Usage](#usage)
        * [Configuration](#configuration)
* [Eloquent](#eloquent)
    * [Models](#models)
        * [Caveats](#caveats)
* [Netflex specific functionality](#netflex-specific-functionality)
    * [Caching](#caching)
    * [Automatically respecting the publishing status of an entry](#automatically-respecting-the-publishing-status-of-an-entry)
    * [Refresh data from API on save](#refresh-data-from-api-on-save)
    * [Automatically setting name of entries](#automatically-setting-name-of-entries)
* [Limitations](#limitations)
* [Todo](#todo)
* [License](#license)

## Motivation / Why?

Laravel provides a powerful database abstraction layer that allows you to use a variety of different database backends. Most third party Laravel packages that and first party functionality assumes that you are using a relational database backend.

This package allows you to use the Netflex API as a database backend for your Laravel application, bridging the gap between the Netflex API and Laravel.

This again allows you to use most of Laravels functionality out of the box, while still using the Netflex API as your backend.

## Installation

You can install the package via composer:

```bash
composer require netflex/database
```

## Usage

### Configuration

Add the following to your `config/database.php` file:

```php

return [
    'connections' => [

        'netflex' => [
            'driver' => 'netflex',
            'adapter' => 'entry',
        ]
    ]
];
```

Note that the `adapter` property is required if you want to be able to perform "write" operations, and must be set to a valid adapter name. The following adapters are currently supported:
* `entry`

If no adapter is specified, the connection will only work as a read-only connection, and you won't be interrogate the schema of the "databases".

Also note that by using the `entry` adapter, if not otherwise configured, the `prefix` property will be implictly set to `entry_`.

You can also provide a custom adapter class name, as long as it implements the `Netflex\Database\Contracts\DatabaseAdapter` interface.

The adapter is responsible for translating Laravel database queries into Netflex API queries.

#### Advanced configuration

##### Using different API connections

If you have multiple API connections configured, you can specify which one to use by setting the `connection` property on the connection configuration.

```php
[
    'driver' => 'netflex',
    'adapter' => 'entry',
    'connection' => 'my-connection'
]
```

## Eloquent

### Models

To use the Netflex API as a database backend for your Eloquent models, simply ensure your models use a 'netflex' driver backed connection, and enure that the `$table` property matches the index name of the structure you want to use.

If you have configured aliases for your Netflex structures, you can skip name `$table` property and let Eloquent resolve the index name for you based on the model name.

If creating structures through Laravel migrations, we will automatically add the table name as an alias for the structure.

Given the following example model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $connection = 'netflex';
}
```

Laravel will infer the table name from the model name, and use the `entry_` prefix configured on the 'netflex' driver backed connection.

This means that the search queries will be mapped to the `entry_articles` index.

If not using aliases, you can use the following syntax:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $connection = 'netflex';
    protected $table = 'entry_10000';
}
```

If you have configured a 'netflex' driver backed database connection as default, and configured it's `prefix` property to `entry_`, you can also use the following syntax:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = '10000';
}
```

#### Caveats

Since the Netflex API uses the field names `created` and `updated` for it's default timestamps, remember to configure that in your model, since Eloquent assumes `created_at` and `updated_at` by default.

```php
const CREATED_AT = 'created';
const UPDATED_AT = 'updated';
```

## Netflex specific functionality

If you have previously used the Model implmentation from the Netflex SDK, some functionality was provided out of the box, that isn't compatible with Eloquent models directly.

This packages provides some traits that you can apply to  your models to bring back this functionality.

### Caching

This driver does not deal with caching, as that is a concern that should be handled by the application.
The recommended substitute for automatic caching is to use the a tagged cache driver and installing a third party package that deals with caching Eloquent models.

Recommended packages:
* [genealabs/laravel-model-caching](https://github.com/GeneaLabs/laravel-model-caching)

### Automatically respecting the publishing status of an entry

If you want to automatically respect the publishing status of an entry, you can apply the `Netflex\Database\Concerns\Publishable` trait to your model.

### Refresh data from API on save

When inserting or updating an entry, some of it's data might get modified by the API, such as the `id` or `url` property, etc.

Laravel does not automatically refresh the model after saving (for performance reasons), so you might end up with stale data.

If you want to automatically refresh the model after saving, you can apply the `Netflex\Database\Concerns\RefreshesOnSave` trait to your model.

### Automatically setting name of entries

If you attempt to insert an entry without a `name` property, the API will throw an error.
In the Netflex SDK, this was automatically handled by the model, but this isn't consistent with Eloquent models.

You can add this functionality back by applying the `Netflex\Database\Concerns\GeneratesName` trait to your model.

This will automatically generate a UUID based name for the entry, unless a name is provided.

## Limitations

This package is still in development, and is not yet feature complete.

This package is not compatible with PHP >7.4. This is due to the signature of the PDO and PDOStatement classes of PHP having changed in PHP 8.0 and later.

Once this package is feature complete, we will look into supporting PHP 8.0 and later. It should in theory be as simple as just updating the typing of the PDO stubs.

One approach we could take here, to allow this driver to work on both 7.4 and later, is to move the PDO stubs into a separate package, witch one version for 7.4 and one for 8.0 and later.

Then the clients composer client would install the appropriate version of the stubs package, depending on the PHP version.

## Todo

* Move field creation logic into the adapter
* Implement more adapters
    * Pages
    * Customers
    * Others?

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
