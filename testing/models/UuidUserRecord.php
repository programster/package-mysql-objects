<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class UuidUserRecord extends Programster\MysqlObjects\AbstractUuidTableRowObject
{
    protected $m_email;
    protected $m_name;


    public function __construct(array $row)
    {
        $this->initializeFromArray($row);
    }


    protected function getAccessorFunctions(): array
    {
        return array(
            'name'  => function() { return $this->m_name; },
            'email' => function() { return $this->m_email; },
        );
    }


    protected function getSetFunctions(): array
    {
        return array(
            'name'  => function($x) { $this->m_name = $x; },
            'email' => function($x) { $this->m_email = $x; },
        );
    }


    public function getTableHandler(): \Programster\MysqlObjects\TableInterface
    {
        return new UuidUserTable();
    }


    # Accessors
    public function getName() { return $this->m_name; }
    public function getEmail() { return $this->m_email; }
}