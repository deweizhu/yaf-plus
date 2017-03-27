<?php
/**
 * 定义系统必备的常量
 */
define('DOCROOT', __DIR__);
define('SYSPATH', __DIR__);
define('APPPATH', __DIR__ . '/application');
define('PUBPATH', __DIR__ . '/public');
define('VIEWPATH', APPPATH . '/views');
define('STORAGEPATH', __DIR__ . '/storage');
define('RESPATH', __DIR__ . '/resources');

$application = new Yaf_Application(DOCROOT . '/conf/application.ini');
$application->bootstrap()->run();
