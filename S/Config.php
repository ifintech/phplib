<?php
namespace S;

class Config {
    protected static $config = array();

    /**
     * @param $fileName
     *
     * @return array
     * @throws Exception
     */
    public static function confFile($fileName) {
        $file   = APP_CONF . "/" . $fileName;
        $config = self::file($file);

        return $config;
    }

    /**
     * /S/Config::confServer('mysql.wechat')
     *
     * @param $key
     *
     * @return array
     * @throws Exception
     */

    public static function confServer($key) {
        return self::confType($key, "server", true);
    }

    /**
     * /S/Config::confSecurity('wechat')
     *
     * @param $key
     *
     * @return array
     * @throws Exception
     */
    public static function confSecurity($key) {
        return self::confType($key, "security", true);
    }

    /**
     * /S/Config::confError('common.succ.code')
     *
     * @param $key
     *
     * @return array
     * @throws Exception
     */
    public static function confError($key) {
        return self::confType($key, "error");
    }

    /**
     * 获取配置信息
     *
     * @param      $key
     * @param      $type
     * @param bool $env
     *
     * @return array|null
     */
    public static function get($key, $type, $env = false) {
        return self::confType($key, $type, $env);
    }

    private static function confType($key, $type, $env = false) {
        $file_path = APP_CONF . "/" . $type;
        $names = explode(".", $key);
        if($env){
            array_unshift($names, \Core\Env::getEnvName());
        }

        $file = "";
        while (!file_exists($file) && $names) {
            $file_name = array_shift($names);
            $file_path = $file_path . "/" . $file_name;
            $file      = $file_path . ".php";
        }

        $config = self::file($file);
        foreach ($names as $v) {
            if (isset($config[$v])) {
                $config = $config[$v];
            } else {
                $config = null;
                break;
            }
        }

        return $config;
    }

    /**
     * @param $file
     *
     * @return array
     * @throws Exception
     */
    public static function file($file) {
        if (!isset(self::$config[$file])) {
            if (!file_exists($file)) {
                throw new Exception("Unable to find config file $file");
            }

            $data = require $file;
            if (!is_array($data)) {
                throw new Exception("config file provided, must be an array");
            }
            self::$config[$file] = $data;
        }

        return self::$config[$file];
    }
}
