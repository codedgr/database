<?php

namespace Coded\Test\Controller;

use Coded\TestHelper\TestCase\ControllerTestCase;

class SelectTest extends ControllerTestCase
{

    public function testQuery()
    {
        $res = $this->db->q("SELECT * from `{$this->table}`");
        $this->assertInternalType('array', $res);
        $this->assertCount(3, $res);
        foreach ($res as $row) {
            $this->assertInternalType('object', $row);
            $this->assertObjectHasAttribute('id', $row);
            $this->assertObjectHasAttribute('name', $row);
        }
    }

    public function testQueryWithWhereSingle()
    {
        $res = $this->db->q("SELECT * from `{$this->table}` where id = ?", 1);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        foreach ($res as $row) {
            $this->assertInternalType('object', $row);
            $this->assertObjectHasAttribute('id', $row);
            $this->assertObjectHasAttribute('name', $row);
            $this->assertEquals('1', $row->id);
            $this->assertEquals('one', $row->name);
        }
    }

    public function testQueryWithWhere()
    {
        $res = $this->db->q("SELECT * from `{$this->table}` where id < ?", 3);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
        foreach ($res as $row) {
            $this->assertInternalType('object', $row);
            $this->assertObjectHasAttribute('id', $row);
            $this->assertObjectHasAttribute('name', $row);
        }
    }

    public function testQueryWithDoubleWhere()
    {
        $res = $this->db->q("SELECT * from `{$this->table}` where id = ? and name = ?", [1, 'one']);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        foreach ($res as $row) {
            $this->assertInternalType('object', $row);
            $this->assertObjectHasAttribute('id', $row);
            $this->assertObjectHasAttribute('name', $row);
            $this->assertEquals('1', $row->id);
            $this->assertEquals('one', $row->name);
        }
    }

    public function testQueryExceptionInvalidParameterNumber()
    {
        $this->expectException(\PDOException::class);
        $this->db->q("SELECT * from `{$this->table}` where id = ?", [1, 'one']);
    }

    public function testQueryExceptionSyntaxError()
    {
        $this->expectException(\PDOException::class);
        $this->db->q("SELECT from `{$this->table}` where id = ");
    }

}