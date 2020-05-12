<?php
namespace app\common\controller;

use app\common\controller\Base;
use think\facade\Request;
use think\facade\Db;
use think\facade\Session;
use app\admin\model\AuthRule;

class Backend extends Base {

	public function initialize() {
		parent::initialize();
		//判断是否登录
		if (!session('admin.id') && !session('admin')) {
			$this->redirect(url('/admin/login/index')); #URL重定向
		}
		$this->authCheck();
	}

	/**
	 * 验证权限
	 */
	public function authCheck(){
		$allow = [
			'index/index',
			'index/main',
			'index/cleardata',
			'index/logout',
			'login/password',
		];
		#获取当前请求的控制器+操作名
		$route = Request::controller(true).'/'.Request::action(true);
		if(session('admin.id')!==0){
			#获取权限节点
			$this->hrefId = Db::name('auth_rule')->where('href',$route)->value('id');
			
			#当前用户权限
			$map['a.id'] = Session::get('admin.id');
			$rules=Db::name('admin')->alias('a')
				->join('auth_group ag','a.group_id = ag.id','left')
				->where($map)
				->value('ag.rules');

			#用户权限规则id
			$adminRules = explode(',',$rules);
			
			#不需要权限的规则id;
			$noruls = AuthRule::where('auth_open',1)->column('id');
			#权限集合
			$this->adminRules = array_merge($adminRules,$noruls);

			if($this->hrefId){
				// 不在权限里面,并且请求为post
				if(!in_array($this->hrefId,$this->adminRules)){
					$this->error($this->lang['permission_denied']);exit();
				}
			}else{
				if(!in_array($route,$allow)) {
					$this->error($this->lang['permission_denied']);exit();
				}
			}
		}

		return $this->adminRules;
	}
}