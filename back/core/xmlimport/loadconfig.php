<?php

class LoadConfig{
    public static function getConfig($configPath){
        $basePath = dirname(__FILE__, 3) . '/';
        return include $basePath . $configPath;
    }
}