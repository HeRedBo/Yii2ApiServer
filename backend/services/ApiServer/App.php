<?php
namespace backend\services\ApiServer;

use Yii;
use backend\models\data\Apps as AppModel;

/**
 * API 服务端 -- App 应用相关
 * 
 * @example App::getInstance('0001')->info();
 */

class App 
{
    /**
     * app_id
     * @var string
     */
    protected $app_id;

    /**
     * 缓存key 前缀
     * @var string
     */
    protected $cache_key_prefix = 'api:app:info:';

    /**
     * 初始化 app_id
     * @param string $app_id app_id
     * @return object
     */
    protected function __construct($app_id)
    {
        $this->app_id = $app_id;
    }

    /**
     * 获取当前对象
     * @param  string $app_id appid
     * @return object
     */
    public static function getInstance($app_id)
    {
        static $_instance = [];
        if(array_key_exists($app_id, $_instance))
            return $_instance[$app_id];

        return $_instance[$app_id] = new self($app_id);
    }

    /**
     * 获取App 信息
     * @return AppModel
     */
    public function info()
    {
        $cache_key = $this->cache_key_prefix . $this->app_id;
        $cache = Yii::$app->cache; 
        $data= $cache->get($cache_key);
        if($data)
        {
            return $data;
        }
        
        $app = AppModel::find()->where(['status' => 1, 'app_id' => $this->app_id ])->one();
        if($app)
            $cache->set($cache_key,$app, 3600); // 一小时后过去
        return $app;
    }
    


}

