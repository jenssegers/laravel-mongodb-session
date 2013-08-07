Laravel MongoDB Session
=======================

A MongoDB session driver for Laravel 4, inspired by LMongo. For more information about Sessions, check http://laravel.com/docs/eloquent.

Installation
------------

Add the package to your `composer.json` or install manually.

    {
        "require": {
            "jenssegers/mongodb-session": "*"
        }
    }

Run `composer update` to download and install the package.

Add the session service provider in `app/config/app.php`:

    'Jenssegers\Mongodb\Session\SessionServiceProvider',

Change the session driver in `app/config/session.php` to mongodb:

    'driver' => 'mongodb',