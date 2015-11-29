<?php

/**
 * Base for all models
 */
abstract class BaseModel
{
    /**
     * Connection instance
     * @var PDO
     */
    private static $dbConnection = null;

    public function __construct()
    {
        // if no connection has been estabilished
        if (self::$dbConnection === null)
        {
            // estabilish one

            $dsn = DB_DBMS.':dbname='.DB_DATABASE.';host='.DB_HOST;
            self::$dbConnection = new PDO($dsn, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            self::$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    /**
     * Retrieves connection instance
     * @return PDO
     */
    protected function getConnection()
    {
        return self::$dbConnection;
    }

    /**
     * Retrieves last inserted row ID
     * @return int
     */
    protected function getInsertId()
    {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * Performs query on current connection
     * @param string $sql
     * @return PDOStatement
     */
    protected function query($sql)
    {
        return $this->getConnection()->query($sql);
    }

    /**
     * Executes query on current connection
     * @param string $sql
     * @return int
     */
    protected function execute($sql)
    {
        return $this->getConnection()->exec($sql);
    }
}
