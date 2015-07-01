<?php

namespace iRAP\MysqlObjects;


abstract class AbstractModel implements MysqlObjectInterface
{
    abstract public function getTableName(); # force the developer to specify which table.
    abstract public function getDb(); # force the developer to implement some way of getting a conn
    
    
    /**
     * Removes the obejct from the mysql database. 
     * @return void
     */
    public function delete($id)
    {
        $query = "DELETE FROM `" . $this->getTableName() . "` WHERE `id`='" . $id . "'";
        $this->query($query, 'Failed to delete object id: ' . $this->m_id);
    }
    
    
    /**
     * Deletes all rows from the table by running TRUNCATE.
     */
    public function deleteAll()
    {
        $query = "TRUNCATE `" . $this->getTableName() . "`";
        $this->query($query, 'Failed to drop table: ' . $this->getTableName());
    }
    
    
    /**
     * Helper function to query the database.
     * @param type $query
     * @param type $errorMessage
     * @return type
     */
    protected function query($query, $errorMessage=null)
    {                
        /* @var $db \mysqli */
        $result = $this->getDb()->query($query);
        
        if ($result === FALSE && $errorMessage !== null)
        {
            $msg = $errorMessage . PHP_EOL . 
                   $query . PHP_EOL . 
                   $db->error;
            
            throw new \Exception($msg);
        }
        
        return $result;
    }
    
    
    /**
     * Loads all of these objects from the database.
     * @param void
     * @return 
     */
    public static function loadAll()
    {
        $objects = array();
        
        $table   = static::get_table_name();
        $query   = "SELECT * FROM `" . $table . "`";
        $result  = static::run_query($query, 'Error selecting all objects for loading.');
        
        if ($result->num_rows > 0)
        {
            while (($row = $result->fetch_assoc()) != null)
            {
                $objects[] = static::create_from_db_row($row);
            }
        }
        
        return $objects;
    }
    
    
    /**
     * Loads a single object of this class's type from the database using the unique row_id 
     * @param id - the id of the row in the datatabase table.
     * @param use_cache - optionally set to false to force a database lookup even if we have a
     *                    cached value from a previous lookup.
     * @return AbstractModel - the loaded object.
     */
    public static function load($id, $use_cache=true)
    {
        static $cache = array();
        $table = static::get_table_name();
        
        if (!isset($cache[$table]))
        {
            $cache[$table] = array();
        }

        if (!isset($cache[$table][$id]) || !$use_cache)
        {
            $query = "SELECT * FROM `" . $table . "` WHERE `id`='" . $id . "'";
            
            $result = static::run_query($query, 'Error selecting object for loading.');
            
            if ($result->num_rows == 0)
            {
                throw new NoSuchIdException('There is no ' . get_called_class() .  ' with id: ' . $id);
            }
            
            $row = $result->fetch_assoc();
            
            $object = static::create_from_db_row($row);
            $cache[$table][$id] = $object;
        }
        
        return $cache[$table][$id];
    }
    
    
    /**
     * Loads a range of data from the table.
     * It is important to note that offset is not tied to ID in any way. 
     * @param type $offset
     * @param type $numElements
     * @return array<AbstractModel>
     */
    public static function loadRange($offset, $numElements)
    {
        $objects = array();
        
        $table   = static::get_table_name();
        $query   = "SELECT * FROM `" . $table . "` LIMIT " . $offset . "," . $numElements;
        $result  = static::run_query($query, 'Error selecting all objects for loading.');
        
        if ($result->num_rows > 0)
        {
            while (($row = $result->fetch_assoc()) != null)
            {
                $objects[] = static::create_from_db_row($row);
            }
        }
        
        return $objects;
    }
    
}


