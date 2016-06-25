<?php

/*
 * This is an fix to Symfony's MongoDbSessionHandler to make it 
 * compatible with PHP's new MongoDB extension.  The original version of this 
 * file depended on many aspects of the legacy Mongo extension (such as the 
 * MongoId and MongoDate classes) that don't exist in the new MongoDB driver.
 *
 * Other legacy methods (such as update and remove) were used as well making it
 * necessary to extend this class and utilize the newer updateOne and deleteOne
 * methods.
 *
 * This file is meant to be temporary until Symfony supports the 
 * PHP MongoDB extension at which point this file may continue to be used
 * but can also be safely removed.
 */


/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jenssegers\Mongodb\Session;

/**
 * MongoDB session handler.
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class MongoDbSessionHandler implements \SessionHandlerInterface
{
    /**
     * @var \Mongo
     */
    private $mongo;

    /**
     * @var \MongoCollection
     */
    private $collection;

    /**
     * @var array
     */
    private $options;

    /**
     * Constructor.
     *
     * List of available options:
     *  * database: The name of the database [required]
     *  * collection: The name of the collection [required]
     *  * id_field: The field name for storing the session id [default: _id]
     *  * data_field: The field name for storing the session data [default: data]
     *  * time_field: The field name for storing the timestamp [default: time]
     *  * expiry_field: The field name for storing the expiry-timestamp [default: expires_at]
     *
     * It is strongly recommended to put an index on the `expiry_field` for
     * garbage-collection. Alternatively it's possible to automatically expire
     * the sessions in the database as described below:
     *
     * A TTL collections can be used on MongoDB 2.2+ to cleanup expired sessions
     * automatically. Such an index can for example look like this:
     *
     *     db.<session-collection>.ensureIndex(
     *         { "<expiry-field>": 1 },
     *         { "expireAfterSeconds": 0 }
     *     )
     *
     * More details on: http://docs.mongodb.org/manual/tutorial/expire-data/
     *
     * If you use such an index, you can drop `gc_probability` to 0 since
     * no garbage-collection is required.
     *
     * @param \Mongo|\MongoClient|\MongoDB\Client   $mongo A MongoDB\Client, MongoClient or Mongo instance
     * @param array                                 $options An associative array of field options
     *
     * @throws \InvalidArgumentException When MongoClient or Mongo instance not provided
     * @throws \InvalidArgumentException When "database" or "collection" not provided
     */
    public function __construct($mongo, array $options)
    {
        if (!($mongo instanceof \MongoDB\Client || $mongo instanceof \MongoClient || $mongo instanceof \Mongo)) {
            throw new \InvalidArgumentException('MongoClient or Mongo instance required');
        }

        if (!isset($options['database']) || !isset($options['collection'])) {
            throw new \InvalidArgumentException('You must provide the "database" and "collection" option for MongoDBSessionHandler');
        }

        $this->mongo = $mongo;

        $this->options = array_merge(array(
            'id_field' => '_id',
            'data_field' => 'data',
            'time_field' => 'time',
            'expiry_field' => 'expires_at',
        ), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $methodName = ($this->mongo instanceof \MongoDB\Client) ? 'deleteOne' : 'remove';

        $this->getCollection()->$methodName(array(
            $this->options['id_field'] => $sessionId,
        ));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        $methodName = ($this->mongo instanceof \MongoDB\Client) ? 'deleteOne' : 'remove';

        $this->getCollection()->$methodName(array(
            $this->options['expiry_field'] => array('$lt' => $this->createDateTime()),
        ));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $expiry = $this->createDateTime(time() + (int) ini_get('session.gc_maxlifetime'));

        if ($this->mongo instanceof \MongoDB\Client) {
            $fields = array(
                $this->options['data_field'] => new \MongoDB\BSON\Binary($data, \MongoDB\BSON\Binary::TYPE_OLD_BINARY),
                $this->options['time_field'] => $this->createDateTime(),
                $this->options['expiry_field'] => $expiry,
            );

            $this->getCollection()->updateOne(
                array($this->options['id_field'] => $sessionId),
                array('$set' => $fields),
                array('upsert' => true)
            );
        } else {
            $fields = array(
                $this->options['data_field'] => new \MongoBinData($data, \MongoBinData::BYTE_ARRAY),
                $this->options['time_field'] => $this->createDateTime(),
                $this->options['expiry_field'] => $expiry,
            );

            $this->getCollection()->update(
                array($this->options['id_field'] => $sessionId),
                array('$set' => $fields),
                array('upsert' => true, 'multiple' => false)
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        $dbData = $this->getCollection()->findOne(array(
            $this->options['id_field'] => $sessionId,
            $this->options['expiry_field'] => array('$gte' => $this->createDateTime()),
        ));

        if (null === $dbData) {
            return '';
        }

        if ($dbData[$this->options['data_field']] instanceof \MongoDB\BSON\Binary) {
            return $dbData[$this->options['data_field']]->getData();
        }

        return $dbData[$this->options['data_field']]->bin;
    }

    /**
     * Return a "MongoCollection" instance.
     *
     * @return \MongoCollection
     */
    private function getCollection()
    {
        if (null === $this->collection) {
            $this->collection = $this->mongo->selectCollection($this->options['database'], $this->options['collection']);
        }

        return $this->collection;
    }

    /**
     * Return a Mongo instance.
     *
     * @return \Mongo
     */
    protected function getMongo()
    {
        return $this->mongo;
    }

    /**
     * Create a date object using the class appropriate for the current mongo connection
     *
     * Return an instance of a MongoDate or \MongoDB\BSON\UTCDateTime
     * @param integer $seconds An integer representing UTC seconds since Jan 1 1970.  Defaults to now.
     */
    private function createDateTime($seconds = null)
    {
        if (is_null($seconds)) {
            $seconds = time();
        }

        if ($this->mongo instanceof \MongoDB\Client) {
            return new \MongoDB\BSON\UTCDateTime($seconds * 1000);
        }

        return new \MongoDate($seconds);
    }
}
