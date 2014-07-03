#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source ${DIR}/../../../src/php/compiler.sh

function setup() {
  mock__make_function_do_nothing 'cd'
  mock__make_function_do_nothing 'test'
  mock__make_function_do_nothing './buildconfig'
  mock__make_function_do_nothing './configure'
}

function test_create_configure_file_if_none_exists() {
  mock__make_function_call 'test' 'return 0'
  mock__make_function_call './buildconfig' 'done=true'
  configure /build/dir /prefix/dir
  assertion__equal 'true' "${done}"
}

function test_can_run_configure() {
  mock__make_function_call './configure' '_configure_mock $@'
  configure /build/dir /prefix/dir
}

function _configure_mock() {
  assertion__equal '--prefix /prefix/dir' "--prefix $2"
}
