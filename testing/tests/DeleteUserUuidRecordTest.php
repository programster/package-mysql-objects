<?php

/* 
 * Test that we can delete a record by running the delete method().
 */

class DeleteUserUuidRecordTest
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
            throw new Exception("Did not have the expected number of user records after insertion");
        }
        
        /* @var $loadedUserRecord UuidUserRecord */
        $loadedUserRecord = $loadedUserRecords[0];
        
        // delete the user record.
        $loadedUserRecord->delete();
        
        $loadedUserRecords2 = UuidUserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords2) !== 0)
        {
            throw new Exception("Did not have the expected number of user records after deletion");
        }
    }
}

