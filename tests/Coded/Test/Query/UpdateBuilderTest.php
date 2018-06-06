<?php

namespace Coded\Test\Query;

use Coded\TestHelper\TestCase\QueryTestCase;

class UpdateBuilderTest extends QueryTestCase{

    public function testUpdate(){
        $res = $this->db->update($this->table, [
            'name' => 'hello man'
        ]);
        $this->assertInternalType('int', $res);
        $this->assertEquals(3, $res);
    }

    public function testUpdateWithWhere(){
        $res = $this->db->update($this->table, [
            'name' => 'hello man'
        ],[
            'id' => 3
        ]);
        $this->assertInternalType('int', $res);
        $this->assertEquals(1, $res);
    }

    public function testUpdateNotFound(){
        $res = $this->db->update($this->table, [
            'name' => 'hello man'
        ], [
            'id' => 4
        ]);
        $this->assertInternalType('int', $res);
        $this->assertEquals(0, $res);
    }

    public function testUpdateWithWhereSecond(){
        $res = $this->db->update($this->table, [
            'name' => 'hello man'
        ],2);
        $this->assertInternalType('int', $res);
        $this->assertEquals(1, $res);
    }

    public function testUpdateWithWhereThird(){
        $res = $this->db->update($this->table, [
            'name' => 'hello man'
        ],'id=2');
        $this->assertInternalType('int', $res);
        $this->assertEquals(1, $res);
    }

    public function testUpdateWithWhereAndLimit(){
        $res = $this->db->update($this->table, [
            'name' => 'hello man'
        ],[
            'id' => ['>=',2]
        ]);
        $this->assertInternalType('int', $res);
        $this->assertEquals(2, $res);
    }
}