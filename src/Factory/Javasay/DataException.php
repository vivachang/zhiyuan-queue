<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-06-30
 * Time: 17:01
 */
namespace Sprovider90\Zhiyuanqueue\Factory\Javasay;

use Sprovider90\Zhiyuanqueue\Helper\CliHelper;
use Sprovider90\Zhiyuanqueue\Model\Orm;

class DataException implements IDataTrategy
{
    function dealData($redis,$data){
        if(!empty($data)){
            $tmp=[];
            $tmp["project_id"]=$data["projectId"];
            $tmp["device_id"]=$data["deviceId"];
            $tmp["type"]=2;
            $tmp["happen_time"]=$data["timestamp"];
            $tmp["created_at"]=date('Y-m-d H:i:s',time());
            $this->saveToMysql($tmp);
            $this->toMessage($redis,$data["deviceId"]);
        }
        return $this;
    }

    function saveToMysql($data)
    {
        if(!empty($data)){
            $db=new Orm();
            $db->insert("breakdowns",$data);
            CliHelper::cliEcho($db->last());
        }

    }
    function toMessage($redis,$deviceId)
    {
        $arr=[];
        $arr["stage"]=1002;
        $arr["dev_no"]=$deviceId;
        $arr["time"]=date('Y-m-d H:i:s',time());
        CliHelper::cliEcho("DataException ".json_encode($arr));
        $redis->rpush('zhiyuan_database_messagelist',json_encode($arr));
        return ;
    }
}