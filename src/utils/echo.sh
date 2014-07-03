#!/usr/bin/env bash

echo_info() {
  printf "\033[00;34m$1\033[0m"
}

echo_user() {
  printf "\033[0;33m$1\033[0m"
}

echo_success() {
  printf "\033[00;32m$1\033[0m"
}

echo_fail() {
  printf "\033[00;31m$1\033[0m"
}
