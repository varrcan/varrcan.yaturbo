<?php

namespace Varrcan\Yaturbo;

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\UserTable;
use Varrcan\Yaturbo\Orm\YaTurboFeedTable;

if (!\defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Class YaTurboList
 * @package Varrcan\Yaturbo
 */
class YaTurboList extends BaseList
{
    protected $startOrmSelect = [
        'id' => 'id',
    ];

    /**
     * @param $arParams
     *
     * @return array
     */
    public function onPrepareComponentParams($arParams):array
    {
        $this->title               = 'Список каналов | Yandex.Turbo';
        $this->gridId              = 'ya_turbo_feeds';
        $this->defaultSearchFilter = 'id';

        $this->startEntity = YaTurboFeedTable::class;

        return parent::onPrepareComponentParams($arParams);
    }

    /**
     * @return mixed|void
     */
    public function executeComponent()
    {
        $this->executeMain();
        $this->includeComponentTemplate();
    }

    /**
     * Получение параметров активных колонок грида
     *
     * @return array
     */
    protected function getColumnsView():array
    {
        return [
            [
                'id'      => 'id',
                'name'    => 'ID',
                'sort'    => 'id',
                'default' => false,
            ],
            [
                'id'      => 'feed_name',
                'name'    => 'Имя канала',
                'sort'    => 'feed_name',
                'default' => true,
            ],
            [
                'id'      => 'active',
                'name'    => 'Активность',
                'sort'    => 'active',
                'default' => true,
            ],
            [
                'id'      => 'status',
                'name'    => 'Статус',
                'sort'    => 'status',
                'default' => true,
            ],
            [
                'id'      => 'type',
                'name'    => 'Режим работы',
                'sort'    => 'type',
                'default' => true,
            ],
            [
                'id'      => 'is_upload',
                'name'    => 'Загружено',
                'sort'    => 'is_upload',
                'default' => true,
            ],
            [
                'id'      => 'errors',
                'name'    => 'Ошибки',
                'sort'    => 'errors',
                'default' => true,
            ],
            [
                'id'      => 'iblock_id',
                'name'    => 'Инфоблок',
                'sort'    => 'iblock_id',
                'default' => false,
            ],
            [
                'id'      => 'site_url',
                'name'    => 'Адрес сайта',
                'sort'    => 'site_url',
                'default' => false,
            ],
            [
                'id'      => 'feed_description',
                'name'    => 'Описание канала',
                'sort'    => 'feed_description',
                'default' => false,
            ],
            [
                'id'      => 'feed_lang',
                'name'    => 'Язык канала',
                'sort'    => 'feed_lang',
                'default' => false,
            ],
            [
                'id'      => 'create_date',
                'name'    => 'Дата создания',
                'sort'    => 'create_date',
                'default' => false,
            ],
            [
                'id'      => 'modified_date',
                'name'    => 'Дата изменения',
                'sort'    => 'modified_date',
                'default' => false,
            ],
            [
                'id'      => 'user_create_by',
                'name'    => 'Создал',
                'sort'    => 'user_create_by',
                'default' => false,
            ],
            [
                'id'      => 'user_modified_by',
                'name'    => 'Изменил',
                'sort'    => 'user_modified_by',
                'default' => false,
            ],
            [
                'id'      => 'files',
                'name'    => 'Файлы',
                'sort'    => 'files',
                'default' => false,
            ],
        ];
    }

    /**
     * Модификация вывода полей
     * требующих нестандартного отображени
     *
     * @param array $arItem
     *
     * @return array
     */
    protected function modifyItems($arItem):array
    {
        $iblockInfo = (new Iblock())->getIblockInfo($arItem['iblock_id']);

        $arModify = [
            'active'    => $arItem['active'] ? 'Активен' : 'Деактивирован',
            'is_upload' => $arItem['is_upload'] ? 'Да' : 'Нет',
            'errors'    => $arItem['errors'] ? '<span style="color: red">Есть ошибки</span>' : '<span style="color: green">Нет ошибок</span>',
            'iblock_id' => $iblockInfo['NAME'] ?? '-',
            'type'      => $arItem['type'] === 'agent' ? 'Автоматический' : 'Ручной',
            'files'     => $this->getFiles($arItem['files'], $arItem['id']),
        ];

        return $arModify;
    }

    /**
     * Сформировать ссылки на файлы
     *
     * @param $jsonFiles
     * @param $elementId
     *
     * @return string
     */
    public function getFiles($jsonFiles, $elementId)
    {
        if (!$jsonFiles) {
            return null;
        }
        $result  = [];
        $arFiles = \GuzzleHttp\json_decode($jsonFiles);

        foreach ($arFiles as $fileName) {
            $result[] = '<a href="' . Items::$workDir . $elementId . '/' . $fileName . '">' . $fileName . '</a>';
        }

        return \implode("\n", $result);
    }

    /**
     * Reference Fields
     *
     * @return array
     */
    protected function getReference():array
    {
        return [
            new ReferenceField('user_create', UserTable::class, Join::on('this.create_by', 'ref.ID')),
            new ExpressionField('user_create_by', '%s', 'user_create.SHORT_NAME'),
            new ReferenceField('user_modified', UserTable::class, Join::on('this.modified_by', 'ref.ID')),
            new ExpressionField('user_modified_by', '%s', 'user_modified.SHORT_NAME'),
        ];
    }

    /**
     * @param array $arItem
     *
     * @return array
     */
    protected function addActions($arItem):array
    {
        $agentType = $arItem['type'] === 'agent' ? 'manual' : 'agent';

        return [
            [
                'ICONCLASS' => 'edit',
                'TEXT'      => 'Сформировать RSS',
                'ONCLICK'   => "feeds.generateRss({$arItem['id']});",
            ],
            [
                'ICONCLASS' => 'edit',
                'TEXT'      => $arItem['is_upload'] ? 'Повторить отправку' : 'Экспорт в Яндекс',
                'ONCLICK'   => "feeds.exportSigned({$arItem['id']});",
            ],
            [
                'ICONCLASS' => 'edit',
                'TEXT'      => $arItem['type'] === 'agent' ? 'Удалить агент' : 'Установить агент',
                'ONCLICK'   => "feeds.agent({$arItem['id']}, \"{$agentType}\");",
            ],
            [
                'ICONCLASS' => 'edit',
                'TEXT'      => 'Редактировать',
                'ONCLICK'   => "window.location.href = '/bitrix/admin/yandex_turbo_item.php?id={$arItem['id']}';",
            ],
            [
                'ICONCLASS' => 'edit',
                'TEXT'      => 'Удалить',
                'ONCLICK'   => "feeds.deleteSigned({$arItem['id']});",
            ],
        ];
    }

    /**
     * Экшен по умолчанию при двойном клике
     *
     * @param $arItem
     *
     * @return array
     */
    protected function getDefaultAction($arItem):array
    {
        return [
            'href' => '/bitrix/admin/yandex_turbo_item.php?id=' . $arItem['id'],
        ];
    }

    /**
     * Получение настроек фильтра по умолчанию
     * поля фильтра типизированы
     * по умолчанию тип text, поддерживается date, list, number, checkbox, quick (поле ввода и список), custom
     * example
     * ['id'=>'PERSONAL_ICQ', 'name'=>'АйСикЮ', 'params'=>['size'=>15]],
     * ['id'=>'PERSONAL_GENDER', 'name'=>'Пол', 'type'=>'list',
     * 'items'=>array(''=>'(пол)', 'M'=>'Мужской', 'F'=>'Женский')],
     *
     * @return array
     */
    protected function getFilterView():array
    {
        return
            [
                [
                    'id'   => 'id',
                    'name' => 'ID',
                    'type' => 'quick',
                ],
            ];
    }
}
