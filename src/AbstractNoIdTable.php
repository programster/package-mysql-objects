<?php

/**
 * A special table handler for tables that don't have an 'id' or 'uuid' primary key. Because of this, a lot
 * of the functionality found in the other classes, such as the cache, have had to be removed. If you can
 * use one of the other classes, please do.
 * This an object for interfacing with a table rather than a row.
 * This can be returned by using getInstance() or getTableHandler() from the objects this
 * table returns.
 * By using instantiating this object rather than using static methods, we can pass it around.
 */

namespace Programster\MysqlObjects;


abstract class AbstractNoIdTable
{
    # Array of all the child instances that get created.
    protected static $s_instances = array();


    /**
     * Loads all of these objects from the database.
     * @param void
     * @return array
     */
    public function loadAll() : array
    {
        $query   = "SELECT * FROM `{$this->getTableName()}`";
        $result  = $this->getDb()->query($query);

        if ($result === FALSE)
        {
            throw new \Exception('Error selecting all objects for loading.');
        }

        return $this->convertMysqliResultToObjects($result);
    }


    /**
     * Loads a range of data from the table.
     * It is important to note that offset is not tied to ID in any way.
     * @param type $offset
     * @param type $numElements
     * @return array<AbstractTableRowObject>
     */
    public function loadRange(int $offset, int $numElements) : array
    {
        $query   = "SELECT * FROM `" . $this->getTableName() . "` " .
                   "LIMIT " . $offset . "," . $numElements;

        $db = $this->getDb();
        $result  = $db->query($query);

        if ($result === FALSE)
        {
           throw new \Exception('Error selecting all objects for loading. ' . $db->error);
        }

        return $this->convertMysqliResultToObjects($result);
    }


    /**
     * Load objects from the table that meet have all the attributes specified
     * in the provided wherePairs parameter.
     * @param array $wherePairs - column-name/value pairs that the object must have in order
     *                            to be fetched. the value in the pair may be an array to load
     *                            any objects that have any one of those falues.
     *                            For example:
     *                              id => array(1,2,3) would load objects that have ID 1,2, or 3.
     * @return array<AbstractTableRowObject>
     * @throws \Exception
     */
    public function loadWhereAnd(array $wherePairs) : array
    {
        $db = $this->getDb();
        $query = $this->generateSelectWhereQuery($wherePairs, 'AND');
        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("Failed to load objects, check your where parameters.");
        }

        return $this->convertMysqliResultToObjects($result);
    }


    /**
     * Load objects from the table that meet the specified WHERE statement.
     *
     * WARNING: Unlike other load methods, this one takes the whole WHERE statement, so does not
     * do any escaping of the values
     *
     * @param string $where - the complete WHERE statement, minus the WHERE keyword and subsequent
     *                          space.
     *                            For example:
     *                              "name = 'John Smith'" would result in "WHERE name = 'John Smith'"
     * @return array<AbstractTableRowObject>
     * @throws \Exception
     */
    public function loadWhereExplicit(string $where) : array
    {
        $db = $this->getDb();
        $query = "SELECT * FROM `{$this->getTableName()}` WHERE {$where}";
        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("Failed to load objects, check your where parameters.");
        }

        return $this->convertMysqliResultToObjects($result);
    }

    /**
     * Load objects from the table that meet meet ANY of the attributes specified
     * in the provided wherePairs parameter.
     * @param array $wherePairs - column-name/value pairs that the object must have at least one of
     *                            in order to be fetched. the value in the pair may be an array to
     *                            load any objects that have any one of those falues.
     *                            For example:
     *                              id => array(1,2,3) would load objects that have ID 1,2, or 3.
     * @return array<AbstractTableRowObject>
     * @throws \Exception
     */
    public function loadWhereOr(array $wherePairs) : array
    {
        $db = $this->getDb();
        $query = $this->generateSelectWhereQuery($wherePairs, 'OR');
        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("Failed to load objects, check your where parameters.");
        }

        return $this->convertMysqliResultToObjects($result);
    }


    /**
     * Create a new object that represents a new row in the database.
     * @param array $row - name value pairs to create the object from.
     * @return AbstractTableRowObject
     */
    public function create(array $row) : AbstractNoIdTableRowObject
    {
        $db = $this->getDb();

        $query = "INSERT INTO `{$this->getTableName()}` SET " .
                 \Programster\MysqliLib\MysqliLib::generateQueryPairs($row, $db);

        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("Insert query failed: " . $db->error);
        }

        $constructor = $this->getRowObjectConstructorWrapper();
        $object = $constructor($row);
        return $object;
    }


    /**
     * Replace rows in a table.
     * WARNING - If they don't exist, then they will be inserted rather than throwing an error
     * or exception. If you just want to replace a single object, try using the update() method
     * instead.
     * This only makes sense if the the primary or unique key is set in the input parameter.
     * @param array $row - row of data to replace with.
     * @return mysqli_result
     */
    public function replace(array $row)
    {
        $db = $this->getDb();

        $query = "REPLACE INTO `{$this->getTableName()}` SET " .
                \Programster\MysqliLib\MysqliLib::generateQueryPairs($row, $db);

        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("replace query failed: " . $db->error);
        }

        return $result;
    }


    /**
     * Deletes all rows from the table by running TRUNCATE.
     * @param bool $inTransaction - set to true to run a slower query that won't implicitly commit
     * @return type
     * @throws Exception
     */
    public function deleteAll($inTransaction=false) : void
    {
        if ($inTransaction)
        {
            # This is much slower but can be run without inside a transaction
            $query = "DELETE FROM `{$this->getTableName()}`";
            $result = $this->getDb()->query($query);

            if ($result === FALSE)
            {
                throw new \Exception('Failed to drop table: ' . $this->getTableName());
            }
        }
        else
        {
            # This is much faster, but will cause an implicit commit.
            $query = "TRUNCATE `{$this->getTableName()}`";
            $result = $this->getDb()->query($query);

            if ($result === FALSE)
            {
                throw new \Exception('Failed to drop table: ' . $this->getTableName());
            }
        }
    }


    /**
     * Delete rows from the table that meet have all the attributes specified
     * in the provided wherePairs parameter.
     * @param array $wherePairs - column-name/value pairs that the object must have in order
     *                            to be deleted. the value in the pair may be an array to delete
     *                            any objects that have any one of those values.
     *                            For example:
     *                              id => array(1,2,3) would delete objects that have ID 1,2, or 3.
     * @return int - the number of rows/objects that were deleted.
     * @throws \Exception
     */
    public function deleteWhereAnd(array $wherePairs) : int
    {
        $db = $this->getDb();
        $query = $this->generateDeleteWhereQuery($wherePairs, "AND");
        /* @var $result \mysqli_result */
        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("Failed to delete objects, check your where parameters.");
        }

        return mysqli_affected_rows($db);
    }


    /**
     * Delete rows from the table that meet meet ANY of the attributes specified
     * in the provided wherePairs parameter.
     * @param array $wherePairs - column-name/value pairs that the object must have at least one of
     *                            in order to be fetched. the value in the pair may be an array to
     *                            delete any objects that have any one of those falues.
     *                            For example:
     *                              id => array(1,2,3) would delete objects that have ID 1,2, or 3.
     * @return array<AbstractTableRowObject>
     * @throws \Exception
     */
    public function deleteWhereOr(array $wherePairs) : int
    {
        $db = $this->getDb();
        $query = $this->generateDeleteWhereQuery($wherePairs, "OR");
        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("Failed to delete objects, check your where parameters.");
        }

        return mysqli_affected_rows($db);
    }


    /**
     * Fetch the single instance of this object.
     * @return AbstractTable
     */
    public static function getInstance()
    {
        $className = get_called_class();

        if (!isset(self::$s_instances[$className]))
        {
            self::$s_instances[$className] = new $className();
        }

        return self::$s_instances[$className];
    }


    /**
     * Get the user to specify fields that may be null in the database and thus don't have
     * to be set when creating this object.
     * @return array<string> - array of column names that may be null.
     */
    abstract public function getFieldsThatAllowNull() : array;


    /**
     * Get the user to specify fields that have default values and thus don't have
     * to be set when creating this object.
     * @return array<string> - array of column names that may be null.
     */
    abstract public function getFieldsThatHaveDefaults() : array;


    /**
     * Return an inline function that takes the $row array and will call the relevant row object's
     * constructor with it.
     * @return Callable - the callable must take the data row as its only parameter and return
     *                     the created object
     *                     e.g. $returnObj = function($row){ return new rowObject($row); }
     */
    public function getRowObjectConstructorWrapper()
    {
        $objectClassName = $this->getObjectClassName();

        $constructor = function($row, $row_field_types=null) use($objectClassName){
            return new $objectClassName($row, $row_field_types);
        };

        return $constructor;
    }


    public abstract function getObjectClassName();


    /**
     * Return the database connection to the database that has this table.
     * @return \mysqli
     */
    public abstract function getDb() : \mysqli;


    /**
     * Helper function that converts a query result into a collection of the row objects.
     * @param \mysqli_result $result
     * @return array<AbstractTableRowObject>
     */
    protected function convertMysqliResultToObjects(\mysqli_result $result) : array
    {
        $objects = array();

        if ($result->num_rows > 0)
        {
            $constructor = $this->getRowObjectConstructorWrapper();
            $fieldInfoMap = array();

            for ($i=0; $i<$result->field_count; $i++)
            {
                $fieldInfo = $result->fetch_field_direct($i);
                $fieldInfoMap[$fieldInfo->name] = $fieldInfo->type;
            }

            while (($row = $result->fetch_assoc()) != null)
            {
                $loadedObject = $constructor($row, $fieldInfoMap);
                $objects[] = $loadedObject;
            }
        }

        return $objects;
    }


    /**
     * Helper function that generates the raw SQL string to send to the database in order to
     * load objects that have any/all (depending on $conjunction) of the specified attributes.
     * @param array $wherePairs - column-name/value pairs of attributes the objects must have to
     *                           be loaded.
     * @param string $conjunction - 'AND' or 'OR' which changes whether the object needs all or
     *                              any of the specified attributes in order to be loaded.
     * @return string - the raw sql string to send to the database.
     * @throws \Exception - invalid $conjunction specified that was not 'OR' or 'AND'
     */
    protected function generateSelectWhereQuery(array $wherePairs, $conjunction) : string
    {
        $query = "SELECT * FROM `{$this->getTableName()}` " . $this->generateWhereClause($wherePairs, $conjunction);
        return $query;
    }


    /**
     * Helper function that generates the raw SQL string to send to the database in order to
     * delete objects that have any/all (depending on $conjunction) of the specified attributes.
     * @param array $wherePairs - column-name/value pairs of attributes the objects must have to
     *                           be deleted.
     * @param string $conjunction - 'AND' or 'OR' which changes whether the object needs all or
     *                              any of the specified attributes in order to be loaded.
     * @return string - the raw sql string to send to the database.
     * @throws \Exception - invalid $conjunction specified that was not 'OR' or 'AND'
     */
    protected function generateDeleteWhereQuery(array $wherePairs, $conjunction) : string
    {
        $query = "DELETE FROM `{$this->getTableName()}` " . $this->generateWhereClause($wherePairs, $conjunction);
        return $query;
    }


    /**
     * Generate the "where" part of a query based on name/value pairs and the provided conjunction
     * @param array $wherePairs - column/value pairs for where clause. Value may or may not be an
     *                            array list of values for WHERE IN().
     * @param string $conjunction - one of "AND" or "OR" for if all/any of criteria need to be met
     * @return string - the where clause of a query such as "WHERE `id`='3'"
     */
    protected function generateWhereClause($wherePairs, $conjunction) : string
    {
        $whereClause = "";
        $upperConjunction = strtoupper($conjunction);
        $possibleConjunctions = array("AND", "OR");

        if (!in_array($upperConjunction, $possibleConjunctions))
        {
            throw new \Exception("Invalid conjunction: " . $upperConjunction);
        }

        $whereStrings = array();

        foreach ($wherePairs as $attribute => $searchValue)
        {
            $whereString = "`" . $attribute . "` ";

            if (is_array($searchValue))
            {
                if (count($searchValue) === 0)
                {
                    $whereString = "FALSE";
                }
                else
                {
                    $escapedValues = \Programster\MysqliLib\MysqliLib::escapeValues($searchValue, $this->getDb());
                    $searchValueWrapped = \Programster\CoreLibs\ArrayLib::wrapElements($escapedValues, "'");
                    $whereString .= " IN(" . implode(",", $searchValueWrapped)  . ")";
                }
            }
            else
            {
                $whereString .= " = '" . $this->getDb()->escape_string($searchValue) . "'";
            }

            $whereStrings[] = $whereString;
        }

        if (count($whereStrings) > 0)
        {
            $whereClause = "WHERE " . implode(" " . $upperConjunction . " ", $whereStrings);
        }

        return $whereClause;
    }
}