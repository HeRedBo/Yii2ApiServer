<?php

namespace backend\models\data;

use Yii;

/**
 * This is the model class for table "{{%apps}}".
 *
 * @property integer $id
 * @property string $app_id
 * @property string $app_secret
 * @property string $app_name
 * @property string $app_desc
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 */
class Apps extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%apps}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['app_id', 'app_secret', 'app_name'], 'required'],
            [['app_desc'], 'string'],
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['app_id'], 'string', 'max' => 60],
            [['app_secret'], 'string', 'max' => 100],
            [['app_name'], 'string', 'max' => 200],
            [['app_id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'app_id' => 'App ID',
            'app_secret' => 'App Secret',
            'app_name' => 'App Name',
            'app_desc' => 'App Desc',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
