<?php

/* 
 * This class represents a single row in a table. E.g. each row in a table can
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
        $this->getTableHandler()->delete($this->m_id);
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
        
        if ($this->get_id() == null)
        {
            $this->getTableHandler()->create($properties);
            $this->m_id = $this->getTableHandler()->getDb()->insert_id;
        }
        else
        {
            $this->getTableHandler()->update($properties);
        }
    }
    
    
    /**
     * Helper to the constructor. Create this object from the passed in inputs.
     * @param array $row - name value pairs of column to values
     * @throws \Exception
     */
    protected function initializeFromArray($row)
    {
        if (isset($row['id']))
        {
            $this->m_id = $row['id'];
        }
         
        $setMethods = $this->getSetFunctions();
                
        foreach ($setMethods as $columnName => $callback)
        {
            /* @var $callback Callback */
            if 
            (
                !isset($row[$columnName])
                && !in_array($columnName, $this->getTableHandler()->getFieldsThatAllowNull())
            )
            {
                $errMsg = $columnName . ' has not yet been created in the mysql table for: ' . 
                          get_class($this);
                
                throw new \Exception($errMsg);
            }
            
            $value = $row[$columnName];
            
            if (!empty($value))
            {
                $callback($value);
            }
        }
    }
    
    
    /**
     * Return an object that can be used to interface with the table in a generic way.
     * E.g. delete(id) load(id), and search()
     * @return TableInterface
     */
    public abstract function getTableHandler();
    
    
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
    abstract protected function filterInputs(Array $data);
    
    
    /**
     * Update part of the object. This is exactly the same as replace, except that it 
     * can take a subset of the objects parameters, rather than requiring all of them.
     * @param type $data - array of unfiltered name value pairs.
     */
    public function update(array $data, $filterData=true)
    {
        if ($filterData)
        {
            $data = $this->filterInputs($data);
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
            $data = $this->filterInputs($data);
        }
        
        $this->initializeFromArray($data);
        $this->save();
    }
    
    
    # Accessors
    public function get_id() { return $this->m_id; }
}