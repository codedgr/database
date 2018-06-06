<?php

namespace Coded\Test\Controller;

use Coded\TestHelper\TestCase\ControllerTestCase;

class InsertTest extends ControllerTestCase
{

    public function testInsert()
    {
        $res = $this->db->q("insert into `{$this->table}` (`name`) values (?)", ['bill']);
        $this->assertInternalType('string', $res);
        $this->assertEquals(4, $res);
    }

    public function testInsertWithKeys()
    {
        $res = $this->db->q("insert into `{$this->table}` (`name`) values (:firstName)", ['firstName' => 'bill']);
        $this->assertInternalType('string', $res);
        $this->assertEquals(4, $res);
    }

}