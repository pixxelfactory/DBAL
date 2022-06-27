<?php
namespace Pixxel;

/**
 * Simple DBAL library / wrapper for pdo with easier syntax
 * @version 1.0
 * @license MIT
 * @author Pixxelfactory
 */
class DBAL
{
    protected $username;
    protected $password;
    protected $database;
    protected $host;
    protected $pdo;
    protected $lastQuery;
    protected $lastError;

    public function __construct($username, $password, $database, $host = 'localhost')
    {
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->host = $host;
        $this->lastQuery = false;
        $this->lastError = false;

        // I really hate exceptions
        try
        {
            $this->pdo = new \PDO('mysql:host=localhost;dbname='.$database, $username, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        }
        catch(\PDOException $e)
        {
            $this->lastError = $e;

            return false;
        }
    }

    /**
     * Perform a query and return true or false, depending on successful or not
     * @param string $query
     * @param  array $parameters
     * @return bool
     */
    public function save($query, $parameters = [])
    {
        $stmt = $this->pdo->prepare($query);
        $this->lastQuery = $query;
        
        if(!$stmt->execute($parameters))
        {
            $this->error = $stmt->errorInfo();
    
            return false;
        }
        
        return true;
    }

    /**
     * Perform a select query and transform result into a php-array for easier access, false if nothing comes back
     * @param string $query
     * @param array $parameters
     * @return array|bool
     */
    public function read($query, $parameters = [])
    {
        $stmt = $this->pdo->prepare($query);
        $this->lastQuery = $query;
        
        if(!$stmt->execute($parameters))
        {
            $this->error = $stmt->errorInfo();

            return false;
        }

        $return = [];

        while($row = $stmt->fetchObject())
        {
            $return[] = $row;
        }

        return $return;
    }

    /**
     * Get the last inserted id or false if nothing is returned
     * @return int|bool
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Get the last query, false if there is none
     * @return string|bool
     */
    public function lastQuery()
    {
        return $this->lastQuery;
    }

    /**
     * Get the last error
     * @return array|bool
     */
    public function lastError()
    {
        return $this->lastError;
    }
}