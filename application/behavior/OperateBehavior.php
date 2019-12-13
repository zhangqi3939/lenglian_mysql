<?php

namespace app\behavior;
use think\Controller;
use think\Exception;
use think\Db;
use app\index\model\UserModel;
class OperateBehavior extends Controller{

    // 定义需要排除的权限路由
    protected $exclude = [
        'index/welcome/user_login',
        'index/welcome/user_info',
        'index/welcome/user_info_change',
        'index/welcome/user_password_change',
        'index/welcome/user_login_out',
        'index/schedule/monitor_latest',
        'index/schedule/monitor_save',
        'index/schedule/station_listing',
        'index/schedule/station_save',
        'index/schedule/station_delete',
        'index/schedule/station_group_add',
        'index/schedule/station_group_delete',
        'index/schedule/user_listing',
        'index/schedule/class_listing',
        'index/schedule/monitor_listing',
        'index/schedule/class_save',
        'index/schedule/schedule_save',
        'index/schedule/class_delete',
        'index/schedule/schedule_listing',
        'index/device/group_listing',
        'index/device/box_latest',
        'index/device/box_data',
        'index/device/box_data_page',
        'index/device/group_list',
        'index/device/group_save',
        'index/device/group_box_save',
        'index/device/box_list',
        'index/device/group_box_clear',
        'index/device/group_delete',
        'index/device/box_save',
        'index/device/box_info',
        'index/device/box_param',
        'index/device/box_param_save',
        'index/device/box_op_state',
        'index/device/box_op',
        'index/device/box_group_save',
        'index/alarm/alarm_index',
        'index/alarm/alarm_setting',
        'index/alarm/alarm_index_info',
        'index/alarm/alarm_msg',
        'index/alarm/alarm_save',
        'index/alarm/alarm_current',
        'index/alarm/alarm_history',
        'index/travel/travel_list',
        'index/travel/travel_delete',
        'index/travel/travel_save',
        'index/travel/travel_change',
        'index/travel/travel_info',
        'index/travel/travel_box_save',
        'index/report/run_time',
        'index/systemlog/log_category',
        'index/systemlog/log_list',
        'index/dept/dept_save',
        'index/dept/dept_delete',
        'index/dept/dept_listing',
        'index/dept/user_listing',
        'index/dept/user_save',
        'index/dept/user_delete',
        'index/dept/limit_add',
        'index/dept/limit_info',
        'index/dept/limit_edit',
        'index/dept/limit_delete',
        'index/dept/role_add',
        'index/dept/role_info',
        'index/dept/role_edit',
        'index/dept/role_delete',
        'index/dept/role_grouplist',
        'index/dept/role_group_add',
        'index/dept/role_group_edit',
        'index/dept/role_group_delete',
        'index/manual/manual_list',
        'index/manual/manual_info',
        //手持设备
       'handheld/information/waybill_list',
       'handheld/information/waybill_list_page',
       'handheld/information/waybill_goods_details',
       'handheld/information/save_waybill',
       'handheld/information/save_operation_list',
       'handheld/information/save_travel_info_status',
       'handheld/information/delete_travel_info_status',
       'handheld/information/all_operation_list_page',
       'handheld/information/operation_list_details',
       'handheld/information/delete_box_info',
       'handheld/information/delete_img',
       'handheld/information/save_box_damage',
       'handheld/information/box_damage_list',
       'handheld/information/damage_style',
       'handheld/information/box_damage_list_details',
       'handheld/information/reminder_version',
       'handheld/information/user_login_info',
       'handheld/information/handle_user_login_out',
       'handheld/information/waybill_delete',
       'handheld/information/operation_list_delete',

    ];

    /**
     * 权限验证
     */
    public function run(&$params){


        // 行为逻辑
        try {
            // 获取当前访问路由
            $url  = $this->getActionUrl();
            $User =  new UserModel();
            $token_exit = $User->getTokenFromHttp();
            $token_exit = trim($token_exit,'"');
            $uid = Db::name('token')->where('TOKEN',$token_exit)->find();
            $user_info = Db::name('user')->where('ID',$uid['USER_ID'])->find();
            if(empty($user_info['ID']) && !in_array($url,$this->exclude)){
                $this->error('请先登录',    '/welcome/user_login');
            }
            $limit_id = Db::name('rbac_user_role_relation')
                ->alias('U')
                ->join('rbac_role_limit_relation R','U.ROLE_ID = R.ROLE_ID','left')
                ->field('R.LIMIT_ID')
                ->where('U.USER_ID',$user_info['ID'])
                ->find();
            $limit_id = explode(",",$limit_id['LIMIT_ID']);
            // 用户所拥有的权限路由
            $auth = Db::name('rbac_limit')->field('URL')->where('ID','in',$limit_id)->select();
            $auth = array_column($auth, 'URL','NUMROW');
            if(!in_array($url, $auth) && !in_array($url, $this->exclude)) {
                app_send('','400','您没有操作权限，请联系管理员');
            }

        } catch (Exception $ex) {
            print_r($ex);
        }
    }
    /**
     * 获取当前访问路由
     * @param $Request
     * @return string
     */
    private function getActionUrl()
    {
        $module     = request()->module();
        $controller = request()->controller();
        $action     = request()->action();
        $url        = $module.'/'.$controller.'/'.$action;
        return strtolower($url);
    }



}