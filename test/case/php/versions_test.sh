#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source ${DIR}/../../../src/php/versions.sh

function test_can_fetch_all_remote_versions_greater_than_4() {
  mock__make_function_call "curl" "_curl_mock \$@"
  assertion__equal '5.5.14 5.4.30 5.3.28 5.5.13 5.5.12 5.5.11 5.5.10 5.5.9 5.5.8 5.5.7 5.5.6 5.5.5 5.5.4 5.5.3 5.5.2 5.5.1 5.5.0 5.4.29 5.4.28 5.4.27 5.4.26 5.4.25 5.4.24 5.4.23 5.4.22 5.4.21 5.4.20 5.4.19 5.4.18 5.4.17 5.4.16 5.4.15 5.4.14 5.4.13 5.4.12 5.4.11 5.4.10 5.4.9 5.4.8 5.4.7 5.4.6 5.4.5 5.4.4 5.4.3 5.4.2 5.4.1 5.4.0 5.3.27 5.3.26 5.3.25 5.3.24 5.3.23 5.3.22 5.3.21 5.3.20 5.3.19 5.3.18 5.3.17 5.3.16 5.3.15 5.3.14 5.3.13 5.3.12 5.3.11 5.3.10 5.3.9 5.3.8 5.3.7 5.3.6 5.3.5 5.3.4 5.2.17 5.2.16 5.2.15 5.3.3 5.2.14 5.3.2 5.2.13 5.3.1 5.2.12 5.2.11 5.3.0 5.2.10 5.2.9 5.2.8 5.2.6 5.2.5 5.2.4 5.2.3 5.2.2 5.2.1 5.2.0 5.1.6 5.1.5 5.1.4' "$(fetch_remote_versions -o)"
}

function test_by_default_only_fetch_versions_greater_than_or_equal_to_53() {
  mock__make_function_call "curl" "_curl_mock \$@"
  assertion__equal '5.5.14 5.4.30 5.3.28 5.5.13 5.5.12 5.5.11 5.5.10 5.5.9 5.5.8 5.5.7 5.5.6 5.5.5 5.5.4 5.5.3 5.5.2 5.5.1 5.5.0 5.4.29 5.4.28 5.4.27 5.4.26 5.4.25 5.4.24 5.4.23 5.4.22 5.4.21 5.4.20 5.4.19 5.4.18 5.4.17 5.4.16 5.4.15 5.4.14 5.4.13 5.4.12 5.4.11 5.4.10 5.4.9 5.4.8 5.4.7 5.4.6 5.4.5 5.4.4 5.4.3 5.4.2 5.4.1 5.4.0 5.3.27 5.3.26 5.3.25 5.3.24 5.3.23 5.3.22 5.3.21 5.3.20 5.3.19 5.3.18 5.3.17 5.3.16 5.3.15 5.3.14 5.3.13 5.3.12 5.3.11 5.3.10 5.3.9 5.3.8 5.3.7 5.3.6 5.3.5 5.3.4 5.3.3 5.3.2 5.3.1 5.3.0' "$(fetch_remote_versions)"
}

function test_can_get_most_recent_version() {
  mock__make_function_call "curl" "_curl_mock \$@"
  assertion__equal 5.5.14 "$(get_most_recent_version)"
}

function test_can_get_latest_minor_release_for_major_release() {
  mock__make_function_call "curl" "_curl_mock \$@"
  assertion__equal 5.5.14 "$(get_latest_for_major 5.5)"
  assertion__equal 5.3.28 "$(get_latest_for_major 5.3)"
}

function _curl_mock() {
  case $2 in
    'http://www.php.net/downloads.php')
      cat ${DIR}/../../fixtures/html/php_net_downloads.html
      ;;
    'http://www.php.net/releases/')
      cat ${DIR}/../../fixtures/html/php_net_releases.html
      ;;
  esac
}
