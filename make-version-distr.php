<?php

require 'install/version.php';
$version = $arModuleVersion['VERSION'];
$filename = "$version.tar.gz";
echo $version;
/*echo "Copying from .last_version.tar.gz to $filename...\n";
copy('../.last_version.tar.gz', "../$filename");
echo "Copied!\n";*/
