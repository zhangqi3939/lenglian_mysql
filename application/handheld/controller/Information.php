<?php
namespace app\handheld\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
use app\handheld\model\InformationModel;
class Information extends Controller
{
    //保存运单
    public function save_waybill()
    {
        $data = input('post.');
        $Information =  new InformationModel();
        $id = $Information->waybill($data);
        if(!empty($id)){
            $Information->goods_info($id,$data['goods_info']);
        }
    }
    /*
     * 运单列表【不带分页】
     * phoebus
     * 2019-11-26
     * */
    public function waybill_list()
    {
        $data = input('post.');
        $Information =  new InformationModel();
        $travelInfo = $Information->getWaybillList($data);
        app_send(arraykeyToLower($travelInfo));
    }
    /*
     * 运单列表【带分页】
     * phoebus
     * 2019-11-26
     * */
    public function waybill_list_page()
    {
        $Information =  new InformationModel();
        $array = $Information->getWaybillListPage();
        app_send($array);
    }
    /*
     *运单货物详情
     * phoebus
     * 2019-11-26
     *  */
    public function waybill_goods_details()
    {
        $travel_id = input('post.travel_id');
        $Information =  new InformationModel();
        $array = $Information->getGoodsDetails($travel_id);
        app_send($array);
    }
    /*
     *操作列表保存
     * phoebus
     * 2019-11-26
     *  */
    public function save_operation_list()
    {
        $data = input('post.');
        $Information =  new InformationModel();
        $id = $Information->operation_list($data);
        if(!empty($id)){
            if($data['list_type'] == 5){
                $file = $_FILES;
                $Information->box_info($id,$data,$data['list_type'],$file);
            }else{
                $Information->box_info($id,$data['box_info'],$data['list_type']);
            }
        }
    }
    /*
     *箱损添加修改
     *phoebus
     *2019-12-01
     */
    public function save_box_damage()
    {
        $data = input('post.');
        $Information =  new InformationModel();
        $Information->setBoxDamage($data);
    }
    /*
     *箱损列表
     *phoebus
     *2019-12-01
     */
    public function box_damage_list()
    {
        $Information =  new InformationModel();
        $boxDamageInfo = $Information->getBoxDamageListPage();
        app_send(arraykeyToLower($boxDamageInfo));
    }
    /*
    *删除选中的图片
    *phoebus
    *2019-12-21
    */
    public function delete_img()
    {
        $img_id = input('post.img_id');
        $img_path = Db::name('travel_box_attachment')->field('IMG_PATH,IMG_NAME')->where('ID',$img_id)->find();
        $PATH = '../public'.$img_path['IMG_PATH'];
        $result = Db::name('travel_box_attachment')->where('ID',$img_id)->delete();
        if($result){
            $res = unlink($PATH);
            if($res){
                app_send();
            }else{
                app_send('','400','图片删除失败');
            }
        }else{
            app_send('','400','无法找到图片或者已经删除');
        }
    }
    /*
     * 删除box中的数据
     * phoebus
     * 2019-11-29
     * */
    public function delete_box_info()
    {
        $box_id = input('post.box_id');
        $result = Db::name('travel_box_list')->where('ID',$box_id)->delete();
        if($result > 0){
            app_send();
        }else{
            app_send('','400','箱子数据删除失败');
        }
    }
    /*
     * 运单状态修改
     * phoebus
     * 2019-12-03
     * */
    public function save_travel_info_status()
    {
        $data = input('post.');
        $Information =  new InformationModel();
        $travelInfoStatus = $Information->travelInfoStatus($data);
    }
    /*
     * 运单状态删除
     * phoebus
     * 2019-12-03
     * */
    public function delete_travel_info_status()
    {
        $status_id = input('post.status_id');
        $travel_id = Db::name('travel_travel_info_status')->field('TRAVEL_ID')->where('ID',$status_id)->find();
        if(!empty($status_id)){
            $arr = array('FLAG_DELETE'=>1);
            $result = Db::name('travel_travel_info_status')->where('ID','>=',$status_id)->where('TRAVEL_ID',$travel_id['TRAVEL_ID'])->update($arr);
            if($result > 0){
                $status_info = Db::name('travel_travel_info_status')->where('TRAVEL_ID',$travel_id['TRAVEL_ID'])->where('FLAG_DELETE',0)->order('ID DESC')->find();
                if(!empty($status_info)){
                    Db::name('travel_travel_info')->where('ID',$travel_id['TRAVEL_ID'])->update(array('STATUS'=>$status_info['STATUS']));
                }else{
                    Db::name('travel_travel_info')->where('ID',$travel_id['TRAVEL_ID'])->update(array('STATUS'=>'-1'));
                }
                app_send();
            }else{
                app_send('','400','删除状态失败，请联系管理员');
            }
        }else{
            app_send('','400','删除状态失败，请联系管理员');
        }
    }
    /*
     * 运单删除
     * phoebus
     * 2019-12-03
     * */
    public function waybill_delete()
    {
        $travel_id = input('post.travel_id');
        $arr = array('FLAG_DELETE'=>1);
        $result = Db::name('travel_travel_info')->where('ID',$travel_id)->update($arr);
        if($result > 0){
            app_send();
        }else{
            app_send('','400','运单删除失败');
        }
    }
    /*
     * 操作清单删除
     * phoebus
     * 2019-12-03
     * */
    public function operation_list_delete()
    {
        $list_id = input('post.list_id');
        $arr = array('FLAG_DELETE'=>1);
        $result = Db::name('travel_box_operation_list')->where('ID',$list_id)->update($arr);
        if($result > 0){
            app_send();
        }else{
            app_send('','400','操作清单删除失败');
        }
    }
    /*
     * 操作清单列表页
     * phoebus
     * 2019-11-27
     * */
    public function all_operation_list_page()
    {
        $Information =  new InformationModel();
        $array = $Information->getAllOperationListPage();
        app_send($array);
    }
    /*
     * 操作清单详情页
     * phoebus
     * 2019-11-27
     * */
    public function operation_list_details()
    {
        $list_id = input('post.list_id');
        $Information =  new InformationModel();
        $array = $Information->getOperationListDetails($list_id);
        app_send($array);
    }
    /*
     * 箱损类型
     * phoebus
     * 2019-12-02
     * */
    public function damage_style()
    {
        //定义箱损类型
        $array = array(
            '1' => '箱前损坏',
            '2' => '箱侧损坏',
            '3' => '箱内损坏',
            '4' => '其他'
        );
        $array = implode(",", $array);
        app_send($array);
    }
    /*
     * 箱损详情
     * phoebus
     * 2019-12-02
     * */
    public function box_damage_list_details()
    {
        $list_id = input('post.list_id');
        $Information =  new InformationModel();
        $array = $Information->getBoxDamageListDetails($list_id);
        app_send($array);
    }
    /*
     * 提醒用户更新
     * phoebus
     * 2019-12-03
     * */
    public function reminder_version()
    {
        $data = input('post.');
        $User =  new UserModel();
        $user_info = $User->checkToken($data['channel']);
        $version = '最新系统版本为'.'1.01';
        app_send($version);
    }
    /*
    * 用户信息
    * phoebus
    * 2019-12-04
    * */
    public function user_login_info()
    {
        $data = input('post.');
        $User =  new UserModel();
        $user_info = $User->checkToken($data['channel']);
        unset($user_info['USER_PASSWORD']);
        app_send(arraykeyToLower($user_info));
    }
    //手持设备退出登录
    public function handle_user_login_out()
    {
        $User =  new UserModel();
        $channel = 'app';
        $result = $User->deleteToken($channel);
        if($result>0){
            app_send();
        }else{
            app_send('','400','退出失败，请联系管理员');
        }
    }
}