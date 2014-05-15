#E_PDOStatement

Drop in replacement for default PHP PDOStatement class allowing devs to view an interpolated version of a parameterized query

##Usage

PHP's PDO are a much improved way for handling database communications, but not being able to view a complete version of the query to be executed on the server after statement parameters have been interpolated can be frustrating. 

A common method for obtaining the interpolated query involves usage of outside functions or extending the native `PDOStatement` object and adding a new method to accomplish this.

E_PDOStatement (<strong>E</strong>nhanced PDOStatement) was designed as a solution to this that doesn't require workflow modifications to generate the resultant query string. The generated query string is accessible on the new `PDOStatement` object as a new `fullQuery` property :

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
INSERT INTO users SET username = 'admin', password = '45ab6941fed66456afe6239ab875e4fa';
```

When correctly configured, the interpolated values are properly escaped appropriately for the database driver, allowing the generated string to be suitable for e.g. log files, backups, etc. 

E_PDOStatement supports pre-executiong binding to both named and ? style parameter markers:
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

$fullQquery = $stmt->interpolateQuery($params);
```

##Installation
Download the file...put it into a suitable location in your application directory (seriously, it's only one file; 2 if you count the license (Apache) which we all have a copy of lying around somewhere; 3 if you count this).

You can also clone (or fork) (or fork then clone).

##Configuration

E_PDOStatement extends the native \PDOStatement object, so the PDO object must be configured to use the extended definition:

```php
<?php

require_once "E_PDOStatement.php";

$dsn        = "mysql:host=localhost;dbname=myDatabase";
$pdo        = new PDO($dsn, $dbUsername, $dbPassword);

$pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array("E_PDOStatement", array($pdo)));
```

That's all there is to it. 

Tests have been completed showing this process to be acceptable using a PSR-0 compatible auto-loading scheme as well as across namespaces.

Ideally, your project would have a PDO abstraction/wrapper class allowing you to implement this modification in only one place. If you don't have this luxury, some success was shown with extending the \PDO class to set the ATTR_STATEMENT_CLASS attribute in the constructor of the PDO, though some issues were seen when crossing namespaces.

##Get in Touch
There are a lot of forum posts related to or requesting this type of functionality, so hopefully someone somewhere will find it helpful. If it helps you, comments are of course appreciated.

Bugs, new feature requests and pull requests are of course welcome as well. This was created to help our pro team solve an issue, so it was designed around our specific work flow. If it doesn't work for you though, let me know and I'll be happy to explore if I can help you out.