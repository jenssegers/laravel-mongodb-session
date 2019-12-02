Laravel MongoDB Session
=======================

A MongoDB session driver for Laravel 4, 5, or 6 which is inspired by LMongo. For more information about Sessions, check http://laravel.com/docs/eloquent.

Installation
------------

Make sure you have [jenssegers\mongodb](https://github.com/jenssegers/Laravel-MongoDB) installed before you continue.

### Laravel Version Compatibility

Laravel   | Package
:---------|:----------
 4.x.x    | 1.0.x
 5.x.x    | 1.1.x
 6.x.x    | 1.2.x



Install using composer:

    composer require jenssegers/mongodb-session

Add the session service provider in `app/config/app.php`:

    'Jenssegers\Mongodb\Session\SessionServiceProvider',

Change the session driver in `app/config/session.php` to mongodb:

    'driver' => 'mongodb',

*Optional*: change the connection to a connection using the mongodb driver from `app/config/database.php`:

	'connection' => 'mongodb',
