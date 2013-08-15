Laravel MongoDB Session
=======================

A MongoDB session driver for Laravel 4, inspired by LMongo. For more information about Sessions, check http://laravel.com/docs/eloquent.

Installation
------------

Add the package to your `composer.json` and run `composer update`.

    {
        "require": {
            "jenssegers/mongodb-session": "*"
        }
    }

Add the session service provider in `app/config/app.php`:

    'Jenssegers\Mongodb\Session\SessionServiceProvider',

Change the session driver in `app/config/session.php` to mongodb:

    'driver' => 'mongodb',