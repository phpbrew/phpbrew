#/usr/bin/env sh

function fetch_remote_versions() {
  local php_file_pattern sources html versions

  php_file_pattern="/php-[0-9.]*\\.tar\\.bz2/"
  sources=('http://www.php.net/downloads.php' 'http://www.php.net/releases/')

  versions=()
  for source in ${sources[@]}; do
    html=$(curl -sSL ${source})

    if test -z "$html" -o "$?" -ne 0; then
      continue
    fi

    for version in $(echo "$html" | awk "match(\$0, $php_file_pattern) {
      print substr(\$0, RSTART + 4, RLENGTH - 12) }"); do
      versions=("${versions[@]}" "${version}")
    done
  done

  echo "${versions[@]}"
}
