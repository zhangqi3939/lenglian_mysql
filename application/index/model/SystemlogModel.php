<?php
namespace app\index\model;
use think\Model;
use think\Db;
use app\index\model\UserModel;
class SystemlogModel extends Model
{
    //登录日志
    function getLoginLog($params,$category=0){
        $page = $params['page'] > 1 ? $params['page'] : 1;
        //$perPage = empty($params['perPage']) ? 10 : $params['perPage'];
        $offset = empty($params['perPage']) ? 10 : $params['perPage'];
        $perPage = ($params['page'] - 1) * $offset + 1;
        $where = '1=1 ';
        //总数
        if($params['USER_LEVEL'] == 2){//管理员

        }elseif($params['USER_LEVEL'] == 1){//agent代理商
           // $this->db->where('u.agentID',$params->agentID);
            $where .= " and U.AGENT_ID = ".$params['AGENT_ID'];
        }elseif($params['USER_LEVEL'] == 0) {//client客户
            //$this->db->where('u.clientID',$params->clientID);
                $where .= " and U.CLIENT_ID = ".$params['CLIENT_ID'];
        }
        $total = Db::name('log')
            ->alias('L')
            ->join('user U','L.USER_ID = U.ID','left')
            ->field('L.*,U.USER_NAME,U.REAL_NAME')
            ->where('category',$category)
            ->where("L.INSERT_TIME >='".$params['startStamp']."'")
            ->where("L.INSERT_TIME <='".$params['endStamp']."'")
            ->where($where)
            ->select();
        $total = count($total);
        //结果
        if($params['USER_LEVEL'] == 2){//管理员

        }elseif($params['USER_LEVEL'] == 1){//agent代理商
            // $this->db->where('u.agentID',$params->agentID);
            $where .= " and U.AGENT_ID = ".$params['AGENT_ID'];
        }elseif($params['USER_LEVEL'] == 0) {//client客户
            //$this->db->where('u.clientID',$params->clientID);
            $where .= " and U.CLIENT_ID = ".$params['CLIENT_ID'];
        }
        //$select = "l.*,u.userName,u.realName";
        $dataList = Db::name('log')
            ->alias('L')
            ->join('user U','L.USER_ID = U.ID','left')
            ->field('L.*,U.USER_NAME,U.REAL_NAME')
            ->where('category',$category)
            ->where("L.INSERT_TIME >='".$params['startStamp']."'")
            ->where("L.INSERT_TIME <='".$params['endStamp']."'")
            ->limit($perPage,$offset)
            ->order('INSERT_TIME desc')
            ->where($where)
            ->select();
        $dataList = arraykeyToLower($dataList);
        $result = array('total'=>$total,'dataList'=>$dataList);
        return $result;
    }
    //操作日志
    function getSystemLog($params,$category=1){
        $page = $params['page'] > 1 ? $params['page'] : 1;
        //$perPage = empty($params['perPage']) ? 10 : $params['perPage'];
        $offset = empty($params['perPage']) ? 10 : $params['perPage'];
        $perPage = ($params['page'] - 1) * $offset + 1;
        $where = '1=1 ';
        //总数
        if($params['USER_LEVEL'] == 2){//管理员

        }elseif($params['USER_LEVEL'] == 1){//agent代理商
            // $this->db->where('u.agentID',$params->agentID);
            $where .= " and U.AGENT_ID = ".$params['AGENT_ID'];
        }elseif($params['USER_LEVEL'] == 0) {//client客户
            //$this->db->where('u.clientID',$params->clientID);
            $where .= " and U.CLIENT_ID = ".$params['CLIENT_ID'];
        }
        $total = Db::name('log')
            ->alias('L')
            ->join('user U','L.USER_ID = U.ID','left')
            ->field('L.*,U.USER_NAME,U.REAL_NAME')
            ->where('category',$category)
            ->where("L.INSERT_TIME >='".$params['startStamp']."'")
            ->where("L.INSERT_TIME <='".$params['endStamp']."'")
            ->where($where)
            ->select();
        $total = count($total);
        //结果
        if($params['USER_LEVEL'] == 2){//管理员

        }elseif($params['USER_LEVEL'] == 1){//agent代理商
            // $this->db->where('u.agentID',$params->agentID);
            $where .= " and U.AGENT_ID = ".$params['AGENT_ID'];
        }elseif($params['USER_LEVEL'] == 0) {//client客户
            //$this->db->where('u.clientID',$params->clientID);
            $where .= " and U.CLIENT_ID = ".$params['CLIENT_ID'];
        }
        $dataList = Db::name('log')
            ->alias('L')
            ->join('user U','L.USER_ID = U.ID','left')
            ->field('L.ID,L.INSERT_TIME,L.USER_IP,L.REMARKS AS OPERATION,L.OBJ_NAME AS OBJECT_ID,U.USER_NAME AS NAME,U.REAL_NAME')
            ->where('category',$category)
            ->where("L.INSERT_TIME >='".$params['startStamp']."'")
            ->where("L.INSERT_TIME <='".$params['endStamp']."'")
            ->limit($perPage,$offset)
            ->order('INSERT_TIME desc')
            ->where($where)
            ->select();
        if(!empty($dataList)){
            foreach ($dataList as $row) {
                $row['insert_time'] = strtotime($row['INSERT_TIME']);
            }
        }
        $dataList = arraykeyToLower($dataList);
        $result = array('total'=>$total,'dataList'=>$dataList);
        return $result;
    }
    //控制日志
    function getCmdLog($params){
        $page = $params['page'] > 1 ? $params['page'] : 1;
        //$perPage = empty($params['perPage']) ? 10 : $params['perPage'];
        $offset = empty($params['perPage']) ? 10 : $params['perPage'];
        $perPage = ($params['page'] - 1) * $offset + 1;
        $where = '1=1 ';
        //总数
        if($params['USER_LEVEL'] == 2){//管理员

        }elseif($params['USER_LEVEL'] == 1){//agent代理商
            $where .= " and U.AGENT_ID = ".$params['AGENT_ID'];
        }elseif($params['USER_LEVEL'] == 0) {//client客户
            $where .= " and U.CLIENT_ID = ".$params['CLIENT_ID'];
        }
        $total = Db::name('cmd_log')
            ->alias('L')
            ->field('L.*,U.USER_NAME,U.REAL_NAME')
            ->join('user U','L.USER_ID = U.ID','left')
            ->join('container C','L.BOX_ID = C.BOX_ID','left')
            ->where("L.INSERT_TIME between ".$params['startStamp']." and ".$params['endStamp'])
            ->where($where)
            ->select();
        $total = count($total);
        if($params['USER_LEVEL'] == 2){//管理员

        }elseif($params['USER_LEVEL'] == 1){//agent代理商
            $where .= " and U.AGENT_ID = ".$params['AGENT_ID'];
        }elseif($params['USER_LEVEL'] == 0) {//client客户
            $where .= " and U.CLIENT_ID = ".$params['CLIENT_ID'];
        }
        $select = "l.*,u.userName,u.realName,c.name";
        $dataList = Db::name('cmd_log')
            ->alias('L')
            ->join('user U','L.USER_ID = U.ID','left')
            ->join('container C','L.BOX_ID = C.BOX_ID','left')
            ->where("L.INSERT_TIME between ".$params['startStamp']." and ".$params['endStamp'])
            ->where($where)
            ->limit($perPage,$offset)
            ->select();
        if(!empty($dataList)){
            foreach ($dataList as $row) {
                # code...
                $row['opCode'] = $row['PID'].$row['ADDR'];
                $row['opValue'] = $row['VALUE'];

            }
        }
        $dataList = arraykeyToLower($dataList);
        $result = array('total'=>$total,'dataList'=>$dataList);
        return $result;
    }
}