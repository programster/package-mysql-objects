<?php

/* 
 * Test that we can create a uuid object when we havent provided the uuid 
 * (it automatically gets created for us)
 */

class CreateUuidObjectTest1
{
    
    public function __construct()
    {
        $db = ConnectionHandler::getDb();
        $query = "TRUNCATE `user_uuid_table`";
        $result = $db->query($query);
        
        if ($result === FALSE)
        {
            throw new Exception("Failed to empty the test table.");
        }
    }
    
    
    public function run()
    {
        $userDetails = array(
            'email' => 'user1@gmail.com',
            'name' => 'user1',
        );
        
        $userRecord = new UuidUserRecord($userDetails);
        
        if ($userRecord->getUuid() === "" || $userRecord->getUuid() === null)
        {
            throw new Exception("The created user object was not automatically given a UUID.");
        }
        
        // vitally important this save call is made AFTER we check for a UUID as the object
        // should have a uuid BEFORE we do anything with the database.
        $userRecord->save();
        
        $loadedUserRecords = UuidUserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords) !== 1)
        {
            throw new Exception("Did not have the expected number of user records after insertion");
        }
        
        /* @var $loadedUserRecord UuidUserRecord */
        $loadedUserRecord = $loadedUserRecords[0];
        
        if ($loadedUserRecord->getName() !== 'user1')
        {
            throw new Exception("User did not have expected name.");
        }
        
        if ($loadedUserRecord->getEmail() !== 'user1@gmail.com')
        {
            throw new Exception("User did not have expected email.");
        }
        
        if ($loadedUserRecord->getUuid() === null || $loadedUserRecord->getUuid() === "")
        {
            throw new Exception("User uuid was null");
        }
        
        if (Programster\MysqlObjects\UuidLib::isBinary($loadedUserRecord->getUuid()))
        {
            throw new Exception("User uuid is binary not hex.");
        }
    }
}

