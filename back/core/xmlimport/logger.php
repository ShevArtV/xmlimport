<?php

class Logger
{
    public function __construct($filename = '/log.txt')
    {
        $this->logpath = dirname(__FILE__) . $filename;
    }

    public function log($msg, $data = [], $isError = false)
    {
        if (!empty($data)) {
            $text = date('d.m.Y H:i:s') . ' ' . $msg . print_r($data, 1) . PHP_EOL;
        } else {
            $text = date('d.m.Y H:i:s') . ' ' . $msg . PHP_EOL;
        }
        file_put_contents($this->logpath, $text, FILE_APPEND);
        if ($isError) die();
    }
}