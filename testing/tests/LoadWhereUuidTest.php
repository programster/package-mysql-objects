<?php

/* 
 * Test that we can create a uuid object when we havent provided the uuid 
 * (it automatically gets created for us)
 */

class LoadWhereUuidTest
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
        $uuid = \iRAP\MysqlObjects\UuidLib::generateUuid();
        
        $userDetails = array(
            'uuid' => $uuid,
            'email' => 'user1@gmail.com',
            'name' => 'user1',
        );
        
        $userRecord = new UuidUserRecord($userDetails);
        $userRecord->save();
        
        UuidUserTable::getInstance()->emptyCache();
        
        
        $loadedUserRecords = UuidUserTable::getInstance()->loadWhereAnd(
            array('uuid' => $uuid)
        );
        
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
        
        if (iRAP\MysqlObjects\UuidLib::isBinary($loadedUserRecord->getUuid()))
        {
            throw new Exception("User uuid is binary not hex.");
        }
        
        # test deletion works.
        $userRecord->delete();
        
        
        $loadedUserRecords2 = UuidUserTable::getInstance()->loadAll();
        
        if (count($loadedUserRecords2) !== 0)
        {
            throw new Exception("Did not have the expected number of user records");
        }
    }
}

