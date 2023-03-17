# Netflex Database Driver for Laravel

<p>
<a href="https://github.com/netflex-sdk/database/actions"><img src="https://github.com/netflex-sdk/database/actions/workflows/static_analysis.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/netflex/database"><img src="https://img.shields.io/packagist/dt/netflex/database" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/netflex/database"><img src="https://img.shields.io/packagist/v/netflex/database" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/netflex/database"><img src="https://img.shields.io/packagist/l/netflex/database" alt="License"></a>
</p>

This package provides a database driver for Laravel that enables you to use the Netflex API as a database backend for your Laravel application.

This package supports Laravel 8 through 10, and PHP 7.4 through 8.2.

## Table of contents

* [Motivation / Why?](#motivation--why)
    * [Installation](#installation)
    * [Usage](#usage)
        * [Configuration](#configuration)
* [Eloquent](#eloquent)
    * [Reference Model](#reference-model)
    * [Models](#models)
        * [Caveats](#caveats)
* [Migrations](#migrations)
* [Netflex specific functionality](#netflex-specific-functionality)
    * [Caching](#caching)
    * [Automatically respecting the publishing status of an entry](#automatically-respecting-the-publishing-status-of-an-entry)
    * [Refresh data from API on save](#refresh-data-from-api-on-save)
    * [Automatically setting name of entries](#automatically-setting-name-of-entries)
* [Limitations](#limitations)
* [Todo](#todo)
* [License](#license)

## Motivation / Why?

Laravel provides a powerful database abstraction layer that allows you to use a variety of different database backends.

Most first and third party packages in the Laravel ecosystem assumes that you are using a relational database backend.

This package enables you to use the Netflex API as a database backend for your Laravel application, bridging the gap between the Netflex API and Laravel.

This enables you to use most of Laravels functionality out of the box, while still using the Netflex API as your backend.

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
* `entry` (Full support)
* `customer` (Write support, schema manipulation not supported due to API limitations)
* `page` (Read-only support)
* `read-only` (also aliased as `default` for fallback purposes)

If no adapter is specified, the connection will only work as a read-only connection, and you won't be able to interrogate your connections Schema or perform any write operations.

Note that by using the `entry` adapter, your models `$prefix` property will be prepended with `entry_` to match the Netflex index naming convention.

You may also provide a custom adapter class. It needs to implement the `Netflex\Database\DBAL\Contracts\DatabaseAdapter` interface.

The adapter is responsible for translating betweeen the database layer of Laravel and the Netflex API.

#### Advanced configuration

##### Using different API connections

If you have multiple API connections configured, you can specify which one to use by setting the `connection` property on the connection configuration. (See [netflex/api](https://github.com/netflex-sdk/api/blob/master/config/api.php) for reference).

```php
[
    'driver' => 'netflex',
    'adapter' => 'entry',
    'connection' => 'my-connection' // Refers to a Netflex API connection
]
```

## Eloquent

### Refrence Model

This package provides a reference model that you can use as a base for your models.
This is recommended for most use cases, as it provides a number of useful features.

```php
namespace App\Models;

use Netflex\Database\Eloquent\Model;

class Article extends Model
{
    //
}
```

### Models

To use the Netflex API as a database backend for your Eloquent models, all you have to do is register a database connection that use the `netflex` driver.

If you have configured aliases for your Netflex structures, you can skip name `$table` property and let Eloquent resolve the index name for you based on the models alias.

If creating structures through Laravel migrations and using the `entry` adapter, we will automatically add a table alias for you. This alias follows the convention of the pluralized model name in snake_case.

Example:

In the following example, a default database connection is configured with the name `structures` to use the `netflex` driver, and the `entry` adapter.

`config/database.php`
```php
return [
    'default' => 'structures',

    'connections' => [

        'structures' => [
            'driver' => 'netflex',
            'adapter' => 'entry',
        ]
    ]
];
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    //
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
    protected $table = '10000';
}
```

If you have multiple database connections, and the default connection is not configured to use the `netflex` driver, you can specify the connection to use by setting the `$connection` property on your model.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $connection = 'structures';
    protected $table = '10000';
}
```

#### Caveats

Since the Netflex API uses the field names `created` and `updated` for it's default timestamps, remember to configure that in your model, since Eloquent assumes `created_at` and `updated_at` by default.

```php
const CREATED_AT = 'created';
const UPDATED_AT = 'updated';
```

## Migrations

Migrations are supported for the `entry` adapter, and will automatically create or alter the structure in Netflex.

Reserved fields in the Netflex API are automatically handled, and will not be added or altered. This allows you to re-use the same migrations for both Netflex and relational databases.

Unique contraints and cascades are not supported. You can have them in your migration, but they will be ignored.

You create and manage migrations as you normally would through Artisan.

If no types are specified, the following types will be used:
* text (string)
* textarea (bigText, text, bigInt etc)
* integer (int)
* float (float, decimal)
* checkbox (boolean)
* json (json)

Default values are supported. Here you may also use the special syntax for Netflex to automatically add certain values, like datetimes and UUID's etc. See [Netflex documentation for more information](https://netflex-sdk.github.io/#/docs/faq?id=default_value).

Assuming you have configured a default database connection to use the `netflex` driver with the `entry` adapter:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id(); // Ignored by Netflex
            $table->string('intro');
            $table->timestamp('written_at')->useCurrent(); // Sets the default_value config to "{datetime}"
            $table->text('body')->widget('editor-large'); // You may specify which widget to use in the Netflex UI
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};

```

## Netflex specific functionality

If you have previously used the Model implmentation from the Netflex SDK, some functionality was provided out of the box, that isn't compatible with Eloquent models directly.

This packages provides some traits that you can apply to  your models to bring back this functionality.

All of these features are provided out of the box (except caching) if you use the `Netflex\Database\Eloquent\Model` as your base model.

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

If you attempt to insert an entry without the `name` attribute set, the API will throw an error.
In the Netflex SDK, this was automatically handled by the model, but this isn't consistent with Eloquent models.

You can add this functionality back by applying the `Netflex\Database\Concerns\GeneratesName` trait to your model.

This will automatically generate a UUID based name for the entry, unless a name is provided.

## Limitations

This package is tested on PHP >7.4.and PHP 8.0 through 8.2.

Currently the internal virtual PDO implementation is implemented as a separate package ([netflex/dbal](https://github.com/netflex-sdk/dbal)).
This is because the internal type signatures of PDO changed between PHP 7.4 and 8.0, and we need to support both versions.

At a later stage, when we drop support for PHP 7.4, we will move the PDO stubs into this package's codebase.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

Copyright © 2023 [Apility AS](https://www.apility.no/)