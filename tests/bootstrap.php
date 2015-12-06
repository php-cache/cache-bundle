<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$autoloadFile = false;
foreach(array(__DIR__ . '/../../../../../vendor/autoload.php', __DIR__ . '/../vendor/autoload.php') as $file) {
    if(is_file($file)) {
        $autoloadFile = $file;
    }
}

if (!$autoloadFile) {
    echo "You must set up the project dependencies, run the following commands:
wget http://getcomposer.org/composer.phar
php composer.phar install --dev
";
    exit(1);
}

require $autoloadFile;
