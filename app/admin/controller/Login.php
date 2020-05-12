<?php
namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\AuthGroup;
use app\common\controller\Base;
use think\facade\Session;
use think\facade\Request;
use work\helper\SignHelper;
use think\facade\View;
use think\facade\Route;
use think\captcha\facade\Captcha;

class Login extends Base {
	protected $maxRemberTime = '7*24*3600';

	/*
	 * 登录
	 */
	public function initialize() {
		parent::initialize();
	}

	public function index(){
		
		if (!Request::isPost()) {
			$admin = Session::get('admin');
			$admin_sign = Session::get('admin_sign') == SignHelper::authSign($admin) ? $admin['id'] : 0;
			// 签名验证
			if ($admin && $admin_sign) redirect('index/index');
			return View::fetch();
		}else{
			/**
			 * 获取输入变量的时候,可以支持默认值,例如当URL中不包含$_POST['username']的时候,默认为空
			 * 自定义规则-参数过滤防止攻击
			 */
			$username = Request::post('username', '', 'work\helper\StringHelper::filterWords');// 获取post变量 并用work\helper\StringHelper类的filterWords方法过滤
			$password = Request::post('password', '', 'work\helper\StringHelper::filterWords');
			$captcha = Request::post('captcha', '', 'work\helper\StringHelper::filterWords');
			$rememberMe = Request::post('rememberMe');
			
			try {
				//验证码验证
				// if(!captcha_check($captcha)) throw new \Exception($this->lang['login']['captcha_error']);
				//登录存储信息&验证权限
				$res = self::checkLogin($username, $password,$rememberMe);
			} catch (\Exception $e) {
				$this->error($this->lang['login']['login_fail'].":{$e->getMessage()}",302);
			}
			//重新跳转
			$this->success($this->lang['login']['login_success'].'...', url('/admin/index'));
		}
	}

	/*
	 * 验证码
	 */
	public function verify() {
		return captcha();
	}

	/**
	 * 根据用户名密码，验证用户是否能成功登陆
	 * @param string $user
	 * @param string $pwd
	 * @throws \Exception
	 * @return mixed
	 */
	protected function checkLogin($user, $password,$rememberMe) {
		try{
			$where['username'] = strip_tags(trim($user)); #过滤
			$password = strip_tags(trim($password));
			$info = Admin::where($where)->find();

			if(!$info) throw new \Exception($this->lang['login']['please_check_username_or_password']);
			if($info['status']==0) throw new \Exception($this->lang['login']['account_is_disabled']);
			if(!password_verify($password,$info['password'])) throw new \Exception($this->lang['login']['please_check_username_or_password']);

			#访客模式
			if(!$info['group_id']) $info['group_id'] = 2; 

			#获取权限
			$rules = AuthGroup::where('id',$info['group_id'])
				->value('rules');

			$info['rules'] = $rules;
			unset($info['password']);
			if($rememberMe){
				Session::set('admin', $info,$this->maxRemberTime);
				#加密存储用户登录信息
				Session::set('admin_sign',  SignHelper::authSign($info),$this->maxRemberTime);
			}else{
				Session::set('admin', $info);
				#加密存储用户登录信息
				Session::set('admin_sign',  SignHelper::authSign($info));
			}
		}catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}

		return true;
	}

}