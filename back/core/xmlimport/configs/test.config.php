<?php
// php -d display_errors -d error_reporting=E_ALL ~/stroybat23/public_html/core/elements/importfeed.class.php
// /usr/local/php/php-7.4/bin/php -d display_errors -d error_reporting=E_ALL art-sites.ru/htdocs/www/core/elements/importfeed.class.php
// https://td-arnika.ru/upload/acrit.exportproplus/arnika_agent.xml
return [
    'ctx' => 'web', // контекст в которром нужно создавать категории и товары
    'debug' => false,
    'feedUrl' => '', // ссылка на фид
    'feedPath' => 'assets/feeds/test.xml', // путь к файлу фида на сервере, указывать от корня сайта
    'importCategories' => true, // импортировать категории?
    'importProducts' => true, // импортировать товары?
    'importMode' => '', // возможные значения create - только создание новых; update - обновление существующих; пустая строка - создание и обновление.
    'createUniquePagetitle' => false, // создавать уникальный pagetitle? Отменяет настройку createUniqueAlias
    'uniquePagetitleFields' => ['supplier', 'feed_id'], // поля добавляемые для уникализации заголовка
    'createUniqueAlias' => true, // добавить feed_id к псевдониму?
    'uniqueAliasFields' => ['supplier', 'feed_id'], // поля добавляемые для уникализации псевдонима
    'updateCategoryStructure' => true, // позволяет сформировать структуру категорий как в файле импорта
    'saveAlias' => false, // сохранить псевдоним указанный в файле импорта?
    'setOptions' => true, // установить опции товара?

    'imagePath' => 'assets/imported_images/', // путь для загрузки картинок, указывать от корня сайта
    'allowDownloadImages' => true, // разрешить загрузку картинок из удалённого источника?
    'removeOldFiles' => true, // очистить галерею перед добавлением новых фото?
    'setGallery' => false, // установить галерею товара?
    'setGalleryOnlyNew' => true, // установить галерею только для новых товаров?

    'valueImplodeSeparator' => '; ', // разделитель значений для преобразования в строку
    'valueExplodeSeparator' => ',', // разделитель значений для преобразования в массив
    'offerCategoryTag' => 'main_category', // тег содержащий feed_id родительской категории, если не указан родитель будет взят из атрибутов тега offer
    'supplier' => 'arnika', // позволяет независимо обновлять товары от разных поставщиков
    'keyForDeleted' => ['offer' => ['in_stock' => 0], 'category' => ['deleted' => 1]], // позволяет указать ключи полей для определения удалённых товаров и категорий
    'customHandlers' => ['offer' => ['path' => 'handlers/test.php', 'function' => 'test']], // позволяет использовать пользовательские функции для обработки данных


    'paramValueTemplates' => [
       'Коллекция' => '{key}: {value}',
       'Год' => '{key}: {value}',
    ], // шаблоны для формирования значения опции, поддерживают плейсхолдеры {key} и {value}, ключом массива должно быть значение атрибута name тега param из файла импорта. Не работает со свойствами товаров из других тегов.

    'searchConditions' => [
        'msProduct' => "(msProduct.feed_id = '{feed_id}' OR msProduct.pagetitle = '{pagetitle}') AND msProduct.supplier = '{supplier}' AND msProduct.context_key = '{context_key}'", // условия проверки существования товара, допускается использовать только основные поля ресурса и товара, для полей товара нужно использовать префикс Data.fieldname
        'msCategory' => "msCategory.feed_id = '{feed_id}' AND msCategory.supplier = '{supplier}' AND msCategory.context_key = '{context_key}'", // условия проверки существования категории, допускается использовать только основные поля ресурса
    ],

    'noUpdate' => [
        'content'
    ], // список полей, которые нужно заполнить при создании, но не обновлять (указывать поля так как они записаны в БД)

    'ignore_params' => [
        'CATEGORY',
        'MAIN_CATEGORY',
        'ID Элемента'
    ], // какие параметры проигнорировать?

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
        'pagetitle' => 'name',
        'content' => 'description',
        'price' => 'price',
        'old_price' => 'old_price',
        'made_in' => 'country_of_origin',
        'introtext' => 'params_Описание для анонса',
        'size' => 'params_Габариты',
        'favorite' => 'count',
        'categories' => 'params_CHAIN_CATEGORY',
    ], // сопоставление полей товара на сайте полям в файле

    'productOptions' => [
        'collection' => [
            'from' => ['params_Коллекция', 'params_Год'], // имя параметра в файле импорта
            'caption' => 'Информация о коллекции', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'textfield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область)
        ],
        'release_date' => [
            'from' => ['params_Дата релиза'], // имя параметра в файле импорта
            'caption' => '', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'datefield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область)
        ],
        'in_stock' => [
            'from' => ['stock'], // имя параметра в файле импорта
            'caption' => 'В наличии', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'combo-boolean', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область)
        ],
        'width' => [
            'from' => ['params_Ширина'], // имя параметра в файле импорта
            'caption' => '', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'numberfield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],
        'height' => [
            'from' => ['params_Высота'], // имя параметра в файле импорта
            'caption' => '', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'numberfield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],
        'depth' => [
            'from' => ['params_Глубина'], // имя параметра в файле импорта
            'caption' => '', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'numberfield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],
        'target' => [
            'from' => ['params_Назначение'], // имя параметра в файле импорта
            'caption' => '', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'combo-options', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],
        'style' => [
            'from' => ['params_Стиль'], // имя параметра в файле импорта
            'caption' => '', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'textfield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],
        'colors' => [
            'from' => ['params_Цвет для сайта'], // имя параметра в файле импорта
            'caption' => '', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'combo-colors', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
            'properties' => [
                'values' => [
                    ['name' => 'Ясень', 'value' => '#000'],
                    ['name' => 'Дуб', 'value' => '#0f0'],
                ]
            ],
        ],
        'material' => [
            'from' => ['params_Материал'], // имя параметра в файле импорта
            'caption' => '', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'combo-multiple', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],
        'weight' => [
            'from' => ['params_Вес'], // имя параметра в файле импорта
            'caption' => '', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'numberfield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
        ],
        'volume' => [
            'from' => ['params_Объем товара'], // имя параметра в файле импорта
            'caption' => '', // подпись, если не заполнено будет взято из from
            'description' => '', // описание опции
            'measure_unit' => '', // единицы измерения
            'category' => 6, // id категории опции, если не указано будет взято значение из параметра optionsCategoryId
            'type' => 'numberfield', // textfield(текст),checkbox(Флажок),combo-boolean(Да/Нет),combo-colors(Множественный список цветов),combo-multiple(множественный список),combo-options(с автодополнением),combobox(список),datefield(дата),numberfield(число),textarea(тестовая область),
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
