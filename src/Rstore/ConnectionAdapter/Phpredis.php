<?php

namespace Rstore\ConnectionAdapter;

use Redis,
    Rstore\Connection;

class Phpredis implements Connection {

    protected $connection = null;

    public function __construct($host = null, $port = null, $timeout = null, $reserved = null) {
        $this->connection = new Redis();
        $this->connection->connect($host, $port, $timeout, $reserved);
    }

    public function rpush($key, $value) {
        return $this->connection->rPush($key, $value);
    }

    public function hset($hash, $key, $value) {
        return $this->connection->hSet($hash, $key, $value);
    }

    public function hget($hash, $key) {
        return $this->connection->hGet($hash, $key);
    }

    public function hgetall($hash) {
        return $this->connection->hGetAll($hash);
    }

    public function hincrby($hash, $key, $amount) {
        return $this->connection->hIncrBy($hash, $key, $amount);
    }

    public function lrange($list, $start, $stop) {
        return $this->connection->lRange($list, $start, $stop);
    }

    public function llen($list, $start, $stop) {
        return $this->connection->lLen($list, $start, $stop);
    }

    public function flushdb() {
        return $this->connection->flushDB();
    }

    public function select($dbIndex) {
        return $this->connection->select($dbIndex);
    }
}
