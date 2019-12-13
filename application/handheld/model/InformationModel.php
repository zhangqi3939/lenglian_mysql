<?php
namespace app\handheld\model;
use think\Model;
use think\Db;
use app\index\model\UserModel;
class InformationModel extends Model
{
    /**
     * 获取所有商品信息
     */
    public function getGoodsInfo()
    {
        $goodsInfo = Db::name('travel_goods')
            ->field('ID AS GOODS_ID,GOODS_NAME,SET_TEMP,TRAVEL_ID')
            ->order('ID DESC')
            ->select();
        if(!empty($goodsInfo)){
            $key = array_column($goodsInfo,'TRAVEL_ID');
            $goodsInfo = array_combine($key,$goodsInfo);
        }
        return $goodsInfo;
    }
    /**
     * 获取所有运单状态
     */
    public function getTravelStatus()
    {
        $TravelStatus = Db::name('travel_travel_info_status')
            ->field('ID AS STATUS_ID,STATUS,TRAVEL_ID')
            ->order('ID ASC')
            ->select();
        if(!empty($TravelStatus)){
            $key = array_column($TravelStatus,'TRAVEL_ID');
            $TravelStatus = array_combine($key,$TravelStatus);
        }
        return $TravelStatus;
    }
    /**
     * 运单货物详情信息
     */
    public function getGoodsDetails($travel_id)
    {
        $travel_info = Db::name('travel_travel_info')->field('ID AS TRAVEL_ID,SHIPPER_NAME,LEAVE_STATION,LOADING_MODE,SHIPPER_PHONE,DEPARTURE_TIME,RECEIVER_NAME,ARRIVAL_STATION,UNLOAD_MODE,RECEIVER_PHONE,DOOR_LOADING,LOADING_BRANCH,LOADING_STATION,ARRAVE_BRANCH,DOOR_UNLOAD,POWER_BOX_NUM,USER_ID,TITLE,GOODS_NAME,STATUS,SET_TEMP')->where('ID',$travel_id)->select();
        $goods_details = Db::name('travel_goods')->field('ID AS GOODS_ID,TRAVEL_ID,TRAIN_NUM,BOX_NUM,GOODS_NAME,GOODS_CATEGORY,SET_TEMP,RECEIVE_TIME,LOADING_TIME')->where('TRAVEL_ID',$travel_id)->select();
        $last_status = Db::name('travel_travel_info_status')->field('STATUS')->where('TRAVEL_ID',$travel_id)->where('FLAG_DELETE',0)->order('ID DESC')->limit('1')->select();
        if(empty($last_status)){
            $last_status = [array(
                'STATUS'=> '-1'
            )];
        }
        $travel_info_status = Db::name('travel_travel_info_status')->field('ID AS STATUS_ID,TRAVEL_ID,USER_ID,STATUS,STATION_NAME,CROSS_STATION_TIME,INSERT_TIME')->where('TRAVEL_ID',$travel_id)->where('FLAG_DELETE',0)->order('ID ASC')->select();
        $alarm_info = [array(
            'ALARM_ID'   => '1',
            'ALARM_INFO' => '温度异常'
        )];
        $array = array(
            "travel"      => arraykeyToLower($travel_info),
            "last_status" => arraykeyToLower($last_status),
            "alarm"       => arraykeyToLower($alarm_info),
            "status"      => arraykeyToLower($travel_info_status),
            "goods"       => arraykeyToLower($goods_details)
        );
        return $array;
    }

    /*
     * 获取运单列表【不带分页】
     * phoebus
     * 2019-11-26
     * */
    public function getWaybillList($data)
    {
        $User =  new UserModel();
        $user_info = $User->checkToken($data['channel']);
        $travelInfo = Db::name('travel_travel_info')
            ->field('ID AS TRAVEL_ID,SHIPPER_NAME,RECEIVER_NAME,LEAVE_STATION,ARRIVAL_STATION,DEPARTURE_TIME,TITLE,LOADING_BRANCH,ARRAVE_BRANCH,USER_ID,GOODS_NAME,SET_TEMP,STATUS')
            ->where('FLAG_DELETE = 0')
            ->order('ID DESC')
            ->select();
        //获取主表信息为空 则直接返回 暂无数据 或者返回空
        if(empty($travelInfo)){
            return $travelInfo;
        }
        /*$goodsInfo = $this->getGoodsInfo();
        $travelStatus = $this->getTravelStatus();
        $goodsName = "";
        $setTemp = "";
        $goodsId = "";
        foreach($travelInfo as $key=>$value){
            if(array_key_exists($value['TRAVEL_ID'],$goodsInfo)){
                $goodsName= $goodsInfo[$value['TRAVEL_ID']]['GOODS_NAME'];
                $setTemp = $goodsInfo[$value['TRAVEL_ID']]['SET_TEMP'];
                $goodsId = $goodsInfo[$value['TRAVEL_ID']]['GOODS_ID'];
            }
            if(array_key_exists($value['TRAVEL_ID'],$travelStatus)){
                $status = $travelStatus[$value['TRAVEL_ID']]['STATUS'];
            }else{
                $status = "-1";
            }
            $travelInfo[$key]['GOODS_NAME'] = $goodsName;
            $travelInfo[$key]['SET_TEMP']   = $setTemp;
            $travelInfo[$key]['GOODS_ID']   = $goodsId;
            $travelInfo[$key]['STATUS'] = $status;
        }*/
        return $travelInfo;
    }
    /*
    * 获取运单列表【带分页】
    * phoebus
    * 2019-11-26
    * */
    public function getWaybillListPage()
    {
        $where = ' 1=1 ';
        $data = input('post.');
        $User =  new UserModel();
        $Information =  new InformationModel();
        $user_info = $User->checkToken($data['channel']);
        $station = Db::name('travel_station')->field('STATION')->where('BRANCH_OFFICE_ID',$user_info['BRANCH_ID'])->select();
        $station =  array_column($station, 'STATION');
        $station = "'".implode("','", $station)."'";
        $page = $data['page'] > 1 ? $data['page'] : 1;
        $offset = 5;
        if($data['page'] == 0){
            $perPage = 0;
        }else{
            $perPage = ($data['page'] - 1) * $offset;
        }
        if(!empty($data['start_time']) && empty($data['end_time'])){
            $data['start_time'] = strtotime($data['start_time']);
            $where .= " and (DEPARTURE_TIME >= ".$data['start_time'].")";
        }
        if(!empty($data['end_time']) && empty($data['start_time'])){
            $data['end_time'] = strtotime($data['end_time']);
            $where .= " and (DEPARTURE_TIME <= ".$data['end_time'].")";
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $where .= " and (DEPARTURE_TIME between ".$data['start_time']." and ".$data['end_time'].")";
        }
        if(!empty($data['keywords'])){
            $where .= " and (LEAVE_STATION like '%".$data['keywords']."%' or ARRIVAL_STATION like '%".$data['keywords']."%'or TITLE like '%".$data['keywords']."%')";
        }
        $where .= " and (FLAG_DELETE = 0)";
        if($data['type'] == 1){
            $info = Db::name('travel_travel_info  ')->field('ID')->where($where)->select();
            $travelInfo = Db::name('travel_travel_info  ')
                ->field('ID AS TRAVEL_ID,SHIPPER_NAME,RECEIVER_NAME,LEAVE_STATION,ARRIVAL_STATION,DEPARTURE_TIME,TITLE,LOADING_BRANCH,ARRAVE_BRANCH,USER_ID,GOODS_NAME,SET_TEMP,STATUS')
                ->limit($perPage,$offset)
                ->order('ID DESC')
                ->where($where)
                ->select();
        }else if($data['type'] == 2 ){
            $where .= " and (LEAVE_STATION in ($station))";
            $info = Db::name('travel_travel_info  ')->field('ID')->where($where)->select();
            $travelInfo = Db::name('travel_travel_info')
                ->field('ID AS TRAVEL_ID,SHIPPER_NAME,RECEIVER_NAME,LEAVE_STATION,ARRIVAL_STATION,DEPARTURE_TIME,TITLE,LOADING_BRANCH,ARRAVE_BRANCH,USER_ID,GOODS_NAME,SET_TEMP,STATUS')
                ->order('ID DESC')
                ->where($where)
                ->limit($perPage,$offset)
                ->select();
        }else if($data['type'] == 3){
            $where .= " and (ARRIVAL_STATION in ($station))";
            $info = Db::name('travel_travel_info  ')->field('ID')->where($where)->select();
            $travelInfo = Db::name('travel_travel_info')
                ->field('ID AS TRAVEL_ID,SHIPPER_NAME,RECEIVER_NAME,LEAVE_STATION,ARRIVAL_STATION,DEPARTURE_TIME,TITLE,LOADING_BRANCH,ARRAVE_BRANCH,USER_ID,GOODS_NAME,SET_TEMP,STATUS')
                ->order('ID DESC')
                ->where($where)
                ->limit($perPage,$offset)
                ->select();
        }
        //获取主表信息为空 则直接返回 暂无数据 或者返回空
        if(empty($travelInfo)){
            $array = array(
                'total'=>0,
                'travelInfo'=>arraykeyToLower($travelInfo)
            );
        }else{
/*            $goodsInfo = $Information->getGoodsInfo();
            $travelStatus = $Information->getTravelStatus();
            $goodsName = "";
            $setTemp = "";
            $goodsId = "";
            foreach($travelInfo as $key=>$value){
                if(array_key_exists($value['TRAVEL_ID'],$goodsInfo)){
                    $goodsName= $goodsInfo[$value['TRAVEL_ID']]['GOODS_NAME'];
                    $setTemp = $goodsInfo[$value['TRAVEL_ID']]['SET_TEMP'];
                    $goodsId = $goodsInfo[$value['TRAVEL_ID']]['GOODS_ID'];
                }
                if(array_key_exists($value['TRAVEL_ID'],$travelStatus)){
                    $status = $travelStatus[$value['TRAVEL_ID']]['STATUS'];
                }else{
                    $status = "-1";
                }
                $travelInfo[$key]['GOODS_NAME'] = $goodsName;
                $travelInfo[$key]['SET_TEMP']   = $setTemp;
                $travelInfo[$key]['GOODS_ID']   = $goodsId;
                $travelInfo[$key]['STATUS'] = $status;
            }*/
            $total = count($info);
            $array = array(
                'total'=>$total,
                'travelInfo'=>arraykeyToLower($travelInfo)
            );
        }
        return $array;
    }
    /*
     * 运单货物信息、
     * phoebus
     * 2019-11-26
     * */
    public function goods_info($id,$data)
    {
        $data = json_decode($data,true);
        if(!empty($data) &&  !empty($id)){
            foreach($data as $row) {
                $arr = array(
                    'TRAVEL_ID'      => $id,
                    'TRAIN_NUM'      => $row['train_num'],
                    'BOX_NUM'        => $row['box_num'],
                    'GOODS_NAME'     => $row['goods_name'],
                    'GOODS_CATEGORY' => $row['goods_category'],
                    'SET_TEMP'       => $row['set_temp'],
                    'RECEIVE_TIME'   => strtotime($row['receive_time']),
                    'LOADING_TIME'   => strtotime($row['loading_time'])
                );
                if(empty($row['goods_id'])){
                    $arr['INSERT_TIME'] = time();
                    $result = Db::name('travel_goods')->insertGetId($arr);
                }else{
                    $arr['UPDATE_TIME'] = time();
                    $result = Db::name('travel_goods')->where('ID',$row['goods_id'])->update($arr);
                }
            }
            $array = array(
                'SET_TEMP' => $row['set_temp'],
                'GOODS_NAME' => $row['goods_name']
            );
            Db::name('travel_travel_info')->where('ID', $id)->update($array);
            if($result > 0){
                app_send($id);
            }else{
                app_send('','400','货物信息插入失败，请联系管理员');
            }
        }
    }
    /*
     * 运单运单信息
     * phoebus
     * 2019-11-26
     * */
    public function waybill($params)
    {
        if($params['door_loading'] == true){
            $loading = 1;
        }else{
            $loading = 0;
        }
        if($params['door_unload'] == true){
            $unload = 1;
        }else{
            $unload = 0;
        }
        if(empty($params['title'])){
            $info = json_decode($params['goods_info'],true);
            $title_info = $params['leave_station'].'-'.$params['arrival_station'].'-'.$info[0]['goods_name'];
        }else{
            $title_info = $params['title'];
        }
        $User =  new UserModel();
        $user_info = $User->checkToken($params['channel']);
        $user_id = $user_info['ID'];
        $shipper_name = $params['shipper_name'];//托运人
        $shipper_phone = $params['shipper_phone'];//手机号【发货人】
        $loading_station = $params['loading_station'];//装车站
        $leave_station = $params['leave_station'];//发站
        $loading_mode = $params['loading_mode'];//装货方式
        $door_loading = $loading;//上门取货
        $departure_time = strtotime($params['departure_time']);//发运时间
        $receiver_name = $params['receiver_name'];//收货人
        $receiver_phone = $params['receiver_phone'];//手机号【收货人】
        $arrival_station = $params['arrival_station'];//到站
        $unload_mode = $params['unload_mode'];//卸货方式
        $door_unload =  $unload;//上门卸货
        $power_box_num = $params['power_box_num'];//发电箱号
        $loading_branch = Db::name('travel_station')
            ->alias('S')
            ->join('department D','D.ID = S.BRANCH_OFFICE_ID')
            ->where('S.STATION',$loading_station)
            ->find();
        $arrive_branch = Db::name('travel_station')
            ->alias('S')
            ->join('department D','D.ID = S.BRANCH_OFFICE_ID')
            ->where('S.STATION',$arrival_station)
            ->find();
        $arr = array(
            'USER_ID' => $user_id,
            'SHIPPER_NAME'    => $shipper_name,
            'SHIPPER_PHONE'   => $shipper_phone,
            'LOADING_BRANCH'  => $loading_branch['TITLE'],
            'LOADING_STATION' => $loading_station,
            'TITLE'           => $title_info,
            'LEAVE_STATION'   => $leave_station,
            'LOADING_MODE'    => $loading_mode,
            'DOOR_LOADING'    => $door_loading,
            'DEPARTURE_TIME'  => $departure_time,
            'RECEIVER_NAME'   => $receiver_name,
            'RECEIVER_PHONE'  => $receiver_phone,
            'ARRAVE_BRANCH'   => $arrive_branch['TITLE'],
            'ARRIVAL_STATION' => $arrival_station,
            'UNLOAD_MODE'     => $unload_mode,
            'DOOR_UNLOAD'     => $door_unload,
            'POWER_BOX_NUM'   => $power_box_num
        );
        if(!empty($params['travel_id'])){
            $arr['UPDATE_TIME'] = time();
            $res = Db::name('travel_travel_info')->where('ID',$params['travel_id'])->update($arr);
        }else{
            $arr['INSERT_TIME'] = time();
            $result = Db::name('travel_travel_info')->insertGetId($arr);
        }
        if(!empty($params['travel_id'])){
            return $params['travel_id'];
        }else if(!empty($result)){
            return $result;
        }else{
            app_send('','400','你提交的运单有问题，请联系管理员');
        }
    }
    /*
     * 操作清单【保存】
     * */
    public function operation_list($params)
    {
        $User = new UserModel();
        $user_info = $User->checkToken($params['channel']);
        $user_id = $user_info['ID'];
        if ($params['list_type'] == 1 || $params['list_type'] == 3 || $params['list_type'] == 4 || $params['list_type'] == 5) {
            $arr = array(
                'HANDLE_STATION' => $params['handle_station'],//办理站
                'OPERATION_TIME' => strtotime($params['operation_time']),//搬移时间
                'LIST_TYPE' => $params['list_type'],//操作清单类型
                'USER_ID' => $user_id
            );
        } else if ($params['list_type'] == 2) {
            $arr = array(
                'HANDLE_STATION' => $params['handle_station'],//办理站
                'DELIVERY_COMPANY' => $params['delivery_company'],//交付单位
                'ACCEPT_COMPANY' => $params['accept_company'],//接收单位
                'DEPARTURE_TIME' => strtotime($params['departure_time']),//出站时间
                'OPERATION_TIME' => strtotime($params['operation_time']),//交接时间
                'LIST_TYPE' => $params['list_type'],//操作清单类型
                'USER_ID' => $user_id
            );
        }
        if (!empty($params['list_id'])) {
            $arr['UPDATE_TIME'] = time();
            $res = Db::name('travel_box_operation_list')->where('ID', $params['list_id'])->update($arr);
        } else {
            $arr['INSERT_TIME'] = time();
            $result = Db::name('travel_box_operation_list')->insertGetId($arr);
        }
        if(empty($params['list_id']) && $result < 0){
            app_send();
        }else if(!empty($params['list_id'])  && $res > 0){
            return $params['list_id'];
        }else if(!empty($result)){
            return $result;
        }else{
            app_send('','400','你提交的箱子操作清单有问题，请联系管理员');
        }
    }
    /*
     *操作清单【箱子信息】
     * */
    public function box_info($id,$data,$list_type,$file = null)
    {
        if($list_type != 5){
            $data = json_decode($data,true);
        }
        if(!empty($id) && !empty($data)){
            if($list_type == 1){
                foreach($data as $row){
                    $arr = array(
                        'LIST_ID'           => $id,
                        'BOX_NUM'           => $row['box_num'],
                        'ORIGINAL_POSITION' => $row['original_position'],
                        'MOVE_POSITION'     => $row['move_position'],
                        'MOVE_REASON'       => $row['move_reason'],
                        'SET_TEMP'          => $row['set_temp'],
                        'OPEN_TEMP'         => $row['open_temp'],
                        'BOX_TYPE'          => $row['box_type'],
                        'BOX_STATUS'        => $row['box_status']
                    );
                    if(empty($row['box_id'])){
                        $arr['INSERT_TIME'] = time();
                        $result = Db::name('travel_box_list')->insert($arr);
                    }else{
                        $arr['UPDATE_TIME'] = time();
                        $result = Db::name('travel_box_list')->where('ID',$row['box_id'])->update($arr);
                    }
                }
            }else if($list_type == 2){
                foreach($data as $row){
                    $arr = array(
                        'LIST_ID'        => $id,
                        'BOX_NUM'        => $row['box_num'],
                        'OUTGOING_TEMP'  => $row['outgoing_temp'],
                        'MOVE_CAR_NUM'   => $row['move_car_num']
                    );
                    if(empty($row['box_id'])){
                        $arr['INSERT_TIME'] = time();
                        $result = Db::name('travel_box_list')->insert($arr);
                    }else{
                        $arr['UPDATE_TIME'] = time();
                        $result = Db::name('travel_box_list')->where('ID',$row['box_id'])->update($arr);
                    }
                }
            }else if($list_type == 3){
                foreach($data as $row){
                    $arr = array(
                        'LIST_ID'            => $id,
                        'CASE_NUM'           => $row['case_num'],
                        'SET_TEMP'           => $row['set_temp'],
                        'CHARGING_TIME'      => $row['charging_time'],
                        'ABNORMAL_SITUATION' => $row['abnormal_situation']
                    );
                    if(empty($row['box_id'])){
                        $arr['INSERT_TIME'] = time();
                        $result = Db::name('travel_box_list')->insert($arr);
                    }else{
                        $arr['UPDATE_TIME'] = time();
                        $result = Db::name('travel_box_list')->where('ID',$row['box_id'])->update($arr);
                    }
                }
            }else if($list_type == 4){
                foreach($data as $row){
                    $arr = array(
                        'LIST_ID'            => $id,
                        'CASE_NUM'           => $row['case_num'],
                        'SET_TEMP'           => $row['set_temp'],
                        'GASOLINE'           => $row['gasoline'],//汽油
                        'DIESEL_OIL'         => $row['diesel_oil'],//柴油
                        'ENGINE_OIL'         => $row['engine_oil'],//机油
                        'ABNORMAL_SITUATION' => $row['abnormal_situation']
                    );
                    if(empty($row['box_id'])){
                        $arr['INSERT_TIME'] = time();
                        $result = Db::name('travel_box_list')->insert($arr);
                    }else{
                        $arr['UPDATE_TIME'] = time();
                        $result = Db::name('travel_box_list')->where('ID',$row['box_id'])->update($arr);
                    }
                }
            }else if($list_type == 5){
                $washing_info = json_decode($data['box_info'],true);
                foreach($washing_info as $row){
                    $arr = array(
                        'LIST_ID'            => $id,
                        'BOX_NUM'            => $row['box_num'],
                        'ABNORMAL_SITUATION' => $row['abnormal_situation']
                    );
                    dump($row);
                    if(empty($row['box_id'])){
                        $arr['INSERT_TIME'] = time();
                        $result = Db::name('travel_box_list')->insert($arr);
                    }else{
                        $arr['UPDATE_TIME'] = time();
                        $result = Db::name('travel_box_list')->where('ID',$row['box_id'])->update($arr);
                    }
                }
                if($result > 0 &&!empty($file)){
                    foreach($file as $image){
                        $name = $image['name'];
                        $type = strtolower(substr($name, strrpos($name, '.') + 1));
                        $allow_type = array('jpg', 'jpeg', 'gif', 'png');
                        if (!in_array($type, $allow_type)) {
                            continue;
                        }
                        $upload_path = ROOT_PATH.'/public/upload'; //上传文件的存放路径
                        $time = date("Ymd");
                        $dir = iconv("UTF-8", "GBK", "$upload_path/".$time);
                        $dir_name = substr($dir,strripos($dir,"\\")+8);
                        //检测文件夹是否存在
                        if (!file_exists($dir)){
                            mkdir ($dir,0777,true);
                        }
                        $file_name = date('Ymd').time().rand(100,999).'.'.$type;
                        //开始移动文件到相应的文件夹
                        if (move_uploaded_file($image['tmp_name'], $dir."/$file_name")) {
                            $data['path'] = "$dir_name/" . "$file_name";
                            $data['img_real_name'] = $name;
                            $data['img_name'] = $file_name;
                        }
                        $img_exit = Db::name('travel_box_attachment')->where('IMG_NAME',$data['img_name'])->where('LIST_ID',$id)->select();
                        if(empty($img_exit)){
                            $array= array(
                                'LIST_ID'       => $id,
                                'IMG_NAME'      => $data['img_name'],
                                'IMG_REAL_NAME' => $data['img_real_name'],
                                'IMG_PATH'      =>$data['path'],
                                'INSERT_TIME'   =>time()
                            );
                            $img_info = Db::name('travel_box_attachment')->insert($array);
                        }else{
                            $img_info = "-1";
                        }
                    }
                }else{
                    $img_info = "0";
                }
            }
        }
        if($list_type == 5){
            if($result >0){
                if($img_info > 0 || $img_info == '-1' || $img_info == '0'){
                    app_send($id);
                }else{
                    app_send('','400','附件图片信息插入失败，请联系管理员');
                }
            }else{
                app_send('','400','箱子信息插入失败，请联系管理员');
            }
        }else{
            if($result > 0){
                app_send($id);
            }else{
                app_send('','400','箱子信息插入失败，请联系管理员');
            }
        }

    }
    public function getBoxInfo()
    {
        $boxInfo = Db::name('travel_box_list')
            ->order('ID DESC')
            ->select();
        if(!empty($boxInfo)){
            $key = array_column($boxInfo,'LIST_ID');
            $boxInfo = array_combine($key,$boxInfo);
        }
        return $boxInfo;
    }
    /*
     * 获取所有的操作清单列表【带分页】
     * */
    public function getAllOperationListPage()
    {
        $where = ' 1=1 ';
        $data = input('post.');
        $Information =  new InformationModel();
        $User =  new UserModel();
        $user_info = $User->checkToken($data['channel']);
        $page = $data['page'] > 1 ? $data['page'] : 1;
        $offset = 5;
        if($data['page'] == 0){
            $perPage = 0;
        }else{
            $perPage = ($data['page'] - 1) * $offset;
        }
        if($data['list_type'] == 0){
           $list = '1,2,3,4,5';
        }else{
           $list = rtrim($data['list_type'],",");
        }
        if(!empty($data['start_time']) && empty($data['end_time'])){
            $data['start_time'] = strtotime($data['start_time']);
            $where .= " and (OPERATION_TIME >= ".$data['start_time'].")";
        }
        if(!empty($data['end_time']) && empty($data['start_time'])){
            $data['end_time'] = strtotime($data['end_time']);
            $where .= " and (OPERATION_TIME <= ".$data['end_time'].")";
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $where .= " and (OPERATION_TIME between ".$data['start_time']." and ".$data['end_time'].")";
        }
        if(!empty($data['keywords'])){
            $where .= " and (HANDLE_STATION like '%".$data['keywords']."%')";
        }
        $where .= "and (FLAG_DELETE = 0 )";
        if($data['list_type'] == 0){
            $info = Db::name('travel_box_operation_list')->where($where)->select();
            $operation_info = Db::name('travel_box_operation_list')
                ->field('ID AS LIST_ID,HANDLE_STATION,OPERATION_TIME,LIST_TYPE')
                ->limit($perPage,$offset)
                ->order('ID DESC')
                ->where($where)
                ->select();
        }else{
            $where .= " and (LIST_TYPE in ($list))";
            $info = Db::name('travel_box_operation_list')->where($where)->select();
            $operation_info = Db::name('travel_box_operation_list')
                ->field('ID AS LIST_ID,HANDLE_STATION,OPERATION_TIME,LIST_TYPE')
                ->limit($perPage,$offset)
                ->order('ID DESC')
                ->where($where)
                ->select();
        }
        if(empty($operation_info)){
            $array = array(
                'total'=>0,
                'operationInfo'=>arraykeyToLower($operation_info)
            );
        }else{
            $total = count($info);
            $array = array(
                'total'=>$total,
                'operationInfo'=>arraykeyToLower($operation_info)
            );
        }
        app_send($array);
    }
    /*
     * 获取操作清单列表详情
     * */
    public function getOperationListDetails($list_id)
    {
       $operation_info = Db::name('travel_box_operation_list')->field('ID AS LIST_ID,HANDLE_STATION,OPERATION_TIME,LIST_TYPE,USER_ID,INSERT_TIME,DELIVERY_COMPANY,ACCEPT_COMPANY,DEPARTURE_TIME')->where('ID',$list_id)->select();
       $box_info = Db::name('travel_box_list')->field('ID AS BOX_ID,LIST_ID,BOX_NUM,ORIGINAL_POSITION,MOVE_POSITION,MOVE_REASON,SET_TEMP,OPEN_TEMP,BOX_TYPE,BOX_STATUS,MOVE_CAR_NUM,ABNORMAL_SITUATION,CASE_NUM,CHARGING_TIME,OUTGOING_TEMP,GASOLINE,DIESEL_OIL,ENGINE_OIL')->where('LIST_ID',$list_id)->select();
       $img_info = Db::name('travel_box_attachment')->field('ID AS IMG_ID,IMG_NAME,IMG_PATH,LIST_ID')->where('LIST_ID',$list_id)->select();
       $array = array(
           "operation" => arraykeyToLower($operation_info),
           "box"       => arraykeyToLower($box_info),
           "img"       => arraykeyToLower($img_info)
       );
       return $array;
    }
    /*
     * 设置箱损
     * */
    public function setBoxDamage($params)
    {
        $User =  new UserModel();
        $user_info = $User->checkToken($params['channel']);
        $user_id = $user_info['ID'];
        $title = $params['discover_station'].'箱损';
        $arr = array(
                'HANDLE_STATION' => $params['discover_station'],
                'OPERATION_TIME' => strtotime($params['discover_time']),
                'USER_ID'        => $user_id,
                'TITLE'          => $title,
                'LIST_TYPE'      => '6'
            );
        $arr['TITLE'] = $params['discover_station'].'箱损';
        if(!empty($params['list_id'])){
            $arr['UPDATE_TIME'] = time();
            $arr_result = Db::name('travel_box_operation_list')->where('ID',$params['list_id'])->update($arr);
            $arr_result = $params['list_id'];
        }else{
            $arr['INSERT_TIME'] = time();
            $arr_result = Db::name('travel_box_operation_list')->insertGetId($arr);
        }
        $array = array(
                'BOX_NUM' => $params['box_num'],
                'REMARK'  => $params['remark'],
                'LIST_ID' => $arr_result,
                'DAMAGE_STYLE' => $params['damage_style']
            );
        if(!empty($params['box_id'])){
            $array['UPDATE_TIME'] = time();
            $array_result = Db::name('travel_box_list')->where('ID',$params['box_id'])->update($array);
        }else{
            $array['INSERT_TIME'] = time();
            $array_result = Db::name('travel_box_list')->insert($array);
        }
        $file = $_FILES;
        if(!empty($file)){
            foreach($file as $image){
                $name = $image['name'];
                $type = strtolower(substr($name, strrpos($name, '.') + 1));
                $allow_type = array('jpg', 'jpeg', 'gif', 'png');
                if (!in_array($type, $allow_type)) {
                    continue;
                }
                $upload_path = ROOT_PATH.'/public/upload'; //上传文件的存放路径
                $time = date("Ymd");
                $dir = iconv("UTF-8", "GBK", "$upload_path/".$time);
                $dir_name = substr($dir,strripos($dir,"\\")+8);
                //检测文件夹是否存在
                if (!file_exists($dir)){
                    mkdir ($dir,0777,true);
                }
                $file_name = date('Ymd').time().rand(100,999).'.'.$type;
                //开始移动文件到相应的文件夹
                if (move_uploaded_file($image['tmp_name'], $dir."/$file_name")) {
                    $data['path'] = "$dir_name/" . "$file_name";
                    $data['img_real_name'] = $name;
                    $data['img_name'] = $file_name;
                }
                $img_exit = Db::name('travel_box_attachment')->where('IMG_NAME',$data['img_name'])->where('LIST_ID',$arr_result)->select();
                if(!$img_exit) {
                    $array = array(
                        'LIST_ID' => $arr_result,
                        'IMG_NAME' => $data['img_name'],
                        'IMG_REAL_NAME' => $data['img_real_name'],
                        'IMG_PATH' => $data['path'],
                        'INSERT_TIME' => time()
                    );
                    $img_info = Db::name('travel_box_attachment')->insert($array);
                }else{
                    $img_info = '-1';
                }
            }
        }else{
            $img_info = '-1';
        }
        if($arr_result > 0){
            if($arr_result > 0){
                if($img_info > 0 || $img_info == '-1'){
                    app_send($arr_result);
                }else{
                  app_send('','400','请仔细核对您的箱损图片信息，保存失败');  
                }
            }else{
                app_send('','400','请仔细核对您的箱损箱子信息，保存失败');
            }
        }else{
            app_send('','400','请仔细核对您的箱损运单信息，保存失败');
        }
    }
    /*
     * 获取箱损列表【带分页】
     * */
    public function getBoxDamageListPage()
    {
        $where = ' 1=1 ';
        $data = input('post.');
        $page = $data['page'] > 1 ? $data['page'] : 1;
        $offset = 5;
        if($data['page'] == 0){
            $perPage = 0;
        }else{
            $perPage = ($data['page'] - 1) * $offset;
        }
        if($data['damage_type'] == 0){
            $list = '1,2,3,4';
        }else{
            $list = rtrim($data['damage_type'],",");
        }
        if(!empty($data['start_time'])){
            $data['start_time'] = strtotime($data['start_time']);
           // $where .= " and (OPERATION_TIME like '%".$data['start_time']."%')";
            $where .= " and (OPERATION_TIME >= ".$data['start_time'].")";
        }
        if(!empty($data['end_time'])){
            $data['end_time'] = strtotime($data['end_time']);
            //$where .= " and (OPERATION_TIME like '%".$data['end_time']."%')";
            $where .= " and (OPERATION_TIME <= ".$data['end_time'].")";
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $where .= " and (OPERATION_TIME between ".$data['start_time']." and ".$data['end_time'].")";
        }
        if(!empty($data['keywords'])){
            $where .= " and (HANDLE_STATION like '%".$data['keywords']."%')";
        }
        $where .= " and (LIST_TYPE = 6 )";
        $where .= " and (FLAG_DELETE = 0 )";
        if($data['damage_type'] == '0,'){
            $info = Db::name('travel_box_operation_list')->where($where)->select();
            $operation_info = Db::name('travel_box_operation_list')
                ->field('ID AS LIST_ID,HANDLE_STATION,OPERATION_TIME,LIST_TYPE,TITLE')
                ->limit($perPage,$offset)
                ->order('ID DESC')
                ->where($where)
                ->select();
        }else{
            //$where .= " and (L.DAMAGE_STYLE instr ($list))";
            $info = Db::name('travel_box_operation_list')
                ->where($where)
                ->select();
            $operation_info = Db::name('travel_box_operation_list')
                ->field('ID AS LIST_ID,HANDLE_STATION,OPERATION_TIME,LIST_TYPE,TITLE')
                ->limit($perPage,$offset)
                ->order('ID DESC')
                ->where($where)
                ->select();
        }

        if(empty($operation_info)){
            $array = array(
                'total'=>0,
                'operationInfo'=>arraykeyToLower($operation_info)
            );
        }else{
            $total = count($info);
            $array = array(
                'total'=>$total,
                'operationInfo'=>arraykeyToLower($operation_info)
            );
        }
        app_send($array);
    }
    /*
     *获取箱损列表详情
     * */
    public function getBoxDamageListDetails($list_id)
    {
        $operation_info = Db::name('travel_box_operation_list')->field('ID AS LIST_ID,HANDLE_STATION,OPERATION_TIME,LIST_TYPE,USER_ID,INSERT_TIME')->where('ID',$list_id)->select();
        $box_info = Db::name('travel_box_list')->field('ID AS BOX_ID,LIST_ID,BOX_NUM,REMARK,DAMAGE_STYLE')->where('LIST_ID',$list_id)->select();
        $img_info = Db::name('travel_box_attachment')->field('ID AS IMG_ID,IMG_NAME,IMG_PATH,LIST_ID')->where('LIST_ID',$list_id)->select();
        $array = array(
            "operation" => arraykeyToLower($operation_info),
            "box"       => arraykeyToLower($box_info),
            "img"       => arraykeyToLower($img_info)
        );
        return $array;
    }
    /*
     * 运单状态操作
     * */
    public function travelInfoStatus($data)
    {
        $User =  new UserModel();
        $user_info = $User->checkToken($data['channel']);
        $user_id = $user_info['ID'];
        if(!empty($data['status_id'])){
            if($data['status'] == 2){
                $arr = array(
                    'STATION_NAME'       => $data['station_name'],
                    'CROSS_STATION_TIME' => strtotime($data['cross_station_time']),
                    'UPDATE_TIME'        => time()
                );
            }else{
                $arr = array(
                    'UPDATE_TIME'        => time(),
                    'CROSS_STATION_TIME' => strtotime($data['cross_station_time']),
                );
            }
            $result = Db::name('travel_travel_info_status')->where('ID',$data['status_id'])->update($arr);
        }else{
            if($data['status'] == 2){
                $arr = array(
                    'TRAVEL_ID'           => $data['travel_id'],
                    'STATUS'              => $data['status'],
                    'STATION_NAME'        => $data['station_name'],
                    'CROSS_STATION_TIME'  => strtotime($data['cross_station_time']),
                    'USER_ID'             => $user_id,
                    'INSERT_TIME'         => time()
                );
            }else{
                $arr = array(
                    'TRAVEL_ID'           => $data['travel_id'],
                    'STATUS'              => $data['status'],
                    'CROSS_STATION_TIME'  => strtotime($data['cross_station_time']),
                    'USER_ID'             => $user_id,
                    'INSERT_TIME'         => time()
                );
            }
            $result = Db::name('travel_travel_info_status')->insert($arr);
            Db::name('travel_travel_info')->where('ID',$data['travel_id'])->update(array('STATUS'=>$data['status']));
        }
        if($result >0 ){
            app_send();
        }else{
            app_send('','400','运单状态保存失败');
        }
    }
}