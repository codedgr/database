<?php

namespace Coded\TestHelper\TestCase;

use Coded\Database\Controller;
use Coded\Database\Query;
use PHPUnit\Framework\TestCase;

class ControllerTestCase extends TestCase
{

    /** @var Controller */
    protected $db;

    protected $config = [];

    protected $table = 'phpunit_test';

    public function setUp()
    {
        $this->config = parse_ini_file(DATABASE_CONFIG_PATH."/config.ini");

        $this->initDB();
        $this->db->query("DROP TABLE IF EXISTS `{$this->table}`;
            CREATE TABLE `{$this->table}` (
              `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
              `name` varchar(255) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            INSERT INTO `{$this->table}` VALUES ('1', 'one');
            INSERT INTO `{$this->table}` VALUES ('2', 'two');
            INSERT INTO `{$this->table}` VALUES ('3', 'three');
        ");
    }

    public function tearDown()
    {
        $this->db->query("DROP TABLE {$this->table}");
        $this->db = null;
    }

    public function initDB(){
        $this->db = new Query($this->config['database'], $this->config['username'], $this->config['password'], $this->config['host'], $this->config['port']);
    }
}