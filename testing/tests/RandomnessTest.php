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
        
        for ($i=0; $i<1000000; $i++)
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

