#!/usr/bin/env bash

function fetch_remote_versions() {
  local php_file_pattern sources html versions with_old

  local OPTIND
  while getopts 'o' option; do
    case "${option}" in
      o)
        with_old=true
        ;;
    esac
  done

  php_file_pattern="$(get_php_file_pattern)"
  sources=('http://www.php.net/downloads.php' 'http://www.php.net/releases/')

  versions=()
  for source in ${sources[@]}; do
    html=$(curl -sSL ${source})

    if test -z "$html" -o "$?" -ne 0; then
      continue
    fi

    for version in $(echo "${html}" | awk "match(\$0, ${php_file_pattern}) {
      print substr(\$0, RSTART + 4, RLENGTH - 12) }"); do
      if ! is_version_lower_than ${version} "5.3.0" || [[ ${with_old} = true ]]; then
        if ! is_version_lower_than ${version} "5.0.0"; then
          versions=("${versions[@]}" "${version}")
        fi
      fi
    done
  done

  echo "${versions[@]}"
}

function is_version_lower_than() {
  [ "$1" = "$2" ] && return 1 || is_version_lower_than_or_equal $1 $2
}

function is_version_lower_than_or_equal() {
  [ "$1" = "$(echo -e "$1\n$2" | sort | head -n 1)" ]
}

function get_most_recent_version() {
  curl -sSL 'http://www.php.net/downloads.php' | awk "match(\$0, $(get_php_file_pattern) ) { print substr(\$0, RSTART + 4, RLENGTH - 12) }" | head -1
}

function get_php_file_pattern() {
  echo "/php-[0-9.]*\\.tar\\.bz2/"
}
