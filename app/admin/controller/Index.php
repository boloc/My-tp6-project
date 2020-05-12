<?php

namespace app\admin\controller;
use app\common\controller\Backend;
use think\facade\View;
use think\facade\Session;
use think\facade\Cache;
use app\admin\model\AuthRule;
use app\common\lib\Menu;
class Index extends Backend {

	public function initialize(){
		parent::initialize();
	}

	public function index(){
		// 所有显示的菜单
        $admin_id = Session::get('admin.id');
        $menus = Cache::get('adminMenus_'.$admin_id);
        if(!$menus){
            $cate = AuthRule::where('menu_status',1)->order('sort asc')->select()->toArray();
            $menus = Menu::authMenu($cate);
            cache('adminMenus_'.$admin_id,$menus,['expire'=>3600]);
        }

        $href = (string)url('main');
        $home = ["href"=>$href,"icon"=>"fa fa-home","title"=>"首页"];
        $menusinit =['menus'=>$menus,'home'=>$home];
        View::assign('menus',json_encode($menusinit));

		return View::fetch();
	}
}