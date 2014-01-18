#!/usr/bin/env bash

mock_lsb_release() {
  export lsb_value

  lsb_value=$1
}

lsb_release() {
  echo $lsb_value
}

port() {
  export port_args

  port_args=$*
}

brew() {
  export brew_args

  brew_args="$brew_args $*"
}
