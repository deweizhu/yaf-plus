<?php
/**
 * 定义系统必备的常量
 */
define('DOCROOT', realpath(__DIR__ . '/../'));
define('SYSPATH', DOCROOT);
define('APPPATH', DOCROOT . '/application');
define('VIEWPATH', APPPATH . '/views');
define('PUBPATH', __DIR__);
define('STORAGEPATH', DOCROOT . '/storage');
define('RESPATH', DOCROOT . '/resources');

$application = new Yaf_Application(DOCROOT . '/conf/application.ini');
$application->bootstrap()->run();
