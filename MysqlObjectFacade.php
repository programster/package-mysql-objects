<?php

/* 
 * Facade to give the ability to call static methods of the MysqlObjects class, but be able to
 * pass this object around.
 */

namespace iRAP\MysqlObjects;

class MyslqlObjectFacade
{
    /* @var $m_className MysqlObjectInterface */
    private $m_className;
    
    public function __construct($className) 
    {
        $this->m_className = $className;
    }
    
    
    public function get($index='')
    {
        # Must use strict type check as id 0 is loosely equal to ''
        if ($index === '')
        {
            $objects = self::getRange(0);
        }
        else 
        {
            $className = $this->m_className;
            $object = $className::load($id);
            $objects = array($object);
        }
        
        return $objects;
    }
    
    
    public function getAll()
    {
        $className = $this->m_className;
        /* @var $className MysqlObjectInterface */
        #$className::
    }
    
    
    /*
     * 
     */
    public function getRange($offset, $numElements=10)
    {
        $className = $this->m_className;
        return $className::load_range($offset, $numElements);
    }
    
    
    /**
     * Delete the element with the specified ID.
     * @param int $id
     */
    public function delete($id)
    {
        $className = $this->m_className;
        
        /* @var $className MysqlObjectInterface */
        return $className::delete_by_id($id);
    }
}