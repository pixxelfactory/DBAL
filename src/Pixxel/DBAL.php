<?php

namespace Pixxel;

/**
 * Simple DBAL library / wrapper for pdo with easier syntax
 * @version 1.0
 * @license MIT
 * @author Pixxelfactory
 */
class Dbal
{
    protected $username;
    protected $password;
    protected $database;
    protected $host;
    protected $pdo = false;
    protected $lastQuery;
    protected $lastError;

    public function __construct($username = false, $password = false, $database = false, $host = 'localhost')
    {
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->host = $host;
        $this->lastQuery = false;
        $this->lastError = false;

        // I really hate exceptions
        try {
            $this->pdo = new \PDO('mysql:host=localhost;dbname=' . $database, $username, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        } catch (\PDOException $e) {
            $this->lastError = $e;
            $this->pdo = false;

            return false;
        }
    }

    /**
     * Manually set a raw pdo object, if you have already a pdo connection, you can set it here
     * @param object $pdo
     */
    public function setPdo(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get the raw pdo object
     * @return object
     */
    public function getPdo(): bool|object
    {
        return $this->pdo;
    }

    /**
     * Check if there is a connection to a db
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->pdo instanceof \PDO ? true : false;
    }

    /**
     * Get a list of all tables in the current db, returns them as array, false if the command failed
     * @return array|bool
     */
    public function getTables(): array|bool
    {
        $tables = $this->read('show tables', [], true);
        $return = [];
        
        // If something went wrong, retur nfalse
        if(!is_array($tables)) {
            return false;
        }

        // Convert all rows to a plain array and get the first value
        foreach($tables as $table) {
            $return[] = reset($table);
        }

        return $return;
    }

    /**
     * Get the column names from a table and return them as array
     * Make sure to whitelist the table name first!
     * @param string $table
     * @param bool $checkTableName=true     If set to true, it will check if the table really exists to prevent sql-injections, if you checked it beforehand, disable it
     * @param string $onlyNames=true        If set to true, only a simple array with the table names will be returned, otherwise a 2dimensional array containing type ecc
     * @return array|bool
     */
    public function getColumns(string $table, $checkTableName = true, $onlyNames = true): array|bool
    {
        // If checkTableName is set to true, get a list of all tables in the current db and whitelist the table-name provided
        if($checkTableName == true) {
            $tables = $this->getTables();

            if(!$tables || !in_array($table, $tables)) {
                return false;
            }
        }

        $columns = $this->read('show columns from `'.$table.'`', []);
        $return = [];

        if(!is_array($columns)) {
            return false;
        }

        // If we want further information like length, type and so on, $onlyNames must be set to false
        if(!$onlyNames) {
            return $columns;
        }

        foreach($columns as $column) {
            $return[] = $column->Field;
        }

        return $return;
    }

    /**
     * Checks if a table really exists in the current database
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        $existingTables = $this->getTables();

        if(!in_array($table, $existingTables)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a column exists / is valid in a certain table
     * @param string $column
     * @param string $table
     * @return bool
     */
    public function columnExists(string $column, string $table): bool
    {
        $existingColumns = $this->getColumns($table);

        if(!in_array($column, $existingColumns)) {
            return false;
        }

        return true;
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

        if (!$stmt->execute($parameters)) {
            $this->error = $stmt->errorInfo();
            return false;
        }

        return true;
    }

    /**
     * Perform a select query and transform result into a php-array for easier access, false if nothing comes back
     * @param string $query
     * @param array $parameters
     * @param bool $fetchAsArray=false      If set to true, it will fetch arrays instead of objects
     * @return array|bool
     */
    public function read($query, $parameters = [], $fetchAsArray = false)
    {
        $stmt = $this->pdo->prepare($query);
        $this->lastQuery = $query;

        if (!$stmt->execute($parameters)) {
            $this->error = $stmt->errorInfo();
            return false;
        }

        $return = [];

        if($fetchAsArray == true) {
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $return[] = $row;
            }
        }
        else {
            while ($row = $stmt->fetchObject()) {
                $return[] = $row;
            }
        }
        
        return $return;
    }

    /**
     * Perform a select query and transform result into a php-object (return the first row only)
     * @param string $query
     * @param array $parameters
     * @return object|bool
     */
    public function readSingle($query, $parameters = []): object|bool
    {
        $result = $this->read($query, $parameters);
        
        return !empty($result) ? $result[0] : false;
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
