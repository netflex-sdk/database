<?php

namespace Netflex\Database\Driver\Doctrine;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Driver as DoctrineDriver;

use Netflex\Database\Driver\Connection as DriverConnection;

class Driver implements DoctrineDriver
{
    protected DriverConnection $connection;

    public function __construct(DriverConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Connection The database connection.
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
    }

    /**
     * @return AbstractPlatform The database platform.
     */
    public function getDatabasePlatform()
    {
        return new Platform();
    }

    /**
     * @return AbstractSchemaManager
     */
    public function getSchemaManager(Connection $conn)
    {
        return new SchemaManager($this->connection, $conn);
    }

    /**
     * Gets the name of the driver.
     *
     * @deprecated
     *
     * @return string The name of the driver.
     */
    public function getName()
    {
        return 'Netflex';
    }

    /**
     * Gets the name of the database connected to for this driver.
     *
     * @deprecated Use Connection::getDatabase() instead.
     *
     * @return string The name of the database.
     */
    public function getDatabase(Connection $conn)
    {
    }
}
