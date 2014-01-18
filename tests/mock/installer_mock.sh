#!/usr/bin/env bash

mock_lsb_release() {
  export lsb_value

  lsb_value=$1
}

lsb_release() {
  echo $lsb_value
}
