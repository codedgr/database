<?php

namespace Coded\Test\Query;

use Coded\TestHelper\TestCase\QueryTestCase;

class SelectBuilderTest extends QueryTestCase{

    public function testSelectByName(){
        $res = $this->db->select($this->table, [
            'name' => 'one'
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertInternalType('object', $res[0]);
        $this->assertObjectHasAttribute('id', $res[0]);
        $this->assertObjectHasAttribute('name', $res[0]);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testSelectedValues(){
        $res = $this->db->select($this->table, [
            'name' => 'one'
        ], ['name']);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertInternalType('object', $res[0]);
        $this->assertObjectNotHasAttribute('id', $res[0]);
        $this->assertObjectHasAttribute('name', $res[0]);
    }

    public function testSelectByIdAsInt(){
        $res = $this->db->select($this->table, 1);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertInternalType('object', $res[0]);
        $this->assertObjectHasAttribute('id', $res[0]);
        $this->assertObjectHasAttribute('name', $res[0]);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testSelectNotFound(){
        $res = $this->db->select($this->table, 4);
        $this->assertInternalType('array', $res);
        $this->assertCount(0, $res);
    }

    public function testSelectByIdAsArray(){
        $res = $this->db->select($this->table, [
            'id'=>1
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertInternalType('object', $res[0]);
        $this->assertObjectHasAttribute('id', $res[0]);
        $this->assertObjectHasAttribute('name', $res[0]);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testSelectByIdAsString(){
        $res = $this->db->select($this->table, 'id=1');
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertInternalType('object', $res[0]);
        $this->assertObjectHasAttribute('id', $res[0]);
        $this->assertObjectHasAttribute('name', $res[0]);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testLikeEnd(){
        $res = $this->db->select($this->table, [
            'name'=>'on%'
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertInternalType('object', $res[0]);
        $this->assertObjectHasAttribute('id', $res[0]);
        $this->assertObjectHasAttribute('name', $res[0]);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testLikeStart(){
        $res = $this->db->select($this->table, [
            'name'=>'%ne'
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertInternalType('object', $res[0]);
        $this->assertObjectHasAttribute('id', $res[0]);
        $this->assertObjectHasAttribute('name', $res[0]);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testLikeStartEnd(){
        $res = $this->db->select($this->table, [
            'name'=>'%n%'
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertInternalType('object', $res[0]);
        $this->assertObjectHasAttribute('id', $res[0]);
        $this->assertObjectHasAttribute('name', $res[0]);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testLikeAsArray(){
        $res = $this->db->select($this->table, [
            'name'=>['like', 'on%']
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertInternalType('object', $res[0]);
        $this->assertObjectHasAttribute('id', $res[0]);
        $this->assertObjectHasAttribute('name', $res[0]);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testNotLikeAsArray(){
        $res = $this->db->select($this->table, [
            'name'=>['not like', 'tw%']
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
        $this->assertInternalType('object', $res[0]);
        $this->assertObjectHasAttribute('id', $res[0]);
        $this->assertObjectHasAttribute('name', $res[0]);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testSymbolsLarger(){
        $res = $this->db->select($this->table, [
            'id'=>['>', 1]
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
    }

    public function testSymbolsSmaller(){
        $res = $this->db->select($this->table, [
            'id'=>['<', 3]
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
    }

    public function testSymbolsNotEqual(){
        $res = $this->db->select($this->table, [
            'id'=>['!=', 3]
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
    }

    public function testIn(){
        $res = $this->db->select($this->table, [
            'id'=>['in', [1,2]]
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
    }

    public function testInWithKeys(){
        $res = $this->db->select($this->table, [
            'id'=>['in', ['key'=>1,'second'=>2]]
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
    }

    public function testNotIn(){
        $res = $this->db->select($this->table, [
            'id'=>['not in', [1,2]]
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
    }

    public function testOrderAsString(){
        $res = $this->db->select($this->table, [
            'order' => 'id, name desc'
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(3, $res);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testOrderAsArray(){
        $res = $this->db->select($this->table, [
            'order' => ['id', 'name']
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(3, $res);
        $this->assertEquals(1, $res[0]->id);
    }

    public function testOrderAsArrayBy(){
        $res = $this->db->select($this->table, [
            'order' => ['id'=>'desc', 'name'=>'desc']
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(3, $res);
        $this->assertEquals(3, $res[0]->id);
    }

    public function testLimit(){
        $res = $this->db->select($this->table, [
            'limit' => 1
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
    }

    public function testLimitAsString(){
        $res = $this->db->select($this->table, [
            'limit' => '1,2'
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
    }

    public function testLimitAsArray(){
        $res = $this->db->select($this->table, [
            'limit' => [1,2]
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
    }

    public function testAll(){
        $res = $this->db->select($this->table, [
            'id'=>['not in', [1]],
            'name'=>['like', 'th%'],
            'order' => ['name','id'],
            'limit' => 1
        ]);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
    }

}