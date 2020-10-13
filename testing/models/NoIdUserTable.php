<?php


class NoIdUserTable extends \Programster\MysqlObjects\AbstractNoIdTable
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
        return NoIdUserRecord::class;
    }

    public function getTableName()
    {
        return 'user_no_id_table';
    }

    public function validateInputs(array $data): array
    {
        return $data;
    }
}

