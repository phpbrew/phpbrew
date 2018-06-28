<?php
/**
 * This file is part of the Universal package.
 *
 * (c) Yo-An Lin <yoanlin93@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Universal\ClassLoader;

interface ClassLoader { 

    public function register($prepend = false);

    public function unregister();

    public function resolveClass($fullclass);

    public function loadClass($class);

}


