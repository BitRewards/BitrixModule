#!/usr/bin/env bash
cd ../

VERSION=`php repo/make-version-distr.php`

rm -rf .last_version
rm -rf $VERSION/
mkdir -p .last_version
mkdir -p $VERSION
rsync -av --progress repo/ .last_version/ --exclude .git/ --exclude .git --exclude .gitignore --exclude prepare-last-version.sh --exclude .idea --exclude make-version-distr.php

recode utf8..cp1251 .last_version/lang/ru/options.php
recode utf8..cp1251 .last_version/lang/ru/general/giftd_helper.php
recode utf8..cp1251 .last_version/lang/ru/install/index.php
recode utf8..cp1251 .last_version/description.ru

cp -r .last_version/ $VERSION/

cp .last_version/description.* ./

rm -f .last_version.tar.gz
rm -f $VERSION.tag.gz

export COPYFILE_DISABLE=true


tar -zcvf ".last_version.tar.gz" ".last_version" description.*
tar -zcvf "$VERSION.tar.gz" "$VERSION" description.*

tar -zcvf ".last_version.tar.gz" ".last_version"

cd repo

echo
echo "Successfully created $VERSION.tar.gz"
echo

