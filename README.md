#E_PDOStatement

Drop in replacement for default PHP PDOStatement class allowing devs to view an interpolated version of a parameterized query

###Update
Now allows for input parameters without leading : as per issue #3.

###Update

Please update and replace with new version (as of 2014-10-22) to address potential matching issues. This is a drop-in update, so no other changes should be necessary.

##Usage

PHP's PDO are a much improved way for handling database communications, but not being able to view a complete version of the query to be executed on the server after statement parameters have been interpolated can be frustrating. 

A common method for obtaining the interpolated query involves usage of outside functions or extending the native `PDOStatement` object and adding a new method to accomplish this.

The E_PDOStatement (<strong>E</strong>nhanced PDOStatement) project was designed as a solution to this that doesn't require workflow modifications to generate the resultant query string. The generated query string is accessible on the new `EPDOStatement` object as a new `fullQuery` property :

```php
<?php
$query      = "INSERT INTO users SET username = :user, password = :password";
$stmt       = $pdo->prepare($query);

$username   = $_POST['username'];
$password   = passwordPrep($_POST['password']);

$stmt->bindParam(":user"    , $username, PDO::PARAM_STR);
$stmt->bindParam(":password", $password, PDO::PARAM_STR);

$stmt->execute();

echo $stmt->fullQuery;

```

The result of this will be (on a MySQL database):

```
INSERT INTO users SET username = 'admin', password = '45ab6941fed66456afe6239ab875e4fa'
```

When correctly configured, the interpolated values are escaped appropriately for the database driver, allowing the generated string to be suitable for e.g. log files, backups, etc. 

E_PDOStatement supports pre-execution binding to both named and ? style parameter markers:
```php
$query      = "INSERT INTO users SET username = ?, password = ?";

...

$stmt->bindParam(1, $username, PDO::PARAM_STR);
$stmt->bindParam(2, $password, PDO::PARAM_STR);
```

as well as un-named parameters provided as input arguments to the `$stmt->execute()` method:

```php
$query      = "INSERT INTO users SET username = ?, password = ?";

...

$params     = array($username, $password);

$stmt->execute($params);

```

Named $key => $value pairs can also be provided as input arguments to the `$stmt->execute()` method:
```php
$query      = "INSERT INTO users SET username = :username, password = :password";

...

$params     = array(
      ":username" => $username
    , ":password" => $password
);

$stmt->execute($params);
```

You can also generate the full query string without executing the query:
```php
$query      = "INSERT INTO users SET username = :user, password = :password";
$stmt       = $pdo->prepare($query);

$username   = $_POST['username'];
$password   = passwordPrep($_POST['password']);

$stmt->bindParam(":user"    , $username, PDO::PARAM_STR);
$stmt->bindParam(":password", $password, PDO::PARAM_STR);

$fullQuery  = $stmt->interpolateQuery();
```
or
```php
$query      = "INSERT INTO users SET username = :user, password = :password";
$stmt       = $pdo->prepare($query);

$username   = $_POST['username'];
$password   = passwordPrep($_POST['password']);

$params     = array($username, $password);

$fullQuery  = $stmt->interpolateQuery($params);
```

##Installation
Download the file...put it into a suitable location in your application directory (seriously, it's only one file; 2 if you count the license (Apache) which we all have a copy of lying around somewhere; 3 if you count this).
### Update
BONUS FILE NOW INCLUDED - composer.json (because, obviously, composer is a good thing to use).

Add to your composer.json to have it loaded when you create your project:

```json
"require" : {
	"noahheck/E_PDOStatement" : "1.2.*"
}
```

You can also clone (or fork) (or fork then clone).

##Configuration

The EPDOStatement class extends the native \PDOStatement object, so the PDO object must be configured to use the extended definition:

```php
<?php

require_once "EPDOStatement.php";// or allow your auto-loader to load the definition e.g.: use \noahheck\EPDOStatement;

$dsn        = "mysql:host=localhost;dbname=myDatabase";
$pdo        = new PDO($dsn, $dbUsername, $dbPassword);

$pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array("noahheck\EPDOStatement", array($pdo)));
```

That's all there is to it. 

The classname has been updated to allow strict conformance to PSR-0 autoloading (e.g. removed the _ from the class/filename).

Ideally, your project would have a PDO abstraction/wrapper class allowing you to implement this modification in only one place. If you don't have this luxury, success was shown with extending the \PDO class to set the ATTR_STATEMENT_CLASS attribute in the constructor of the PDO.

##Get in Touch
There are a lot of forum posts related to or requesting this type of functionality, so hopefully someone somewhere will find it helpful. If it helps you, comments are of course appreciated.

Bugs, new feature requests and pull requests are of course welcome as well. This was created to help our pro team solve an issue, so it was designed around our specific work flow. If it doesn't work for you though, let me know and I'll be happy to explore if I can help you out.

###Update

E_PDOStatement now has a sister project aimed at providing the same functionality for php devs using the `mysqli` extension:

[E_mysqli](https://github.com/noahheck/E_mysqli)
