<?php

/* 
 * Test that we can create a user record from the table, without providing our own ID.
 */

class CreateUserTest3
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
        
        $userRecord = UserTable::getInstance()->create($userDetails);
        $userRecord->save();
        $loadedUserRecords = UserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords) !== 1)
        {
            throw new Exception("Did not have the expected number of user records");
        }
        
        /* @var $loadedUserRecord UserRecord */
        $loadedUserRecord = $loadedUserRecords[0];
        
        if ($loadedUserRecord->getName() !== 'user1')
        {
            throw new Exception("User did not have expected name.");
        }
        
        if ($loadedUserRecord->getEmail() !== 'user1@gmail.com')
        {
            throw new Exception("User did not have expected email.");
        }
        
        # test table delete all.
        UserTable::getInstance()->deleteAll();
        
        $loadedUserRecords = UserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords) !== 0)
        {
            throw new Exception("Did not have the expected number of user records");
        }
    }
}

