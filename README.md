# Logger

## Getting started
Simply copy the logger.class.php into your project folder and include the class in your project at the beginning of the files in which you want to use it.
Create a new Logger-object and that's it (Well... don't forget to create the database table)!

```php
require 'path/to/class/logger.class.php';

$pdo = new PDO(...);
$logger = new Logger($pdo);
```

Execute the function for the table creation once.

```php
$logger->createDatabaseTables();
```

To use the logger you need a [PDO](http://php.net/manual/en/pdo.connections.php) connection to communicate with your database.

## Configuration
You can change the table prefix and suffix to use your own naming style.
The following example would result in table names like "example_log_table".

```php
private $prefix = 'example_';
private $suffix = '_table';
```

You can use your desired datetime format by changing the Format-Variable.

```php
private $dateFormat = 'd.m.Y - H:i:s';
```

##### To be continued...
