<?php

/* 
 * Test that we can generate a large number of unique UUIDs without clashes.
 */

class RandomnessTest
{
    
    public function __construct()
    {
    }
    
    
    public function run()
    {
        $uuids = array();
        $million = 1000000;
        
        for ($i=0; $i<$million; $i++)
        {
            $uuid = \iRAP\MysqlObjects\UuidLib::generateUuid();
            
            if (isset($uuids[$uuid]))
            {
                throw new Exception("Clash found. Did not generate unique UUID");
            }
            else
            {
                $uuids[$uuid] = 1;
            }
        }
    }
}

