<?php

/*
 * Test that we can create a user object when we havent provided the ID
 * (it automatically gets created for us)
 */

class LoadWhereNoIdTest
{
    public function __construct()
    {
        $db = ConnectionHandler::getDb();
        $query = "TRUNCATE `" . NoIdUserTable::getInstance()->getTableName() . "`";
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

        $noIdUserTable = NoIdUserTable::getInstance();
        /* @var $noIdUserTable NoIdUserTable */
        $noIdUserTable->create($userDetails);

        $loadedUserRecords = NoIdUserTable::getInstance()->loadWhereAnd(
            array('email' => 'user1@gmail.com')
        );

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

        NoIdUserTable::getInstance()->deleteAll();
        $loadedUserRecords2 = $noIdUserTable->loadAll();

        if (count($loadedUserRecords2) !== 0)
        {
            throw new Exception("Did not have the expected number of user records");
        }
    }
}

