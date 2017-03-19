<?php
namespace backend\controllers\Api;

use Yii;
use yii\web\Controller;
use backend\services\ApiServer\ApiServerAction as ServerAction;
use backend\services\ApiServer\ApiServerAction;

/**
 * API 服务端公共控制器 校验数据请求是否正确
 * @author RedBo 
 */
class ApiServerController extends Controller
{
    public function actions()
    {
        return [
            'index' => [
                'class' => 'backend\services\ApiServer\ApiServerAction', // 使用 serverAction 实现 不可以使用继承 
            ]
        ];
    }
}