<?php

namespace Coded\Database;

use Coded\Exception\DatabaseException;

class Controller extends \PDO
{
    protected $username;
    protected $password;
    protected $database;
    protected $host;
    protected $port;

    function __construct($database, $username, $password, $host, $port = '3306', $options = [])
    {
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->host = $host;
        $this->port = $port;

        parent::__construct('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $database, $username, $password, $options);
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
    }

    function getDatabaseName()
    {
        return $this->database;
    }

    static function addColonsToKeys($input)
    {
        $args = [];
        if ($input) {
            foreach ((array)$input as $key => $arg) {
                $args[is_numeric($key) ? $key : ':' . $key] = $arg;
            }
        }
        return $args;
    }

    function q($query, $input = [], &$stmt = null, $fetchObject = null)
    {
        $stmt = $this->prepare($query);
        $stmt->execute(static::addColonsToKeys($input));

        $queryType = strtolower(substr(trim($query), 0, 6));
        if ($queryType == 'insert') {
            $result = $this->lastInsertId();
        } elseif (in_array($queryType, ['update', 'delete', 'create'])) {
            $result = $stmt->rowCount();
        } else {
            if ($fetchObject) {
                $result = $stmt->fetchAll(\PDO::FETCH_CLASS, $fetchObject);
            } else {
                $result = $stmt->fetchAll();
            }
        }
        $stmt->closeCursor();
        return $result;
    }

    function transactionIfNotExists(callable $function)
    {
        $exists = $this->inTransaction();
        if (!$exists) $this->beginTransaction();
        try {
            $result = $function();
            if (!$exists) $this->commit();
            return $result;
        } catch (\Exception $e) {
            if (!$exists) $this->rollBack();
            throw $e;
        }
    }

    function import($sqlFile, $exception = DatabaseException::class)
    {
        if (!file_exists($sqlFile)) {
            if ($exception) throw new $exception("File '$sqlFile' doesn't exists.");
            return false;
        }

        $update = file_get_contents($sqlFile);
        if (!strlen($update)) {
            if ($exception) throw new $exception("File '$sqlFile' is empty.");
            return false;
        }

        try {
            $this->beginTransaction();
            //todo when multiple queries, unable to catch sql syntax errors
            $this->query($update);

            //todo this causing PDOException "There is already an active transaction"
            //$result = $this->query($update);
            //if (!$result) throw \Exception('Query failed.');

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollBack();
            if ($exception) throw new $exception($e->getMessage());
        }
    }

    function dump($filename = null, array $excludeTables = [])
    {
        if (!defined('DATABASE_DUMP_PATH')) throw new DatabaseException("Can't dump because the DATABASE_DUMP_PATH is not defined.");

        $filename = $filename ?: date('Y.m.d.H.i.s');
        $default = rtrim(DATABASE_DUMP_PATH, DIRECTORY_SEPARATOR);

        $oldUmask = umask(0);
        if (!file_exists($path = $default)) {
            mkdir($path, 0775, true);
        }
        if (!file_exists($path = $default . '/' . $this->database)) {
            mkdir($path, 0775, true);
        }

        $i = 1;
        while (file_exists($file = $path . '/' . str_replace(' ', '_', $filename) . '.' . $i . '.sql')) $i++;

        $ignore = implode(' ', array_map(function ($value) {
            return '--ignore-table=' . $this->database . '.' . $value;
        }, $excludeTables));

        umask(0006);
        exec('mysqldump --routines --events --single-transaction ' . $ignore . ' --user=' . $this->username . ' --password=' . $this->password . ' --host=' . $this->host . ' ' . $this->database . ' > ' . $file);
        umask($oldUmask);

        return $file;
    }

}
