<?php
namespace S\Log\Handler;

class File extends Abstraction {

    public function write($level, $message, $to_path) {
        $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        $log_path = $this->getPath($level, $to_path);
        $file_path = '/data1/logs/' . $log_path;
        $dir_path  = dirname($file_path);
        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0777, true);
        }

        // 32位系统日志小于1G
        if (PHP_INT_MAX <= 2147483647 && file_exists($file_path) && filesize($file_path) > 1000000000) {
            $lock_file = '/tmp/logging.lock';
            $fp        = fopen($lock_file, 'w+');
            if ($fp && flock($fp, LOCK_EX)) {
                clearstatcache();
                if (file_exists($file_path) && filesize($file_path) > 1000000000) {
                    rename($file_path, $file_path . '.' . date('H:i:s'));
                }
                flock($fp, LOCK_UN);
                fclose($fp);
                @unlink($lock_file);
            }
        }

        // file mode use 666
        $old = umask(0);
        $ret = true;

        if (false === file_put_contents($file_path, $message . "\n", FILE_APPEND | LOCK_EX)) {
            $ret = false;
        }
        umask($old);

        return $ret;
    }

}