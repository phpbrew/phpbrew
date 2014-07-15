#!/usr/bin/env bash

function run_process() {
  local pid
  local log_file='/dev/null'

  local OPTIND
  while getopts 'l:' option; do
    case "${option}" in
      l)
        log_file=${OPTARG}
        shift 2
        ;;
    esac
  done

  set +o monitor

  $@ >> ${log_file} 2>&1 &
  pid=$!

  trap "kill ${pid} 2> /dev/null" EXIT
  while kill -0 ${pid} 2> /dev/null
  do
    printf "\033[0;33\#\033[0m"
    sleep 1
  done
  trap - EXIT

  echo ""

  set -o monitor

  wait ${pid}
  return $?
}
