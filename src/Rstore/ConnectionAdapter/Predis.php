<?php

namespace Rstore\ConnectionAdapter;

use Predis\Client,
    Rstore\Connection;

class Predis implements Connection {

    protected $connection = null;

    public function __construct(Client $connection) {
        $this->connection = $connection;
    }

    public function rpush($key, $value) {
        return $this->connection->rpush($key, $value);
    }

    public function hset($hash, $key, $value) {
        return $this->connection->hset($hash, $key, $value);
    }

    public function hget($hash, $key) {
        return $this->connection->hget($hash, $key);
    }

    public function hgetall($hash) {
        return $this->connection->hgetall($hash);
    }

    public function hincrby($hash, $key, $amount) {
        return $this->connection->hincrby($hash, $key, $amount);
    }

    public function lrange($list, $start, $stop) {
        return $this->connection->lrange($list, $start, $stop);
    }

    public function llen($list) {
        return $this->connection->llen($list);
    }

    public function zadd($set, $score, $key) {
        return $this->connection->zadd($set, $score, $key);
    }

    public function zrange($set, $start, $stop) {
        return $this->connection->zrange($set, $start, $stop);
    }

    public function zrevrange($set, $start, $stop) {
        return $this->connection->zrevrange($set, $start, $stop);
    }

    public function flushdb() {
        return $this->connection->flushdb();
    }
}
