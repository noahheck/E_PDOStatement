##Test Suite

The E_PDOStatement project ships with a 100% coverage PHPUnit test suite. In addition to the test case, the following components are included:

- `run_tests` shell script to execute the tests (see below)
- `phpunit.xml` configuration file
- `bootstrap.php` test suite configuration file
- configuration directory

If you're interested in running the tests, copy the `config.dist.php` file to `config.php` and fill in the appropriate configuration values (Note: a valid PDO connection is created during test suite execution, but no modifications to the database are made).

Invoke the `run_tests` script from the terminal in order to execute the test suite:

```
 # path/to/tests/run_tests
```

The `run_tests` script includes an easy way to generate the PHPUnit code coverage report by invoking the command with the `--report` flag:

```
 # path/to/tests/run_tests --report
```

Find out more about `run_tests` with the `--help` flag:

```
 # path/to/tests/run_tests --help
```
