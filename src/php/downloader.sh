#!/usr/bin/env bash

function download() {
  curl -SL -# -o "$2" "http://php.net/get/php-$1.tar.bz2/from/this/mirror"
}
