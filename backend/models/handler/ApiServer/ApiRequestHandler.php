<?php
namespace backend\models\handler\ApiServer;

use Yii;
use yii\base\Model;

class ApiRequestHandler extends Model 
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['app_id'], 'required', 'message' => '1001'],
            [['method'], 'required', 'message' => '1003'],
            [['sign'],   'required', 'message' => '1006'],
            [['nonce'],  'required', 'message' => '1010'],
            [['nonce'], 'string', 'min' => 1, 'max' => 32, 'message' => '1010'],
            ['format', 'in', 'range' => ['josn'] , 'message' => '1004'], 
            ['sign_method', 'in', 'range' => ['md5'] , 'message' => '1004'], 
            [['sign_method','format','data'], 'safe'],
        ];
    }
}