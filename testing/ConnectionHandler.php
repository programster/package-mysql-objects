<?php

/* 
 * 
 */

class ConnectionHandler
{
    /**
     * Get the connection to the mysql database and create it if it doesn't already exit.
     * @staticvar type $db
     * @return \mysqli
     */
    public static function getDb()
    {
        static $db = null;
        
        if ($db == null)
        {
            $db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        }
        
        return $db;
    }
}

