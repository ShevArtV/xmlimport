<?php

/**
 *
 */
class ImportFeed
{
    /**
     * @var array
     */
    private array $config;
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var ModX
     */
    private ModX $modx;
    /**
     * @var string|array|mixed
     */
    private string $basePath;
    /**
     * @var string
     */
    private string $supplierPath;
    /**
     * @var array
     */
    private array $vendors;
    /**
     * @var bool
     */
    private bool $isNew;

    /**
     * @param array $config
     * @param Logger $logger
     * @param ModX $modx
     */
    public function __construct(array $config, Logger $logger, ModX $modx)
    {
        $this->modx = $modx;
        $this->config = $config;
        $this->logger = $logger;
        $this->basePath = $this->modx->getOption('base_path');
        $this->supplierPath = $this->basePath . 'assets/imported/' . $this->config['supplier'] . '/';
        $this->vendors = [];
        $this->isNew = false;
    }


    /**
     * @return void
     */
    public function import()
    {
        $start_time = microtime(true);

        if ($this->config['importCategories']) {
            $this->process($this->getFileList('category'));
        }

        if ($this->config['importProducts']) {
            $this->process($this->getFileList('offer'));
        }
        $this->modx->reloadContext($this->config['ctx']);

        $end_time = microtime(true);
        $left['Времени, сек'] = $end_time - $start_time;
        $left['Памяти, мб'] = round(memory_get_peak_usage(true) / 1048576, 2);
        $this->logger->log('[ImportFeed::import] Затрачено ', $left);
    }

    /**
     * @param string $type
     * @return array
     */
    private function getFileList(string $type): array
    {
        $output['type'] = $type;
        $output['directory'] = $this->supplierPath . $type . '/';
        if (!file_exists($output['directory'])) {
            if($this->config['debug']) $this->logger->log("[ImportFeed:getFileList] Директория с файлами типа $type не найдена.", [], 1);
        }
        $output['fileList'] = scandir($output['directory']);
        unset($output['fileList'][0], $output['fileList'][1]);
        if (empty($output['fileList'])) {
            if($this->config['debug']) $this->logger->log("[ImportFeed:getFileList] Директория с файлами типа $type пустая.", [], 1);
        }

        if($this->config['debug']) $this->logger->log("[ImportFeed:getFileList] Получен список файлов типа $type.", $output);

        return $output;
    }

    /**
     * @param array $result
     * @return bool
     */
    private function process(array $result): bool
    {
        foreach ($result['fileList'] as $filename) {
            $data = json_decode(file_get_contents($result['directory'] . $filename), true);
            $data['params'] = json_decode($data['params'], true);
            $pictures = $data['picture'];
            unset($data['param']);

            $data = $this->prepareData($data, $result['type']);
            $productOptions = $data['productOptions'];
            unset($data['productOptions']);

            if (!$resource = $this->manageResource($data)) return false;

            if (!empty($productOptions) && $this->config['setOptions']) {
                $this->setOptions($productOptions, $resource);
            }

            if (!empty($data['categories'])) {
                $this->setAdditionalCategories($data['categories'], $resource->get('id'), $resource->get('parent'));
            }

            if (($this->config['setGallery'] || ($this->config['setGalleryOnlyNew'] && $this->isNew)) && !empty($pictures)) {
                $this->setGallery($pictures, $resource);
            }
            $this->isNew = false;
            unset($data);
        }
        return true;
    }

    /**
     * @param array $data
     * @param string $type
     * @return array
     */
    private function prepareData(array $data, string $type): array
    {
        if (empty($data)) {
            if($this->config['debug']) $this->logger->log('[ImportFeed::prepareData] Не переданы данные ресурса.', [], true);
        }
        $output = [];
        $output['feed_id'] = $data['id'];
        $output['supplier'] = $this->config['supplier'];
        $output['context_key'] = $this->config['ctx'];
        switch ($type) {
            case 'category':
                $output['pagetitle'] = $data['name'] ?: 'Ресурс ' . time();
                $data = array_merge($this->config['categoryDefaultFields'], $data);
                $output['parent'] = (int)$data['@attributes']['parentId'] > 0 ? $this->getParentId((int)$data['@attributes']['parentId']) : $data['parent'];
                $output = array_merge($this->config['categoryDefaultFields'], $output);
                break;
            case 'offer':
                if (!empty($this->config['productOptions'])) {
                    $output['productOptions'] = $this->getProductOptions($this->config['productOptions'], $data);
                }
                if (!empty($this->config['vendorFields'])) {
                    $vendorData = $this->getVendorData($this->config['vendorFields'], $data);
                    $output['vendor'] = $this->vendors[$vendorData['name']] ?: $this->manageVendor($vendorData);
                }

                foreach ($this->config['productFields'] as $key => $value) {
                    if (strpos($value, 'params') === 0) {
                        $k = str_replace('params_', '', $value);
                        if (is_array($data['params'][$k])) {
                            $output[$key] = count($data['params'][$k]) === 1 ? $data['params'][$k][0] : implode($this->config['valueImplodeSeparator'], $data['params'][$k]);
                        } else {
                            $output[$key] = $data['params'][$k];
                        }
                    } elseif (strpos($value, 'attr') === 0) {
                        $k = str_replace('attr_', '', $value);
                        $output[$key] = $data['@attributes'][$k];
                    } else {
                        $output[$key] = $data[$value];
                    }
                }

                $output = array_merge($this->config['productDefaultFields'], $output);

                $output['parent'] = ($data[$this->config['offerCategoryTag']] && (int)$data[$this->config['offerCategoryTag']] > 0) ?
                    $this->getParentId((int)$data[$this->config['offerCategoryTag']]) : $data['parent'];

                break;
        }

        foreach ($this->config['truncated'] as $field => $length) {
            if ($length) {
                $output[$field] = $this->truncate($output[$field], $length);
            }
        }
        $output['published'] = $data['deleted'] ? 0 : 1;
        if($this->config['debug']) $this->logger->log("[ImportFeed::prepareData] Подготовлены данные товара {$output['pagetitle']}");
        return $output;
    }

    /**
     * @param int $feedId
     * @return int
     */
    private function getParentId(int $feedId): int
    {
        $conditions = [
            'feed_id' => $feedId,
            'class_key' => 'msCategory',
            'context_key' => $this->config['ctx']
        ];
        if ($this->config['supplier']) {
            $conditions['supplier'] = $this->config['supplier'];
        }

        if (!$resource = $this->modx->getObject('modResource', $conditions)) {
            $resource = $this->manageResource($conditions);
        }
        $id = $resource->get('id');
        unset($resource);
        if($this->config['debug']) $this->logger->log("[ImportFeed::getParentId] Для feed_id = {$feedId} получен родитель {$id}.");
        return $id;
    }

    /**
     * @param array $productOptions
     * @param array $data
     * @return array
     */
    private function getProductOptions(array $productOptions, array $data): array
    {
        $output = [];
        foreach ($productOptions as $key => $optionData) {
            $optionData['key'] = $key;
            $optionData['value'] = [];
            if (!is_array($optionData['from'])) {
                $optionData['from'] = [$optionData['from']];
            }
            foreach ($optionData['from'] as $key) {
                if (strpos($key, 'params') === 0) {
                    $k = str_replace('params_', '', $key);
                    if (!$data['params'][$k]) continue;
                    $optionData['value'] = array_merge($optionData['value'], $data['params'][$k]);
                    $optionData['caption'] = $optionData['caption'] ?: $k;
                } elseif (strpos($key, 'attr') === 0) {
                    $k = str_replace('attr_', '', $key);
                    if (!$data['@attributes'][$k]) continue;
                    $optionData['value'] = array_merge($optionData['value'], [$data['@attributes'][$k]]);
                    $optionData['caption'] = $optionData['caption'] ?: $k;
                } else {
                    if (!$data['params'][$key]) continue;
                    $optionData['value'] = array_merge($optionData['value'], $data['params'][$key]);
                    $optionData['caption'] = $optionData['caption'] ?: $key;
                }
            }

            unset($optionData['from']);
            $output[] = $optionData;
        }
        if($this->config['debug']) $this->logger->log("[ImportFeed::getProductOptions] Получены опции для feed_id = {$data['id']}. ", $output);
        return $output;
    }

    /**
     * @param array $vendorFields
     * @param array $data
     * @return array
     */
    private function getVendorData(array $vendorFields, array $data): array
    {
        $vendorData = [];

        foreach ($vendorFields as $k => $v) {
            if (strpos($v, 'params') === 0) {
                $v = str_replace('params_', '', $v);
                $vendorData[$k] = $data['params'][$v];
            } else {
                $vendorData[$k] = $data[$v];
            }
        }
        if($this->config['debug']) $this->logger->log("[ImportFeed::getVendorData] Получены данные производителя. ", $vendorData);
        return $vendorData;
    }

    /**
     * @param array $vendorData
     * @return int
     */
    private function manageVendor(array $vendorData): int
    {
        if (!$vendorData['name']) return 0;

        if ($vendorData['logo']) {
            $logoPath = $this->basePath . $this->config['imagePath'] . basename($vendorData['logo']);
            if (!file_exists($logoPath)) {
                $this->download($vendorData['logo'], $logoPath);
            }
            $vendorData['logo'] = $this->config['imagePath'] . basename($vendorData['logo']);
        }
        if (!$vendor = $this->modx->getObject('msVendor', ['name' => $vendorData['name']])) {
            $vendor = $this->modx->newObject('msVendor');
        }
        $vendor->fromArray($vendorData, '', 1);
        $vendor->save();
        $id = $vendor->get('id');
        $this->vendors[$vendorData['name']] = $id;
        unset($vendor);
        if($this->config['debug']) $this->logger->log("[ImportFeed:manageVendor] Обработан производитель {$vendorData['name']}.");
        return $id;
    }

    /**
     * @param array $data
     * @return false|object
     */
    private function manageResource(array $data)
    {
        if (empty($data)) {
            if($this->config['debug']) $this->logger->log('[ImportFeed::manageResource] Не переданы данные ресурса.', [], true);
        }
        if (!$data['pagetitle']) {
            $data['pagetitle'] = 'Ресурс ' . time();
        } else {
            if ($this->config['createUniquePagetitle']) {
                $data['pagetitle'] .= ' ' . $this->config['supplier'] . ' ' . $data['feed_id'];
            }
        }
        foreach ($this->config['truncated'] as $field => $length) {
            if ($length) {
                $data[$field] = $this->truncate($data[$field], $length);
            }
        }

        if (empty($this->config['searchConditions'][$data['class_key']])) {
            if($this->config['debug']) $this->logger->log('[ImportFeed::manageResource] Не указаны условия проверки существования ресурса.', [], true);
        }

        $conditions = $this->config['searchConditions'][$data['class_key']];
        foreach ($data as $key => $v) {
            $conditions = str_replace('{' . $key . '}', $v, $conditions);
            if (in_array($key, $this->config['fieldTypes']['array'])) {
                $data[$key] = !is_array($v) ? explode($this->config['valueImplodeSeparator'], $v) : $v;
            }
            elseif (in_array($key, $this->config['fieldTypes']['bool'])) {
                $v = is_array($v) ? $v[0] : $v;
                $data[$key] = in_array($v, ['true', 'YES', 'Y', 'Да', '1']) ? 1 : 0;
            }else{
                $data[$key] = is_array($v) ? implode($this->config['valueImplodeSeparator'], $v) : $v;
            }
        }

        $c = $this->modx->newQuery($data['class_key']);
        if ($data['class_key'] === 'msProduct') {
            $c->leftJoin('msProductData', 'Data');
        }
        $c->where($conditions);
        $c->prepare();
        if($this->config['debug']) $this->logger->log('[ImportFeed::manageResource] SQL для поиска ресурса: ' . $c->toSQL());
        $resource = $this->modx->getObject($data['class_key'], $c);

        switch ($this->config['importMode']) {
            case 'create':
                if (!$resource) {
                    $resource = $this->modx->newObject($data['class_key']);
                    $this->isNew = true;
                }
                break;
            case 'update':
                if ($resource) {
                    $data['parent'] = $this->config['updateCategoryStructure'] ? $data['parent'] : $resource->get('parent');
                    if (!empty($this->config['noUpdate'])) {
                        foreach ($this->config['noUpdate'] as $fieldName) {
                            unset($data[$fieldName]);
                        }
                    }
                }
                break;
            default:
                if (!$resource) {
                    $resource = $this->modx->newObject($data['class_key']);
                    $this->isNew = true;
                } else {
                    $data['parent'] = $this->config['updateCategoryStructure'] ? $data['parent'] : $resource->get('parent');
                }
                break;
        }

        if (!$resource) {
            if($this->config['debug']) $this->logger->log('[ImportFeed::manageResource] Ресурс не получен.');
            return false;
        }

        if ($this->config['saveAlias'] && $data['url']) {
            $url = explode('/', $data['url']);
            $data['alias'] = $url[count($url) - 1];
        } else {
            $pagetitle = $data['pagetitle'];
            if ($this->config['createUniqueAlias'] && !$this->config['createUniquePagetitle']) {
                $pagetitle .= ' ' . $this->config['supplier'] . ' ' . $data['feed_id'];
            }
            $data['alias'] = $resource->get('alias') ?: $this->translit($pagetitle);
        }

        $data['updatedon'] = time();
        $type = $data['class_key'] === 'msCategory' ? 'category/' : 'offer/';
        $fileName = $this->supplierPath . $type . $data['feed_id'] . '.json';
        if ($data['deleted'] && file_exists($fileName)) {
            unlink($fileName);
        }
        unset($data['productOptions']);
        if($this->config['debug']) $this->logger->log('[ImportFeed::manageResource] Был обработан ресурс со следующими данными. ', $data);
        $resource->fromArray($data, '', 1);
        $resource->save();
        return $resource;

    }

    /**
     * @param array $options
     * @param modResource $resource
     * @return void
     */
    private function setOptions(array $options, modResource $resource): void
    {
        if($this->config['debug']) $this->logger->log("[ImportFeed::setOptions] Опции товара. ", ['options' => $options, 'rid' => $resource->id]);
        foreach ($options as $optionData) {
            $option = $this->manageOption($optionData['key'], $optionData);
            $this->manageCategoryOption($option, $resource);
            $this->manageProductOption($option, $resource, $optionData['value']);
            unset($option);
        }
    }

    /**
     * @param string $key
     * @param array $optionData
     * @return msOption
     */
    private function manageOption(string $key, array $optionData): msOption
    {
        $optionData[$key] = $key;
        $optionData['type'] = $optionData['type'] ?: 'textfield';
        $properties = [];
        switch ($optionData['type']) {
            case 'combo-multiple':
            case 'combobox':
                $properties['values'] = $optionData['value'];
                break;
        }
        $optionData['properties'] = $optionData['properties'] ?: $properties;
        if (!$option = $this->modx->getObject('msOption', ['key' => $key])) {
            $option = $this->modx->newObject('msOption');
            $option->fromArray($optionData, '', true);
            $option->save();
            if($this->config['debug']) $this->logger->log("[ImportFeed::manageOption] Опция {$key} создана.");
        } else {
            if($this->config['debug']) $this->logger->log("[ImportFeed::manageOption] Опция {$key} уже существует.");
            switch ($optionData['type']) {
                case 'combo-multiple':
                case 'combobox':
                    if ($properties = $option->get('properties')) {
                        $optionData['properties'] = array_merge($properties, $optionData['properties']);
                    }
                    break;
            }
            $option->fromArray($optionData);
            $option->save();
        }
        return $option;
    }

    /**
     * @param $option
     * @param $res
     * @return void
     */
    private function manageCategoryOption($option, $res): void
    {
        $table = $this->modx->getTableName('msCategoryOption');
        if (!$this->modx->getObject('msCategoryOption', array('option_id' => $option->id, 'category_id' => $res->parent))) {
            $sql = "INSERT INTO {$table} (`option_id`,`category_id`,`active`, `required`, `value`) VALUES ({$option->id}, {$res->parent}, 1, 0, '')";
            $stmt = $this->modx->prepare($sql);
            $stmt->execute();
            if($this->config['debug']) $this->logger->log("[ImportFeed::manageCategoryOption] Опция для категории создана.");
        } else {
            $q = $this->modx->newQuery('msCategoryOption');
            $q->command('UPDATE');
            $q->where(['option_id' => $option->id, 'category_id' => $res->parent]);
            $q->set(['active' => 1]);
            $q->prepare();
            $q->stmt->execute();
            if($this->config['debug']) $this->logger->log("[ImportFeed::manageCategoryOption] Опция для категории обновлена.");
        }
    }

    /**
     * @param $option
     * @param $res
     * @param $val
     * @return void
     */
    private function manageProductOption($option, $res, $val): void
    {
        if (!is_array($val)) {
            $val = [$val];
        } elseif (in_array($option->get('type'), ['textfield', 'textarea'])) {
            $val = [implode($this->config['valueImplodeSeparator'], $val)];
        }

        $this->modx->removeCollection('msProductOption', ['key' => $option->key, 'product_id' => $res->id]);
        $table = $this->modx->getTableName('msProductOption');

        foreach ($val as $v) {
            switch ($option->get('type')) {
                case 'datefield':
                    $v = '"' . date('Y-m-d', strtotime($v)) . '"';
                    break;
                case 'numberfield':
                    $v = (float)$v;
                    break;
                case 'combo-boolean':
                case 'checkbox':
                    $v = in_array($v, ['true', 'YES', 'Y', 'Да', '1']) ? 1 : 0;
                    $v = '"' . $v . '"';
                    break;
                default:
                    $v = '"' . $v . '"';
                    break;
            }
            $sql = "INSERT INTO {$table} (`product_id`,`key`,`value`) VALUES ({$res->id}, \"{$option->key}\", {$v});";
            $stmt = $this->modx->prepare($sql);
            $stmt->execute();
            if($this->config['debug']) $this->logger->log("[ImportFeed::manageProductOption]  Установлено значение $v для опция с ключом {$option->key}.");
        }
    }

    /**
     * @param string $categories
     * @param int $product_id
     * @param int $parent
     * @return void
     */
    private function setAdditionalCategories(string $categories, int $product_id, int $parent): void
    {
        $this->modx->removeCollection('msCategoryMember', ['product_id' => $product_id]);
        $categories = explode($this->config['valueExplodeSeparator'], $categories);
        foreach ($categories as $category) {
            if ($categoryObj = $this->modx->getObject('msCategory', [
                'feed_id' => $category,
                'supplier' => $this->config['supplier'],
                'context_key' => $this->config['ctx'],
            ])) {
                if ($categoryObj->get('id') === $parent) continue;
                $categoryMember = $this->modx->newObject('msCategoryMember');
                $categoryMember->fromArray([
                    'product_id' => $product_id,
                    'category_id' => $categoryObj->get('id')
                ], '', true);
                $categoryMember->save();
            }
        }
    }

    /**
     * @param $photos
     * @param $resource
     * @return void
     */
    private function setGallery($photos, $resource): void
    {
        if (!is_dir($this->basePath . $this->config['imagePath'])) {
            mkdir($this->basePath . $this->config['imagePath'], 0700, 1);
        }
        if($this->config['debug']) $this->logger->log("[ImportFeed::setGallery] Устанавливаем галерею ", $photos);
        if ($this->config['removeOldFiles']) {
            $this->modx->removeCollection('msProductFile', ['product_id' => $resource->get('id')]);
            if($this->config['debug']) $this->logger->log("[ImportFeed::setGallery] Старые файлы галереи были удалены");
        }
        foreach ($photos as $url) {
            $url = trim($url);
            if($this->config['debug']) $this->logger->log("[ImportFeed::setGallery] Обрабатывается фото {$url}");
            $path = $this->config['imagePath'] . basename($url);
            $fullPath = $this->basePath . $path;

            if (!file_exists($fullPath)) {
                if ($this->config['allowDownloadImages']) {
                    if (!DownloadFeed::download($url, $path)) {
                        if($this->config['debug']) $this->logger->log("[ImportFeed::setGallery] Не удалось загрузить файл {$url}");
                        continue;
                    }
                    if($this->config['debug']) $this->logger->log("[ImportFeed::setGallery] Фото загружено на сервер по пути {$fullPath}");
                } else {
                    if($this->config['debug']) $this->logger->log("[ImportFeed::setGallery] Фото отсутствует на сервере и не было загружено по инициативе пользователя.");
                    continue;
                }
            }

            $data = [
                'id' => $resource->get('id'),
                'file' => $fullPath,
                'description' => $resource->get('pagetitle'),
                'source' => $resource->get('source'),
            ];
            $response = $this->modx->runProcessor('gallery/upload', $data, [
                'processors_path' => $this->modx->getOption('core_path') . 'components/minishop2/processors/mgr/',
            ]);
            if ($response->isError()) {
                if($this->config['debug']) $this->logger->log("[ImportFeed::setGallery] Не удалось добавить фото в галерею ", $response->getAllErrors());
            } else {
                if($this->config['debug']) $this->logger->log("[ImportFeed::setGallery] Фото {$url} успешно добавлено в галерею");
                unlink($fullPath);
            }
            unset($path, $data, $response, $url, $fullPath);
        }

        unset($photos);
    }

    /**
     * @param string $value
     * @return string
     */
    private function translit(string $value): string
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        );

        $value = mb_strtolower($value);
        $value = strtr($value, $converter);
        $value = mb_ereg_replace('[^-0-9a-z]', '-', $value);
        $value = mb_ereg_replace('[-]+', '-', $value);
        return trim($value, '-');
    }

    /**
     * @param string $str
     * @param int $length
     * @return string
     */
    private function truncate(string $str, int $length): string
    {
        $arr = explode(' ', $str);
        $c = 0;
        $newArr = [];
        foreach ($arr as $r) {
            $c += mb_strlen($r);
            $newArr[] = $r;
            if ($c > $length) {
                break;
            }
        }
        return implode(' ', $newArr);
    }
}