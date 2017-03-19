<?php

namespace backend\controllers;

use Yii;
use backend\components\FileLog;
use backend\controllers\Api\ApiServerController as ApiController;
class ResponseController extends ApiController
{

    public function actionRegester($request)
    {   
        $data = ['code' => 200,'message' => 'success'];
        FileLog::getInstance('request')->info(json_encode($request));
        return $data;
    }   
}