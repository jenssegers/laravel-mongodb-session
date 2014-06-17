<?php namespace Jenssegers\Mongodb\Session;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\MongoDbSessionHandler;

class SessionManager extends \Illuminate\Support\Manager {

    /**
     * Create an instance of the database session driver.
     *
     * @return \Illuminate\Session\Store
     */
    protected function createMongoDBDriver()
    {
        $connection = $this->getMongoDBConnection();

        $collection = $this->app['config']['session.table'];

        $database = (string) $connection->getMongoDB();

        return new MongoDbSessionHandler($connection->getMongoClient(), $this->getMongoDBOptions($database, $collection));
    }

    /**
     * Get the database connection for the MongoDB driver.
     *
     * @return Connection
     */
    protected function getMongoDBConnection()
    {
        $connection = $this->app['config']['session.connection'];

        // The default connection may still be mysql, we need to verify if this connection
        // is using the mongodb driver.
        if (is_null($connection))
        {
            $default = $this->app['db']->getDefaultConnection();

            $connections = $this->app['config']['database.connections'];

            // If the default database driver is not mongodb, we will loop the available
            // connections and select the first one using the mongodb driver.
            if ($connections[$default]['driver'] != 'mongodb')
            {
                foreach ($connections as $name => $candidate)
                {
                    // Check the driver
                    if ($candidate['driver'] == 'mongodb')
                    {
                        $connection = $name;
                        break;
                    }
                }
            }
        }

        return $this->app['db']->connection($connection);
    }

    /**
     * Get the database session options.
     *
     * @return array
     */
    protected function getMongoDBOptions($database, $collection)
    {
        return array('database' => $database, 'collection' => $collection, 'id_field' => '_id', 'data_field' => 'payload', 'time_field' => 'last_activity');
    }

    /**
     * Get the default session driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return 'mongodb';
    }

}
