<?php

/* 
 * Test that running deleteAll on the table works.
 */

class DeleteAllTest
{
    
    public function __construct()
    {
        $db = ConnectionHandler::getDb();
        $query = "TRUNCATE `user`";
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
        
        $userRecord = new UserRecord($userDetails);
        $userRecord->save();
        
        $userDetails2 = array(
            'email' => 'user2@gmail.com',
            'name' => 'user2',
        );
        
        $userRecord2 = new UserRecord($userDetails2);
        $userRecord2->save();
        
        
        $loadedUserRecords = UserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords) !== 2)
        {
            throw new Exception("Did not have the expected number of user records");
        }
        
        UserTable::getInstance()->deleteAll();
        
        $loadedUserRecords2 = UserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords2) !== 0)
        {
            throw new Exception("Did not have the expected number of user records");
        }
    }
}

