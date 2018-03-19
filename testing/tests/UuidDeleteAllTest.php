<?php

/* 
 * Test that running deleteAll on a UUID table works.
 */

class UuidDeleteAllTest
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
        
        $userDetails2 = array(
            'email' => 'user2@gmail.com',
            'name' => 'user2',
        );
        
        $userRecord2 = new UuidUserRecord($userDetails2);
        $userRecord2->save();
        
        
        $loadedUserRecords = UuidUserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords) !== 2)
        {
            throw new Exception("Did not have the expected number of user records");
        }
        
        UuidUserTable::getInstance()->deleteAll();
        
        $loadedUserRecords2 = UuidUserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords2) !== 0)
        {
            throw new Exception("Did not have the expected number of user records");
        }
    }
    
    
    private function testPreExisitngUuid()
    {
        $uuid = iRAP\MysqlObjects\UuidLib::generateUuid();
        
        $userDetails = array(
            'uuid' => $uuid,
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

