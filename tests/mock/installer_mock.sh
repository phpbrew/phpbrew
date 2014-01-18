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

sudo() {
  $*
}

apt-get() {
  export apt_args

  apt_args="$apt_args $*"
}

curl() {
  export curl_args

  curl_args=$*
}

chmod() {
  export chmod_args

  chmod_args=$*
}

mv() {
  export mv_args

  mv_args=$*
}
