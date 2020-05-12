<?php
namespace app\command;
use think\Console;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use work\custom\Crontab;

use work\api\model\EbayOrders;

/**
 * Ebay 订单获取 定时任务
 * @author  ZHOU 2020.04.07
 * 
 */
class EbayGetOrders extends Command
{	

	protected $description = '获取ebay订单'; //指令描述

	# 程序配置
	protected $enable_multi_process = true;  //是否开启多进程
	protected $max_fork = 3; 	//最大运行的子进程数
	
	# 系统固定
	protected $model; 			//用户继承逻辑类
	private $commandName;

	/**
	 * 指令配置
	 * PS:此方法不允许删除更改
	 */
	protected function configure() {
		// 指令配置
		$this->commandName = __CLASS__;
		$this->setName($this->commandName)  #指令名
			 ->setDescription($this->description);	#指令描述 
	}

	/**
	 * windows子进程为循环操作
	 * PS:此方法不允许删除更改
	 */
	protected function getWindowsChild($params) {
		echo '<pre>'.'当前运行环境为: '.Crontab::system()."\r\n";
		$num = 0;
		foreach ($params as $val) {
			$num++; 
			$this->child($val);
			echo '<pre>'."第{$num}个程序运行结束."."\r\n";
		}
	}

	/**
	 * windows单进程操作
	 * PS:此方法不允许删除更改
	 */
	protected function getWindowsSingleProcess() {
		echo '<pre>'.'当前运行环境为: '.Crontab::system();
		$this->singleProcess();
		echo '<pre>'."程序运行结束.";
	}

	/**
	 * linux子进程操作
	 * PS:此方法不允许删除更改
	 */
	protected function getLinuxChild($params) {
		echo '当前运行环境为: '.Crontab::system()."\n";

		# 文件锁根目录
		$lock_dir = dirname(__FILE__).DIRECTORY_SEPARATOR.'lock'.DIRECTORY_SEPARATOR;

		$num = 0; //初始化子进程数
		$ppid = posix_getpid(); //获取当前父进程
		
		cli_set_process_title("{$this->commandName} father Process , pid is {$ppid}."); //给父进程命名
		foreach ($params as $k => $v) {
			$num++;

			//产生子进程
			$pid = pcntl_fork(); 

			if($pid){ //父进程处理
				if($num >= $this->max_fork) pcntl_wait($status); //如果子进程数超过了最大值则挂起父进程
			}else{ //子进程处理 可以用pcntl_exec执行其他代码

				/*进程基本信息 start*/ 
					$cpId = posix_getpid(); //获取当前子进程
					cli_set_process_title("{$this->commandName} children Process , pid is {$cpId}."); //给子进程命名
				/*进程基本信息 start*/	

				/*文件排他锁(非阻塞模式),防止重复进程 start*/
					$lock_file = "$lock_dir$this->commandName$k".".lock";
					$lock_file_handle = fopen($lock_file, 'w');
					if ($lock_file_handle === false){
						echo "Can not create lock file : $lock_file\r\n";
						die();
					}
					if (!flock($lock_file_handle, LOCK_EX + LOCK_NB)) {
						echo date("Y-m-d H:i:s") . " Process already exists.\r\n";
						die();
					}
					fclose($lock_file_handle);
				/*文件排他锁(非阻塞模式),防止重复进程 end*/
				
				/*子程序逻辑 start*/
					echo "当前正在运行第{$num}个进程,父进程为{$ppid},子进程为{$cpId}\r\n";
					$this->child($v);
					echo "第{$num}个进程运行结束\r\n";
				/*子程序逻辑 end*/
				sleep(10);
				exit();
			}
		}
		exit();
	}

	/**
	 * Linux单进程操作
	 * PS:此方法不允许删除更改
	 */
	protected function getLinuxSingleProcess() {
		echo '当前运行环境为: '.Crontab::system()."\r\n";
		$this->singleProcess();
		exit();
	}

	/**
	 * 系统执行
	 * PS:此方法不允许删除更改
	 */
	protected function execute(Input $input, Output $output) {	
		// 系统信息配置
		$this->define($input,$output);
		if($this->enable_multi_process) {  //多进程运行
			//主进程运行返回结果
			$main_result = $this->main();
			if(!is_array($main_result)) return $output->writeln("主进程仅允许返回数组");
			if(empty($main_result)) return $output->writeln("主进程结果为空");

			//子进程根据运行环境运行
			$system = Crontab::system();
			switch ($system) {
				case 'Windows':
					$this->getWindowsChild($main_result);
				break;
				case 'Linux':
					$this->getLinuxChild($main_result);
				break;
				default:
					return $output->writeln("系统未给当前环境配置进程运行".' -> '.php_uname());
				break;
			}
		}else{  //单进程运行
			$system = Crontab::system(); //进程根据运行环境运行
			switch ($system) {
				case 'Windows':
					$this->getWindowsSingleProcess();
				break;
				case 'Linux':
					$this->getLinuxSingleProcess();
				break;
				default:
					return $output->writeln("系统未给当前环境配置进程运行".' -> '.php_uname());
				break;
			}
		}
	}

	////////////////////////////////////////////////////////////////////////
	/// 	 分割线以上直接复制即可,所有内容以及方法名都不允许改变		 	 ///
	/// 分割线以下为用户业务操作,内容可根据自身业务进行操作,但方法名不允许改变 ///
	////////////////////////////////////////////////////////////////////////
	
	/**
	 * 用户定义/继承操作方法
	 */
	protected function define($input,$output) {
		$this->model = new EbayOrders(); //ebay订单逻辑类
	}
	
	/**
	 * 主进程
	 * @return array 返回代表子进程所需要的数据
	 */
	public function main() {
		$accountTokenArray = call_user_func_array([$this->model,'getAuthToken'],[]);
		return $accountTokenArray;
	}

	/**
	 * 子进程
	 * 获取范围订单
	 * @param string $param 账号token
	 */
	public function child($param) {
		call_user_func_array([$this->model,'getPageOrders'],[$param]);
	}

	/**
	 * 单进程 - 根据指定业务填写
	 * 获取指定订单
	 */
	public function singleProcess() {
		$orderIds = [
			'15-04763-91139'
		];
		$token = 'AgAAAA**AQAAAA**aAAAAA**GIgsXA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AAloOiCpCBpwWdj6x9nY+seQ**NkgBAA**AAMAAA**VnaQX+YFcUE7uYjTECs+/m/e7tWYrBjESyA4cVCIn5HHryAeeekrJYhEZQGUJXGkLRDxMQx/mNqz6NyuyhKUpXl91YnktBg5fShZ2d54Vxzjk7ehn57cCpgtk/HcBTwGFQZrB9EQdZirJFWCBjnYS7+EUknjxe698jEDt832mlorfNIhemrh5haSLXEhigAwgm/fiv08rR5G7z7vgc+aQeArQR6lqZEuXvJx8jzn0/18EKW9Sxr7usJ+AG1E+zvmviNWvj8WUsSm9jo3OGIaKg7erblbMGn6/1YNkhk5n/XIze/OGHgvxCAqczT7nfUqitaZuRS9d0vKIwNGy4IudEVcCK/kqEji+3GnBHyFI7QcpHOp+8hx/GB9Ay+fEzUeFVUUcxFAqWZK2mGo5SlOqqdmXjRKbail1b/YeZ8yO5ifIouGoAWgLZ+ir0jgg+9iYAMRogisUaGLZe+8ixVCNLUjFKHqWOaw5CQyUxnS4wI4E1gggHInwIhb5mDcXBLo+xW/fjyR2aF/UWzcjfKIqtKnrdlUviFdgdYeBrQWTw7X594SM3Fvs3uMHp3YkuKVaDhxgIh1DX9HY2Wdr2U1lO20Z7s89/Rkswv86iUVxAJD0jg+RIMNu83VLXt3UY7xKme65GZX1ZU0laJysGv98D4EPn9Oa5oP/h1iPIDJIZNkpDgt0+2nQwkMeqomUYLSJZ7QsivQUfT4EC9dosldrQZEE+VrCPLrY7rw5yVqZNSog3gTGFEQSikR6oiXsg0h';
		call_user_func_array([$this->model,'getOrders'],[$token,$orderIds]);
	}

}
