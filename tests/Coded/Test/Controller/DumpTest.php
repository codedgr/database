<?php

namespace Coded\Test\Controller;

use Coded\TestHelper\TestCase\ControllerTestCase;

class DumpTest extends ControllerTestCase
{

    public function testQuery()
    {
        $file = $this->db->dump();
        $this->assertFileExists($file);
        unlink($file);
    }

}