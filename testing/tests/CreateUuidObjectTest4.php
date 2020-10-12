<?php

/* 
 * Test that we can create a uuid record from the table, using our own generated uuid
 */

class CreateUuidObjectTest4
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
        $uuid = Programster\MysqlObjects\UuidLib::generateUuid();
        
        $userDetails = array(
            'uuid' => $uuid,
            'email' => 'user1@gmail.com',
            'name' => 'user1',
        );
        
        $userRecord = UuidUserTable::getInstance()->create($userDetails);
        $userRecord->save();
        
        
        $loadedUserRecords = UuidUserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords) !== 1)
        {
            throw new Exception("Did not have the expected number of user records");
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
        
        
        if ($loadedUserRecord->getUuid() !== $uuid)
        {
            throw new Exception("User uuid was not what was expected");
        }
        
        # test table delete all.
        UuidUserTable::getInstance()->deleteAll();
        
        $loadedUserRecords = UuidUserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords) !== 0)
        {
            throw new Exception("Did not have the expected number of user records");
        }
    }
}

