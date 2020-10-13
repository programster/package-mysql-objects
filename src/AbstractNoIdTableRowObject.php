<?php

/*
 * This class represents a single row in a table that does not use an 'id' or 'uuid' priarmary key.
 * Each row in a table can be turned into one of these and interacted with.
 */

namespace Programster\MysqlObjects;

abstract class AbstractNoIdTableRowObject
{
    /**
     * Return an object that can be used to interface with the table in a generic way.
     * @return TableInterface
     */
    public abstract function getTableHandler() : AbstractNoIdTable;


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
    protected abstract function getAccessorFunctions() : array;


    /**
     * Fetches an array of mysql column name to property references for this object allowing us
     * to get and set them.
     * @return Array<\Closure>
     */
    protected abstract function getSetFunctions() : array;


    /**
     * Get this object in array form. This will be keyed by the column names
     * and have values of what is in the database. This is in its "raw" form and
     * may not be suitable for returning in an API response where some things may
     * need to be filtered out (such as passwords) or formatted (such as unix timestamps).
     * @return array
     */
    public function getArrayForm() : array
    {
        $arrayForm = array();
        $accessors = $this->getAccessorFunctions();

        foreach ($accessors as $column_name => $callback)
        {
            $arrayForm[$column_name] = $callback();
        }

        return $arrayForm;
    }
}

