<?php
namespace S\Log\Handler;

class Stdout extends Abstraction {

    public function write($level, $message, $to_path) {
        $log_path = $this->getPath($level, $to_path);
        $message['log_path'] = $log_path;
        $message = json_encode($message, JSON_UNESCAPED_UNICODE);

        //todo php7.1通过tail -f日志文件实现标准输出 预计到php7.3会优化此记录方式
        file_put_contents("/tmp/php.log", $message, FILE_APPEND | LOCK_EX);

        return true;
    }

}