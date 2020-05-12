<?php


namespace app\common\model;

use think\facade\Config;
use think\Model;

class BaseModel extends Model
{
    /**
     * @var bool 自动写入2019年8月14日 12:32:24
     */
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public function __construct(array $data = [])
    {
        parent::__construct($data);

    }

    public static function getList($where = array(), $pageSize, $order = ['sort', 'id' => 'desc'])
    {
        return self::where($where)->where($order)->select();

    }

    public static function getOne($id)
    {

        return self::find($id);
    }

    //表前缀
    public static function get_table_prefix(){

        return Config::get('database.connections.mysql.prefix');


    }
    
}
