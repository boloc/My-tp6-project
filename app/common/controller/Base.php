<?php
namespace app\common\controller;

use app\BaseController;
use think\facade\Request;
use think\facade\Cookie;
use think\facade\View;
use think\facade\Lang;
class Base extends BaseController {
	
	protected $lang = []; #语言包

	public function initialize() {
		parent::initialize();
		$this->getLangData();
	}

	/**
	 * 配置获取
	 * @author by zhou
	 * 
	 **/
	public function getLangData($lang_set=null) {
		$lang = empty($lang_set)?get_lang():$lang_set;

		#通过请求对象获取当前请求的应用(多应用下适用)。
		$appName = app('http')->getName();

		/**
		 * 通过请求对象获取当前请求的控制器。
		 * @var bool true -> 返回小写,默认返回控制器的驼峰形式(首字母大写),和控制器类名保持一致(不含后缀)。
		 */
		$lang_file = Request::controller(true);
		
		#获取语言包文件位置
		$file = root_path().'app/'.$appName.'/lang/'.$lang.'/'.$lang_file.'.php';

		#确认是否存在文件,存在则引入
		$spec_lang = file_exists($file)?include $file : [];
		#公共语言包文件,必须引入
		$common_file =  root_path().'app/'.$appName.'/lang/'.$lang.'.php';
		$common_lang = file_exists($common_file)?include $common_file : [];

		#语言包合并
		$this->lang = array_merge($common_lang,$spec_lang);

		#同时输出作为模板变量
		View::assign('langStrData',json_encode($this->lang));
		View::assign('langData',$this->lang);
	}
	
	/*
	 * 切换语言包
	 */
	public function enlang() {
		$lang = Request::get('lang');
		switch ($lang) {
			case 'zh-cn':
				Cookie::set('cookie_var', 'zh-cn');
				break;
			case 'en-us':
				Cookie::set('cookie_var', 'en-us');
				break;
			default:
				Cookie::set('cookie_var', 'zh-cn');
				break;
		}
		$this->success($this->lang['switch_success']);
	}
  
}