<?php
namespace app;

// 应用请求对象类
class Request extends \think\Request
{	
	// 全局过滤属性
	protected $filter = ["trim","htmlspecialchars","addslashes","strip_tags"];
}
