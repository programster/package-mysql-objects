<?php

/* 
 * A library to help with using UUIDs.
 */

namespace iRAP\MysqlObjects;

class UuidLib
{
    /**
     * Convert a binary string to a hexadecimal string representation
     * @param string $binaryUUID
     * @return string
     */
    public static function convertBinaryToHex(string $binaryUUID) : string
    {
        $string = unpack("H*", $binaryUUID);
        
        $uuidArray = preg_replace(
            "/([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})/", 
            "$1-$2-$3-$4-$5", 
            $string
        );

        $uuidString = array_pop($uuidArray); // should only be one element.
        return $uuidString;
    }
    
    
    /**
     * Convert a string UUID to binary string format for MySQL
     * @param string $uuidString
     * @return string
     */
    public static function convertUuidHexToBinary(string $uuidString) : string
    {
        return pack("H*", str_replace('-', '', $uuidString));
    }
    
    
    /**
     * Generates a v4 UUID that is in sequential form for database performance.
     * @return string - the generated UUID string.
     */
    public static function generateUuid() : string
    {
        static $factory = null;
        
        if ($factory == null)
        {
            $factory = new \Ramsey\Uuid\UuidFactory();

            $generator = new \Ramsey\Uuid\Generator\CombGenerator(
                $factory->getRandomGenerator(), 
                $factory->getNumberConverter()
            );

            $codec = new Ramsey\Uuid\Codec\TimestampFirstCombCodec($factory->getUuidBuilder());

            $factory->setRandomGenerator($generator);
            $factory->setCodec($codec);
        }

        Ramsey\Uuid\Uuid::setFactory($factory);
        $uuidString = Ramsey\Uuid\Uuid::uuid4()->toString();
        return $uuidString;
    }
    
    
    /**
     * Return whether the input string is bianry or not.
     * This is useful if we can't tell if the uuid is in hex or binary format.
     * @return bool
     */
    public static function isBinary(string $input) : bool
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $input) > 0;
    }
}