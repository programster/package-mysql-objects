<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class UuidUserTable extends \iRAP\MysqlObjects\AbstractUuidTable
{
    public function getDb(): \mysqli
    {
        return ConnectionHandler::getDb();
    }
    
    public function getFieldsThatAllowNull(): array
    {
        return array();
    }
    
    
    public function getFieldsThatHaveDefaults(): array
    {
        return array();
    } 
    
    
    public function getObjectClassName() 
    {
        return 'UuidUserRecord';
    }

    public function getTableName() 
    {
        return 'user_uuid_table';
    }

    public function validateInputs(array $data): array 
    {
        return $data;
    }
}

