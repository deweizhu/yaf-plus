<?php
/**
 * 定义系统必备的常量
 */
define('DOCROOT', realpath(__DIR__ . '/../'));
define('SYSPATH', DOCROOT);
define('APPPATH', DOCROOT . '/application');
define('MODPATH', APPPATH . '/library');

define('PUBPATH', __DIR__);
define('STORAGEPATH', DOCROOT . '/storage');
define('VIEWPATH', DOCROOT . '/resources/views');
define('RESPATH', DOCROOT . '/resources');

$application = new Yaf_Application(DOCROOT . '/conf/application.ini');
$application->bootstrap()->run();
