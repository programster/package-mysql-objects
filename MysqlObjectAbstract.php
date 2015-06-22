<?php

namespace iRAP\MysqlObjects;


/**
 * Using traits allows us to have static abstract functions
 * http://stackoverflow.com/questions/999066/why-does-php-5-2-disallow-abstract-static-class-methods
 */
trait MysqlObjectAbstractTrait 
{
    /**
     * Fetch the name of the table that holds this object
     */
    abstract public static function get_table_name();
    
    /**
     * Fetch the mysqli connection that this object relates to.
     * @param void
     * @return \mysqli
     */
    protected static abstract function get_db();
}


abstract class MysqlObjectAbstract implements MysqlObjectInterface
{
    use MysqlObjectAbstractTrait;
    
    protected $m_id = null;
    
    
    /**
     * When cloning objects, we remove the id so that if the cloned object is inserted, it does not replace the
     * object that it was cloned from, but will insert a new row.
     */
    public function __clone() 
    {
        unset($this->m_id);
    } 
    
    /**
     * Removes the obejct from the mysql database. 
     * @return void
     */
    public function delete()
    {
        $query = "DELETE FROM `" . static::get_table_name() . "` WHERE `id`='" . $this->m_id . "'";
        static::run_query($query, 'Failed to delete object id: ' . $this->m_id);
    }
    
    
    /**
     * Deletes a row  from the database provided by the items id
     * @param int $id - the id of the row in the database.
     */
    public static function delete_by_id($id)
    {
        $query = "DELETE FROM `" . static::get_table_name() . "` WHERE `id`='" . $id . "'";
        static::run_query($query, 'Failed to delete object id: ' . $id);
    }
    
    
    /**
     * Deletes all rows from the table by running TRUNCATE.
     */
    public static function delete_all()
    {
        $query = "TRUNCATE `" . static::get_table_name() . "`";
        static::run_query($query, 'Failed to drop table: ' . static::get_table_name());
    }
    
    
    /**
     * Saves this object to the mysql database.
     * @param void
     * @return 
     */
    public function save()
    {
        $table = static::get_table_name();

        $properties = array();
        
        $getFuncs = static::get_accessor_functions();
        
        foreach ($getFuncs as $mysqlColumnName => $callback)
        {
            /* @var $callback Callback */
            $property = $callback();
            $properties[$mysqlColumnName] = $property;            
        }
        
        $msg = '';
        
        $connection = static::get_db();
        
        if ($this->get_id() == null)
        {
            $query = "INSERT INTO `" . $table . "` " .
                    "SET " . \iRAP\CoreLibs\MysqliLib::generateQueryPairs($properties, $connection);
            $msg = 'running insert \n';
        }
        else
        {
            $msg = 'running update \n';
            
            $query = "UPDATE `" . $table . "` " .
                    "SET " .  \iRAP\CoreLibs\MysqliLib::generateQueryPairs($properties, $connection) . 
                    " WHERE `id`='" . $this->get_id() . "'";
        }
        
        /* @var $connection \mysqli */
        static::run_query($query, 'Error when saving abstract mysql object.');
        
        if ($this->get_id() == null)
        {
            $this->m_id = mysqli_insert_id($connection);
        }
    }
    
    
    /**
     * Fetches an array of mysql column name to property references for this object allowing us
     * to get and set them.
     */    
    abstract protected function get_accessor_functions();
    
    abstract protected function get_set_functions();
    
     
    /**
     * Creates an object of this type from a provided mysql table row.
     * @param row - the row from the mysql table this object belongs to.
     * @return object - the generated object.
     */
    protected static function create_from_db_row($row)
    {
        $object = new static();
        $object->m_id = $row['id'];
        
        $setMethods = $object->get_set_functions();
                
        foreach ($setMethods as $columnName => $callback)
        {
            /* @var $callback Callback */
            if (!isset($row[$columnName]))
            {
                # This is ok and may be the result of setting a column to allow Null
                $className = get_class($object);
                $errMsg = $columnName . ' has not yet been created in the mysql table for: ' . 
                          $className;
                
                trigger_error($errMsg, E_USER_WARNING);
            }
            
            $dbValue = $row[$columnName];
            
            if (!empty($dbValue))
            {
                $callback($dbValue);
            }
        }
        
        return $object;
    }
    
    
    /*
     * As the constructor needs to be protected, use the createNew static function instead and 
     * define in each class.
     * Unfortunately PHP cannot specify a required method without specifying the parameter list.
     */
    #public abstract static function createNew(....);
    
    
    /**
     * Loads all of these objects from the database.
     * @param void
     * @return 
     */
    public static function load_all()
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
     * @return object - the loaded object.
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
     * @return type
     */
    public static function load_range($offset, $numElements)
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
    
    
    protected static function run_query($query, $errorMessage)
    {        
        //print get_called_class() . "<BR>";
        $db = static::get_db();
        
        /* @var $db \mysqli */
        $result = $db->query($query) or 
            \iRAP\CoreLibs\Core::throwException($errorMessage . PHP_EOL . $query . PHP_EOL . $db->error);
        
        return $result;
    }
    
    # Accessor methods
    public function get_id() { return $this->m_id; }
}
