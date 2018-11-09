<?php

namespace Coded\Database;

use Coded\Exception\MaintenanceException;
use mysql_xdevapi\Exception;

class Maintenance
{
    private $db;
    private $currentVersion;
    private $maintenanceFile = '';
    private $updateFolder;
    const SETTINGS_TABLE = 'settings';
    const MAINTENANCE_FILE = '.maintenance';

    function __construct(Controller $db, $updateFolder = DATABASE_UPDATE_FOLDER)
    {
        $updateFolder = rtrim($updateFolder, DIRECTORY_SEPARATOR);
        if(!file_exists($updateFolder)) throw new MaintenanceException("Update folder '$updateFolder' doesn't exists.");

        $this->db = $db;
        $this->updateFolder = rtrim($updateFolder,'/');
        $this->maintenanceFile = $this->updateFolder.'/'.static::MAINTENANCE_FILE.'.'.$this->db->getDatabaseName();
        $this->currentVersion = $this->getVersion();
    }

    function getVersion()
    {
        if(!$this->db->q("SHOW TABLES LIKE '".static::SETTINGS_TABLE."'" )) return 0;
        if(!$result = $this->db->q("select `value` from `settings` where `key` = 'database_version'")) return 0;
        return $this->currentVersion = $result[0]->value;
    }

    function updateAvailable()
    {
        $nextVersion = $this->getVersion()+1;
        $file = $this->updateFolder.'/'.$nextVersion;
        if(file_exists($file.'.sql') or file_exists($file.'.php')) return $nextVersion;
        return false;
    }

    function doUpdate($version)
    {
        try{
            if(file_exists($sqlFile = $this->updateFolder.'/'.$version.'.sql')){
                return $this->db->import($sqlFile, MaintenanceException::class);
            }elseif(file_exists($phpFile = $this->updateFolder.'/'.$version.'.php')){
                return static::runPHP($phpFile, $this->db);
            }
        }catch(\Exception $e){
            throw new MaintenanceException($e->getMessage());
        }
        return false;
    }

    static function runPHP($phpFile, Controller $db)
    {
        require $phpFile;
        return true;
    }

    function enterMaintenanceMode($info = ''){
        if($this->isMaintenanceMode()) return true;

        if(!file_put_contents($this->maintenanceFile, implode(' ', array_filter([date('d-m-Y H:i:s'), $info])))){
            throw new MaintenanceException("I can't enter maintenance mode. Unable to create the maintenance file at '$this->maintenanceFile'");
        }
        return true;
    }

    function exitMaintenanceMode(){
        if($this->isMaintenanceMode() and !unlink($this->maintenanceFile)){
            throw new MaintenanceException("I can't exit the maintenance mode. Unable to delete the maintenance file at '$this->maintenanceFile'");
        }
        return true;
    }

    function isMaintenanceMode(){
        return file_exists($this->maintenanceFile);
    }

    function upgrade($dumb = false, array $excludeTables = []){
        $updated = false;
        while($version = $this->updateAvailable()){
            $preVersion = $version-1;
            $this->enterMaintenanceMode('from version: '.$preVersion);
            if($dumb) $this->db->dump(date('Y.m.d.H.i.s').'.'.$preVersion, $excludeTables);
            if(!$this->doUpdate($version)) break;
            $this->db->q('update `settings` set `value` = ? where `key` = "database_version"', $version);
            $updated = $version;
        }
        $this->exitMaintenanceMode();
        return $updated;
    }
}