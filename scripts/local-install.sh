#!/bin/bash
bash scripts/compile.sh
onion build
sudo pear install -f package.xml
