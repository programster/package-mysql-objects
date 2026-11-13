<?php

/*
 * A library to help with using UUIDs.
 */

namespace Programster\MysqlObjects;


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
    public static function convertHexToBinary(string $uuidString) : string
    {
        return pack("H*", str_replace('-', '', $uuidString));
    }


    /**
     * Generates a v4 UUID that is in sequential form for database performance. This is a
     * "timestamp-first COMB": the first 6 bytes are the current time, in units of 10
     * microseconds, so UUIDs sort in the order they were generated, and the other 10 bytes
     * are random (apart from the version and variant bits). This is the same format that
     * ramsey/uuid's CombGenerator and TimestampFirstCombCodec produced, which earlier versions
     * of this package used, so new UUIDs keep sorting after existing ones.
     *
     * @return string - the generated UUID string.
     */
    public static function generateUuid() : string
    {
        $time = explode(' ', microtime());
        $timestamp = (int)($time[1] . substr($time[0], 2, 5));
        $timestampBytes = hex2bin(str_pad(dechex($timestamp), 12, '0', STR_PAD_LEFT));
        $bytes = $timestampBytes . random_bytes(10);

        # Set the version (4) and the RFC 4122 variant bits.
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return self::convertBinaryToHex($bytes);
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