<?php

/**
 * The table handler is an object for interfacing with a table rather than a row.
 * This can be returned from a ModelObject from a static method. Thus if the programmer wants
 * to fetch a resource using the ModelObject definition, they would do:
 * MyModelName::getTableHandler()->load($id);
 * This is allows the developer to treat the model as an object that represents a row in the table
 */

namespace iRAP\MysqlObjects;


interface TableInterface
{
    /**
     * Return the singleton instance of this object.
     */
    public static function getInstance();
    
    
    /**
     * Return the name of this table.
     */
    public function getTableName();
    
    
    /**
     * Get a connection to the database.
     * @return \mysqli
     */
    public function getDb();
    
    
    /**
     * Removes the obeject from the mysql database. 
     * @return void
     */
    public function delete($id);
    
    
    /**
     * Deletes all rows from the table by running TRUNCATE.
     */
    public function deleteAll();
    
    
    /**
     * Loads all of these objects from the database.
     * @param void
     * @return 
     */
    public function loadAll();
    
    
    /**
     * Loads a single object of this class's type from the database using the unique ID of the row. 
     * @param int id - the id of the row in the datatabase table.
     * @param bool useCache - optionally set to false to force a database lookup even if we have a
     *                    cached value from a previous lookup.
     * @return AbstractTableRowObject - the loaded object.
     */
    public function load($id, $useCache=true);
    
    
    /**
     * Loads a range of data from the table.
     * It is important to note that offset is not tied to ID in any way. 
     * @param type $offset
     * @param type $numElements
     * @return array<AbstractTableRowObject>
     */
    public function loadRange($offset, $numElements);
    
    
    /**
     * Create a new row with unfiltered data.
     * @return AbstractTableRowObject
     */
    public function create(array $inputs);
    
    
    /**
     * Replace a row by id.
     */
    public function replace(array $row);
    
    
    /**
     * Update a specified row with inputs
     * @return AbstractTableRowObject
     */
    public function update($id, array $row);
    
    
    /**
     * Search the table for items and return any matches as objects.
     * @param array $parameters
     * @return type
     * @throws \Exception
     */
    public function search(array $unfilteredParameters);
    
    
    /**
     * Take a given array of USER PROVIDED data and validate it.
     * This is where you would check that the provided date is the correct type such as an int
     * instead of a string, and possibly run more advanced logic to ensure a date was in UK format
     * instead of american format
     * WARNING - Do NOT perform mysqli escaping here as that is performed at the last possible 
     * moment in the save method. 
     * This is a good point to throw exceptions if someone has provided  a string when expecting a 
     * boolean etc.
     * @return array - the validated inputs
     */
    public function validateInputs(Array $data);
}
