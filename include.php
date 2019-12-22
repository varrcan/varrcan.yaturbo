<?php

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

$arJsConfig = [
    'varrcan_yaturbo' => [
        'js'  => '/bitrix/js/varrcan.yaturbo/yandex_turbo_main.js',
        'rel' => ['jquery'],
    ],
];

foreach ($arJsConfig as $ext => $arExt) {
    \CJSCore::RegisterExt($ext, $arExt);
}
