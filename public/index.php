<?php
/**
 * 定义系统必备的常量
 */

define('DOCROOT', realpath(__DIR__.'/../'));
define('SYSPATH', DOCROOT);
define('APPPATH', DOCROOT . '/application');
define('MODPATH', APPPATH . '/library');
define('VIEWPATH', APPPATH . '/views');

define('PUBPATH', __DIR__ );
define('PUBLICPATH', DOCROOT.'/public');
define('STORAGEPATH', DOCROOT.'/storage');
define('UPLOAD_PATH', DOCROOT.'/storage/upload');

$application = new Yaf\Application(DOCROOT . '/conf/application.ini');

$application->bootstrap()->run();

