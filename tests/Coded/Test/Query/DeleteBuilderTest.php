<?php

namespace Coded\Test\Query;

use Coded\TestHelper\TestCase\QueryTestCase;

class DeleteBuilderTest extends QueryTestCase{

    public function testDelete(){
        $res = $this->db->delete($this->table, 1);
        $this->assertInternalType('int', $res);
        $this->assertEquals(1, $res);
    }

    public function testDeleteNotFound(){
        $res = $this->db->delete($this->table, 4);
        $this->assertInternalType('int', $res);
        $this->assertEquals(0, $res);
    }
}