<?php

/*
 * Extend the basic table object into one that can be used as a resource.
 */

namespace iRAP\MysqlObjects;

abstract class AbstractResourceObject extends AbstractTableRowObject
{
    /**
     * Generate an array representation of this object. This will be json serialized
     * and sent to the user. Thus, it should not contain anything sensitive like passwords
     * and unix timestamps may want to be converted into a human readable time such as
     * 01 Jan 2015.
     */
    public abstract function getPublicArray();
}