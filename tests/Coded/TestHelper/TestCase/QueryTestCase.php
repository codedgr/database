<?php

namespace Coded\TestHelper\TestCase;

use Coded\Database\Query;

class QueryTestCase extends ControllerTestCase
{

    /** @var Query */
    protected $db;

    public function initDB(){
        $this->db = new Query($this->config['database'], $this->config['username'], $this->config['password'], $this->config['host'], $this->config['port']);
    }
}