<?php

/* 
 * The resource object is exactly the same as a model, except that it needs to be convertable into
 * a form that can be sent in an API response.
 */

abstract class AbstractResourceObject extends iRAP\MysqlObjects\AbstractModelObject implements JsonSerializable
{
    # Specify the fields that should not be returned in a general API request (e.g. passwords)
    protected static $m_hiddenFields = array();
    
    
    /**
     * Create a public view of this resource which will be sent in a response to an API request.
     * @return String
     */
    public function jsonSerialize() 
    {
        $vars = get_object_vars($this);
        $publicVars = array();
        
        foreach($vars as $name => $value)
        {
            if (!isset($this->m_hiddenFields[$name]))
            {
                $publicVars[$name] = $value;
            }
        }
        
        return json_encode($publicVars);
    }
}