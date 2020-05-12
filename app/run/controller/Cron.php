<?php

namespace app\run\controller;
use think\facade\Console;

/**
 * 浏览器运行计划任务入口
 * @author zhou 2020.04.13
 * @param $class 计划任务入口类名
 **/

class Cron {
	public function task($class)
	{	
		$output = Console::call(strtolower($class));
		return $output->fetch();
	}
}