<?php

class ReadingFile
{
    public function __construct($config, $logger)
    {
        $this->basePath = dirname(__FILE__, 3) . '/';
        $this->logger = $logger;
        $this->config = $config;
        $this->importedDir = $this->basePath . 'assets/imported/';
        $this->importedSupplierDir = $this->importedDir . $this->config['supplier'] . '/';
    }

    public function read()
    {
        $read_time = microtime(true);
        $search = ['category', 'offer'];
        $directories = [];

        $reader = new \XMLReader;
        $success = $reader->open($this->basePath . $this->config['feedPath']);
        if (!$success) {
            $this->logger->log("[ReadingFile::read] Невозможно считать файл {$this->config['feedPath']}. Возможно он содержит ошибки XML.", [], 1);
        }
        $removedResources = [];
        foreach ($search as $item) {
            $directories[$item] = $this->importedSupplierDir  . $item . '/';
            if (!file_exists($directories[$item])) {
                $this->createDirectory($directories[$item]);
            }
            $fileNames = scandir($directories[$item]);
            unset($fileNames[0], $fileNames[1]);
            if(!empty($fileNames)){
                foreach ($fileNames as $fileName){
                    $removedResources[] =  $item . '/'.$fileName;
                }
            }
        }

        while ($reader->read()) {
            if (in_array($reader->name, $search)) {
                $xml = $reader->readOuterXML();
                if (strpos($xml, "</{$reader->name}>") === false) continue;
                if ($obj = new \SimpleXMLElement($xml)) {
                    $obj->deleted = 0;
                    $obj->id = $obj->attributes()->id ? $obj->attributes()->id->__toString() : $obj->id;
                    if (!$obj->name && $obj->__toString()) {
                        $obj->name = $obj->__toString();
                    }
                    if ($obj->description->__toString()) {
                        $obj->description = $obj->description->__toString();
                    }
                    $params = [];
                    foreach ($obj->param as $param) {
                        $key = trim($param->attributes()->name->__toString());
                        if (in_array($key, $this->config['ignore_params'])) continue;
                        $value = trim($param->__toString());
                        if(!empty($this->config['paramValueTemplates'][$key])){
                            $params[$key][] = str_replace(['{key}','{value}'], [$key, $value], $this->config['paramValueTemplates'][$key]);
                        }else{
                            $params[$key][] = $value;
                        }

                        unset($param);
                    }
                    if (!empty($params)) {
                        $obj->params = json_encode($params, JSON_UNESCAPED_UNICODE);
                    }
                    $fileName = $directories[$reader->name] . $obj->id . '.json';
                    $key = array_search($reader->name . '/' . $obj->id . '.json', $removedResources, true);
                    unset($removedResources[$key]);
                    file_put_contents($fileName, json_encode($obj, JSON_UNESCAPED_UNICODE));
                    unset($obj, $filename, $key, $id, $params);
                }
                unset($xml);
            }
        }
        $reader->close();
        unset($reader);

        if(!empty($removedResources)){
            foreach($removedResources as $filename){
                $content = json_decode(file_get_contents($this->importedSupplierDir . $filename),  true);
                $content['deleted'] = 1;
                file_put_contents($this->importedSupplierDir . $filename, json_encode($content, JSON_UNESCAPED_UNICODE));
            }
        }

        $end_time = microtime(true);
        $left['Времени, сек'] = $end_time - $read_time;
        $left['Памяти, мб'] = round(memory_get_peak_usage(true) / 1048576, 2);
        $this->logger->log('[ReadingFile::read] Затрачено ', $left);
    }

    private function createDirectory($path)
    {
        if (!is_dir($path)) {
            $this->createDirectory(dirname($path));
            mkdir($path);
        }
    }
}
