<?php

/* 
 * This class represents the base of a "data" object for each table. E.g. each row in a table can
 * be turned into one of these and interacted with.
 */

namespace iRAP\MysqlObjects;

abstract class AbstractModelObject
{
    protected $m_id;
    
    /**
     * When cloning objects, we remove the id so that if the cloned object is inserted, it does not replace the
     * object that it was cloned from, but will insert a new row.
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
        /* @var $model AbstractModel */
        $model = $this->getModel();
        $model->delete($this->m_id);
    }
    
    
    /**
     * Saves this object to the mysql database.
     * @param void
     * @return 
     */
    public function save()
    {
        $properties = array();
        
        $getFuncs = $this->getAccessors();
        
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
                "INSERT INTO `" . $this->getTableName() . "` " .
                "SET " . \iRAP\CoreLibs\MysqliLib::generateQueryPairs($properties, $db);
        }
        else
        {            
            $query = 
                "UPDATE `" . $this->getTableName() . "` " .
                "SET " .  \iRAP\CoreLibs\MysqliLib::generateQueryPairs($properties, $db) . 
                " WHERE `id`='" . $this->get_id() . "'";
        }
        
        /* @var $db \mysqli */
        $db->query($query, 'Error when saving abstract mysql object.');
        
        if ($this->get_id() == null)
        {
            $this->m_id = mysqli_insert_id($db);
        }
    }
    
    
    /**
     * Creates an object of this type from a provided mysql table row.
     * @param row - the row from the mysql table this object belongs to.
     * @return object - the generated object.
     */
    public static function createFromDbRow($row)
    {
        $object = $this->createNew($row);
        $object->m_id = $row['id'];
        return $object;
    }
    
    
    /**
     * Create a new instance of this object.
     * @param array $dataArray (name value pairs with names being the same as the db columns)
     * @return \static
     */
    public function createNew($dataArray)
    {
        $object = new static();        
        $setMethods = $object->getSetters();
                
        foreach ($setMethods as $columnName => $callback)
        {
            /* @var $callback Callback */
            if 
            (
                !isset($dataArray[$columnName]) &&
                !in_array($columnName, $this->m_fieldsThatAllowNull)
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
    
    # Allow the user to specify fields that may be null in the database and thus don't have
    # to be set when creating this object.
    protected $m_fieldsThatAllowNull = array();
    
    
    /**
     * Force the developer to define which model relates to this modelObject.
     * @return AbstractModel
     */
    abstract protected function getModel();
    
    
    /**
     * Shortcut for getting the table name (directly tied to the model object)
     */
    protected final function getTableName() { return $this->getModel()->getTableName(); }
    
    
    /**
     * Helper shortcut for getting the database connection.
     * @return type
     */
    protected final function getDb() { return $this->getModel()->getDb(); }
    
    /**
     * Fetches an array of mysql column name to property clusures for this object allowing us
     * to get and set them.
     * @return Array<\Closure>
     */    
    abstract protected function getAccessors();
    
    
    /**
     * Fetches an array of mysql column name to property references for this object allowing us
     * to get and set them.
     * @return Array<\Closure>
     */  
    abstract protected function getSetters();
    
    
    # Accessors
    public function get_id() { return $this->m_id; }
}
