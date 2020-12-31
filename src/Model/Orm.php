<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-07-19
 * Time: 15:09
 */

namespace Sprovider90\Zhiyuanqueue\Model;

use Medoo\Medoo;
use Sprovider90\Zhiyuanqueue\Factory\Config;
use Sprovider90\Zhiyuanqueue\Helper\CliHelper;

class Orm
{
    protected $database;
    public function __construct()
    {
        $configDb=Config::get("Db");
        $this->database = new Medoo([
            'database_type' => 'mysql',
            'database_name' => $configDb["database_name"],
            'server' => $configDb["server"],
            'username' => $configDb["username"],
            'password' => $configDb["password"],
            'port' => $configDb["port"],
            'charset' => 'utf8',
        ]);

    }
    function insert($table_name,$data=[]){
        $this->database->insert($table_name,$data);
        CliHelper::cliEcho($this->last());
        return $this->database->id();
    }
    function insertAll($table_name,$data=[]){
        return $this->database->insert($table_name,$data);
    }
    function getAll($sql){

        return $this->database->query($sql)->fetchAll(2);
    }
    function find($table_name,$feilds="*",$where=[]){
        return $this->database->get($table_name, [
            $feilds
        ], $where);
    }
    function del($table_name,$where=[]){
        return $this->database->delete($table_name,$where);
    }
    function last(){
        return $this->database->last();
    }
    function update($table_name,$data=[],$where=[]){
        return $this->database->update($table_name, $data, $where);
    }
}