<?php

namespace FoxTool\Debra;

class Database
{

    /**
     * Load database configuration from file
     */
    public function loadConfiguration()
    {
        if (isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
            $config = $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
        } else {
            $config = __DIR__ . '/../../../../../config/database.php';
        }

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
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']}";

            return new \PDO($dsn, $this->config['user'], $this->config['password']);

        } catch (\PDOException $e) {
            echo '<strong>PDO Error:</strong> ' . $e->getMessage();
        }
    }
}

