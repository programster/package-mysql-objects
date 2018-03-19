<?php

/* 
 * Test that we can update a uuid record.
 */

class UpdateUuidObjectTest1
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
        
        $loadedUserRecord->update(array(
            'name' => 'another name'
        ));
        
        if ($loadedUserRecord->getName() !== 'another name')
        {
            throw new Exception("User did not have expected name.");
        }
        
        $loadedUserRecords2 = UuidUserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords2) !== 1)
        {
            throw new Exception("Did not have the expected number of user records");
        }
        
        /* @var $loadedUserRecord UuidUserRecord */
        $loadedUserRecord2 = $loadedUserRecords[0];
        
        if ($loadedUserRecord2->getName() !== 'another name')
        {
            throw new Exception("User did not have expected name.");
        }
    }
}

