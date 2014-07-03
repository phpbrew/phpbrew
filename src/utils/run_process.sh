#!/usr/bin/env bash

function run_process() {
  local pid

  set +o monitor

  $@ > /dev/null 2>&1 &
  pid=$!

  printf "[ ]"

  trap "kill ${pid} 2> /dev/null" EXIT
  while kill -0 ${pid} 2> /dev/null
  do
    printf "\033[0;33\b\b#\033[0m ]"
    sleep 1
  done
  trap - EXIT

  echo ""

  set -o monitor
}
