<?php

namespace FoxTool\Debra\Core;

class Database
{

    /**
     * Load database configuration from file
     */
    public function loadConfiguration()
    {
        $config = $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
        try {
            if (file_exists($config)) {
                $this->config = require_once($config);
            } else {
                throw new \Exception("Database configuration file doesn't exist", 1);
            }
        } catch(\Exception $e) {
            echo '<strong>Error:</strong> ' . $e->getMessage();

        }
    }

    public function connect()
    {
        try {
            // Load database configuration from file
            $this->loadConfiguration();

            // Initialize connection with database
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']}";
            return new \PDO($dsn, $this->config['dbuser'], $this->config['dbpass']);

        } catch (\PDOException $e) {
            echo '<strong>PDO Error:</strong> ' . $e->getMessage();
        }
    }
}

