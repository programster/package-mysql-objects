<?php

namespace iRAP\MysqlObjects;



interface MysqlObjectInterface
{
    /**
     * Deletes a row  from the database provided by the items id
     * @param int $id - the id of the row in the database.
     */
    public static function delete_by_id($id);
    
    
    /**
     * Deletes all rows from the table by running TRUNCATE.
     */
    public static function delete_all();
    

    /**
     * Loads all of these objects from the database.
     * @param void
     * @return 
     */
    public static function load_all();
    
    
    /**
     * Loads a single object of this class's type from the database using the unique row_id 
     * @param id - the id of the row in the datatabase table.
     * @param use_cache - optionally set to false to force a database lookup even if we have a
     *                    cached value from a previous lookup.
     * @return object - the loaded object.
     */
    public static function load($id, $use_cache=true);
    
    
    /**
     * Loads a range of data from the table.
     * It is important to note that offset is not tied to ID in any way. 
     * @param type $offset
     * @param type $numElements
     * @return type
     */
    public static function load_range($offset, $numElements);
}


