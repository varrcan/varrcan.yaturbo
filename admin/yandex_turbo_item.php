<?php

namespace Varrcan\Yaturbo;

use BadMethodCallException;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

Loader::includeModule('varrcan.yaturbo');

$formFields = [];
$request    = Context::getCurrent()->getRequest();
$items      = new Items();
$iblock     = new Iblock();
$arIblock   = $items->getAllIblock();
$sites      = $items->getSites();
$elementId  = $request->getQuery('id');

if ($elementId) {
    $formFields = $items->getItemConfig($elementId);
}

if ($request->isPost()) {
    $method = $request->getPost('funcName');
    if ($method) {
        if (!method_exists($items, $method)) {
            throw new BadMethodCallException("Method $method not found");
        }
        $items->$method($request->getPost('params'));
    } else {
        $items->saveItem($request->getPostList()->toArray());
    }
}

global $APPLICATION;
$APPLICATION->SetTitle($formFields['id'] ? 'Редактирование канала #' . $formFields['id'] . ' | Yandex.Turbo' : 'Добавление RSS-канала | Yandex.Turbo');

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

\CJSCore::Init(['varrcan_yaturbo']);
?>

    <div class="yandex-turbo-response" style="display:none"></div>

    <?php if ($formFields['error']) : ?>
        <?=$items::setNote($formFields['error'], 'ERROR')?>
    <?php endif; ?>

    <form method="POST" action="" name="turbo-item" id="turbo-item">
        <input type="hidden" name="id" value="<?=$formFields['id']?>">
        <div class="adm-detail-block">
            <div class="adm-detail-tabs-block">
                <span id="setting" class="adm-detail-tab adm-detail-tab-active">Основные</span>
                <!--                <span id="" class="adm-detail-tab"></span>-->
                <!--                <span id="" class="adm-detail-tab"></span>-->
            </div>
            <div class="adm-detail-content-wrap">
                <div id="wrap-setting" class="adm-detail-content">
                    <div class="adm-detail-title">Параметры канала</div>
                    <div class="adm-detail-content-item-block">
                        <table class="adm-detail-content-table edit-table">
                            <tbody>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Инфоблок:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <select name="iblock_id" id="select_target">
                                        <option>Выберите инфоблок</option>
                                        <?php foreach ($arIblock as $type => $arItem) : ?>
                                            <optgroup label="<?=$type?>">
                                                <?php foreach ($arItem as $item) : ?>
                                                    <option value="<?=$item['ID']?>"
                                                            <?=$formFields['iblock_id'] === $item['ID'] ? 'selected' : ''?>>
                                                        <?=$item['NAME']?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" id="selected_iblock_id" value="<?=$formFields['iblock_id']?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Активность:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <?php $active = $formFields['active'] ?? 1 ?>
                                    <input name="active" value="<?=$active?>" <?=$active ? 'checked="checked"' : ''?> type="checkbox">
                                </td>
                            </tr>
                            <tr class="heading">
                                <td colspan="2">Описание канала</td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Заголовок:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input name="feed_name"
                                           size="50"
                                           value="<?=$formFields['feed_name']?>"
                                           type="text">
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Сайт (например, https://site.ru):
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input name="site_url"
                                           size="50"
                                           value="<?=$formFields['site_url'] ?? $items::getHost()?>"
                                           type="text">
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">Краткое описание:</td>
                                <td class="adm-detail-content-cell-r">
                                    <textarea name="feed_description" cols="54" rows="7"><?=$formFields['feed_description']?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Язык канала:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <select name="feed_lang">
                                        <?php foreach ($sites as $item) : ?>
                                            <option value="<?=$item['LANGUAGE_ID']?>"
                                                    <?=$formFields['feed_lang'] === $item['LANGUAGE_ID'] ? 'selected' : ''?>>
                                                <?=$item['LANGUAGE_ID']?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr class="heading">
                                <td colspan="2">Настройка источника данных</td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Дата публикации:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <select name="pub_date">
                                        <option value="DATE_CREATE"
                                                <?=$formFields['pub_date'] === 'DATE_CREATE' ? 'selected' : ''?>>
                                            дата создания
                                        </option>
                                        <option value="TIMESTAMP_X"
                                                <?=$formFields['pub_date'] === 'TIMESTAMP_X' ? 'selected' : ''?>>
                                            дата изменения
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Показывать основное изображение:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <select name="main_img">
                                        <option value="none"
                                                <?=$formFields['main_img'] === 'none' ? 'selected' : ''?>>
                                            не показывать
                                        </option>
                                        <option value="PREVIEW_PICTURE"
                                                <?=$formFields['main_img'] === 'PREVIEW_PICTURE' ? 'selected' : ''?>>
                                            картинка анонса
                                        </option>
                                        <option value="DETAIL_PICTURE"
                                                <?=$formFields['main_img'] === 'DETAIL_PICTURE' ? 'selected' : ''?>>
                                            детальная картинка
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Отображать автора:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <?php $author = $formFields['author'] ?>
                                    <input name="author" value="<?=$author?>" <?=$author ? 'checked="checked"' : ''?> type="checkbox">
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Отображать категорию:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <?php $category = $formFields['category'] ?>
                                    <input name="category" value="<?=$category?>" <?=$category ? 'checked="checked"' : ''?> type="checkbox">
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Шаблон адреса детальной страницы:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input name="detail_page_template"
                                           size="50"
                                           value="<?=$formFields['detail_page_template']?>"
                                           type="text">
                                    <div><small>Доступные шаблоны: #ELEMENT_ID#, #ELEMENT_CODE#, #SECTION_ID#, #SECTION_CODE#</small></div>
                                    <div><small>Пример с ЧПУ: /news/#SECTION_CODE#/#ELEMENT_CODE#/</small></div>
                                </td>
                            </tr>
                            <!--
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Шаблон адреса категории:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input name="section_page_template"
                                           size="50"
                                           value="<?//=$formFields['section_page_template']
                            ?>"
                                           type="text">
                                </td>
                            </tr>
                            -->
                            <tr class="heading">
                                <td colspan="2">Содержимое страницы</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align: center">
                                    <table class="internal" style="width: 980px;margin: 0 auto">
                                        <tbody>
                                        <tr>
                                            <td class="heading"><b>Тип</b></td>
                                            <td class="heading"><b>Свойство</b></td>
                                            <td class="heading"><b>Сортировка</b></td>
                                            <td class="heading"><b></b></td>
                                        </tr>
                                        <?php if ($formFields['content_config']) : ?>
                                            <?php $defaultProperty = $iblock->getDefaultProperty($formFields['iblock_id']) ?>
                                            <?php $defaultCustomProperty = $iblock->getCustomProperty($formFields['iblock_id'], true) ?>

                                            <?php foreach ($formFields['content_config'] as $id => $val) : ?>
                                                <?php $blockProperty = $val['block_type'] === 'element' ? $defaultProperty : $defaultCustomProperty ?>

                                                <tr id="block_<?=$id?>">
                                                    <td>
                                                        <select name="content_config[<?=$id?>][block_type]" id="block_type_<?=$id?>" onchange="getProperty(<?=$id?>);">
                                                            <option>Выберите тип</option>
                                                            <option value="element"
                                                                    <?=$val['block_type'] === 'element' ? 'selected' : ''?>>
                                                                Поле элемента
                                                            </option>
                                                            <option value="property"
                                                                    <?=$val['block_type'] === 'property' ? 'selected' : ''?>>
                                                                Свойство элемента
                                                            </option>
                                                            <!--<option value="tag">Мета-тег</option>-->
                                                        </select>
                                                    </td>
                                                    <td><select name="content_config[<?=$id?>][block_property]">
                                                            <?php foreach ($blockProperty as $value => $title) : ?>
                                                                <option value="<?=$value?>"
                                                                        <?=$val['block_property'] === $value ? 'selected' : ''?>>
                                                                    [<?=$value?>] <?=$title?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select></td>
                                                    <td><input type="text" name="content_config[<?=$id?>][block_sort]" value="<?=$val['block_sort']?>" style="width: 55px"></td>
                                                    <td><input value="Удалить" type="button" onclick="deleteBlock(<?=$id?>);"></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr id="block_0">
                                                <td>
                                                    <select name="content_config[0][block_type]" id="block_type_0" onchange="getProperty(0);">
                                                        <option>Выберите тип</option>
                                                        <option value="element">Поле элемента</option>
                                                        <option value="property">Свойство элемента</option>
                                                        <!--<option value="tag">Мета-тег</option>-->
                                                    </select>
                                                </td>
                                                <td><select name="content_config[0][block_property]"></select></td>
                                                <td><input type="text" name="content_config[0][block_sort]" value="10" style="width: 55px"></td>
                                                <td><input value="Удалить" disabled="disabled" type="button"></td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr class="new_block"></tr>
                                        <input type="hidden"
                                               value="<?=$formFields['content_config'] ? count($formFields['content_config']) : 0?>"
                                               name="count_block"
                                               id="count_block">
                                        <tr>
                                            <td colspan="4">
                                                <input id="add_block" name="add_block" value="Добавить блок" type="button">
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr class="heading">
                                <td colspan="2">Меню</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align: center">
                                    <table class="internal" style="width: 980px;margin: 0 auto">
                                        <tbody>
                                        <tr>
                                            <td class="heading"><b>Название</b></td>
                                            <td class="heading"><b>Ссылка</b></td>
                                            <td class="heading"><b>Сортировка</b></td>
                                            <td class="heading"><b></b></td>
                                        </tr>
                                        <?php if ($formFields['menu']) : ?>
                                            <?php foreach ($formFields['menu'] as $id => $val) : ?>
                                                <tr id="menu_<?=$id?>">
                                                    <td><input type="text" name="menu[<?=$id?>][menu_name]" value="<?=$val['menu_name']?>"></td>
                                                    <td><input type="text" name="menu[<?=$id?>][menu_path]" value="<?=$val['menu_path']?>"></td>
                                                    <td><input type="text" name="menu[<?=$id?>][menu_sort]" value="<?=$val['menu_sort']?>" style="width: 55px"></td>
                                                    <td><input value="Удалить" type="button" onclick="deleteMenu(<?=$id?>);"></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr id="menu_0">
                                                <td><input type="text" name="menu[0][menu_name]" value="Главная"></td>
                                                <td><input type="text" name="menu[0][menu_path]" value="/"></td>
                                                <td><input type="text" name="menu[0][menu_sort]" value="10" style="width: 55px"></td>
                                                <td><input value="Удалить" disabled="disabled" type="button"></td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr class="new_menu"></tr>
                                        <input type="hidden"
                                               value="<?=$formFields['menu'] ? count($formFields['menu']) : 0?>"
                                               name="count_menu"
                                               id="count_menu">
                                        <tr>
                                            <td colspan="4">
                                                <input id="add_menu" name="add_menu" value="Добавить пункт меню" type="button">
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr class="heading">
                                <td colspan="2">Блок со ссылками</td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Источник:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <select name="link_block_source">
                                        <option value="auto"
                                                <?=$formFields['link_block_source'] === 'auto' ? 'selected' : ''?>>
                                            Авто рекомендации
                                        </option>
                                        <option value="link"
                                                <?=$formFields['link_block_source'] === 'link' ? 'selected' : ''?>>
                                            По очереди
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Кол-во элементов (максимум 30):
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input name="link_block_count"
                                           size="50"
                                           value="<?=$formFields['link_block_count'] ?? 3?>"
                                           type="text">
                                </td>
                            </tr>
                            <tr class="heading">
                                <td colspan="2">Кнопка "Поделиться"</td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l"></td>
                                <td class="adm-detail-content-cell-r">
                                    <?php foreach ($items->getShareBlockItem() as $key => $name) : ?>
                                        <span style="vertical-align: 3px;"><?=$name?>:</span>
                                        <input name="share_block[<?=$key?>]" <?=$formFields['share_block'][$key] ? 'checked="checked"' : ''?>
                                               value="<?=$formFields['share_block'][$key]?>"
                                               type="checkbox">
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <tr class="heading">
                                <td colspan="2">Блок обратной связи</td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Отображать блок:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <?php $showFeedback = $formFields['show_feedback'] ?>
                                    <input name="show_feedback" value="<?=$showFeedback?>" <?=$showFeedback ? 'checked="checked"' : ''?> type="checkbox">
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Заголовок (отобразится, если выбран тип Внизу страницы):
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input name="feedback_title"
                                           size="50"
                                           value="<?=$formFields['feedback_title'] ?? 'Обратная связь'?>"
                                           type="text">
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Размещение:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <select name="feedback_stick">
                                        <option value="left"
                                                <?=$formFields['feedback_stick'] === 'left' ? 'selected' : ''?>>
                                            Выравнивание по левому краю страницы
                                        </option>
                                        <option value="right"
                                                <?=$formFields['feedback_stick'] === 'right' ? 'selected' : ''?>>
                                            Выравнивание по правому краю страницы
                                        </option>
                                        <option value="false"
                                                <?=$formFields['feedback_stick'] === 'false' ? 'selected' : ''?>>
                                            Внизу страницы
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align: center">
                                    <table class="internal" style="width: 650px;margin: 0 auto">
                                        <tbody>
                                        <tr>
                                            <td class="heading"><b>Тип связи</b></td>
                                            <td class="heading"><b>Значение</b></td>
                                            <td class="heading"><b></b></td>
                                        </tr>
                                        <?php if ($formFields['feedback_block']) : ?>
                                            <?php $typeItems = $items->getFeedbackBlockItem(); ?>
                                            <?php foreach ($formFields['feedback_block'] as $id => $val) : ?>
                                                <tr id="feedback_<?=$id?>">
                                                    <td>
                                                        <select name="feedback_block[<?=$id?>][type]" id="feedback_block_type_<?=$id?>">
                                                            <?php foreach ($typeItems as $value => $title) : ?>
                                                                <option value="<?=$value?>"
                                                                        <?=$val['type'] === $value ? 'selected="selected"' : ''?>>
                                                                    <?=$title?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td><input type="text" name="feedback_block[<?=$id?>][value]" value="<?=$val['value']?>"></td>
                                                    <td><input value="Удалить" type="button" onclick="deleteFeedback(<?=$id?>);"></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr id="feedback_0">
                                                <td>
                                                    <select name="feedback_block[0][type]">
                                                        <?php $typeItems = $items->getFeedbackBlockItem(); ?>
                                                        <?php foreach ($typeItems as $value => $title) : ?>
                                                            <option value="<?=$value?>"><?=$title?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td><input type="text" name="feedback_block[0][value]" value=""></td>
                                                <td><input value="Удалить" disabled="disabled" type="button"></td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr class="new_feedback"></tr>
                                        <input type="hidden"
                                               value="<?=$formFields['feedback_block'] ? count($formFields['feedback_block']) : 0?>"
                                               name="count_feedback_block"
                                               id="count_feedback_block">
                                        <tr>
                                            <td colspan="3">
                                                <input id="add_feedback_block" name="add_feedback_block" value="Добавить тип связи" type="button">
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr class="heading">
                                <td colspan="2">Веб-аналитика</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align: center">
                                    <table class="internal" style="width: 650px;margin: 0 auto">
                                        <tbody>
                                        <tr>
                                            <td class="heading"><b>Тип</b></td>
                                            <td class="heading"><b>Идентификатор</b></td>
                                            <td class="heading"><b></b></td>
                                        </tr>
                                        <?php if ($formFields['analytics']) : ?>
                                            <?php $typeItems = $items->getAnalyticsItem(); ?>
                                            <?php foreach ($formFields['analytics'] as $id => $val) : ?>
                                                <tr id="analytics_<?=$id?>">
                                                    <td>
                                                        <select name="analytics[<?=$id?>][type]" id="analytics_type_<?=$id?>">
                                                            <?php foreach ($typeItems as $value) : ?>
                                                                <option value="<?=$value?>"
                                                                        <?=$val['type'] === $value ? 'selected="selected"' : ''?>>
                                                                    <?=$value?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td><input type="text" name="analytics[<?=$id?>][value]" value="<?=$val['value']?>"></td>
                                                    <td><input value="Удалить" type="button" onclick="deleteAnalytics(<?=$id?>);"></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <tr class="new_analytics"></tr>
                                        <input type="hidden"
                                               value="<?=$formFields['analytics'] ? count($formFields['analytics']) : 0?>"
                                               name="count_analytics"
                                               id="count_analytics">
                                        <tr>
                                            <td colspan="4">
                                                <input id="add_analytics" name="add_analytics" value="Добавить пункт" type="button">
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="adm-detail-content-btns-wrap adm-detail-content-btns-pin">
                    <div class="adm-detail-content-btns">
                        <button type="submit" class="adm-btn adm-btn-save">Сохранить</button>
                        <a href="yandex_turbo_list.php" class="adm-btn">Отменить</a>
                    </div>
                </div>

            </div>
        </div>
    </form>

    <?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
