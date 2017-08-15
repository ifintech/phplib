<?php
namespace S\Log\Handler;

class Stdout extends Abstraction {

    public function write($level, $message, $to_path) {
        $log_path = $this->getPath($level, $to_path);
        $message['log_path'] = $log_path;
        $message = json_encode($message, JSON_UNESCAPED_UNICODE);

        file_put_contents("php://stdout", $message."\n");

        return true;
    }

}