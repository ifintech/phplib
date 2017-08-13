<?php
namespace Base;

/**
 * Class Bootstrap
 *
 * @package Base
 * @desc    进行初始化工作
 */
class Bootstrap extends \Yaf\Bootstrap_Abstract {

    /**
     * 全局设置开发加载
     */
    public function _initBaseSet(\Yaf\Dispatcher $dispatcher) {
        //加载APP相关设置
        require APP_CONF . "/defined.php";
        //注册异常处理函数
        set_exception_handler(array("\\Core\\Error", "handle"));

        return true;
    }

    /**
     * 在此处注册非YAF的autoload
     * 注册YAF的localnamespace和map
     */
    public function _initBaseLoader() {
        //yaf autoloader
        \Yaf\Loader::getInstance()->registerLocalNamespace(\Core\Env::$namespace);
        //自身 autoloader
        \Core\Loader::init();

        return true;
    }

    /**
     * 注册插件
     *
     * @param \Yaf\Dispatcher $dispatcher
     *
     * @return bool
     */
    public function _initPlugin(\Yaf\Dispatcher $dispatcher) {
        $dispatcher->registerPlugin(new Plugin\Base());

        return true;
    }

    /**
     * 确定输出方式
     *
     * @param \Yaf\Dispatcher $dispatcher
     *
     * @return bool
     */
    public function _initBaseResponse(\Yaf\Dispatcher $dispatcher) {
        //关闭自动渲染
        $dispatcher->autoRender(false);

        //cli模式下response是Yaf_Response_Cli
        if ($dispatcher->getRequest()->getMethod() == "CLI") {
            return true;
        }

        //初始化请求类型
        if (\S\Request::server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest') {
            \S\Response::setFormatter(\S\Response::FORMAT_JSON);
        } else {
            \S\Response::setFormatter(\S\Response::FORMAT_HTML);
        }

        return true;
    }

    /**
     * 注册日志模式
     */
    public function _initBaseLog() {
        if (\Core\Env::isProductEnv()) {
            \S\Log\Logger::getInstance()->pushHandler(new \S\Log\Handler\Stdout());
        } else {
            \S\Log\Logger::getInstance()->pushHandler(new \S\Log\Handler\Stdout());
        }

        return true;
    }

    /**
     * 开启debug
     */
    public function _initBaseDebug() {
        if (\Core\Env::getEnvName() === APP_ENVIRON_DEV || \Core\Env::getEnvName() === APP_ENVIRON_TEST) {
            ini_set('display_errors', true);
            ini_set('xdebug.var_display_max_depth', 10);
            error_reporting(E_ALL ^ E_NOTICE);
        }

        return true;
    }

}