<?php

namespace App;

use mysqli;

class Database {
    private string $host;

    private string $user;

    private string $password;

    private string $dbName;

    // mysqli je by default NULL, vytvoří se automaticky až s prvním dotazem
    private ?mysqli $connection = NULL;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbName
     */
    public function __construct(string $host, string $user, string $dbName, string $password) {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->dbName = $dbName;
    }


    /**
     * Vrací mysqli, pokud není zatím vytvořená, tak ji vytvoří
     *
     * @return \mysqli
     */
    public function getConnection(): mysqli
    {
        if (NULL === $this->connection) {

            $this->connection = new mysqli($this->host, $this->user, $this->password, $this->dbName);
            $this->connection ->set_charset("utf8");
        }

        return $this->connection;
    }
}