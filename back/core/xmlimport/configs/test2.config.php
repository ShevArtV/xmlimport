<?php
// php -d display_errors -d error_reporting=E_ALL ~/stroybat23/public_html/core/elements/importfeed.class.php
// /usr/local/php/php-7.4/bin/php -d display_errors -d error_reporting=E_ALL art-sites.ru/htdocs/www/core/elements/importfeed.class.php
// https://td-arnika.ru/upload/acrit.exportproplus/arnika_agent.xml
return [
    'ctx' => 'web', // контекст в которром нужно создавать категории и товары
    'feedUrl' => '', // ссылка на фид
    'feedPath' => 'assets/feeds/wellfitness.xml', // путь к файлу фида на сервере, указывать от корня сайта
    'imagePath' => 'assets/imported_images/', // путь для загрузки картинок, указывать от корня сайта
    'importCategories' => true, // импортировать категории?
    'importProducts' => true, // импортировать товары?
    'importMode' => '', // возможные значения create - только создание новых; update - обновление существующих; пустая строка - создание и обновление.
    'createUniquePagetitle' => false, // создавать уникальный pagetitle? Отменяет настройку createUniqueAlias
    'uniquePagetitleFields' => ['supplier', 'feed_id'], // поля добавляемые для уникализации заголовка
    'createUniqueAlias' => true, // добавить feed_id к псевдониму?
    'uniqueAliasFields' => ['supplier', 'feed_id'], // поля добавляемые для уникализации псевдонима
    'updateCategoryStructure' => true, // позволяет сформировать структуру категорий как в файле импорта
    'saveAlias' => false, // сохранить псевдоним?
    'setGallery' => true, // установить галерею товара?
    'setGalleryOnlyNew' => false, // установить галерею только для новых товаров?
    'setOptions' => true, // установить опции товара?
    'removeOldFiles' => true, // очистить галерею перед добавлением новых фото?
    'allowDownloadImages' => true, // разрешить загрузку картинок из удалённого источника?
    'valueImplodeSeparator' => '; ', // разделитель значений для преобразования в строку
    'valueExplodeSeparator' => ',', // разделитель значений для преобразования в массив
    'offerCategoryTag' => 'categoryId', // тег содержащий feed_id родительской категории, если не указан родитель будет взят из атрибутов тега offer
    'supplier' => 'wellfitness', // позволяет независимо обновлять товары от разных поставщиков
    'keyForDeleted' => ['offer' => ['in_stock' => 0], 'category' => ['deleted' => 1]], // позволяет указать ключи полей для определения удалённых товаров и категорий
    'customHandlers' => ['offer' => ['path' => 'handlers/test.php', 'function' => 'test']], // позволяет использовать пользовательские функции для обработки данных

    'paramValueTemplates' => [
       'Тип беговой дорожки' => '{key}: {value}',
       'Регулировка угла наклона' => '{key}: {value}',
       'Поддержка кардиопояса' => '{key}: {value}',
    ], // шаблоны для формирования значения опции, поддерживают плейсхолдеры {key} и {value}, ключом массива должно быть значение атрибута name тега param из файла импорта. Не работает со свойствами товаров из других тегов.

    'searchConditions' => [
        'msProduct' => "(msProduct.feed_id = '{feed_id}' OR msProduct.pagetitle = '{pagetitle}') AND msProduct.supplier = '{supplier}' AND msProduct.context_key = '{context_key}'", // условия проверки существования товара, допускается использовать только основные поля ресурса и товара, для полей товара нужно использовать префикс Data.fieldname
        'msCategory' => "msCategory.feed_id = '{feed_id}' AND msCategory.supplier = '{supplier}' AND msCategory.context_key = '{context_key}'", // условия проверки существования категории, допускается использовать только основные поля ресурса
    ],

    'noUpdate' => [
        'content'
    ], // список полей, которые нужно заполнить при создании, но не обновлять (указывать поля так как они записаны в БД)

    'ignore_params' => [

    ], // какие параметры проигнорировать? при большом количестве параметров позволяет немного ускорить чтение файла

    'truncated' => [
        'pagetitle' => 90,
        'longtitle' => '',
        'description' => '',
    ], // список основных полей ресурса и их максимальная длина, если хотите её ограничить

    'categoryDefaultFields' => [
        'parent' => 1,
        'template' => 1,
        'hidemenu' => false,
        'published' => 1,
        'class_key' => 'msCategory'
    ], // общие поля категорий

    'productDefaultFields' => [
        'parent' => 1,
        'template' => 2,
        'hidemenu' => 1,
        'published' => 1,
        'show_in_tree' => 0,
        'class_key' => 'msProduct',
        'source' => 2
    ], // общие поля товара

    'productFields' => [
        'pagetitle' => 'model',
        'content' => 'description',
        'price' => 'price',
        'article' => 'params_Артикул',
        'made_in' => 'params_Страна изготовления',
        'introtext' => 'params_Анонс',
        'size' => 'params_Габариты',
        'favorite' => 'attr_available',
    ], // сопоставление полей товара на сайте полям в файле

    'productOptions' => [
        'features' => [
            'from' => ['params_Тип беговой дорожки', 'params_Регулировка угла наклона', 'params_Поддержка кардиопояса'], // имя параметра в файле импорта
            'caption' => 'Особенности', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'textarea', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область)
        ],
        'in_stock' => [
            'from' => ['attr_available'], // имя параметра в файле импорта
            'caption' => 'В наличии', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'combo-boolean', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область)
        ],
        'width' => [
            'from' => ['params_Размер в рабочем состоянии Ширина, см'], // имя параметра в файле импорта
            'caption' => 'Ширина', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'numberfield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],
        'height' => [
            'from' => ['params_Размер в рабочем состоянии Высота, см'], // имя параметра в файле импорта
            'caption' => 'Высота', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'numberfield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],
        'depth' => [
            'from' => ['params_Размер в рабочем состоянии Длина, см'], // имя параметра в файле импорта
            'caption' => 'Длина', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'numberfield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],
        'advantages' => [
            'from' => ['params_Преимущество 1', 'params_Преимущество 2', 'params_Преимущество 3', 'params_Преимущество 4', 'params_Преимущество 5'], // имя параметра в файле импорта
            'caption' => 'Преимущества', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'textarea', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],


    ], // опции товара, можно указывать как существующие ключи, так и несуществующие(будут созданы)

    'vendorFields' => [
        'name' => 'vendor',
        'resource' => '',
        'country' => '',
        'logo' => '',
        'address' => '',
        'phone' => '',
        'fax' => '',
        'email' => '',
        'description' => '',
        'properties' => ''
    ], // сопоставление полей производителя на сайте полям в файле

    'fieldTypes' => [
        'array' => [
            'size'
        ], // если основное поле товара имеет тип список со множественным выбором или список с автодополнением.
        'bool' => [
            'favorite'
        ] // если основное поле товара это чекбокс
    ]
];
