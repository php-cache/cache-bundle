<?php
/**
 * @author    Aaron Scherer
 * @date      12/11/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0Elephant
 */

if( !@include __DIR__ . '/../../../../../vendor/autoload.php' ) {
	die( "You must set up the project dependencies, run the following commands:
wget http://getcomposer.org/composer.phar
php composer.phar install --dev
" );
}