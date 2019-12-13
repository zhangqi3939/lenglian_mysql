<?php
use think\Db;
use app\index\model\UserModel;
/*
 * 公共函数
 * phoebus
 * 2019.10.17
 * */

function curl_post($url,$pdata,$json=FALSE){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($json){

    }
    // post数据
    curl_setopt($ch, CURLOPT_POST, 1);
    // post的变量
    curl_setopt($ch, CURLOPT_POSTFIELDS, $pdata);
    $output = curl_exec($ch);
    //打印获得的数据
    curl_close($ch);
    return $output;
}

function curl_get($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);// 要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_HEADER,0);//不要http header 加快效率
    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_TIMEOUT,20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function send_json($data){
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function app_send($data='',$code='200',$reason=''){
    send_json(array(
        'code'=>$code,
        'reason'=>$reason,
        'result'=>$data
    ));
}

function app_send_400($reason=''){
    app_send('',400,$reason);
    exit();
}
//获取IP
function getIP(){
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $cip = $_SERVER["HTTP_CLIENT_IP"];
    }elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
        $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    }elseif(!empty($_SERVER["REMOTE_ADDR"])){
        $cip = $_SERVER["REMOTE_ADDR"];
    }else{
        $cip = "无法获取！";
    }
    return $cip;
}
/*
权限判断，判断三个权限得级别是否大于等于目标权限
如果$strict=1,则判断三个权限是否严格等于目标权限
*/
function checkRights($userLevel=-1,$isAdmin=-1,$conOption=-1,$strict = 0){
   /* $sessionLevel = $this->session->userLevel;
    $sessionAdmin = $this->session->isAdmin;
    $sessionOption = $this->session->conOption;
    $disAllow = 0;
    if($strict <= 0){//高权限允许
        $userLevel > -1 && $sessionLevel < $userLevel && $disAllow = 1;
        $isAdmin > -1 && $sessionAdmin < $isAdmin && $disAllow = 1;
    }else{//必须等于权限，例如客户的操作不让更高权限的代理商操作，
        $sessionLevel > -1 && $sessionLevel < $userLevel && $disAllow = 1;
        $sessionAdmin > -1 && $sessionAdmin < $isAdmin && $disAllow = 1;
    }
    if($disAllow){
        app_send('',400,'您不能执行此操作');
        exit();
    }*/
}
//报警部分
function alarmType($code,$cat){
    switch($code){
        case 't1':
            if($cat==1){
                return '温度1过高';
            }else if($cat == 2){
                return '温度1过低';
            }
            break;
        case 't2':
            if($cat==1){
                return '温度2过高';
            }else if($cat == 2){
                return '温度2过低';
            }
            break;
        case 't3':
            if($cat==1){
                return '温度3过高';
            }else if($cat == 2){
                return '温度3过低';
            }
            break;
        case 't4':
            if($cat==1){
                return '温度4过高';
            }else if($cat == 2){
                return '温度4过低';
            }
            break;
        case 'h1':
            if($cat==1){
                return '湿度过高';
            }else if($cat == 2){
                return '湿度过低';
            }
            break;
        case 'o1':
            if($cat==1){
                return '油位过高';
            }else if($cat == 2){
                return '油位过低';
            }
            break;
        case 'v1':
            if($cat==1){
                return '电压过高';
            }else if($cat == 2){
                return '电压过低';
            }
            break;
        case 'v2':
            if($cat==1){
                return '电压过高';
            }else if($cat == 2){
                return '电压过低';
            }
            break;
    }
}
/*
  * 将数组中，大写的键名转换为小写
  */
function arraykeyToLower( $data = array() )
{
    if( !empty($data) )
    {
        //一维数组
        if(count($data) == count($data,1))
        {
            $data = array_change_key_case($data , CASE_LOWER);
        }
        else    //二维数组
        {
            foreach( $data as $key => $value )
            {
                $data[$key] = array_change_key_case($value , CASE_LOWER);
            }
        }
    }
    return $data;
}
//存入日志
function saveToLog($params=array()){
    $remarks = '';
    switch ($params['op']) {
        case 'group_add':
            $remarks = '分添加组';
            break;
        case 'group_edit':
            $remarks = '分组修改';
            break;
        case 'group_delete':
            $remarks = '分删除组';
            break;
        case 'alarm_setting':
            $remarks = '报警设置';
            break;
        case 'travel_add':
            $remarks = '行程添加';
            break;
        case 'travel_edit':
            $remarks = '行程修改';
            break;
        case 'travel_delete':
            $remarks = '行程删除';
            break;
        case 'device_add':
            $remarks = '设备添加';
            break;
        case 'device_edit':
            $remarks = '设备修改';
            break;
        default:
            break;
    }
    $d=array(
        'CATEGORY'=>1,
        'USER_ID'=>$params['UID'],
        'USER_IP'=>getIP(),
        'BOX_ID'=>$params['objID'],
        'OBJ_NAME'=>$params['objName'],
        'REMARKS'=>$remarks,
        'INSERT_TIME'=>time()
    );
    //$this->db->insert('log',$d);
   Db::name('log')->insert($d);
}
//保存设备到分组
function saveBoxToGroup($groupID,$boxs,$exclusive=0){
    if(!empty($boxs)){
        if($exclusive == 1) {
            //$this->db->set('groupID',-1)->where('groupID',$groupID)->update('container');
            //$result = Db::name('container')->where('GROUP_ID',$groupID)->fetchSql(true)->update(array('GROUP_ID'=>-1));
            Db::name('container')->where('GROUP_ID',$groupID)->update(array('GROUP_ID'=>-1));
        }
        $result = Db::name('container')->where('BOX_ID', 'in', $boxs)->update(array('GROUP_ID' => $groupID));

        if ($result == 0){
            return 0;
        }else{
            return 1;
        }
    }
    return 0;
}
/*
    更新或者插入内容
    如果数据库中存在数据就更新，没有就插入
*/
function updateOrInsert($table,$whereArr,$dataArray){
    //$rownum=$this->db->from($table)->where($whereArr)->get()->num_rows();
    $rownum = Db::name($table)->where($whereArr)->select();
    //$dataArray['insert_time']=time();
    if($rownum){
        //$this->db->where($whereArr)->update($table,$dataArray);
        Db::name($table)->where($whereArr)->update($dataArray);
    }else{
        //$this->db->insert($table,$dataArray);
        Db::name($table)->insert($dataArray);
    }
}function formatTime($time=0,$stamp=1,$leavespace=0){
    if(empty($time)&&$leavespace>0) return '';
    $timeStamp=$time;
    $theTime='';
    switch($stamp){
        case 1://yyyy-mm-dd
            $theTime=date('Y-m-d',$timeStamp);
            break;
        case 2://yyyy-mm-dd hh:mm:ss
            $theTime=date('Y-m-d H:i:s',$timeStamp);
            break;
        case 3://mm-dd
            $theTime=date('m-d',$timeStamp);
            break;
        case 4://mm-dd H:i
            $theTime=date('m-d H:i',$timeStamp);
            break;
        case 9://m月d日
            $theTime=date('n月j日',$timeStamp);
            break;
        case 10://mm-dd-yyyy hh:mm:ss,倒计时timer格式
            $theTime=date('m-d-Y H:i:s',$timeStamp);
            break;
        default:
            $theTime=date('Y-m-d',$timeStamp);
    }
    return $theTime;
}
function formatDate($dateTime='',$stamp=1,$leavespace=0){
    if(empty($dateTime)&&$leavespace>0) return '';
    $timeStamp=$dateTime==''?time():strtotime($dateTime);
    $theTime='';
    switch($stamp){
        case 1://yyyy-mm-dd
            $theTime=date('Y-m-d',$timeStamp);
            break;
        case 2://yyyy-mm-dd hh:mm:ss
            $theTime=date('Y-m-d H:i:s',$timeStamp);
            break;
        case 3://mm-dd
            $theTime=date('m-d',$timeStamp);
            break;
        case 9://m月d日
            $theTime=date('n月j日',$timeStamp);
            break;
        case 10://mm-dd-yyyy hh:mm:ss,倒计时timer格式
            $theTime=date('m-d-Y H:i:s',$timeStamp);
            break;
        default:
            $theTime=date('Y-m-d',$timeStamp);
    }
    return $theTime;
}
function C2F($t){
    if($t== -999 || $t == '-') return '-';
    return sprintf("%01.1f",$t*1.8 + 32);
}
//将php整数封装成字符串(二进制,4 Byte)
function long2mem($v){
    return  chr($v & 255). chr(($v >> 8 ) & 255). chr(($v >> 16 ) & 255). chr(($v >> 24) & 255);
}
function short2mem($v){
    return  chr($v & 255). chr(($v >> 8 ) & 255);
}
//更新分组设备数量
function updateGroupDeviceNum($groupID){
    $model = new UserModel();
    $groupTable = 'zj_group';
    $boxTable = 'zj_container';
    $sql = "update $groupTable set BOX_NUM = (select count(1) from $boxTable where GROUP_ID = $groupID) where ID = $groupID";
    $result = $model->execute($sql);
}
function set_userdata($data, $value = NULL)
{
    if (is_array($data))
    {
        foreach ($data as $key => &$value)
        {
            $_SESSION[$key] = $value;
        }

        return;
    }

    $_SESSION[$data] = $value;
}
function getActionUrl()
{
    $module     = request()->module();
    $controller = request()->controller();
    $action     = request()->action();
    $url        = $module.'/'.$controller.'/'.$action;
    return strtolower($url);
}
//给关联数组key增加引号
function arrayKeysQuotation($arr){
    return array_combine(
        array_map(function($key){ return '"'.$key.'"'; }, array_keys($arr)),
        $arr
    );
}