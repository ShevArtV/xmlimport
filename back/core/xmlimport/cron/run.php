<?php
// /usr/local/php/php-7.4/bin/php -d display_errors -d error_reporting=E_ALL /home/host1860015/art-sites.ru/htdocs/xml/core/xmlimport/cron/run.php

define('MODX_API_MODE', true);
require_once  dirname(__FILE__, 4) . '/index.php';
require_once  dirname(__FILE__, 2) . '/logger.php';
require_once  dirname(__FILE__, 2) . '/loadconfig.php';
require_once  dirname(__FILE__, 2) . '/downloadfeed.php';
require_once  dirname(__FILE__, 2) . '/readingfile.php';
require_once  dirname(__FILE__, 2) . '/importfeed.php';

$logger = new Logger();
$configPath = "core/xmlimport/configs/{$argv[1]}.config.php";
if(!$config = LoadConfig::getConfig($configPath)){
    $logger->log('Конфигурация не была загружена из файла ' . $configPath, [], 1);
}
if($config['feedUrl']){
    if(!DownloadFeed::download($config['feedUrl'], $config['feedPath'])){
        $logger->log('Не удалось загрузить файл фида по ссылке ' . $config['feedUrl'], [], 1);
    }
}
$rf = new ReadingFile($config, $logger);
$rf->read();
unset($rf);
$importfeed = new ImportFeed($config, $logger, $modx);
$importfeed->import();
unset($importfeed);

