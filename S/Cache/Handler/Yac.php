<?php
namespace S\Cache\Handler;

/**
 * Class \S\Cache\Yac
 *
 *
 * Yac is lockless, that means, there could be a chance you will get a wrong data
 * (depends on how many key slots are allocated and how many keys are stored),
 * so you'd better make sure that your product is not very sensitive to that.
 * According my test(I used the this for test script https://github.com/laruence/yac/blob/master/tests/yac_conflict.php),
 * there is 1/10000000 chance you will get a wrong data, but in the real application, this chance must be less.
 *
 */
class Yac extends Abstraction {
    /**
     * @var \Yac $yac
     */
    protected $yac = null;

    /**
     * @param string $key
     *
     * @return string
     */
    public function get($key) {
        //后台管理不用 local cache
        if (0 === strpos(strtolower(\S\Request::server('PATH_INFO')), '/admin')) {
            return null;
        }

        return $this->getInstance()->get($this->hashKey($key));
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $expire
     *
     * @return mixed
     */
    public function set($key, $value, $expire = 60) {
        return $this->getInstance()->set($this->hashKey($key), $value, $expire);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function del($key) {
        return $this->getInstance()->delete($this->hashKey($key));
    }

    /**
     * 去掉危险操作的功能
     *
     * @return bool
     */
    public function flush() {
        return false;
    }

    public function close() {
        $this->yac = null;

        return true;
    }

    public function __destruct() {
        $this->close();
    }

    /**
     * @return \Yac
     * @throws \S\Exception
     */
    public function getInstance() {
        if (!$this->yac) {
            $this->yac = new \Yac(substr(APP_NAME . "_", 0, 16));
        }

        return $this->yac;
    }

    /**
     * 防止超出长度,yac默认限制 48位
     *
     * @param string $key
     *
     * @return string
     */
    private function hashKey($key) {
        return md5($key);
    }

}