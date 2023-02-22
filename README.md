# Netflex Database Driver for Laravel

This package provides a database driver for Laravel that allows you to use the Netflex API as a database backend for your Laravel application.

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
    'default' => env('DB_CONNECTION', 'structures'), // Change this to 'structures' if you want to use the Netflex API as your default database connection

    'connections' => [

        'structures' => [
            'driver' => 'netflex',
            'prefix' => 'entry_',
            'connection' => 'default', // Which API connection to use, leave blank for default
        ]
    ]
];
```

## Eloquent

### Models

To use the Netflex API as a database backend for your Eloquent models, simply ensure your models a 'netflex' driver backed connection, and enure that the `$table` property matches the index name of the structure you want to use.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $connection = 'structures';
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

## Limitations

This package is still in development, and is not yet feature complete. Currently only selects are supported.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.