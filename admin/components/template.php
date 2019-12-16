<?php

/**
 * @global CMain                 $APPLICATION
 * @var array                    $arParams
 * @var array                    $arResult
 * @var CBitrixComponent         $component
 * @var CBitrixComponentTemplate $this
 * @var string                   $templateName
 * @var string                   $componentPath
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
CJSCore::Init('jquery');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/utils.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/varrcan.yaturbo/yandex_turbo_list.js');
$APPLICATION->SetAdditionalCSS('/bitrix/css/main/grid/webform-button.css');
?>
<div class="yandex-turbo-response" style="display:none"></div>

<div class="adm-toolbar-panel-container">
    <div class="adm-toolbar-panel-flexible-space">
        <?php $APPLICATION->IncludeComponent(
            'bitrix:main.ui.filter',
            '',
            [
                'FILTER_ID'          => $arResult['GRID_ID'],
                'GRID_ID'            => $arResult['GRID_ID'],
                'FILTER'             => $arResult['FILTER'],
                'FILTER_PRESETS'     => $arResult['PRESETS'],
                'ENABLE_LIVE_SEARCH' => false,
                'ENABLE_LABEL'       => true,
            ],
            $component,
            ['HIDE_ICONS' => true]
        ); ?>
        <a class="ui-btn ui-btn-primary" href="/bitrix/admin/yandex_turbo_item.php">Добавить канал</a>
    </div>
</div>

<?php

//вызовем компонент грида для отображения данных
$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    [
        'GRID_ID'                          => $arResult['GRID_ID'],
        'COLUMNS'                          => $arResult['COLUMNS'], //описание колонок грида, поля типизированы
        'SORT'                             => $arResult['SORT'], //сортировка
        'SORT_VARS'                        => $arResult['SORT_VARS'],
        'ROWS'                             => $arResult['ROWS'], //данные

        //объект постранички
        'NAV_OBJECT'                       => $arResult['NAV_OBJECT'],
        'TOTAL_ROWS_COUNT'                 => $arResult['ROWS_COUNT'],

        //можно использовать в режиме ajax
        'AJAX_MODE'                        => 'Y',
        'AJAX_OPTION_JUMP'                 => 'N',
        'AJAX_OPTION_STYLE'                => 'Y', // old
        'AJAX_ID'                          => CAjax::GetComponentID('bitrix:main.ui.grid', '.default', ''),

        //разрешить действия над всеми элементами
        'ALLOW_COLUMNS_SORT'               => true,
        'ALLOW_ROWS_SORT'                  => $arResult['CAN']['SORT'],
        'ALLOW_COLUMNS_RESIZE'             => true,
        'ALLOW_HORIZONTAL_SCROLL'          => true,
        'ALLOW_GROUP_ACTIONS'              => true,
        'ALLOW_CONTEXT_MENU'               => true,
        'ALLOW_SORT'                       => true,
        'ALLOW_PIN_HEADER'                 => true,
        'SHOW_GROUP_EDIT_BUTTON'           => true,
        'SHOW_GROUP_DELETE_BUTTON'         => true,
        'SHOW_GROUP_ACTIONS_HTML'          => true,
        'SHOW_SELECT_ALL_RECORDS_CHECKBOX' => true,
        'SHOW_MORE_BUTTON'                 => true,
        'ALLOW_SELECT_ROWS'                => true,

        //разрешено редактирование в списке
        'EDITABLE'                         => true,
        'SHOW_ROW_CHECKBOXES'              => true,

        // групповые действия
        'HIDE_GROUP_ACTIONS'               => false,
        'SHOW_ACTION_PANEL'                => true,
        'ACTION_PANEL'                     => [
            'GROUPS' => [
                [
                    'ITEMS' => [
                        [
                            'TYPE'     => 'BUTTON',
                            'ID'       => 'grid_export_button',
                            'NAME'     => '',
                            'CLASS'    => 'apply',
                            'TEXT'     => 'Экспорт в Яндекс',
                            'ONCHANGE' => [
                                '0' => [
                                    'ACTION'               => 'CALLBACK',
                                    'CONFIRM'              => true,
                                    'CONFIRM_APPLY_BUTTON' => 'Экспорт',
                                    'DATA'                 => [
                                        '0' => [
                                            'JS' => 'feeds.exportSelected();',
                                        ],
                                    ],

                                    'CONFIRM_MESSAGE'       => 'Применить для всех отмеченных элементов?',
                                    'CONFIRM_CANCEL_BUTTON' => 'Отменить',
                                ],
                            ],

                        ],
                        [
                            'TYPE'     => 'BUTTON',
                            'ID'       => 'grid_delete_button',
                            'NAME'     => '',
                            'CLASS'    => 'apply',
                            'TEXT'     => 'Удалить',
                            'ONCHANGE' => [
                                '0' => [
                                    'ACTION'               => 'CALLBACK',
                                    'CONFIRM'              => true,
                                    'CONFIRM_APPLY_BUTTON' => 'Удалить',
                                    'DATA'                 => [
                                        '0' => [
                                            'JS' => 'feeds.removeSelected();',
                                        ],
                                    ],

                                    'CONFIRM_MESSAGE'       => 'Применить для всех отмеченных элементов?',
                                    'CONFIRM_CANCEL_BUTTON' => 'Отменить',
                                ],
                            ],

                        ],
                    ],
                ],
            ],
        ],
    ],
    $component
);
?>

<?=BeginNote()?>
<p>Вы можете сформировать RSS-файлы вручную, если выбрали ручной режим работы.</p>
<p>Либо установить агент, который будет выгружать данные в файл и автоматически отправлять в Яндекс. Время выполнения агента устанавливается в Настройках. По умолчанию 1 раз в сутки.</p>
<?=EndNote()?>
