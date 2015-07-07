<?php

/**
 * The table handler is an object for interfacing with a table rather than a row.
 * This can be returned from a ModelObject from a static method. Thus if the programmer wants
 * to fetch a resource using the ModelObject definition, they would do:
 * MyModelName::getTableHandler()->load($id);
 * This is allows the developer to treat the model as an object that represents a row in the table
 */

namespace iRAP\MysqlObjects;


class BasicTableHandler implements TableHandlerInterface
{
    private $m_tableName;
    private $m_methodGetDb;
    private $m_methodConstructor;
    private $m_defaultSearchLimit;
    
    /**
     * 
     * @param string $tableName - the name of the table this class interacts with.
     * @param \Closure $getDbFunc - closure that should return a connection to the relevant db.
     * @param \Closure $methodConstructor - function taking an array of name value pairs to create
     *                                      an object with.
     */
    public function __construct($tableName, 
                                \Closure $getDbFunc, 
                                \Closure $methodConstructor, 
                                $defaultSearchLimit=10)
    {
        $this->m_tableName = $tableName;
        $this->m_methodGetDb = $getDbFunc;
        $this->m_methodConstructor = $methodConstructor;
        $this->m_defaultSearchLimit = 10;
    }
    
    
    /**
     * Get a connection to the database.
     * @return \mysqli
     */
    public function getDb() 
    {
        $getDbMethod = $this->m_methodGetDb;
        return $getDbMethod();
    }
    
    
    /**
     * Removes the obejct from the mysql database. 
     * @return void
     */
    public function delete($id)
    {
        $query = "DELETE FROM `" . $this->get_table_name() . "` WHERE `id`='" . $id . "'";
        $this->query($query, 'Failed to delete object id: ' . $this->m_id);
    }
    
    
    /**
     * Deletes all rows from the table by running TRUNCATE.
     */
    public function deleteAll()
    {
        $query = "TRUNCATE `" . $this->get_table_name() . "`";
        $this->query($query, 'Failed to drop table: ' . $this->get_table_name());
    }
    
    
    /**
     * Helper function to query the database.
     * @param type $query
     * @param type $errorMessage
     * @return type
     */
    public function query($query, $errorMessage=null)
    {                
        /* @var $db \mysqli */
        $db = $this->getDb();
        $result = $db->query($query);
        
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
    public function loadAll()
    {
        $objects = array();
        
        $query   = "SELECT * FROM `" . $this->get_table_name() . "`";
        $result  = static::run_query($query, 'Error selecting all objects for loading.');
        
        $constructor = $this->m_methodConstructor;
        
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
        $table = $this->get_table_name();
        
        $constructor = $this->m_methodConstructor;
        
        if (!isset($cache[$table]))
        {
            $cache[$table] = array();
        }
        
        if (!isset($cache[$table][$id]) || !$use_cache)
        {
            $query = "SELECT * FROM `" . $this->get_table_name() . "` WHERE `id`='" . $id . "'";
            
            $result = static::run_query($query, 'Error selecting object for loading.');
            
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
        
        $query   = "SELECT * FROM `" . $this->get_table_name() . "` " .
                   "LIMIT " . $offset . "," . $numElements;
        
        $result  = $this->query($query, 'Error selecting all objects for loading.');
        
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
     * Create a object that represents a row in the database.
     * @param array $inputs - name value pairs to create the object from.
     * @return AbstractModelObject
     */
    public function create(array $inputs)
    {
        $constructor = $this->m_methodConstructor;
        return $constructor($inputs);
    }
    
    
    public function replace($id, $inputs)
    {
        /* @var $existingObject AbstractModelObject */
        $existingObject = $this->load($id);
        $existingObject->replace($inputs);
        $existingObject->save();
    }
    
    
    public function update($id, $unfilteredInputs)
    {
        $existingObject = $this->load($id);
        /* @var $existingObject AbstractModelObject */
        $existingObject->update($unfilteredInputs);
        $existingObject->save();
    }
    
    
    /**
     * Search the table for items and return any matches as objects.
     * @param array $parameters
     * @return type
     * @throws \Exception
     */
    public function search(array $parameters)
    {
        $objects = array();
        
        $whereClauses = array();
        
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
            $whereClause = " WHERE " . implod(" AND ", $whereClauses);
        }
        else 
        {
            $whereClause = "";
        }
        
        $query = 
            "SELECT * " . 
            "FROM `" . $this->get_table_name() . "` " . 
            $whereClause .
            "LIMIT " . $offset . "," . $limit;
        
        $result = $this->query($query, 'Error selecting all objects.');
        
        
        $constructor = $this->m_methodConstructor;
        
        if ($result->num_rows > 0)
        {
            while (($row = $result->fetch_assoc()) != null)
            {
                $objects[] = $constructor($row);
            }
        }
        
        return $objects;
    }
    
    
    public function get_table_name() { return $this->m_tableName; }
    
}

