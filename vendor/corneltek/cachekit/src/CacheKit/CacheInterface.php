<?php
/*
 * This file is part of the CacheKit package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace CacheKit;

interface CacheInterface
{
    public function get($key);
    public function set($key,$value,$ttl = 0);
    public function remove($key);
    public function clear();
}

