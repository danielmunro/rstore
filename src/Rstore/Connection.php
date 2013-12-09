<?php

/**
 * This file is part of the rstore package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rstore;

/**
 * Comment
 *
 * @author Dan Munro <dan @ danmunro.com>
 */

interface Connection {

    public function rpush($key, $value);

    public function hset($hash, $key, $value);

    public function hget($hash, $key);

    public function hgetall($hash);

    public function hincrby($hash, $key, $amount);

    public function lrange($list, $start, $stop);

    public function llen($list);

    public function flushdb();
}
