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
        $table = $this->getName();
        
        $constructor = $this->m_methodConstructor;
        
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
     * Create a object that represents a row in the database.
     * @param array $row - name value pairs to create the object from.
     * @return AbstractModelObject
     */
    public function create(array $row)
    {
        $constructor = $this->getRowObjectConstructor();
        return $constructor($row);
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
     */
    public function deleteAll()
    {
        $query = "TRUNCATE `" . $this->getName() . "`";
        $result = $this->getDb()->query($query);
        
        if ($result === FALSE)
        {
            throw new Exception('Failed to drop table: ' . $this->getName());
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
    
    
    public function getName() { return $this->m_tableName; }
    public abstract function getRowObjectConstructor();
    public abstract function getDb();
}