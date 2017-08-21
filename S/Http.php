<?php
namespace S;

include(PHPLIB . "/Ext/GuzzleHttp/Promise/functions_include.php");
include(PHPLIB . "/Ext/GuzzleHttp/functions_include.php");
include(PHPLIB . "/Ext/GuzzleHttp/Psr7/functions_include.php");

use GuzzleHttp\Exception\RequestException;

/**
 * Class Http
 *
 * @package     S
 * @description Http请求服务工具
 */
class Http {

    const ERROR_CODE = '30040001';
    const ERROR_MESSAGE = 'HTTP ERROR';

    const METHOD_GET = 'get';
    const METHOD_POST = 'post';
    const METHOD_DELETE = 'delete';

    /**
     * @see http://docs.guzzlephp.org/en/latest/request-options.html
     */
    private static $_options_sets = array(
        'allow_redirects',
        'auth',
        'cert',
        'cookies',
        'connect_timeout',
        'debug',
        'decode_content',
        'delay',
        'expect',
        'form_params',
        'headers',
        'http_errors',
        'json',
        'multipart',
        'proxy',
        'ssl_key',
        'timeout',
    );
    private static $_method = array(
        'get',
        'post',
        'delete',
        'head',
        'put',
        'patch',
    );
    /**
     * @var string 请求资源根路径
     */
    private $_base_uri;
    /**
     * @var \GuzzleHttp\Client
     */
    private $_client;
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $_response;
    /**
     * @var bool 是否内网请求
     */
    private $_is_private_req = false;

    /**
     * Http constructor.
     *
     * @param string $base_uri 服务根路径 e.g. http://demo.com/api
     * @param array  $options  请求选项
     *
     * @throws Exception
     */
    public function __construct($base_uri, array $options = array()) {
        if (!$this->_checkOptions($options)) {
            throw new Exception('invalid options');
        }

        $this->_base_uri = $base_uri;

        $config['base_uri']        = $base_uri;
        $config['timeout']         = 10000;
        $config['connect_timeout'] = 3000;
        $config['version']         = '1.1';
        $config['http_errors']     = true;
        $config['verify']          = false;

        // 区分公网请求和私网请求
        $hostname = parse_url($base_uri);
        $request_ip = gethostbyname($hostname['host']);
        if (\S\Util\Ip::isPrivateIp($request_ip)) {
            $this->_is_private_req = true;
        } else {
            if(\Core\Env::isProductEnv()){
                #去掉线上出口代理
//                $config['proxy'] = ($options['proxy'] ?: "http://10.0.2.5:3128");
            }
        }

        $this->_client = new \GuzzleHttp\Client(array_merge($config, $options));
    }

    /**
     * 外部请求
     *
     * @param string $method  请求方法
     * @param string $path    请求资源相对路径 e.g. foo/bar foo/bar?a=1&b=2
     * @param mixed  $data    请求数据
     * @param array  $options 请求选项
     *
     * @return string
     * @throws \S\Exception
     */
    public function request($method, $path, $data = array(), array $options = array()) {
        $method = strtolower($method);
        if (!$this->_checkMethod($method)) {
            throw new Exception('invalid http method:' . $method);
        }

        //todo 应用网关依赖 等待应用网关上线时再使用
//        if ($this->_is_private_req && is_array($data)) {
//            $options['headers'] = array_merge((array)$options['headers'], $this->_getPrivateRequestHeaders($data));
//        }
        if (!$this->_checkOptions($options)) {
            throw new Exception('invalid options');
        }

        if (self::METHOD_GET == $method) {
            $options['query'] = $data;
        } else if (self::METHOD_POST == $method && empty($options['multipart'])) {
            if (is_array($data)) {
                $options['form_params'] = $data;
            } else {
                $options['body'] = $data;
            }
        }

        try {
            \S\Strace\Timelog::instance()->resetTime();
            $this->_response = $this->_client->request($method, $path, $options);
            $this->_setStrace($method, $path, $data);
        } catch (RequestException $e) {
            $this->_setStrace($method, $path, $data);

            \S\Log\Logger::getInstance()->debug(array(
                $e->getMessage(),
                $e->getCode(),
                $e->getTraceAsString(),
            ));
            throw new \S\Exception(self::ERROR_MESSAGE, self::ERROR_CODE, $e);
        }

        return $this->_response->getBody()->getContents();
    }

    /**
     * url解析方法
     * @param $url
     * @return array
     */
    public static function parseUrl($url){
        $url = parse_url($url);
        $base_uri = $url['scheme']."://".$url['host'];
        $base_uri .= $url['port'] ? ":{$url['port']}" : "";
        $path = ltrim($url['path'], "/");
        $path .= $url['query'] ? "?{$url['query']}" : "";
        return array(
            "base" => $base_uri,
            "path" => $path
        );
    }

    /**
     * 获取最近一次请求响应的http code码
     * @return int
     */
    public function getLastResponseCode(){
        return $this->getLastResponseInfo()->getStatusCode();
    }

    /**
     * 获取最近一次请求响应信息
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getLastResponseInfo() {
        return $this->_response;
    }

    /**
     * 获取内部请求(走http-gateway)的headers
     *
     * @param array $data 请求数据, k-v格式
     *
     * @return array
     * @throws Exception
     */
    private function _getPrivateRequestHeaders(array $data = array()) {
        $secret = Config::confSecurity('app.' . APP_NAME . '.secret');
        if (!$secret) {
            throw new Exception('app secret not configured');
        }
        $time = time();
        ksort($data);
        $fields_values = array_values($data);
        $fields_keys   = array_keys($data);

        $headers             = array();
        $headers['x-rid']    = (\S\Request::server('x-rid') ?: '');
        $headers['x-source'] = APP_NAME;
        $headers['x-time']   = $time;
        $headers['x-fields'] = implode(",", $fields_keys);
        $headers['x-m']      = md5(APP_NAME . "|" . $time . "|" . $secret . "|" . implode("", $fields_values));

        return $headers;
    }

    /**
     * 校验http方法是否合法
     *
     * @param string $method
     *
     * @return bool
     */
    private function _checkMethod($method) {
        return in_array($method, self::$_method);
    }

    /**
     * 校验options选项是否合法
     *
     * @param array $options
     *
     * @return bool
     */
    private function _checkOptions(array $options) {
        if ($options) {
            return empty(array_diff(array_keys($options), self::$_options_sets));
        } else {
            return true;
        }
    }

    /**
     * 记录资源调用时间
     *
     * @param string $method http方法 get|post etc.
     * @param string $path   请求资源相对路径 e.g. foo/bar foo/bar?a=1&b=2
     * @param mixed  $data   请求数据
     */
    private function _setStrace($method, $path, $data) {
        \S\Strace\Timelog::instance()->log('http', array(
            'class'    => get_class($this),
            'method'   => $method,
            'resource' => $this->_base_uri . $path,
            'params'   => $data,
        ));
    }

}