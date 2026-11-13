<?php

/*
 * Test that loadWhereAnd() finds records by uuid whether it's given a single binary uuid, a list
 * of hex uuids, or a list that mixes hex and binary uuids.
 */

use Programster\MysqlObjects\UuidLib;

class LoadWhereUuidFormatsTest
{
    public function __construct()
    {
        $db = ConnectionHandler::getDb();
        $query = "TRUNCATE `user_uuid_table`";
        $result = $db->query($query);

        if ($result === false)
        {
            throw new Exception("Failed to empty the test table.");
        }
    }


    public function run() : void
    {
        $uuids = [];

        foreach (['user1', 'user2', 'user3'] as $name)
        {
            $userRecord = new UuidUserRecord(['email' => "{$name}@gmail.com", 'name' => $name]);
            $userRecord->save();
            $uuids[] = $userRecord->getUuid();
        }

        $table = UuidUserTable::getInstance();
        $binaryUuid = UuidLib::convertHexToBinary($uuids[1]);

        $table->emptyCache();
        $this->assertNames("a binary uuid", ['user2'], ...$table->loadWhereAnd(['uuid' => $binaryUuid]));

        $table->emptyCache();
        $hexList = [$uuids[0], $uuids[2]];
        $this->assertNames("a list of hex uuids", ['user1', 'user3'], ...$table->loadWhereAnd(['uuid' => $hexList]));

        $table->emptyCache();
        $mixedList = [$uuids[0], $binaryUuid];
        $this->assertNames("a mixed list", ['user1', 'user2'], ...$table->loadWhereAnd(['uuid' => $mixedList]));
    }


    /**
     * Throw an exception if the loaded records don't have exactly the expected names.
     * @param string $description - what the records were loaded by, for the error message.
     * @param string[] $expectedNames - the names the records should have, in any order.
     * @param UuidUserRecord ...$records - the records that were loaded.
     * @throws Exception - if the names don't match.
     */
    private function assertNames(string $description, array $expectedNames, UuidUserRecord ...$records) : void
    {
        $names = [];

        foreach ($records as $record)
        {
            $names[] = $record->getName();
        }

        sort($names);
        sort($expectedNames);

        if ($names !== $expectedNames)
        {
            $message = "Loading by {$description} found [" . implode(', ', $names) . "], " .
                "expected [" . implode(', ', $expectedNames) . "].";

            throw new Exception($message);
        }
    }
}
