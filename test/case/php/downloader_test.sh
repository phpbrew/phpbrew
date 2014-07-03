#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source ${DIR}/../../../src/php/downloader.sh

function test_can_curl_php_version() {
  mock__make_function_call 'curl' 'curl_args=$@'
  download 5.5.13 /to/file
  assertion__equal '-SL -C - -# -o /to/file http://php.net/get/php-5.5.13.tar.bz2/from/this/mirror' "${curl_args}"
}
