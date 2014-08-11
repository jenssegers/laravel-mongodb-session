<?php namespace Jenssegers\Mongodb\Session;

use Illuminate\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving('session', function($session)
        {
            $session->extend('mongodb', function($app)
            {
                $manager = new SessionManager($app);

                return $manager->driver('mongodb');
            });
        });
    }

}
