<?php

namespace Coded\Test\Query;

use Coded\TestHelper\TestCase\QueryTestCase;

class MathTest extends QueryTestCase{

    public function testMax(){
        $res = $this->db->max('id', $this->table);
        $this->assertInternalType('string', $res);
        $this->assertEquals(3, $res);
    }

    public function testMin(){
        $res = $this->db->min('id', $this->table);
        $this->assertInternalType('string', $res);
        $this->assertEquals(1, $res);
    }

    public function testSum(){
        $res = $this->db->sum('id', $this->table);
        $this->assertInternalType('string', $res);
        $this->assertEquals(6, $res);
    }

    public function testAvg(){
        $res = $this->db->avg('id', $this->table);
        $this->assertInternalType('string', $res);
        $this->assertEquals(2, $res);
    }

    public function testCount(){
        $res = $this->db->count($this->table);
        $this->assertInternalType('string', $res);
        $this->assertEquals(3, $res);
    }

}