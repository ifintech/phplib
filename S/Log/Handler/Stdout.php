<?php
namespace S\Log\Handler;

class Stdout extends Abstraction {

    public function write($level, $message, $to_path) {
        $log_path = $this->getPath($level, $to_path);
        $message['log_path'] = $log_path;
        $message = json_encode($message, JSON_UNESCAPED_UNICODE);

        if(\Core\Env::isCli()){
            file_put_contents("php://stdout", $message."\n");
        }else{
            file_put_contents("/tmp/php.log", $message."\n", FILE_APPEND | LOCK_EX);
        }

        return true;
    }

}