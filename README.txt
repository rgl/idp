= About

This is a OpenID 1.1 Provider prototype.



= Install

Make sure you have installed all the required dependencies:

 * PHP 5.1.0+
 * GMP (preferable) or BCMath PHP extension
 * mhash PHP extension
 * cURL PHP extension
 * PDO extension (and respective PDO database driver)
 * JSON extension (available by default on PHP 5.2.0+, or as a PECL extension)
 * Apache web server
 * mod_unique_id Apache module


Create the database, eg:

  mysql -u root -p < config/create.mysql.sql

Create the database schema, eg:

  mysql -u root -p idp_development < config/schema.mysql.sql
  mysql -u root -p idp_test < config/schema.mysql.sql
  mysql -u root -p idp_test < config/fixtures.mysql.sql

Assign user permissions, eg:

  mysql -u root -p < config/permissions.mysql.sql


Map the "web" subdirectory into a URL segment of your choice, eg:

  Alias /prototype /path/to/this/application/web

Assuming your domain normally has the URL http://example/, this
will map this application at http://example/prototype/.

You can now access any valid Identity page using URL like:

  http://example/prototype/id.php/test-alice
  http://example/prototype/id.php/test-janedoe

NB: The schema file contains some example identities.  Though, you'll
    need to manually create them.

You can use them as an Identity in any Consumer.  They point to the
Identity Provider located at http://example/prototype/idp.php.



= Setup

Copy the example configuration file from config/config.example.php to
config/config.php and edit that file.  You can see all the available
configuration settings inside lib/OpenID/Config.php.

You can also have different configuration files for each profile, eg:
config/config-test.php for the test profile.

You need to run bin/idp-maintenance every day.  This script will
run the required maintenance jobs.



= Directory structure

.                application home
|- bin/
|  `- idp-maintenance        maintenance script
|- config/       configuration
|  |- config.php             application configuration
|  |- create.mysql.sql       MySQL database creation script
|  |- schema.mysql.sql       MySQL database schema
|  |- fixtures.mysql.sql     MySQL database test fixtures
|  `- permissions.mysql.sql  MySQL database permissions
|- lib/          library code
|- doc/          documentation
|  |- api/       api documentation
|  `- coverage/  code coverage report
|- log/          application logs
|- test/         unit tests
|- web/          subdirectory exported by Apache
|- .project      PHPEclipse project file
|- buid.xml      phing build file
`- README.txt    this file



= Tests

Make sure you have the required dependencies to run the tests (and the
ones mentioned before):

 * PHPUnit 3.x       (http://www.phpunit.de/)
 * Graphviz          (http://www.graphviz.org/)
 * Xdebug PHP module (http://www.xdebug.org/)
 * Phing             (http://www.phing.info/)

The Identity Provider tests expect you to have the "web" directory
mapped at http://localhost/prototype/ (you can change this by edting
the TEST_BASE_URL constant defined inside the test/global.php file).

To execute the tests use one of the following ways:

 * run "phing" inside this application root directory to execute the
   entire test suite.
 * run "phpunit --verbose AllTests.php" inside the "test" subdirectory
   to run the entire test suite.
 * run "phpunit --verbose <individual test file>" inside the "test"
   subdirectory to run an individual test case, eg:
      phpunit --verbose Base64Test.php

To generate the test code coverage report run:

 * "phing doc-coverage" inside the application root directory, or
   XXX These test reports do not seem accurate.  Use the next method
       instead.
 * "phing doc-coverage" inside the "test" subdirectory, then open for
   the "../doc/coverage/index.html" file.



= Docs

To automatically generate API docs run:

  phing doc-api

This will place the documentation at "doc/api/index.html".



= Source code

The code is maintained in a mercurial repository located at:

  http://bitbucket.org/rgl/idp/


The source code is marked with "TODO" and "XXX" comments.  These mark
parts that need to be done, and parts that can/must be improved.



= Update

== Update Country/Language/TimeZone data

We use an internal database of Country/Language/TimeZone codes that
needs to be updated from time to time, to update it, read the header of
the lib/OpenID/SR/data/generate.php file.
