#!/bin/bash
bash scripts/compile.sh
onion build --pear
sudo pear install -f package.xml
