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
     * Replace the current object.
     * @param array $data - name value pairs with names being that of the db columns.
     */
    public function replace(array $data)
    {
        $this->initializeFromArray($data);
        $this->save();
    }
    
    
    /**
     * Update part of the object. This is the same as replace, except that it 
     * can take a subset of the objects parameters, rather than requiring all of them.
     * @param type $data - array of name value pairs.
     */
    public function update(array $data)
    {
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
        
        $this->save();
    }
    
    
    /**
     * Deletes a row from the database provided by the items id
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
        
        if (!isset($this->m_id) || $this->m_id == null)
        {
            $createdObject = $this->getTableHandler()->create($properties);
            $this->m_id = $createdObject->get_id();
        }
        else
        {
            $this->getTableHandler()->update($this->m_id, $properties);
        }
    }
    
    
    /**
     * When cloning objects, we remove the id so that if the cloned object is inserted, it does not
     * replace the object that it was cloned from, but will insert a new row.
     */
    public function __clone() 
    {
        unset($this->m_id);
    }
    
    
    /**
     * Return an object that can be used to interface with the table in a generic way.
     * E.g. delete(id) load(id), and search()
     * @return TableInterface
     */
    public abstract function getTableHandler();
    
    
    /**
     * Helper to the constructor. Create this object from the passed in inputs.
     * @param array $row - name value pairs of column to values
     * @param array $row_field_types - optional array of field-name/type pairs. For the possible
     *                                 types, refer to http://bit.ly/2af5tyx              
     * @throws \Exception
     */
    protected function initializeFromArray($row, $row_field_types=null)
    {
        $intFieldTypes = array(
            1, // tinyint,
            2, // smallint,
            3, // int,
            8, // bigint,
            9, // mediumint,
            16 // bit,
        );
        
        $floatFieldTypes = array(
            4, // float,
            5, // double,
            246 // decimal
        );
        
        if (isset($row['id']))
        {
            $this->m_id = $row['id'];
        }
         
        $setMethods = $this->getSetFunctions();
                
        foreach ($setMethods as $columnName => $callback)
        {
            /* @var $callback Callback */
            if ( !isset($row[$columnName]) )
            {
                if 
                (
                    !in_array($columnName, $this->getTableHandler()->getFieldsThatAllowNull())
                    && !in_array($columnName, $this->getTableHandler()->getFieldsThatHaveDefaults())
                )
                {
                    $errMsg = $columnName . ' has not yet been created in the mysql table for: ' . 
                              get_class($this);
                    
                    throw new \Exception($errMsg);
                }
            }
            else
            {
                $value = $row[$columnName];
                
                if 
                (
                    $row_field_types != null
                    && isset($row_field_types[$columnName])
                )
                {
                    $fieldType = $row_field_types[$columnName];
                    
                    if (in_array($fieldType, $floatFieldTypes))
                    {
                        if ($value !== null)
                        {
                            $value = floatval($value);
                        }
                        
                        $callback(floatval($value));
                    }
                    else if (in_array($fieldType, $intFieldTypes))
                    {
                        if ($value !== null)
                        {
                            $value = intval($value);
                        }
                        
                        $callback($value);
                    }
                    else
                    {
                        $callback($value);
                    }
                }
                else
                {
                    $callback($value);
                }
            }
        }
    }
    
    
    /**
     * Fetches an array of mysql column name to property clusures for this object allowing us
     * to get and set them.
     * @return Array<\Closure>
     */    
    protected abstract function getAccessorFunctions();
    
    
    /**
     * Fetches an array of mysql column name to property references for this object allowing us
     * to get and set them.
     * @return Array<\Closure>
     */  
    protected abstract function getSetFunctions();
    
    
    /**
     * Get this object in array form. This will be keyed by the column names
     * and have values of what is in the database. This is in its "raw" form and
     * may not be suitable for returning in an API response where some things may
     * need to be filtered out (such as passwords) or formatted (such as unix timestamps).
     * @return array
     */
    public function getArrayForm()
    {
        $arrayForm = array();
        
        if (isset($this->m_id))
        {
            $arrayForm['id'] = $this->m_id;
        }
        
        $accessors = $this->getAccessorFunctions();
        
        foreach ($accessors as $column_name => $callback)
        {
            $arrayForm[$column_name] = $callback();
        }
        
        return $arrayForm;
    }
    
    
    # Accessors
    public function get_id() { return $this->m_id; }
}

