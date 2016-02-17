<?php

/**
 * The table handler is an object for interfacing with a table rather than a row.
 * This can be returned from a ModelObject from a static method. Thus if the programmer wants
 * to fetch a resource using the ModelObject definition, they would do:
 * MyModelName::getTableHandler()->load($id);
 * This is allows the developer to treat the model as an object that represents a row in the table
 */

namespace iRAP\MysqlObjects;


abstract class AbstractTable implements TableInterface
{
    protected static $s_instance = null;
    protected $m_tableName;
    protected $m_defaultSearchLimit = 999999999999999999;
    
    
    /**
     * Loads all of these objects from the database.
     * @param void
     * @return
     */
    public function loadAll()
    {
        $objects = array();
        
        $query   = "SELECT * FROM `" . $this->getName() . "`";
        $result  = $this->query($query, 'Error selecting all objects for loading.');
        
        $constructor = $this->getRowObjectConstructor();
        
        if ($result->num_rows > 0)
        {
            while (($row = $result->fetch_assoc()) != null)
            {
                $objects[] = $constructor($row);
            }
        }
        
        return $objects;
    }
    
    
    /**
     * Loads a single object of this class's type from the database using the unique row_id
     * @param id - the id of the row in the datatabase table.
     * @param use_cache - optionally set to false to force a database lookup even if we have a
     *                    cached value from a previous lookup.
     * @return AbstractModelObject - the loaded object.
     */
    public function load($id, $use_cache=true)
    {
        static $cache = array();
        $table = $this->getName();
        
        $constructor = $this->getRowObjectConstructor();
        
        if (!isset($cache[$table]))
        {
            $cache[$table] = array();
        }
        
        if (!isset($cache[$table][$id]) || !$use_cache)
        {
            $query = "SELECT * FROM `" . $this->getName() . "` WHERE `id`='" . $id . "'";
            
            $result = $this->query($query, 'Error selecting object for loading.');
            
            if ($result->num_rows == 0)
            {
                throw new NoSuchIdException('There is no ' . get_called_class() .  ' with id: ' . $id);
            }
            
            $row = $result->fetch_assoc();
            
            $object = $constructor($row);
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
    public function loadRange($offset, $numElements)
    {
        $objects = array();
        
        $query   = "SELECT * FROM `" . $this->getName() . "` " .
                   "LIMIT " . $offset . "," . $numElements;
        
        $result  = $this->query($query, 'Error selecting all objects for loading.');
        
        if ($result->num_rows > 0)
        {
            $constructor = $this->getRowObjectConstructor();
            
            while (($row = $result->fetch_assoc()) != null)
            {
                $objects[] = $constructor($row);
            }
        }
        
        return $objects;
    }
    
    
    /**
     * Create a new object that represents a new row in the database.
     * @param array $row - name value pairs to create the object from.
     * @return AbstractModelObject
     */
    public function create(array $row)
    {
        $db = $this->getDb();
        
        $query = "INSERT INTO " . $this->getName() . " SET " . 
                \iRAP\CoreLibs\MysqliLib::generateQueryPairs($row, $db);
        
        $result = $db->query($query);
        
        if ($result === FALSE)
        {
            throw new \Exception("replace query failed: " . $db->error);
        }
        
        return $result;
    }
    
    
    /**
     * Replace rows in a table. If they don't exist, then they will be inserted.
     * This only makes sense if the the primary or unique key is set in the input parameter.
     * @param array $row - row of data to replace with.
     * @return mysqli_result
     */
    public function replace($row)
    {
        $db = $this->getDb();
        
        $query = "REPLACE INTO " . $this->getName() . " SET " . 
                \iRAP\CoreLibs\MysqliLib::generateQueryPairs($row, $db);
        
        $result = $db->query($query);
        
        if ($result === FALSE)
        {
            throw new \Exception("replace query failed: " . $db->error);
        }
        
        return $result;
    }
    
    
    /**
     * Update rows in a table.
     * @param array $row - name value pairs
     * @param array $wherePairs - name value pairs of where x = y
     * @return mysqli_result
     * @throws Exception if query failed.
     */
    public function update(array $row, array $wherePairs)
    {
        $whereStrings = array();
        
        foreach ($wherePairs as $key => $value)
        {
            $whereStrings[] = $query .= "`" . $key . "`='" . $value . "'";
        }
        
        $query =
            "UPDATE `" . $this->getName() . "`" . 
            "SET " . \iRAP\CoreLibs\MysqliLib::generateQueryPairs($row, $this->getDb()) . 
            "WHERE " . explode(" AND ", $whereStrings);
        
        $result = $this->getDb()->query($query);
        
        if ($result === FALSE)
        {
            throw new Exception("Failed to update row in " . $this->getName());
        }
        
        return $result;
    }
    
    
    /**
     * Removes the obejct from the mysql database.
     * @return void
     */
    public function delete($id)
    {
        $query = "DELETE FROM `" . $this->getName() . "` WHERE `id`='" . $id . "'";
        $result = $this->getDb->query($query);
        
        if ($result === FALSE)
        {
            throw new \Exception('Failed to delete ' . $this->getName() .  ' with row id: ' . $id);
        }
        
        return $result;
    }
    
    
    /**
     * Deletes all rows from the table by running TRUNCATE.
     * @param bool $inTransaction - set to true to run a slower query that won't implicitly commit
     */
    public function deleteAll($inTransaction=false)
    {
        if ($inTransaction)
        {
            # This is much slower but can be run without inside a transaction
            $query = "DELETE FROM `" . $this->getName() . "`";
            $result = $this->getDb()->query($query);
            
            if ($result === FALSE)
            {
                throw new Exception('Failed to drop table: ' . $this->getName());
            }
        }
        else
        {
            # This is much faster, but will cause an implicit commit.
            $query = "TRUNCATE `" . $this->getName() . "`";
            $result = $this->getDb()->query($query);
            
            if ($result === FALSE)
            {
                throw new Exception('Failed to drop table: ' . $this->getName());
            }
        }
        
        return $result;
    }
    
    
    /**
     * Search the table for items and return any matches as objects. This method is
     * required by the TableHandlerInterface
     * @param array $parameters
     * @return type
     * @throws \Exception
     */
    public function search(array $parameters)
    {
        return $this->advancedSearch($parameters);
    }
    
    
    /**
     * Search the table for items and return any matches as objects. This method is
     * required by the TableHandlerInterface
     * @param array $parameters - these may not be sanitized already.
     * @return type
     * @throws \Exception
     */
    public function advancedSearch(array $parameters, $whereClauses=array())
    {
        $objects = array();
        
        if (isset($parameters['start_id']))
        {
            $whereClauses[] = "`id` >= '" . intval($parameters['start_id']) . "'";
        }
        
        if (isset($parameters['end_id']))
        {
            $whereClauses[] = "`id` <= '" . intval($parameters['end_id']) . "'";
        }
        
        if (isset($parameters['in_id']))
        {
            if (!is_array($parameters['in_id']))
            {
                $possibleIds = array();
                $idArray = $parameters['in_id'];
                
                foreach ($idArray as $idInput)
                {
                    $possibleIds[] = '"' . intval($idInput) . "'";
                }
                
                $whereClauses[] = "`id` IN (" . implode(",", $possibleIds) . ")'";
            }
            else
            {
                throw new \Exception('"in_id" needs to be an array of IDs ');
            }
        }
        
        $offset = isset($parameters['offset']) ? intval($parameters['offset']) : 0;
        $limit = (isset($parameters['limit'])) ? intval($parameters['limit']) : $this->m_defaultSearchLimit;
        
        if (count($whereClauses) > 0)
        {
            $whereClause = " WHERE " . implode(" AND ", $whereClauses);
        }
        else
        {
            $whereClause = "";
        }
        
        $query = 
            "SELECT *" . 
            " FROM `" . $this->getName() . "` " . 
            $whereClause .
            " LIMIT " . $offset . "," . $limit;
        
        $result = $this->query($query, 'Error selecting all objects.');
        
        $constructor = $this->getRowObjectConstructor();
        
        if ($result->num_rows > 0)
        {
            while (($row = $result->fetch_assoc()) != null)
            {
                $objects[] = $constructor($row);
            }
        }
        
        return $objects;
    }
    
    
    /**
     * Fetch the single instance of this object.
     * @return type
     */
    public static function getInstance() 
    {
        if (self::$s_instance == null)
        {
            self::$s_instance = static::__construct();
        }
        
        return self::$s_instance;
    }
    
    
    # Get the user to specify fields that may be null in the database and thus don't have
    # to be set when creating this object.
    # This needs to be static so that it can be used in static creation methods.
    public function getFieldsThatAllowNull();
    
    
    public function getName() { return $this->m_tableName; }
    
    
    /**
     * Return an inline function that takes the $row array and will construct the TableRowObject
     * @return Callable
     */
    public abstract function getRowObjectConstructor();
    
    
    public abstract function getDb();
}