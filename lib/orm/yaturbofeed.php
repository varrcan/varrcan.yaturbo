<?php

namespace Varrcan\Yaturbo\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class YaTurboFeedTable
 *
 * @package Varrcan\Yaturbo\Orm
 */
class YaTurboFeedTable extends Entity\DataManager
{
    /**
     * Returns DB table name for entity
     *
     * @return string
     */
    public static function getTableName():string
    {
        return 'ya_turbo_feed';
    }

    /**
     * Returns entity map definition.
     * To get initialized fields @see \Bitrix\Main\Entity\Base::getFields() and \Bitrix\Main\Entity\Base::getField()
     */
    public static function getMap():array
    {
        return [
            (new IntegerField('id'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new StringField('status'))
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new StringField('type'))
                ->configureRequired(false)
                ->configureDefaultValue(null), // режим работы Ручной или Агент
            (new BooleanField('is_upload'))
                ->configureRequired(false)
                ->configureDefaultValue(false),
            (new StringField('task_id'))
                ->configureRequired(false)
                ->configureDefaultValue(null), // json Идентификаторы задач на загрузку RSS-канала
            (new TextField('errors'))
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new BooleanField('active'))
                ->configureTitle('Активность')
                ->configureRequired(false)
                ->configureDefaultValue(true),
            (new StringField('lid_id'))
                ->configureRequired(true)
                ->configureDefaultValue('s1'), //TODO
            (new StringField('iblock_id'))
                ->configureTitle('ID инфоблока')
                ->configureRequired(true)
                ->configureDefaultValue(null),
            (new DatetimeField('create_date'))
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new IntegerField('create_by'))
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new DatetimeField('modified_date'))
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new IntegerField('modified_by'))
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new StringField('feed_name'))
                ->configureTitle('Имя канала')
                ->configureRequired(true)
                ->configureDefaultValue(null),
            (new StringField('site_url'))
                ->configureTitle('Адрес сайта')
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new StringField('feed_description'))
                ->configureTitle('Описание канала')
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new StringField('feed_lang'))
                ->configureTitle('Язык канала')
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new TextField('analytics'))
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new TextField('ad_network'))
                ->configureRequired(false)
                ->configureDefaultValue(null), //TODO
            (new TextField('metrics'))
                ->configureRequired(false)
                ->configureDefaultValue(null), //TODO
            (new StringField('pub_date'))
                ->configureRequired(true)
                ->configureDefaultValue(null),
            (new BooleanField('author'))
                ->configureRequired(false)
                ->configureDefaultValue(false),
            (new StringField('main_img'))
                ->configureTitle('Основное изображение')
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new BooleanField('category'))
                ->configureTitle('Категория')
                ->configureRequired(false)
                ->configureDefaultValue(false),
            (new StringField('detail_page_template'))
                ->configureRequired(false)
                ->configureDefaultValue(null), // DETAIL_PAGE_URL
            (new StringField('section_page_template'))
                ->configureRequired(false)
                ->configureDefaultValue(null), // SECTION_PAGE_URL
            (new TextField('content_config'))
                ->configureRequired(false)
                ->configureDefaultValue(null), // json
            (new TextField('menu'))
                ->configureTitle('Меню')
                ->configureRequired(false)
                ->configureDefaultValue(null), // json
            (new StringField('link_block_source'))
                ->configureRequired(false)
                ->configureDefaultValue(null), // link, auto
            (new IntegerField('link_block_count'))
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new TextField('share_block'))
                ->configureRequired(false)
                ->configureDefaultValue(null), // json
            (new StringField('callback_mail'))
                ->configureTitle('Email организации')
                ->configureRequired(false)
                ->configureDefaultValue(null), //TODO
            (new StringField('callback_org_name'))
                ->configureTitle('Имя организации')
                ->configureRequired(false)
                ->configureDefaultValue(null), //TODO
            (new StringField('callback_agreement_link'))
                ->configureTitle('Ссылка на пользовательское соглашение организации')
                ->configureRequired(false)
                ->configureDefaultValue(null), //TODO
            (new BooleanField('show_feedback'))
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new TextField('feedback_title'))
                ->configureTitle('Заголовок обратной связи')
                ->configureRequired(false)
                ->configureDefaultValue(null),
            (new StringField('feedback_stick'))
                ->configureRequired(true)
                ->configureDefaultValue(null),
            (new TextField('feedback_block'))
                ->configureRequired(false)
                ->configureDefaultValue(null), // json
            (new TextField('files'))
                ->configureRequired(false)
                ->configureDefaultValue(null), // json
        ];
    }
}
