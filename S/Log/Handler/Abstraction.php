<?php
namespace S\Log\Handler;

abstract class Abstraction {

    public function handle($level, $message, $to_path){
        $path = $this->getPath($level, $to_path);
        $this->write($path, $message);
        return true;
    }

    /**
     * 获取存储的关键key
     * 在文件存储里就是文件名
     *
     * @param $level
     * @param $to_path
     * @return string
     */
    protected function getPath($level, $to_path){
        $level = strtolower($level);
        if(!$to_path){
            if(\Core\Env::isCli()){
                $cli_classname = \Core\Env::getCliClass();
                $cli_classname = strtolower(str_replace('\\', '/', $cli_classname));
                $key = $cli_classname . "/" . date("Ym") . "/" . $level . "." . date("Ymd") . ".log";
            }else{
                $module_name = strtolower(\Core\Env::getModuleName(true));
                if ($module_name === 'index') {
                    $module_name = "";
                }
                $controller_name = strtolower(\Core\Env::getControllerName(true));
                $controller_name = strtolower(str_replace('_', '/', $controller_name));
                $key = $module_name . "/" . $controller_name . "/" . date("Ym") . "/" . $level . "." . date("Ymd") . ".log";
            }
        }else{
            $key = $to_path."/".date("Ym")."/".$level.".".date("Ymd").".log";;
        }

        return $key;
    }

    /**
     * 写日志具体方法
     *
     * @param $key
     * @param $message
     * @return mixed
     */
    abstract protected function write($key, $message);

}
