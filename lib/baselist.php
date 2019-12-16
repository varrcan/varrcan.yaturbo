<?php

namespace Varrcan\Yaturbo;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Grid;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;

/**
 * Базовый компонент для наследования и отображения таблицы
 */
class BaseList extends \CBitrixComponent
{
    public $sortArg;
    /**
     * @var string Уникальный идентификатор грида
     */
    public $gridId = 'unique_grid_id';
    /**
     * Базовая фильтрация ORM
     * example
     * '!==GROUP.ID' => null
     * @var array
     */
    protected $defaultOrmFilter = [];
    /**
     * Обязательный набор выборки
     * (не обязательны к отображению)
     * @var array
     */
    protected $startOrmSelect = [
        'ID' => 'ID',
    ];
    protected $debugSql = false;
    protected $resetSettings = false;
    /**
     * Заголовок страницы
     * @var string
     */
    protected $title;
    /** @var DataManager */
    protected $startEntity = ElementTable::class;
    /** @var string */
    protected $filterFind;
    /** @var array */
    protected $activeFilter;
    /** @var string Поле поиска по умолчанию */
    protected $defaultSearchFilter = 'TITLE';
    /** @var array */
    private $filterOptions;
    /** @var bool */
    private $isFilterApplied;
    /** @var Options */
    private $filter;
    /* @var Grid\Options */
    private $gridOptions;
    /** @var array */
    private $activePreset;
    /** @var PageNavigation */
    private $nav;

    public function executeMain()
    {
        $this->debugSql      = (bool)$this->request->get('sql');
        $this->resetSettings = (bool)$this->request->get('reset') || (bool)$this->request->get('clear');

        // Базовые настройки
        // =================
        $this->arResult['GRID_ID'] = $this->gridId;
        $this->gridOptions         = new Grid\Options($this->arResult['GRID_ID']);
        $this->filter              = new Options($this->arResult['GRID_ID']);

        if ($this->resetSettings) {
            $this->gridOptions->ResetDefaultView();
            $this->filter->reset();
            // \CUserOptions::DeleteOptionsByName('main.interface.grid', $this->gridId);
        }

        $this->filterOptions   = $this->filter->getOptions();
        $this->activeFilter    = $this->filter->getFilter();
        $this->isFilterApplied = $this->activeFilter['FILTER_APPLIED'];
        $this->filterFind      = $this->activeFilter['FIND'];
        $this->activePreset    = $this->filterOptions['filters'][$this->activeFilter['PRESET_ID']]['fields'];

        // VIEW
        // ====
        $this->arResult['FILTER']  = $this->getFilterView();
        $this->arResult['COLUMNS'] = $this->getColumnsView();
        $this->setDefaultColumns();

        // СОРТИРОВКА
        // ==========
        $sortParams = $this->gridOptions->getSorting([
            'sort' => ['ID' => 'asc'],
            'vars' => [
                'by'    => 'by',
                'order' => 'order',
            ],
        ]);

        // TODO: refactoring this
        /** @noinspection PhpDeprecationInspection */
        $this->sortArg               = each($sortParams['sort']);
        $this->arResult['SORT']      = $sortParams['sort'];
        $this->arResult['SORT_VARS'] = $sortParams['vars'];

        // НАВИГАЦИЯ
        // =========
        $this->nav = new PageNavigation('nav-more-news');
        $this->nav->allowAllRecords(true)
            ->setPageSize(20)
            ->initFromUri();

        // ВЫБОРКА
        // =======
        $this->arResult['ROWS'] = $this->getRowsData();

        // объект постранички
        $this->nav->setRecordCount($this->arResult['ROWS_COUNT']);
        $this->arResult['NAV_OBJECT'] = $this->nav;

        if ($this->title) {
            global $APPLICATION;
            $APPLICATION->SetTitle($this->title);
        }
    }

    /**
     * Получение настроек фильтра по умолчанию
     * поля фильтра типизированы
     * по умолчанию тип text, поддерживается date, list, number, checkbox, quick (поле ввода и список), custom
     *
     * example
     *
     * ['id'=>'PERSONAL_ICQ', 'name'=>'АйСикЮ', 'params'=>['size'=>15]],
     * ['id'=>'PERSONAL_GENDER', 'name'=>'Пол', 'type'=>'list',
     * 'items'=>array(''=>'(пол)', 'M'=>'Мужской', 'F'=>'Женский')],
     * @return array
     */
    protected function getFilterView():array
    {
        return [];
    }

    /**
     * Получение списка полей для выборки
     * @return mixed
     */
    public function getSelect()
    {
        $arSelect         = $this->startOrmSelect;
        $arVisibleColumns = $this->gridOptions->GetVisibleColumns();
        foreach ($arVisibleColumns as $column) {
            $clearName            = str_replace('.', '_', $column);
            $arSelect[$clearName] = $column;
        }

        return $arSelect;
    }

    /**
     * Получение параметров активных колонок грида
     * @return array
     */
    protected function getColumnsView():array
    {
        return [
            [
                'id'       => 'ID',
                'name'     => 'ID',
                'sort'     => 'ID',
                'default'  => false,
                'editable' => false,
            ],
        ];
    }

    /**
     * Получение данных таблицы
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     */
    public function getRowsData():array
    {
        $result = [];

        if ($this->debugSql) {
            HttpApplication::getConnection()->startTracker();
        }

        $dbResult = $this->startEntity::getList([
            'select'      => $this->getSelect(),
            'filter'      => $this->getOrmFilter(),
            'order'       => $this->getOrmOrder(),
            'runtime'     => $this->getReference(),
            'group'       => $this->getOrmGroup(),
            'offset'      => $this->nav->getOffset(),
            'limit'       => $this->nav->getLimit(),
            'count_total' => true,
        ]);

        if ($this->debugSql) {
            echo '<pre>', $dbResult->getTrackerQuery()->getSql(), '</pre>';
        }

        while ($arItem = $dbResult->fetch()) {
            $result[] = [
                'id'             => $arItem['id'],
                'data'           => $arItem,
                'actions'        => $this->addActions($arItem),
                'columns'        => $this->modifyItems($arItem),
                'default_action' => $this->getDefaultAction($arItem),
            ];
        }

        // информация для футера списка
        $this->arResult['ROWS_COUNT'] = $dbResult->getCount();

        return $result;
    }

    /**
     * Получение ORM фильтра для выборки
     * @return array
     */
    protected function getOrmFilter():array
    {
        $arFilters = $this->defaultOrmFilter;

        foreach ($this->arResult['FILTER'] as $filter) {
            $fieldCode = $filter['id'];

            switch ($filter['type']) {
                case 'number':
                case 'date':
                    $from = $fieldCode . '_from';
                    $to   = $fieldCode . '_to';

                    if ($this->isFilterExist($from)) {
                        $arFilters['>=' . $fieldCode] = $this->activeFilter[$from];
                    }
                    if ($this->isFilterExist($to)) {
                        $arFilters['<=' . $fieldCode] = $this->activeFilter[$to];
                    }
                    break;

                default:
                    if ($this->isFilterExist($fieldCode)) {
                        if (is_numeric($this->activeFilter[$fieldCode])) {
                            $arFilters[$fieldCode] = $this->activeFilter[$fieldCode];
                        } else {
                            $arFilters['%' . $fieldCode] = $this->activeFilter[$fieldCode];
                        }
                    }
                    break;
            }
        }

        // Главный поиск
        if ($this->filterFind) {
            $arFilters['%' . $this->defaultSearchFilter] = $this->filterFind;
        }

        return $arFilters;
    }

    /**
     * @param $filterName
     *
     * @return bool
     */
    private function isFilterExist($filterName)
    {
        return array_key_exists($filterName, $this->activeFilter)
               && !empty($this->activeFilter[$filterName]);
    }

    /**
     * Сортировка таблицы
     * @return array
     */
    private function getOrmOrder():array
    {
        return [
            $this->sortArg['key'] => strtoupper($this->sortArg['value']),
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
        return $arItem;
    }

    /**
     * Добавление action действий над строкой
     *
     * @param array $arItem
     *
     * @return array
     */
    protected function addActions($arItem):array
    {
        return [];
    }

    /**
     * Reference Fields
     * @return array
     */
    protected function getReference():array
    {
        return [];
    }

    /**
     * Экшен по умолчанию при двойном клике
     *
     * @param $arItem
     *
     * @return array
     */
    protected function getDefaultAction($arItem)
    {
        return [];
    }

    /**
     * Получение группировок ORM
     */
    protected function getOrmGroup():array
    {
        return [];
    }

    /**
     * Установка отображения списка колонок по умолчанию
     */
    private function setDefaultColumns()
    {
        if (count($this->gridOptions->GetVisibleColumns()) > 0) {
            return;
        }

        $visibleColumns = [];
        foreach ($this->getColumnsView() as $column) {
            if ($column['default']) {
                $visibleColumns[] = $column['id'];
            }
        }

        $this->gridOptions->SetVisibleColumns($visibleColumns);
    }
}
