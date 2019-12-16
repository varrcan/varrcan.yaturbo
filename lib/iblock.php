<?php

namespace Varrcan\Yaturbo;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use CFile;

Loader::includeModule('iblock');

/**
 * Class Iblock
 * @package Varrcan\Yaturbo
 */
class Iblock
{
    /**
     * Стандартные поля инфоблока
     *
     * @param $iblock
     *
     * @return array
     */
    public function getDefaultProperty($iblock):array
    {
        $result = [];
        $fields = ElementTable::getMap();

        foreach ($fields as $key => $value) {
            $result[$key] = $value->getTitle();
        }

        return $result;
    }

    /**
     * Пользовательские поля
     *
     * @param $iblock
     *
     * @param $form
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getCustomProperty($iblock, $form = false):array
    {
        $result = [];
        $query  = PropertyTable::query()
            ->addSelect('ID')
            ->addSelect('NAME')
            ->addSelect('CODE')
            ->addSelect('PROPERTY_TYPE')
            ->addSelect('MULTIPLE')
            ->addSelect('VERSION')
            ->addSelect('LINK_IBLOCK_ID')
            ->where('IBLOCK_ID', $iblock)
            ->exec()
            ->fetchAll();

        foreach ($query as $item) {
            $result[$item['CODE']] = $form ? $item['NAME'] : $item;
        }

        return $result;
    }

    /**
     * Получить сведения об инфоблоке
     *
     * @param $id
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getIblockInfo($id)
    {
        $result = IblockTable::query()
            ->addSelect('IBLOCK_TYPE_ID')
            ->addSelect('CODE')
            ->addSelect('NAME')
            ->addSelect('VERSION')
            ->addSelect('DETAIL_PAGE_URL')
            ->addSelect('SECTION_PAGE_URL')
            ->where('ID', $id)
            ->exec()
            ->fetch();

        return $result ?? [];
    }

    /**
     * Получить элементы инфоблока
     *
     * @param     $config
     * @param int $page
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getIblockItems($config, $page = 1)
    {
        $addSelect = [];

        foreach ($config['content_config'] as $key => $param) {
            switch ($param['block_type']) {
                case 'element':
                    $addSelect[$key] = $param['block_property'];
                    break;
                case 'property':
                    $addSelect[$key] = 'PROPERTY_' . $param['block_property'];
                    break;
            }
        }

        if ($config['main_img'] !== 'none') {
            $addSelect[] = $config['main_img'];
        }

        $defaultSelect = [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CREATED_BY',
            'DATE_CREATE',
            'TIMESTAMP_X',
            'IBLOCK_SECTION_ID',
            'CODE',
            'DETAIL_PAGE_URL',
        ];

        $arSelect = \array_merge($defaultSelect, $addSelect);

        $arFilter = [
            'IBLOCK_ID' => (int)$config['iblock_id'],
            'ACTIVE'    => 'Y',
        ];

        $user    = new UserTable();
        $section = new SectionTable();

        //TODO: переписать под новую ORM
        $res = \CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            $arFilter,
            false,
            [
                'iNumPage'        => $page,
                'nPageSize'       => 500,
                'checkOutOfRange' => true,
            ],
            $arSelect
        );

        $i = 0;
        while ($element = $res->GetNext()) {
            $arFields[$i] = $element;

            // Имя и код раздела
            $dbSection = $section::query()
                ->addSelect('CODE')
                ->addSelect('NAME')
                ->where('ID', $element['IBLOCK_SECTION_ID'])
                ->exec()
                ->fetch();

            // Получить ссылку на изображение
            if ($config['main_img'] !== 'none') {
                $arFields[$i][$config['main_img']] = CFile::GetFileArray($element[$config['main_img']])['SRC'];
            }

            // Ссылка на страницу
            if ($config['detail_page_template']) {
                $templates = [
                    '#ELEMENT_ID#'   => $element['ID'],
                    '#ELEMENT_CODE#' => $element['CODE'],
                    '#SECTION_ID#'   => $element['IBLOCK_SECTION_ID'],
                    '#SECTION_CODE#' => $dbSection['CODE'],
                ];

                $arFields[$i]['DETAIL_PAGE_URL'] = \str_replace(array_keys($templates), array_values($templates), $config['detail_page_template']);
            }

            // Название категории
            if ($config['category']) {
                $arFields[$i]['IBLOCK_SECTION'] = $dbSection['NAME'] ?? null;
            }

            // имя автора
            if ($config['author']) {
                $arFields[$i]['USER'] = $user::query()
                    ->addSelect('NAME')
                    ->addSelect('SECOND_NAME')
                    ->addSelect('LAST_NAME')
                    ->addSelect('SHORT_NAME')
                    ->where('ID', $element['CREATED_BY'])
                    ->exec()
                    ->fetch();
            }

            $i++;
        }

        return $arFields ?? [];
    }

    public function getPropertyContent()
    {
        //
    }

    public function getCustomPropertyContent()
    {
        //
    }

    /**
     * СЕО параметры
     *
     * @param $iblock
     *
     * @return array
     */
    public function getMetaProperty($iblock):array
    {
        return [];
    }
}
