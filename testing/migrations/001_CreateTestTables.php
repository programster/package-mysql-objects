<?php

/* 
 * 
 */

class CreateTestTables implements \iRAP\Migrations\MigrationInterface
{
    public function up(\mysqli $mysqliConn) 
    {
        $createUserIdTable = 
            "CREATE TABLE `user` (
                `id` int NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `email` text NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        $createUserIdTableResult = $mysqliConn->query($createUserIdTable);
        
        if ($createUserIdTableResult === FALSE)
        {
            throw new Exception("Failed to create the test table.");
        }
    }
    
    
    public function down(\mysqli $mysqliConn) 
    {
        
    }
}

