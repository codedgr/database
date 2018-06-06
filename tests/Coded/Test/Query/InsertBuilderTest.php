<?php

namespace Coded\Test\Query;

use Coded\TestHelper\TestCase\QueryTestCase;

class InsertBuilderTest extends QueryTestCase{

    public function testInsert(){
        $res = $this->db->insert($this->table,[
            'name' => 'hello man'
        ]);
        $this->assertInternalType('string', $res);
        $this->assertEquals(4, $res);
    }

    public function testInsertOnDuplicate(){
        $res = $this->db->insert($this->table,[
            'id' => 3,
            'name' => 'hello man'
        ], [
            'name' => 'hello man'
        ], $stmt);
        $this->assertInternalType('string', $res);
        $this->assertEquals(3, $res);
    }
}