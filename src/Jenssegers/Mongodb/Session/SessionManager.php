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

		return $this->app['mongodb']->connection($connection);
	}

	/**
	 * Get the database session options.
	 *
	 * @return array
	 */
	protected function getMongoDBOptions($database, $collection)
	{
		return array('database' => $database,'collection' => $collection, 'id_field' => '_id', 'data_field' => 'payload', 'time_field' => 'last_activity');
	}

}
