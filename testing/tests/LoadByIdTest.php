<?php

/* 
 * Test that we can create a user object when we havent provided the ID 
 * (it automatically gets created for us)
 */

class LoadByIdTest
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
        $userID = $userRecord->get_id();
        
        UserTable::getInstance()->emptyCache();
        
        /* @var $loadedUserRecord UserRecord */
        $loadedUserRecord = UserTable::getInstance()->load($userID);
        
        if ($loadedUserRecord->getName() !== 'user1')
        {
            throw new Exception("User did not have expected name.");
        }
        
        if ($loadedUserRecord->getEmail() !== 'user1@gmail.com')
        {
            throw new Exception("User did not have expected email.");
        }
        
        if ($loadedUserRecord->get_id() === null || $loadedUserRecord->get_id() === "")
        {
            throw new Exception("User ID was null");
        }
        
        # test deletion works.
        $userRecord->delete();
        
        
        $loadedUserRecords2 = UserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords2) !== 0)
        {
            throw new Exception("Did not have the expected number of user records");
        }
    }
}

