<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class UserTable extends \Programster\MysqlObjects\AbstractTable
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
        return 'UserRecord';
    }

    public function getTableName()
    {
        return 'user';
    }

    public function validateInputs(array $data): array
    {
        return $data;
    }
}

