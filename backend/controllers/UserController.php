<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;

class UserController extends Controller
{

    public function actionIndex()
    {
        $url = 'http://www.yii2api.com/index.php?r=response&';
        $params = [
            'name' => 'HeRedBo',
            'age'  => 23,
            'email'=> 'hhbjkd@163.com',
        ];
        var_dump(Yii::$app->apiRequest->get($url,'regester',$params));exit;
        
    }
}