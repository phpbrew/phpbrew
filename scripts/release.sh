#!/bin/bash
bash scripts/compile.sh
onion build
git commit -am "Release"
git push origin HEAD
