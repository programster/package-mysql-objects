<?php

/**
 * This is just like the AbstractTable object, but for tables using UUID instead of auto incremented
 * IDs.
 */

namespace Programster\MysqlObjects;


abstract class AbstractUuidTable implements TableInterface
{
    # Array of all the child instances that get created.
    protected static $s_instances = array();

    protected $m_defaultSearchLimit = 999999999999999999;

    # Cache of loaded objects so we don't need to go and re-fetch them.
    # This object needs to ensure we clear these when we update rows.
    protected $m_objectCache = array();


    /**
     * Loads all of these objects from the database.
     * This also clears and fully loads the cache.
     * @param void
     * @return array
     */
    public function loadAll()
    {
        $this->emptyCache();

        $query   = "SELECT * FROM `" . $this->getTableName() . "`";
        $result  = $this->getDb()->query($query);

        if ($result === FALSE)
        {
            throw new \Exception('Error selecting all objects for loading.');
        }

        return $this->convertMysqliResultToObjects($result);
    }


    /**
     * Loads a single object of this class's type from the database using the unique row_id
     * @param string uuid - the uuid of the row in the database table.
     * @param bool useCache - optionally set to false to force a database lookup even if we have a
     *                    cached value from a previous lookup.
     * @return AbstractTableRowObject - the loaded object.
     */
    public function load($uuid, $useCache=true)
    {
        $objects = $this->loadIds(array($uuid), $useCache);

        if (count($objects) == 0)
        {
            $msg = 'There is no ' . $this->getTableName() .  ' object with id: ' . $uuid;
            throw new NoSuchIdException($msg);
        }

        return \Programster\CoreLibs\ArrayLib::getFirstElement($objects);
    }


    /**
     * Loads a number of objects of this class's type from the database using the provided array
     * list of IDs. If any of the objects are already in the cache, they are fetched from there.
     * NOTE: The returned array of objects is indexed by the IDs of the objects.
     * @param array uuids - the list of IDs of the objects we wish to load.
     * @param bool useCache - optionally set to false to force a database lookup even if we have a
     *                        cached value from a previous lookup.
     * @return array<AbstractTableRowObject> - list of the objects with the specified IDs indexed
     *                                         by the objects ID.
     */
    public function loadIds(array $uuids, $useCache=true)
    {
        $loadedObjects = array();
        $constructor = $this->getRowObjectConstructorWrapper();
        $uuidsToFetch = array();

        foreach ($uuids as $uuid)
        {
            if (!isset($this->m_objectCache[$uuid]) || !$useCache)
            {
                $uuidsToFetch[] = UuidLib::convertHexToBinary($uuid); // convert to binary for mysql
            }
            else
            {
                $loadedObjects[$uuid] = $this->m_objectCache[$uuid];
            }
        }

        if (count($uuidsToFetch) > 0)
        {
            $db = $this->getDb();
            $escapedIdsToFetch = \Programster\MysqliLib\MysqliLib::escapeValues($uuidsToFetch, $db);
            $idsToFetchWrapped = \Programster\CoreLibs\ArrayLib::wrapElements($escapedIdsToFetch, "'");

            $query = "SELECT * FROM `" . $this->getTableName() . "` " .
                     "WHERE `uuid` IN(" . implode(", ", $idsToFetchWrapped) . ")";

            /* @var $result \mysqli_result */
            $result = $db->query($query);

            if ($result === FALSE)
            {
                throw new \Exception("Failed to select from table. " . $db->error);
            }

            $fieldInfoMap = array();

            for ($i=0; $i<$result->field_count; $i++)
            {
                $fieldInfo = $result->fetch_field_direct($i);
                $fieldInfoMap[$fieldInfo->name] = $fieldInfo->type;
            }

            while (($row = $result->fetch_assoc()) != null)
            {
                /* @var $object AbstractUuidTableRowObject */
                $object = $constructor($row, $fieldInfoMap);
                $objectUUID = $object->getUuid();
                $this->m_objectCache[$objectUUID] = $object;
                $loadedObjects[$objectUUID] = $this->m_objectCache[$objectUUID];
            }
        }

        return $loadedObjects;
    }


    /**
     * Loads a range of data from the table.
     * It is important to note that offset is not tied to ID in any way.
     * @param type $offset
     * @param type $numElements
     * @return array<AbstractTableRowObject>
     */
    public function loadRange($offset, $numElements)
    {
        $query = "SELECT * FROM `" . $this->getTableName() . "` " .
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
    public function loadWhereAnd(array $wherePairs)
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
    public function loadWhereExplicit($where)
    {
        $db = $this->getDb();
        $query = "SELECT * FROM `" . $this->getTableName() . "` WHERE ".$where;
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
    public function loadWhereOr(array $wherePairs)
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
    public function create(array $row)
    {
        $db = $this->getDb();

        // user may decide not to set the uuid themselves, in which case we create it for them.
        if (!isset($row['uuid']))
        {
            $row['uuid'] = UuidLib::generateUuid();
        }

        if (UuidLib::isBinary($row['uuid']) === FALSE)
        {
            $row['uuid'] = UuidLib::convertHexToBinary($row['uuid']);
        }

        $query = "INSERT INTO " . $this->getTableName() . " SET " .
                \Programster\MysqliLib\MysqliLib::generateQueryPairs($row, $db);

        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("Insert query failed: " . $db->error);
        }

        $constructor = $this->getRowObjectConstructorWrapper();
        $object = $constructor($row);
        $this->updateCache($object);
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

        if (UuidLib::isBinary($row['uuid']) === FALSE)
        {
            $row['uuid'] = UuidLib::convertHexToBinary($row['uuid']);
        }

        $query = "REPLACE INTO " . $this->getTableName() . " SET " .
                \Programster\MysqliLib\MysqliLib::generateQueryPairs($row, $db);

        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("replace query failed: " . $db->error);
        }

        return $result;
    }


    /**
     * Update a row specified by the ID with the provided data.
     * @param int $id - the ID of the object being updated
     * @param array $row - the data to update the object with
     * @return AbstractTableRowObject
     * @throws \Exception if query failed.
     */
    public function update($uuid, array $row)
    {
        if (UuidLib::isBinary($uuid) === FALSE)
        {
            $binaryUuid = UuidLib::convertHexToBinary($uuid);
            $stringUuid = $uuid;
        }
        else
        {
            $binaryUuid = $uuid;
            $stringUuid = UuidLib::convertBinaryToHex($uuid);
        }

        if (isset($row['uuid']))
        {
            if (UuidLib::isBinary($row['uuid']) === FALSE)
            {
                $row['uuid'] = UuidLib::convertHexToBinary($row['uuid']);
            }
        }

        # This logic must not ever be changed to load the row object and then call update on that
        # because it's update method will call this method and you will end up with a loop.
        $query =
            "UPDATE `" . $this->getTableName() . "` " .
            "SET " . \Programster\MysqliLib\MysqliLib::generateQueryPairs($row, $this->getDb()) . " " .
            "WHERE `uuid`='" . $binaryUuid . "'";

        $result = $this->getDb()->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("Failed to update row in " . $this->getTableName());
        }

        if (isset($this->m_objectCache[$stringUuid]))
        {
            $existingObject = $this->getCachedObject($stringUuid);
            $existingArrayForm = $existingObject->getArrayForm();
            $newArrayForm = $existingArrayForm;

            # overwrite the existing data with the new.
            foreach ($row as $column_name => $value)
            {
                $newArrayForm[$column_name] = $value;
            }

            $newArrayForm['uuid'] = $stringUuid;

            $objectConstructor = $this->getRowObjectConstructorWrapper();
            $updatedObject = $objectConstructor($newArrayForm);
            $this->updateCache($updatedObject);
        }
        else
        {
            # We don't have the object loaded into cache so we need to fetch it from the
            # database in order to be able to return an object. This updates cache as well.
            # We also need to handle the event of the update being to change the ID.
            if (isset($row['uuid']))
            {
                $updatedObject = $this->load($row['uuid']);
            }
            else
            {
                $updatedObject = $this->load($stringUuid);
            }
        }

        # If we changed the object's ID, then we need to remove the old cached object.
        if (isset($row['id']) && $row['id'] != $id)
        {
            $this->unsetCache($id);
        }

        return $updatedObject;
    }


    /**
     * Removes the obejct from the mysql database.
     * @TODO - have this method use the deleteIds() method which requires a different return type.
     * @param int $uuid - the ID of the object we wish to delete.
     * @return mysqli_result
     * @throws \Exception - if query failed, returning FALSE.
     */
    public function delete($uuid)
    {
        if (UuidLib::isBinary($uuid) === FALSE)
        {
            $uuid = UuidLib::convertHexToBinary($uuid);
        }

        $db = $this->getDb();
        $query = "DELETE FROM `" . $this->getTableName() . "` WHERE `uuid`='" . $db->escape_string($uuid) . "'";
        $result = $db->query($query);

        if ($result === FALSE)
        {
            print $query . PHP_EOL;
            $msg = 'Failed to delete ' . $this->getTableName() .  ' with uuid: ' .
                    UuidLib::convertBinaryToHex($uuid) . PHP_EOL . $this->getDb()->error;
            ($msg);
        }

        $this->unsetCache($uuid);
        return $result;
    }


    /**
     * Deletes objects that have the any of the specified IDs. This will not throw an error or
     * exception if an object with one of the IDs specified does not exist.
     * This is a fast and cache-friendly operation.
     * @param array $uuids - the list of IDs of the objects we wish to delete.
     * @return int - the number of objects deleted.
     */
    public function deleteIds(array $uuids)
    {
        $db = $this->getDb();
        $uuidsToDelete = \Programster\MysqliLib\MysqliLib::escapeValues($uuids, $db);
        $wherePairs = array("uuid" => $uuidsToDelete);
        $query = $this->generateDeleteWhereQuery($wherePairs, "AND");
        $result = $db->query($query);

        if ($result == FALSE)
        {
            throw new \Exception("Failed to delete objects by ID.");
        }

        # Remove these objects from our cache.
        foreach ($uuids as $objectId)
        {
            $this->unsetCache($objectId);
        }

        return $db->affected_rows;
    }


    /**
     *  Deletes all rows from the table by running TRUNCATE.
     * @param bool $inTransaction - set to true to run a slower query that won't implicitly commit
     * @return type
     * @throws Exception
     */
    public function deleteAll($inTransaction=false)
    {
        if ($inTransaction)
        {
            # This is much slower but can be run without inside a transaction
            $query = "DELETE FROM `" . $this->getTableName() . "`";
            $result = $this->getDb()->query($query);

            if ($result === FALSE)
            {
                throw new \Exception('Failed to drop table: ' . $this->getTableName());
            }
        }
        else
        {
            # This is much faster, but will cause an implicit commit.
            $query = "TRUNCATE `" . $this->getTableName() . "`";
            $result = $this->getDb()->query($query);

            if ($result === FALSE)
            {
                throw new \Exception('Failed to drop table: ' . $this->getTableName());
            }
        }

        $this->emptyCache();
        return $result;
    }


    /**
     * Delete rows from the table that meet have all the attributes specified
     * in the provided wherePairs parameter.
     * WARNING - by default this will clear your cache. You can manually set clearCache to false
     *           if you know what you are doing, but you may wish to delete by ID instead which
     *           will be cache-optimised. We clear the cache to prevent loading cached objects
     *           from memory when they were previously deleted using one of these methods.
     * @param array $wherePairs - column-name/value pairs that the object must have in order
     *                            to be deleted. the value in the pair may be an array to delete
     *                            any objects that have any one of those values.
     *                            For example:
     *                              id => array(1,2,3) would delete objects that have ID 1,2, or 3.
     * @param bool $clearCache - optionally set to false to not have this operation clear the
     *                           cache afterwards.
     * @return int - the number of rows/objects that were deleted.
     * @throws \Exception
     */
    public function deleteWhereAnd(array $wherePairs, $clearCache=true)
    {
        $db = $this->getDb();
        $query = $this->generateDeleteWhereQuery($wherePairs, "AND");
        /* @var $result \mysqli_result */
        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("Failed to delete objects, check your where parameters.");
        }

        if ($clearCache)
        {
            $this->emptyCache();
        }

        return mysqli_affected_rows($db);
    }


    /**
     * Delete rows from the table that meet meet ANY of the attributes specified
     * in the provided wherePairs parameter.
     * WARNING - by default this will clear your cache. You can manually set clearCache to false
     *           if you know what you are doing, but you may wish to delete by ID instead which
     *           will be cache-optimised. We clear the cache to prevent loading cached objects
     *           from memory when they were previously deleted using one of these methods.
     * @param array $wherePairs - column-name/value pairs that the object must have at least one of
     *                            in order to be fetched. the value in the pair may be an array to
     *                            delete any objects that have any one of those falues.
     *                            For example:
     *                              id => array(1,2,3) would delete objects that have ID 1,2, or 3.
     * @param bool $clearCache - optionally set to false to not have this operation clear the
     *                           cache afterwards.
     * @return array<AbstractTableRowObject>
     * @throws \Exception
     */
    public function deleteWhereOr(array $wherePairs, $clearCache=true)
    {
        $db = $this->getDb();
        $query = $this->generateDeleteWhereQuery($wherePairs, "OR");
        $result = $db->query($query);

        if ($result === FALSE)
        {
            throw new \Exception("Failed to delete objects, check your where parameters.");
        }

        if ($clearCache)
        {
            $this->emptyCache();
        }

        return mysqli_affected_rows($db);
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
            if (is_array($parameters['in_id']))
            {
                $possibleIds = array();
                $idArray = $parameters['in_id'];

                foreach ($idArray as $idInput)
                {
                    $possibleIds[] = intval($idInput);
                }

                $whereClauses[] = "`id` IN (" . implode(",", $possibleIds) . ")";
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
            "SELECT * " .
            "FROM `" . $this->getTableName() . "` " .
            $whereClause . " " .
            "LIMIT " . $offset . "," . $limit;

        $result = $this->getDb()->query($query);

        if ($result === FALSE)
        {
            throw new \Exception('Error selecting all objects.');
        }

        $constructor = $this->getRowObjectConstructorWrapper();

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
     * @return AbstractUuidTable
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
    abstract public function getFieldsThatAllowNull();


    /**
     * Get the user to specify fields that have default values and thus don't have
     * to be set when creating this object.
     * @return array<string> - array of column names that may be null.
     */
    abstract public function getFieldsThatHaveDefaults();


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
    public abstract function getDb();


    /**
     * Remove the cache entry for an object.
     * This should only happen when objects are destroyed.
     * This will not throw exception/error if id doesn't exist.
     * @param int $objectId - the ID of the object we wish to clear the cache of.
     */
    public function unsetCache($objectId)
    {
        unset($this->m_objectCache[$objectId]);
    }


    /**
     * Completely empty the cache. Do this if a table is emptied etc.
     */
    public function emptyCache()
    {
        $this->m_objectCache = array();
    }


    /**
     * Fetch an object from our cache.
     * @param int $uuid - the id of the row the object represents.
     * @return AbstractTableRowObject
     */
    protected function getCachedObject(string $uuid)
    {
        if (!isset($this->m_objectCache[$uuid]))
        {
            throw new \Exception("There is no cached object");
        }

        return $this->m_objectCache[$uuid];
    }


    /**
     * Update our cache with the provided object.
     * Note that if you simply changed the object's ID, you will need to call unsetCache() on
     * the original ID.
     * @param \Programster\MysqlObjects\AbstractTableRowObject $object
     */
    protected function updateCache(AbstractUuidTableRowObject $object)
    {
        $this->m_objectCache[$object->getUuid()] = $object;
    }


    /**
     * Helper function that converts a query result into a collection of the row objects.
     * @param \mysqli_result $result
     * @return array<AbstractTableRowObject>
     */
    protected function convertMysqliResultToObjects(\mysqli_result $result)
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
                $this->updateCache($loadedObject);
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
    protected function generateSelectWhereQuery(array $wherePairs, $conjunction)
    {
        // if provided uuid in hex format, convert to binary for mysql.
        if (isset($wherePairs['uuid']) && UuidLib::isBinary($wherePairs['uuid']))
        {
            $wherePairs['uuid'] = UuidLib::convertHexToBinary($wherePairs['uuid']);
        }

        $query = "SELECT * FROM `" . $this->getTableName() . "` " .
                $this->generateWhereClause($wherePairs, $conjunction);

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
    protected function generateDeleteWhereQuery(array $wherePairs, $conjunction)
    {
        $query = "DELETE FROM `" . $this->getTableName() . "` " .
                $this->generateWhereClause($wherePairs, $conjunction);

        return $query;
    }


    /**
     * Generate the "where" part of a query based on name/value pairs and the provided conjunction
     * @param array $wherePairs - column/value pairs for where clause. Value may or may not be an
     *                            array list of values for WHERE IN().
     * @param string $conjunction - one of "AND" or "OR" for if all/any of criteria need to be met
     * @return string - the where clause of a query such as "WHERE `id`='3'"
     */
    protected function generateWhereClause($wherePairs, $conjunction)
    {
        $db = $this->getDb();
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
            // perform a last second check to make sure uuid is in binary form for mysql
            if ($attribute === 'uuid')
            {
                if (is_array($searchValue))
                {
                    foreach ($searchValue as $index => $uuidValue)
                    {
                        if (UuidLib::isBinary($uuidValue) === FALSE)
                        {
                            $searchValue[$index] = UuidLib::convertHexToBinary($uuidValue);
                        }
                    }
                }
                else
                {
                    if (UuidLib::isBinary($searchValue) === FALSE)
                    {
                        $searchValue = UuidLib::convertHexToBinary($searchValue);
                    }
                }
            }

            $whereString = "`" . $attribute . "` ";

            if (is_array($searchValue))
            {
                if (count($searchValue) === 0)
                {
                    $whereString = "FALSE";
                }
                else
                {
                    $escapedSearchValues = \Programster\MysqliLib\MysqliLib::escapeValues($searchValue, $db);
                    $searchValueWrapped = \Programster\CoreLibs\ArrayLib::wrapElements($escapedSearchValues, "'");
                    $whereString .= " IN(" . implode(",", $searchValueWrapped)  . ")";
                }
            }
            else
            {
                $whereString .= " = '" . $db->escape_string($searchValue) . "'";
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