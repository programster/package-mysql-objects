<?php

/*
 *
 */

class CreateTestTables implements \iRAP\Migrations\MigrationInterface
{
    public function up(\mysqli $mysqliConn)
    {
        $createUuidTableQuery =
            "CREATE TABLE `user_uuid_table` (
                `uuid` binary(16) NOT NULL,
                `name` varchar(255) NOT NULL,
                `email` text NOT NULL,
                PRIMARY KEY (`uuid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $createUuidTableResult = $mysqliConn->query($createUuidTableQuery);

        if ($createUuidTableResult === FALSE)
        {
            throw new Exception("Failed to create the test table.");
        }


        $createUserIdTable =
            "CREATE TABLE `user` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `email` text NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $createUserIdTableResult = $mysqliConn->query($createUserIdTable);

        if ($createUserIdTableResult === FALSE)
        {
            throw new Exception("Failed to create the test table.");
        }

        $createNoIdTableQuery =
            "CREATE TABLE `user_no_id_table` (
                `name` varchar(255) NOT NULL,
                `email` text NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $createNoIdTableQueryResult = $mysqliConn->query($createNoIdTableQuery);

        if ($createNoIdTableQueryResult === FALSE)
        {
            throw new Exception("Failed to create the test table for testing no IDs.");
        }
    }


    public function down(\mysqli $mysqliConn)
    {

    }
}

