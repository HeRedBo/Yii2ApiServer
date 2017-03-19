<?php 
namespace backend\components;

use yii\base\Component;
use backend\components\FileLog;
/**
 * api 请求组件
 * @example 
 * post 请求
 * Yii::app()->ApiRequest->post($url,$method,$data);
 */
class ApiRequest extends Component
{
    /**
     * 应用ID
     */
    const APP_ID = 1;
    const APP_SECRET = 'api1234asr34as79asdfqwzxfa22';

    /**
     * 回调格式
     * @var string 
     */
    protected $format;

    /**
     * 签名方法
     * @var string
     */
    protected $sign_method;

    /**
     * 随机字符串
     * @var string
     */
    protected $nonce;

    /**
     * 签名字符串
     * @var string
     */
    protected $sign;

    /**
     * 日志文件名称
     * @var string
     */
    protected $logname = 'ApiRequest';

    public function __construct()
    {
        $this->nonce       = $this->createNonce();
    }

    /**
     * api 发送post 请求方法
     * @param string $url URL
     * @param string $method 请求方法
     * @param array 请求数据
     * @param bool 是否为https协议
     * @return string 相应主体Content
     */
    public function post($url, $method, $data, $ssl = false)
    {
        $requestData = $this->buildBodyParams($method, $data);
        // TODO log 
        try 
        {
            $response = $this->requestPost($url,$requestData);
            $response = json_decode($response,true);
            return $response;
        } 
        catch (Exception $e) 
        {
            // log 
            throw new Exception('Valid Request：'.$e->getMessage(), $e->getCode());
        }
    }

     /**
     * api 发送get 请求方法
     * @param string $url URL
     * @param string $method 请求方法
     * @param array 请求数据
     * @param bool 是否为https协议
     * @return string 相应主体Content
     */
    public function get($url, $method, $params, $ssl = false)
    {
        $requestStr = $this->buildQueryStringParams($method, $params);
        $url = $url . $requestStr;
        $this->log('[ 请求数据 ]'.$url);
        echo $url;exit;
        try 
        {
            $response = $this->requestGet($url);
            $response = json_decode($response,true);
            return $response;
        } 
        catch (Exception $e) 
        {
            // log 
            
            throw new Exception('Valid Request：'.$e->getMessage(), $e->getCode());
        }
    }

    protected function buildQueryStringParams($method, $params = [])
    {
        $requestData = $this->buildRequestParams($method, $params);
        return http_build_query($requestData);
    }

    protected function buildBodyParams($method, $params)
    {
        $data = $this->buildRequestParams($method,$params);
        return json_encode($data);
    }

    protected function buildRequestParams($method, $params = [])
    {
        

        $this->format      = isset($params['format']) ? $params['format'] : '';
        $this->sign_method = isset($params['sign_method']) ? $params['sign_method'] : '';

        $data = [];
        $data['method'] = $method;
        $data['nonce']  = $this->nonce;
        $data['app_id'] = self::APP_ID;
        $data['app_secret']  = self::APP_SECRET;
        if($this->format)
            $data['format'] = $this->format;
        if($this->sign_method) 
            $data['sign_method'] = $this->sign_method;
        $data = array_merge($data, $params);
        $data['sign']   = $this->makeSign($data);
        return $data;
    }

    /**
     * 生成请求签名
     * @param array $params 请求参数
     * @return string
     */
    protected function makeSign($params)
    {
        if(!$this->sign_method ||$this->sign_method == 'md5')
            return $this->generateMd5Sign($params);
        return false;
    }

    /**
     * 生成MD5 签名
     * @param  array $params 请求的参数
     * @return string
     */
    protected function generateMd5Sign($params)
    {
        ksort($params);
        $tmps = [];
        foreach ($params as $k => $v) 
        {
            $tmps[] = $k . $v;
        }

        $string = self::APP_SECRET .implode('', $tmps) . self::APP_SECRET;
        return strtolower(md5($string));
    }

    /**
     * 生成随机字符串
     * @return string
     */
    protected function createNonce()
    {
        return md5(md5(uniqid()));
    }

    /**
     * 发送GET请求的方法
     * @param string $url URL
     * @param bool $ssl 是否为https协议
     * @return string 响应主体Content
     */
    protected function requestGet($url, $ssl = false)
    {
        // curl完成
        $curl = curl_init();
        //设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);//URL
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '
Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);//user_agent，请求代理信息
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);//referer头，请求来源
        if ($ssl) 
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//禁用后CURL将终止从服务端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);   //检查服务器SSL证书中是否存在一个公用名(common name)。
        }

        curl_setopt($curl, CURLOPT_HEADER, false);//是否处理响应头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//curl_exec()是否返回响应结果

        // 发出请求
        $response = curl_exec($curl);
        if (false === $response) {
            throw new Exception(curl_error($curl),curl_errno($curl));
        }
        curl_close($curl);
        return $response;
    }

    /**
     * 发送GET请求的方法
     * @param string $url URL
     * @param string $data 请求数据
     * @param bool $ssl 是否为https协议
     * @return string 响应主体Content
     */
    protected function requestPost($url, $data, $ssl = false)
    {
           // curl完成
        $curl = curl_init();
        //设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);//URL
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '
Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);//user_agent，请求代理信息
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);//referer头，请求来源
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);//设置超时时间
        //SSL相关
        if ($ssl) 
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//禁用后cURL将终止从服务端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);    //检查服务器SSL证书中是否存在一个公用名(common name)。
        }
        // 处理post相关选项
        curl_setopt($curl, CURLOPT_POST, true);// 是否为POST请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);// 处理请求数据
        // 处理响应结果
        curl_setopt($curl, CURLOPT_HEADER, false);//是否处理响应头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//curl_exec()是否返回响应结果
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json;charset=utf-8',
            'Content-Length: ' . strlen($data)]
        );
        // 发出请求
        $response = curl_exec($curl);
        if (false === $response) 
        {
            throw new Exception(curl_error($curl),curl_errno($curl));
        }
        curl_close($curl);
        return $response;
    }

    /**
     * 保存文件日志信息
     * @param  string $msg   日志消息
     * @param  string $level 级别
     * @return 
     */
    public function log($msg,$level = 'info')
    {
        try {
            FileLog::getInstance($this->logname)->$level($msg);
        } catch (Exception $e) {
            var_dump($e);exit;
        }
        
    }
}