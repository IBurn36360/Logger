# IBurn36360\Logger

A simple logging utility used to capture all PHP related issues in a formatted manner and keep them out of page output.

## License

```
Copyright 2015 Anthony D. Diaz

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
```

## Usage

Using this logger is fairly simple.  Creating an instance of the logger will create the logging direcotry (Recursively and with permissions 775 to account for different hosting setups) and make the initial logging file.  If a logging file exists already it will reopen it in append mode.

```php
$logger = new \IBurn36360\Logger\Logger(__DIR__ . '/logs/init_log.php');
// This step is required if you want this logger to take over the complete error handling PHP has natively.
// I left this as an option so that you can use another logger along side this one.
$logger->registerErrorHandlers();
```

Log files should end with .php so that they are execute instead of read.  Log files are created with ```<?php exit; ?>``` as the first line to prevent them from being readable in most environments.

## Writing to the log

Writing to the log can be done in a few ways.  The traditional ```trigger_error()``` function works properly with this library and will emit a log entry, as well as a complete stack trace for contect of the error.  For manually logging entries, such as cases that aren't necessarilly errors, you can also call into the logLine function.

```php
$logger->logLine('This is an error message', 'Custom Error');
```

Both will produce a log entry, completely formatted for readability.  The format looks something like the following:

```
11-15-15[04:05:53]            [NOTICE] Array to string conversion In [/srv/www/test.com/public_html/controller/siteController.php:69]
                                             From: siteController->testingAction()
                                             From: call_user_func_array((Array), (Array)) Called at [/srv/www/test.com/public_html/includes/Route/Strategy/Dispatcher.php:68]
                                             From: IBurn36360\Route\Strategy\Dispatcher->safe_call_user_func((Array), (Array)) Called at [/srv/www/test.com/public_html/includes/Route/Strategy/Dispatcher.php:57]
                                             From: IBurn36360\Route\Strategy\Dispatcher->dispatch() Called at [/srv/www/test.com/public_html/index.php:64]
```

The format is as follows: 
```
[Timestamp][spacer][Log Type][Message][File and line number]
[spacer][function call]
```

The spacers account for changes in the log type so that the start of entries is almost always in the same place.  Stack traces are indented after the error and no timestamp is logged for the trace lines.  This aids readability by spacing out individual entries from one another.

## Custom logging

Custom logging can be achieved by calling into the logCustomLine function 
```php
$logger->logCustomLine('Log entry');
```

Every custom log entry is spaced out as if it has no log type, omitting that part of the log.  Every custom log entry is written as-is and always includes a timestamp.

## Updating the log file

If you have any needs to update the log file, such as swapping from an initialition state log to an operation log (I do this), there is a function to also facilitate this.  It first closes the initial log file and opens up the new one, creating the path recursively if the path does not exist.

```php
$logger->updateLogFile('test.com_log.php');
```
