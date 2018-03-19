<?php

/* 
 * 
 */

class UuidTest
{
    
    public function __construct()
    {
        
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
        
        # Test creating a record with a UUID we generate.
        $this->testPreExisitngUuid();
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

