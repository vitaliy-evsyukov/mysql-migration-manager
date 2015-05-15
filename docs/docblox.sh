#!/usr/bin/env sh
DOCBLOX=`which phpdoc`
rm -rf docblox/*
$DOCBLOX -v -c config.xml