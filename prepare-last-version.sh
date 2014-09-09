cd ../

rm -rf .last_version
mkdir -p .last_version
rsync -av --progress repo/ .last_version/ --exclude .git/ --exclude .git --exclude .gitignore --exclude prepare-last-version.sh

recode utf8..cp1251 .last_version/lang/ru/options.php
recode utf8..cp1251 .last_version/lang/ru/general/giftd_helper.php
recode utf8..cp1251 .last_version/lang/ru/install/index.php

rm -f .last_version.tar.gz

tar -zcvf ".last_version.tar.gz" ".last_version"