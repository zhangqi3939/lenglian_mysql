<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
class Dept extends Controller
{
    function dept_listing(){
        $result = Db::name('department')->select();
        app_send(arraykeyToLower($result));
    }

    public function dept_save()
    {
        $d=array(
            'PARENT_ID'=>intval(input('post.parent_id')),
            'TITLE'=>input('post.title'),
            'REMARKS'=>input('post.remarks')
        );
        $id=intval(input('post.id'));
        if($id > 0){
            Db::name('department')->where('id',$id)->update($d);
            //$this->db->where('id',$id)->update('department',$d);
        }else{
            Db::name('department')->insert($d);
           //$this->db->insert('department',$d);
        }
        app_send();
    }
    //部门->删除
    public function dept_delete(){
        $id = input('post.id');
        Db::name('department')->where('id',$id)->delete();
        app_send();
    }
    public function user_listing(){
        $users = Db::name('user')->field('ID,USER_NAME,REAL_NAME,TEL,EMAIL,GENDER,IS_ADMIN,IS_SUPER')->select();
        if(!empty($users)){
            foreach ($users as &$row) {
                $row['user_Type'] = 'common';
                $row['IS_ADMIN'] == 1 && $row['user_Type'] = 'admin';
                $row['IS_SUPER'] == 1 && $row['user_Type'] = 'super';
                unset($row['IS_ADMIN']);
                unset($row['IS_SUPER']);
            }
        }
        app_send(arraykeyToLower($users));
    }
    public function user_save(){
        $formData['USER_NAME'] = input('post.userName');
        $formData['itemID'] = input('post.itemID');
        $userType = input('post.userType');
        $password = input('post.userPassword');
        $formData['REAL_NAME'] = input('post.realName');
        $formData['TEL'] = input('post.tel');
        $formData['EMAIL'] = input('post.email');
        $formData['GENDER'] = input('post.gender');
        $id = empty(input('post.id')) ? 0 : input('post.id');
        unset($formData['itemID']);
        $formData['USER_LEVEL']=2;
        $formData['IS_SUPER']=0;
        $formData['IS_ADMIN']=0;

        if($userType == 'super'){
            $formData['IS_SUPER'] = 1;
            $formData['IS_ADMIN'] = 1;
        }elseif($userType == 'admin'){
            $formData['IS_ADMIN'] = 1;
        }
        if(!empty($password)){
            $formData['USER_PASSWORD'] = md5($password);
        }else{
            $formData['USER_PASSWORD'] = md5(123456);
        }
        if($id > 0){
            if(empty($password)) unset($formData['USER_PASSWORD']);
            Db::name('user')->where('id',$id)->update($formData);
        }else{
            Db::name('user')->insert($formData);
        }
        app_send();
    }
    //用户删除
    public function user_delete(){
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        if($user_info['IS_SUPER'] != '1'){
            app_send('','400','您不是超级管理员，不能管理用户');
            exit();
        }
        $id = input('post.id');
        if($id>0){
            Db::name('user')->where('id',$id)->delete();
            app_send();
        }else{
            app_send('','400','用户选择错误。');
        }
    }
    //权限添加
    public function limit_add()
    {
        $limit_name = input('post.limit_name');
        $model = input('post.model');
        $controller = input('post.controller');
        $action = input('post.action');
        $parent_id = input('post.parent_id');
        $d = array(
            'LIMIT_NAME' => $limit_name,
            'MODEL' => $model,
            'CONTROLLER' => $controller,
            'ACTION' => $action,
            'PARENT_ID' => $parent_id
        );
        Db::name('rbac_limit')->insert($d);
        app_send();
    }
    //权限编辑
    public function limit_edit()
    {
        $limit_id = input('post.limit_id');
        $limit_name = input('post.limit_name');
        $model = input('post.model');
        $controller = input('post.controller');
        $action = input('post.action');
        $parent_id = input('post.parent_id');
        $d = array(
            'LIMIT_NAME' => $limit_name,
            'MODEL' => $model,
            'CONTROLLER' => $controller,
            'ACTION' => $action,
            'PARENT_ID' => $parent_id
        );
        $result = Db::name('rbac_limit')->where('ID',$limit_id)->update($d);
        if($result < 0){
            app_send('','400','您所选择的权限信息为空，请联系管理员');
        }else{
            app_send();
        }
    }
    //权限详情
    public function limit_info()
    {
        $limit_id = input('post.limit_id');
        $result = Db::name('rbac_limit')->where('ID',$limit_id)->select();
        if(!empty($result)){
            app_send($result);
        }else{
            app_send('','400','您所选择的权限信息为空，请联系管理员');
        }
    }
    //权限删除
    public function limit_delete()
    {
        $limit_id = input('post.limit_id');
        $result = Db::name('rbac_limit')->where('ID',$limit_id)->delete();
        if($result > 0){
            app_send('',200,'删除成功');
        }else{
            app_send('',400,'删除失败，请联系管理员，请仔细核对您的步骤');
        }
    }
    //角色添加
    public function role_add()
    {
        $role_name = input('post.role_name');
        $role_limit_id = input('post.role_limit_id');
        $role_exit = Db::name('rbac_role')->where('ROLE_NAME',$role_name)->select();
        if(!$role_exit){
            $result = Db::name('rbac_role')->insert(array('ROLE_NAME'=>$role_name));
            if($result){
                $role_id = Db::name('rbac_role')->where('ROLE_NAME',$role_name)->find();
                Db::name('rbac_role_limit_relation')->insert(array(
                        'ROLE_ID' => $role_id['ID'],
                        'LIMIT_ID' => $role_limit_id
                    )
                );
            }
        }else{
            app_send('','400','该角色名称已存在');
        }
        app_send();
    }
    //角色详情
    public function role_info()
    {
        $role_id = input('post.role_id');
        $role_info = Db::name('rbac_role')
                    ->alias('R')
                    ->join('rbac_role_limit_relation L' , 'R.ID = L.ROLE_ID', 'LEFT' )
                    ->field('R.ID,R.ROLE_NAME,L.LIMIT_ID')
                    ->where('R.ID',$role_id)
                    ->select();
        app_send($role_info);
    }
    //角色编辑
    public function role_edit()
    {
        $role_id = input('post.role_id');
        $role_name = input('post.role_name');
        $role_limit_id = input('post.role_limit_id');
        $d = array(
            'ROLE_NAME' => $role_name
        );
        $b = array(
            'LIMIT_ID' => $role_limit_id
        );
        $result = Db::name('rbac_role')->where('ID',$role_id)->update($d);
        $res = Db::name('rbac_role_limit_relation')->where('ROLE_ID',$role_id)->update($b);
        if($result >0 && $res>0){
            app_send();
        }else{
            app_send('','400','角色编辑失败，请联系管理员');
        }
    }
    //角色删除
    public function role_delete()
    {
        $role_id = input('post.role_id');
        if(!empty($role_id)){
            $result = Db::name('rbac_role_limit_relation')->where('ROLE_ID',$role_id)->delete();
            if($result>0){
                Db::name('rbac_role')->where('ID',$role_id)->delete();
            }else{
                app_send('','400','角色用户表删除失败');
            }
        }else{
            app_send('','400','请选择您要删除的角色');
        }
        app_send();
    }
    //角色分组【列表】
    public function role_grouplist()
    {
        //$result = Db::name('rbac_role_group_relation')->select();
        $result = Db::name('rbac_role_group_relation')
            ->alias('L')
            ->join('rbac_role R','L.ROLE_ID = R.ID')
            ->field('L.ID AS ID,L.ROLE_ID,L.GROUP_ID,R.ROLE_NAME')
            ->select();
        app_send(arraykeyToLower($result));
    }
    //角色分组【添加】
    public function role_group_add()
    {
        $role_id = input('post.role_id');
        $group_id = input('post.group_id');
        //判断是否存在角色分组
        $result = Db::name('rbac_role_group_relation')->where('ROLE_ID',$role_id)->select();
        if(empty($result)){
            if(!empty($group_id)){
                Db::name('rbac_role_group_relation')->insert(array('ROLE_ID'=>$role_id,'GROUP_ID'=>$group_id));
            }else{
                app_send('','400','请选择您要添加的分组');
            }

        }else{
            app_send('','400','该角色下已经存在分组，请勿重复添加');
        }
        app_send();
    }
    //角色分组【修改】
    public function role_group_edit()
    {

        $id = input('post.id');
        $group_id = input('post.group_id');
        $result = Db::name('rbac_role_group_relation')->where('ID',$id)->update(array('GROUP_ID'=>$group_id));
        if($result>0){
            app_send();
        }else{
            app_send('','400','角色分组，修改失败');
        }
    }
    //角色分组【删除】
    public function role_group_delete()
    {
        $id = input('post.id');
        $result = Db::name('rbac_role_group_relation')->where('ID',$id)->delete();
        if($result > 0){
            app_send();
        }else{
            app_send('','400','删除失败');
        }
    }
    public function role_ce()
    {
        $role_id = getActionUrl();
        var_dump($role_id);
    }
}
