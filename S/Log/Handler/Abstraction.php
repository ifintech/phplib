<?php
namespace S\Log\Handler;

abstract class Abstraction {

    /**
     * 获取存储的关键key
     * 在文件存储里就是文件名
     *
     * @param $level
     * @param $to_path
     *
     * @return string
     */
    protected function getPath($level, $to_path) {
        $level = strtolower($level);

        if ($to_path) {
            $key = $to_path;
        } else {
            if (\Core\Env::isCli()) {
                $optind = null;
                $opts   = getopt('c:m:a:', [], $optind);

                $module        = $opts['m'] ?: 'Index';
                $cli_classname = $opts['c'];
                $action        = $opts['a'] ?: 'Index';

                $key = ('index' == strtolower($module) ? '' : $module . '/') . str_replace('_', '/', $cli_classname) . '/' . $action;
            } else {
                $key = trim(\S\Request::server('PATH_INFO'), '/');
            }
        }
        $key = APP_NAME . "/" . $key . "/" . date("Ym") . "/" . $level . "." . date("Ymd") . ".log";

        return strtolower($key);
    }

    /**
     * 写日志具体方法
     *
     * @param $level
     * @param $message
     * @param $to_path
     *
     * @return mixed
     */
    abstract public function write($level, $message, $to_path);

}
