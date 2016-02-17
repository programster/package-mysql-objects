<?php

/* 
 * This class represents the base of a "data" object for each table. E.g. each row in a table can
 * be turned into one of these and interacted with.
 */

namespace iRAP\MysqlObjects;

abstract class AbstractTableRowObject
{
    protected $m_id;
    
    
    /**
     * When cloning objects, we remove the id so that if the cloned object is inserted, it does not
     * replace the object that it was cloned from, but will insert a new row.
     */
    public function __clone() 
    {
        unset($this->m_id);
    } 
    
    
    /**
     * Deletes a row  from the database provided by the items id
     * @param int $id - the id of the row in the database.
     */
    public function delete() 
    { 
        /* @var $tableHandler TableHandler */
        $tableHandler = $this->getTableHandler();
        $tableHandler->delete($this->m_id);
    }
    
    
    /**
     * Saves this object to the mysql database.
     * @param void
     * @return 
     */
    public function save()
    {
        $properties = array();
        
        $getFuncs = $this->getAccessorFunctions();
        
        foreach ($getFuncs as $mysqlColumnName => $callback)
        {
            /* @var $callback Callback */
            $property = $callback();
            $properties[$mysqlColumnName] = $property;            
        }
                
        $db = $this->getDb();
        
        if ($this->get_id() == null)
        {
            $query = 
                "INSERT INTO `" . $this->getName() . "` " .
                "SET " . \iRAP\CoreLibs\MysqliLib::generateQueryPairs($properties, $db);
        }
        else
        {            
            $query = 
                "UPDATE `" . $this->getName() . "` " .
                "SET " .  \iRAP\CoreLibs\MysqliLib::generateQueryPairs($properties, $db) . 
                " WHERE `id`='" . $this->get_id() . "'";
        }
        
        /* @var $db \mysqli */
        $queryResult = $db->query($query);
        
        if ($queryResult === FALSE)
        {
            throw new \Exception('Error when saving abstract mysql object.');
        }
        
        if ($this->get_id() == null)
        {
            $this->m_id = $db->insert_id;
        }
    }
    
    
    /**
     * Creates an object of this type from a provided mysql table row.
     * This is protected so that it is only used from the base model handler returned in 
     * getBaseModelHandler, yet we need child classes to be able to override this.
     * @param row - the row from the mysql table this object belongs to.
     * @return object - the generated object.
     */
    protected static function createFromDbRow($row)
    {
        $object = static::createNew($row);
        $object->m_id = $row['id'];
        return $object;
    }
    
    
    /**
     * Create a new instance of this object.
     * This is protected so that it is only used from the base model handler returned in 
     * getBaseModelHandler, yet we need child classes to be able to override this.
     * @param row - the row from the mysql table this object belongs to.
     * @param array $dataArray (name value pairs with names being the same as the db columns)
     * @return \static
     */
    protected static function createNew($dataArray)
    {
        $object = new static();        
        $setMethods = $object->getSetFunctions();
                
        foreach ($setMethods as $columnName => $callback)
        {
            /* @var $callback Callback */
            if 
            (
                !isset($dataArray[$columnName])
                && !in_array($columnName, static::getFieldsThatAllowNull())
            )
            {
                $errMsg = $columnName . ' has not yet been created in the mysql table for: ' . 
                          get_class($object);
                
                throw new \Exception($errMsg);
            }
            
            $dbValue = $dataArray[$columnName];
            
            if (!empty($dbValue))
            {
                $callback($dbValue);
            }
        }
        
        return $object;
    }
    
    
    /**
     * Return an object that can be used to interface with the table in a generic way.
     * E.g. delete(id) load(id), and search()
     * @return TableHandler
     */
    public abstract function getTableHandler();
    
    
    /**
     * Helper shortcut for getting the database connection.
     * @return type
     */
    protected abstract static function getDb();
    
    
    /**
     * Fetches an array of mysql column name to property clusures for this object allowing us
     * to get and set them.
     * @return Array<\Closure>
     */    
    abstract protected function getAccessorFunctions();
    
    
    /**
     * Fetches an array of mysql column name to property references for this object allowing us
     * to get and set them.
     * @return Array<\Closure>
     */  
    abstract protected function getSetFunctions();
    
    
    /**
     * Take a given array of data and filter it. Do NOT perform mysqli escaping here as that
     * is performed at the last possible moment in the save method. This is a good point to
     * throw exceptions if someone has provided a string when expecting a boolean etc.
     */
    abstract protected static function filterInputs(Array $data);
    
    
    /**
     * Update part of the object. This is exactly the same as replace, except that it 
     * can take a subset of the objects parameters, rather than requiring all of them.
     * @param type $data - array of unfiltered name value pairs.
     */
    public function update(array $data, $filterData=true)
    {
        if ($filterData)
        {
            $data = static::filterInputs($data);
        }
        
        $setters = $this->getSetFunctions();
        
        foreach ($data as $name => $value)
        {
           if (!isset($setters[$name]))
           {
               $warningMessage = "Missing setter for: $name when updating: " . get_called_class();
               trigger_error($warningMessage, E_USER_WARNING);
           }
           else
           {
               $setter = $setters[$name];
               $setter($value);
           }
        }
    }
    
    
    /**
     * Replace the current object.
     * @param array $data - name value pairs with names being that of the db columns.
     * @param bool $filterData - manually specify to false to force not filtering.
     */
    public function replace(array $data, $filterData=true)
    {
        if ($filterData)
        {
            $data = static::filterInputs($data);
        }
        
        $object = static::createNew($data);
        $object->m_id = $this->m_id;
        $object->save();
    }
    
    
    # Get the user to specify fields that may be null in the database and thus don't have
    # to be set when creating this object.
    # This needs to be static so that it can be used in static creation methods.
    protected abstract static function getFieldsThatAllowNull();
    
    
    # Accessors
    public function get_id() { return $this->m_id; }
}