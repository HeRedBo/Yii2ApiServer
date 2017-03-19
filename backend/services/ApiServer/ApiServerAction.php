<?php
namespace backend\services\ApiServer;

use Yii;
use yii\base\Action;
use backend\components\FileLog;
use backend\models\handler\ApiServer\ApiRequestHandler;
use backend\services\ApiServer\App;
use backend\services\ApiServer\Error;
use backend\Controllers;

/**
 * API 服务端公共Action 
 * @author RedBo 
 */

class ApiServerAction extends Action
{

    /**
     * 请求参数
     * @var array
     */
    protected $params = [];

    /**
     * API请求的Method名
     * @var string
     */
    protected $method;

    /**
     * app_secret
     * @var string
     */
    protected  $app_secret;

    /**
     * 回调格式数据
     * @var string
     */
    protected $format = 'json';

    /**
     * 签名方式
     * @var string
     */
    protected $sign_method  = 'md5';

    /**
     * 是否输出错误码
     * @var boolean
     */
    protected $error_code_show = false;

    /**
     * app_id
     * @var int
     */
    protected $app_id;

    /**
     * error 公共错误码对象
     * @var object
     */
    protected $error;

    /**
     * 返回的对象
     * @var array
     */
    protected $response;

    /**
     * 请求类名的路径
     * @var string
     */
    protected $classPath;

    protected function beforeRun()
    {
        // 请求参数写log 
        try 
        {
            $request = Yii::$app->request;
            FileLog::getInstance('request')->info('[ 请求地址 ]'. $request->url);
            $this->params = array_merge($request->get(), $request->post());
            $this->error = new Error;
            FileLog::getInstance('request')->info('[ 请求参数 ]'. json_encode($this->params));
        } 
        catch (Exception $e) 
        {
            throw new Exception(500);
            exit;
        }
        return true;
    }


    public function run()
    {
        // A.1  初步校验
        $handler = new ApiRequestHandler;
        if($handler->load($this->params) && !$handler->validate()) 
        {
            return $this->response(['status' => false, 'code' => $handler->getError()]);
        }
        // A.2 赋值对象
        $this->format       = isset($this->params['format']) ? $this->params['format']:$this->format;
        $this->sign_method  = isset($this->params['sign_method']) ? $this->params['sign_method'] : $this->sign_method;
        $this->app_id       = $this->params['app_id'];
        $this->method       = 'action'.ucfirst($this->params['method']);

        // B. appid 校验
        $app = App::getInstance($this->app_id)->info();
        if( !$app )
            return $this->response(['status' => false, 'code' => '1002']);
        $this->app_secret = $app->app_secret;

        // C. 校验签名
        $signRes = $this->checkSign($this->params);
        if (!$signRes || !$signRes['status']) 
        {
            return $this->response(['status' => false, 'code' => $signRes['code']]);
        }

        // D. 校验接口名
        $methodRes = $this->isMethodValiable($this->method);
        if (!$methodRes || !$methodRes['status']) 
        {
            return $this->response(['status' => false, 'code' => $methodRes['code']]);
        }

        // E. 接口分发
        $instance = Yii::$app->controller;
        return $this->response((array) $instance->$method($this->params));
    }

    /**
     * 签名校验
     * @param array $params 请求参数
     * @return array
     */
    protected function checkSign($params)
    {
        $sign = array_key_exists('sign', $params) ? $params['sign'] :"";
        if(empty($sign)) 
            return ['status' => false, 'code' => '1006'];
        unset($params['sign']);
        if($sign != $this->generateSign($params))
        {
            return ['status' => false, 'code' => '1007'];
        }
        return ['status' => true, 'code' => '200'];
    }

    /**
     * 生成签名
     * @param  array $params 待校验的参数
     * @return string|false
     */
    protected function generateSign($params)
    {
        if($this->sign_method == 'md5')
            return $this->generateMd5Sign($params);
        return false;
    }

    protected function generateMd5Sign($params)
    {
        ksort($params);
        $tmps = [];
        foreach ($params as $k => $v) 
        {
            $tmps[] = $k . $v;
        }

        $string = $this->app_secret .implode('', $tmps) . $this->app_secret;
        return strtolower(md5($string));
    }

    protected function isMethodValiable($method)
    {
        if(!array_key_exists($method, $this->getCallMethodNames()))
        {
           return ['status' => false, 'code' => '1009'];
        }
        return ['status' => true, 'code' => '200'];
    }   

    /**
     * 获取类所有的方法集合
     * @return array 
     */
    protected function getCallMethodNames()
    {
        $methodNames = [];
        $this->classPath = $this->getControllerPath();
        $reflection  = new \ReflectionClass($this->classPath);
        $methods = $reflection->getMethods();
        foreach ($methods as $method) 
        {
            $methodNames[$method->name] = $method->getParameters(); //参数对象数组
        }
        return $methodNames;
    }

    /**
     * 获取当前控制器的路径 包含命名空间
     * @return string
     */
    protected function getControllerPath()
    {
        $classPath = '';
        $controller = Yii::$app->controller->id;
        $controller = ucfirst($controller).'Controller';
        $namespace = __NAMESPACE__;
        $namespace = substr($namespace,0,strpos($namespace,'\\'));
        $classPath = $namespace. '\\'.'Controllers\\'.$controller;
        return $classPath;
    }

    /**
     * 输出结果
     * @param  array  $result 结果
     * @return response
     */
    protected function response(array $result)
    {
        if (!array_key_exists('msg', $result) && array_key_exists('code', $result))
        {
            $result['msg'] = $this->getError($result['code']);
        }
        $this->response = $result;
        if(method_exists($this, 'afterRun')){
            $this->afterRun();
        }
        if($this->format == 'json') 
        {
            $response = Yii::$app->response;
            $response->format = \yii\web\Response::FORMAT_JSON;
            $response->data = $result;
            $response->send();
            exit;
        }
        return false;
    }

    /**
     * 返回错误信息
     * @param  string $code 错误码
     * @return string
     */
    protected function getError($code)
    {
        return $this->error->getError($code,$this->error_code_show);
    }

    /**
     * 返回数据日志记录
     * @return 
     */
    protected function afterRun()
    {
        if($this->response)
            FileLog::getInstance('apiResponse')->info('[ api返回数据 ]'. json_encode($this->response));

    }




}