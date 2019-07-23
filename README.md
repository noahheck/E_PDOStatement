# E_PDOStatement

Extension to the default PHP PDOStatement class providing the ability to generate a version of a parameterized query with the parameters injected into the query string.

The result is generally suitable for logging activities, debugging and performance analysis.

View the [changelog](CHANGELOG.md)

## Usage

PHP's PDO are a much improved way for handling database communications, but not being able to view a complete version of the query to be executed on the server after statement parameters have been interpolated can be frustrating.

A common method for obtaining the interpolated query involves usage of outside functions or extending the native `PDOStatement` object and adding a new method to accomplish this.

The E_PDOStatement (<strong>E</strong>nhanced PDOStatement) project was designed as a solution to this that doesn't require workflow modifications to generate the resultant query string. The generated query string is accessible on the new `EPDOStatement` object as a new `fullQuery` property :

```php
<?php
$content    = $_POST['content'];
$title      = $_POST['title'];
$date       = date("Y-m-d");

$query      = "INSERT INTO posts SET content = :content, title = :title, date = :date"
$stmt       = $pdo->prepare($query);

$stmt->bindParam(":content", $content, PDO::PARAM_STR);
$stmt->bindParam(":title"  , $title  , PDO::PARAM_STR);
$stmt->bindParam(":date"   , $date   , PDO::PARAM_STR);

$stmt->execute();

echo $stmt->fullQuery;

```

The result of this will be (on a MySQL database):

```
INSERT INTO posts SET content = 'There are several reasons you shouldn\'t do that, including [...]', title = 'Why You Shouldn\'t Do That', date = '2016-05-13'
```

When correctly configured, the interpolated values are escaped appropriately for the database driver, allowing the generated string to be suitable for e.g. log files, backups, etc.

E_PDOStatement supports pre-execution binding to both named and ? style parameter markers:
```php
$query      = "INSERT INTO posts SET content = ?, title = ?, date = ?";

...

$stmt->bindParam(1, $content, PDO::PARAM_STR);
$stmt->bindParam(2, $title  , PDO::PARAM_STR);
$stmt->bindParam(3, $date   , PDO::PARAM_STR);
```

as well as un-named parameters provided as input arguments to the `$stmt->execute()` method:

```php
$query      = "INSERT INTO posts SET content = ?, title = ?, date = ?";

...

$params     = array($content, $title, $date);

$stmt->execute($params);

```

Named $key => $value pairs can also be provided as input arguments to the `$stmt->execute()` method:
```php
$query      = "INSERT INTO posts SET content = :content, title = :title, date = :date";

...

$params     = array(
      ":content" => $content
    , ":title"   => $title
    , ":date"    => $date
);

$stmt->execute($params);
```

You can also generate the full query string without executing the query:
```php
$content    = $_POST['content'];
$title      = $_POST['title'];
$date       = date("Y-m-d");

$query      = "INSERT INTO posts SET content = :content, title = :title, date = :date"
$stmt       = $pdo->prepare($query);

$stmt->bindParam(":content", $content, PDO::PARAM_STR);
$stmt->bindParam(":title"  , $title  , PDO::PARAM_STR);
$stmt->bindParam(":date"   , $date   , PDO::PARAM_STR);

$fullQuery  = $stmt->interpolateQuery();
```
or
```php
$content    = $_POST['content'];
$title      = $_POST['title'];
$date       = date("Y-m-d");

$query      = "INSERT INTO posts SET content = ?, title = ?, date = ?"
$stmt       = $pdo->prepare($query);

$params     = array(
      $content
    , $title
    , $date
);

$fullQuery  = $stmt->interpolateQuery($params);
```

## Installation

Preferred method: install using composer:

```bash
composer require noahheck/e_pdostatement
```

Alternatively, you can download the project, put it into a suitable location in your application directory and include into your project as needed.

## Configuration

The EPDOStatement class extends the native \PDOStatement object, so the PDO object must be configured to use the extended definition:

```php
<?php

require_once "path/to/vendor/autoload.php";

/**
 * -- OR --
 *
 * require_once "EPDOStatement.php";
 */

$dsn        = "mysql:host=localhost;dbname=myDatabase";
$pdo        = new PDO($dsn, $dbUsername, $dbPassword);

$pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array("EPDOStatement\EPDOStatement", array($pdo)));
```

That's all there is to it.

## Debugging

`EPDOStatement`'s `fullQuery` property and `interpolateQuery` method are great tools for viewing a resultant query string, and is designed to be suitable for query logging. They don't provide any insight into the process used to track datapoints and generate the resultant query string.

To add debugging and development support, `EPDOStatement` also now implements PSR-3 `LoggerAwareInterface`. Provide an instance of a PSR-3 `LoggerInterface` (such as [Monolog](https://packagist.org/packages/monolog/monolog)), and you gain introspection into how your queries are being composed:

```php
<?php
$logger = new \Monolog\Logger();
$logger->pushHandler(new Monolog\Handler\StreamHandler('/path/to/log.file'));

$stmt = $pdo->prepare("UPDATE contacts SET first_name = :first_name WHERE id = :id");

$stmt->setLogger($logger);

$stmt->bindParam(":first_name", $_POST['first_name'], PDO::PARAM_STR);
$stmt->bindParam(":id", $_POST['id'], PDO::PARAM_INT);

$stmt->execute();
```

After executing, the log file content will include:

```
DEBUG: Binding parameter :first_name (as parameter) as datatype 2: current value Noah
DEBUG: Binding parameter :id (as parameter) as datatype 1: current value 1
DEBUG: Interpolating query...
DEBUG: Preparing value Noah as string
DEBUG: Replacing marker :first_name with value 'Noah'
DEBUG: Preparing value 1 as integer
DEBUG: Replacing marker :id with value 1
DEBUG: Query interpolation complete
DEBUG: Interpolated query: UPDATE contacts SET first_name = 'Noah' WHERE id = 1
INFO: UPDATE contacts SET first_name = 'Noah' WHERE id = 1
```

Logging levels utilized by `EPDOStatement` include debug, warn, error, and info.

For more information, see the [PHP-FIG PSR-3 Logger documentation](https://www.php-fig.org/psr/psr-3/). For information on working with Monolog, see the [Monolog documentation](https://github.com/Seldaek/monolog).

*Note*: `EPDOStatement` considers successful database query execution a PSR-3 *Interesting Event* and logs them at the `info` level. 

## Get in Touch
There are a lot of forum posts related to or requesting this type of functionality, so hopefully someone somewhere will find it helpful. If it helps you, comments are of course appreciated.

Bugs, new feature requests and pull requests are of course welcome as well. This was created to help our pro team solve an issue, so it was designed around our specific work flow. If it doesn't work for you though, let me know and I'll be happy to explore if I can help you out.

#### E_mysqli

E_PDOStatement now has a sister project aimed at providing the same functionality for php devs using the `mysqli` extension:

[E_mysqli](https://github.com/noahheck/E_mysqli)
