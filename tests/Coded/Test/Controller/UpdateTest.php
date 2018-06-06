<?php

namespace Coded\Test\Controller;

use Coded\TestHelper\TestCase\ControllerTestCase;

class UpdateTest extends ControllerTestCase
{

    public function testUpdate()
    {
        $res = $this->db->q("update `{$this->table}` set `name` = ? where `id` = ?", ['bill', 1]);
        $this->assertInternalType('int', $res);
        $this->assertEquals(1, $res);
    }

    public function testUpdateWithKeys()
    {
        $res = $this->db->q("update `{$this->table}` set `name` = :firstName where `id` = :id", ['firstName' => 'bill', 'id' => 1]);
        $this->assertInternalType('int', $res);
        $this->assertEquals(1, $res);
    }

}