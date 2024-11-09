<?php

/**
 * @description Данный обработчик позволяет дополнительно обработать отдельные поля ресурса перед сохранением в БД.
 * @param array $data - массив данных ресурса
 * @param string $type - тип ресурса offer - товар, category - категория
 * @param xmlimport\Logger $logger - класс для вывода логов
 *
 * @return array
 */
function test($data, $type, $logger){
    $logger->log('test customHandlers ', $type);
    if ($type === 'offer') {
        $data['longtitle'] = $type;
    }

    return $data;
}
