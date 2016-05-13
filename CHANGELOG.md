##Change Log

###2.1.5 (2015-05-13)

####Added

- Test for successful execution of database query with named placeholders
- Updated README

####Fixed

- Remove un-needed escape sequences in regular expression patterns

###2.1.4 (2015-10-25)

####Added

- Nothing

####Fixed

- Remove duplication of code for input and bound parameters
- Identified and fixed documentation errors

###2.1.3 (2015-10-24)

####Added

- Full PHPUnit Test Suite
- Reorganize code to more suitable project structure

###2.1.2 (2015-07-19)

####Added

- Nothing

####Fixed

- Now takes into account bound arguments' datatypes when compiling interpolated string (previously, all values were quoted when it's likely inappropriate to quote INT datatypes). This allows for viewing/using bound values in e.g. LIMIT clauses where the quotes would interfere with processing the resultant query.
