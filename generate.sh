#!/bin/sh

[ -z $1 ] && exit 1
[ ! -d $1 ] && exit 1

ROOTPATH=`realpath $1`

COUNT=65

for i in `seq 1 65`
do
  TEMPDIR=`mktemp -d --tmpdir=$ROOTPATH`
  mktemp --tmpdir=$TEMPDIR --suffix='.html'
done
