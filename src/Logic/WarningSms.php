<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-07-10
 * Time: 13:13
 */

namespace Sprovider90\Zhiyuanqueue\Logic;
use Sprovider90\Zhiyuanqueue\Helper\CliHelper;
use Sprovider90\Zhiyuanqueue\Model\Orm;

/**
 * Class WarningSms
 * @package Sprovider90\Zhiyuanqueue\Logic
 * 根据java方写入磁盘的数据来判断预警消息是否触发
 * 根据laravel任务写的项目每个阶段的预警监控指标
 */
class WarningSms implements Icommand
{

    protected $zhibaos=["humidity","temperature","formaldehyde","PM25","CO2","PM10","TVOC","PM1"];
    function run(){

        $doeds = array();
        $dirpath = "./../testdata/yingjian/";
        //$rundate=date('Ymd')="20200710";
        $rundate="20200710";
        $dirpath .= $rundate;

        while (true) {
            if(date('Ymd')>$rundate){
//                CliHelper::cliEcho($rundate."WarningSms任务处理完成");
//                exit();
            }
            if (!is_dir($dirpath)) {
                CliHelper::cliEcho("当前目录下，目录 " . $dirpath . " 不存在 线程休眠1秒");
                usleep(1000 * 1000);
                continue;

            }
            $allfiles = scandir($dirpath);

            $files = array_diff($allfiles, $doeds);//差集
            $doeds = $allfiles;

            foreach ($files as $file) {
                $file = $dirpath . '/' . $file;
                if (is_file($file)) {

                    $start_time = microtime(true);
                    $filecontent=file_get_contents($file);
                    $json_arr=json_decode($filecontent,true);
                    if(empty($json_arr)){
                        CliHelper::cliEcho($file." content not is jsonData");
                    }
                    $this->deal($json_arr,$file);
                    $endTime = microtime(true);
                    $runTime = round($endTime - $start_time,6) * 1000;
                    CliHelper::cliEcho("runtime-".$runTime);
                }

            }
            CliHelper::cliEcho("no new file come");
            usleep(1000 * 100);
        }

    }

    /**
     * 1.入库
     * 2.发送到消息服务
     */
    public function deal($yingjian,$file){
        $file=str_replace("yingjian","prokz",$file);
        while(!is_file($file)){
            CliHelper::cliEcho("no new prokz file come sleep 1s");
            sleep(1);
        }

        $kzs=file_get_contents($file);
        $kzarr=json_decode($kzs);
        $kzarr=array_column($kzarr,"thresholdinfo","project_id");
        $this->dealKzData($kzarr);
        $this->mergeData($kzarr,$yingjian);
        $points=$this->getTriggerPonits($yingjian);

        $this->saveToMysql($points);


    }

    function saveToMysql($data)
    {
        $data=$this->TurnDataToMysql($data);
        if(!empty($data)){
            $db=new Orm();
            $db->insert("warnigs",$data);
        }

    }
    function TurnDataToMysql($data)
    {
        $result=[];
        if(!empty($data)){
            foreach ($data as $k=>$v) {
                $tmp=[];
                $tmp_threshold_keys="";

                foreach ($v as $v_k=>$v_v) {

                    if($v_k==0){
                        $tmp["project_id"]=$v_v["projectId"];
                        $tmp["point_id"]=$v_v["monitorId"];
                        $tmp["waring_time"]=$v_v["timestamp"];
                        $tmp["created_at"]=date('Y-m-d H:i:s',time());
                        $tmp["originaldata"]=json_encode($v);
                    }

                    $tmp_threshold_keys.=$v_v["trigger_zhibiao"].",";

                }
                $tmp["threshold_keys"]=substr($tmp_threshold_keys,0,-1);
                $result[]=$tmp;
            }
        }
        return $result;
    }
    function dealKzData(&$kzarr)
    {
        foreach ($kzarr as $k=>&$v) {
            $tmparr=json_decode($v,true);
            foreach ($tmparr as $tmparrk=>&$tmparrv) {
                $tmparrv=explode("~",$tmparrv)[1];
            }
            $v=$tmparr;

        }
    }
    function mergeData($kzarr,&$yingjian)
    {
        foreach ($yingjian as $k=>&$v){
            foreach ($this->zhibaos as $k_zhibiao =>$v_zhibiao){
                $v["proTrigger_".$v_zhibiao]=NULL;
            }

            if(isset($kzarr[$v["projectId"]])){

                foreach ($kzarr[$v["projectId"]] as $kz_k=>$kz_v) {
                    $v["proTrigger_".$kz_k]=$kz_v;
                }
            }
        }
    }
    function getTriggerPonits($yingjian){

        $result=[];
        if(!empty($yingjian)){
            foreach ($yingjian as $yingjian_k=>$yingjian_v) {
                foreach ($yingjian_v as $k => $v) {
                    //触发预警消息列表
                    if (in_array($k, $this->zhibaos) && $yingjian_v["proTrigger_" . $k] !== NULL && $yingjian_v[$k] >= $yingjian_v["proTrigger_" . $k]) {

                        $result[$yingjian_v["projectId"]."-".$yingjian_v["monitorId"]][] = array_merge($yingjian_v, ["trigger_zhibiao" => $k]);
                    }
                }
            }
        }
        return $result;
    }
}