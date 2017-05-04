<?php
namespace Base\Test;

class TestCase extends \PHPUnit_Framework_TestCase {
    /**
     * @var \Yaf\Application
     */
    protected static $_app;

    public function setUp(){
        $application = \Yaf\Registry::get('application');
        if (!$application) {
            self::$_app = (new \Yaf\Application(APP_CONF . '/application.ini'))->bootstrap();
            \Yaf\Registry::set('application', self::$_app);
        }else{
            self::$_app = $application;
        }
    }

    protected function execRequest($method, $controller, $params = array()){
        $request = new \Yaf\Request\Simple($method, 'index', $controller, 'index', $params);
        $_REQUEST = $request->getParams();
        if(strtolower($method) === "post"){
            $_POST = $request->getParams();
        }else{
            $_GET = $request->getParams();
        }
        self::$_app->getDispatcher()->dispatch($request);
        $result = \Yaf\Registry::get('test_response');
        \Yaf\Registry::del('test_response');
        return json_decode($result, true)?:$result; // 兼容改前的情况
    }

    /**
     * 获取刚发送的验证码(仅支持通过Captcha类发送的验证码)
     * @return mixed
     */
    protected function getCaptcha(){
        $code = \Yaf\Registry::get('captcha');
        \Yaf\Registry::del('captcha');
        return $code;
    }
}