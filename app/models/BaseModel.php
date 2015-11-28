<?php

abstract class BaseModel
{
    /**
     *
     * @var PDO
     */
    private static $dbConnection = null;

    public function __construct()
    {
        if (self::$dbConnection === null)
        {
            $dsn = DB_DBMS.':dbname='.DB_DATABASE.';host='.DB_HOST;
            self::$dbConnection = new PDO($dsn, DB_USER, DB_PASS, array());
        }
    }

    /**
     *
     * @return PDO
     */
    protected function getConnection()
    {
        return self::$dbConnection;
    }

    protected function getInsertId()
    {
        return $this->getConnection()->lastInsertId();
    }

    protected function query($sql)
    {
        return $this->getConnection()->query($sql);
    }

    protected function execute($sql)
    {
        return $this->getConnection()->exec($sql);
    }
}
