<?php

use think\facade\Db;
use think\facade\Cache;
use think\facade\Lang;
use think\facade\app;


/*常量定义 start*/
if (!define('XML_DIR')) define('XML_DIR',app::getRootPath().'docs\xml\\');
if (!define('__LIBRARY__')) define('__LIBRARY__', app::getRootPath()."extend".DIRECTORY_SEPARATOR."library");
/*常量定义 end*/


//网站标题
if (!function_exists('site_name')) {
	function site_name() {
		return Db::name('config')->where('code', 'site_name')->value('value');
	}
}

//logo标签
if (!function_exists('site_logo')) {
	function site_logo() {
		return Db::name('config')->where('code', 'site_logo')->value('value');
	}
}

//返回指定key的数组
if (!function_exists('get_key_by_value')) {
	function get_key_by_value($keyName,$data) {
		$retrunArray = [];
		foreach ($data as $k => $v) {
			$key = isset($v[$keyName])?$v[$keyName]:'';
			if(!$key) continue;
			$retrunArray[$key] = $v;
		}
		return $retrunArray;
	}
}

//返回指定key对应value的数组
if (!function_exists('get_key_value')) {
	function get_key_value($key = '', $value='', $data) {
		$result = [];
		if(!empty($data)) {
			foreach($data as $val) {
				if(array_key_exists($key, $val) && array_key_exists($value, $val)) {
					$result[$val[$key]] = $val[$value];
				}
			}
		}
		return $result;
	}
}

//判断当前语言
if (!function_exists('get_lang')) {
	function get_lang() {
		$lang = cookie('think_lang');
		if(!$lang) $lang = Lang::defaultLangSet();
		return $lang;
	}
}

/**
 * 系统文件缓存写入
 * @param string $file_name 文件名
 * @param array $zh_data 中文缓存数据
 * @param array $en_data 英文缓存数据
 */
if (!function_exists('set_system_cache')) {
	function set_system_cache($file_name,$zh_data,$en_data=[]) {
		$file_name = trim($file_name);
		$dir = root_path()."cache".DIRECTORY_SEPARATOR."systemcache";

		if(!empty($zh_data)) {
			$zh_path = $dir.DIRECTORY_SEPARATOR.'zh-cn'.DIRECTORY_SEPARATOR.$file_name.'.php';
			$fp = fopen($zh_path, 'w+');
			$data = "<?php \n return " . var_export($zh_data, true) . "; \n?>";
			fwrite($fp, $data);
			fclose($fp);
		}
		
		if(!empty($en_data)) {
			$en_path = $dir.DIRECTORY_SEPARATOR.'en-us'.DIRECTORY_SEPARATOR.$file_name.'.php';
			$fp = fopen($en_path, 'w+');
			$data = "<?php \n return " . var_export($en_data, true) . "; \n?>";
			fwrite($fp, $data); 
			fclose($fp);
		}
	}
}

//系统文件缓存读取
if (!function_exists('get_system_cache')) {
	function get_system_cache($file_name) {
		$dir = root_path()."cache".DIRECTORY_SEPARATOR."systemcache";
		// 判断当前语言
		$lang = get_lang();
		$path = $dir.DIRECTORY_SEPARATOR.$lang.DIRECTORY_SEPARATOR.$file_name.'.php';
		$result = @include $path;
		return $result;
	}
}

// 生成令牌 - token
if(!function_exists('shopToken')) {
	function shopToken() {
		$data = request()->buildToken('__token__', 'sha1');
		return '<input type="hidden" name="__token__" value="' . $data . '" class="token">';
	}
}

//Object转array
if(!function_exists('shopToken')) {
	function object_array($array) {  
		if(is_object($array)) {  
			$array = (array)$array;  
		} 
		if(is_array($array)) {
			foreach($array as $key=>$value) {  
				$array[$key] = object_array($value);  
			}  
		}  
		return $array;  
	}
}

if(!function_exists('is_timestamp')) {
	function is_timestamp($timestamp) {

		if( !is_int($timestamp) ) return false;
		if(strtotime(date('Y-m-d H:i:s',$timestamp)) === $timestamp) {
			return $timestamp;
		} else return false;
	}
}

//两个时间计算秒差
if(!function_exists('time_diff')) {
	function time_diff($time1,$time2) {
		
		// 时间戳类型
		$timeStr1 = is_timestamp($time1)?$time1:strtotime($time1);
		$timeStr2 = is_timestamp($time2)?$time2:strtotime($time2);
		
		$diff = $timeStr1 - $timeStr2 ;
		$seconds = round($diff);

		return $seconds;
	}
}

//object转数组
if(!function_exists('object_array')) {
	function object_array($array) {  
		if(is_object($array)) {  
			$array = (array)$array;  
		} 
		if(is_array($array)) {
			foreach($array as $key=>$value) {  
				$array[$key] = object_array($value);  
			}  
		}  
		return $array;  
	}
}

