<?php

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Varrcan\Yaturbo\Config;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

$APPLICATION->SetTitle('Настройки | Yandex.Turbo');
Loader::includeModule('varrcan.yaturbo');

$request = Context::getCurrent()->getRequest();
$config  = new Config();

if ($request->isPost()) {
    $config->saveConfig($request->getPostList()->toArray());
}

$formFields = $config->getConfig();

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

CJSCore::Init(['jquery']);
Asset::getInstance()->addJs('/bitrix/js/varrcan.yaturbo/yandex_turbo_main.js');
?>
    <div class="yandex-turbo-response" style="display:none"></div>

    <form method="POST" action="" name="turbo-settings" id="turbo-settings">
        <div class="adm-detail-block">
            <div class="adm-detail-tabs-block">
                <span id="setting" class="adm-detail-tab adm-detail-tab-active">Настройки</span>
                <!--                <span id="user" class="adm-detail-tab">Пользователи</span>-->
                <!--                <span id="proxy" class="adm-detail-tab">Настройки прокси</span>-->
            </div>
            <div class="adm-detail-content-wrap">
                <div id="wrap-setting" class="adm-detail-content">
                    <div class="adm-detail-title">Параметры модуля</div>
                    <div class="adm-detail-content-item-block">
                        <table class="adm-detail-content-table edit-table">
                            <tbody>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Включить модуль:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input name="module_on"
                                           value="<?=$formFields['module_on']?>"
                                        <?=(bool)$formFields['module_on'] === true ? 'checked="checked"' : ''?>
                                           type="checkbox">
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Токен бота:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input name="token"
                                           size="50"
                                           value="<?=$formFields['token']?>"
                                           type="text">
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Интервал агента (в минутах):
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input name="agent"
                                           size="50"
                                           value="<?=$formFields['agent'] ?? 1440?>"
                                           type="text">
                                </td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    Режим debug:
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input name="debug"
                                           value="<?=$formFields['debug']?>"
                                        <?=(bool)$formFields['debug'] === true ? 'checked="checked"' : ''?>
                                           type="checkbox">
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="adm-detail-content-btns-wrap adm-detail-content-btns-pin">
                    <div class="adm-detail-content-btns">
                        <button type="submit" class="adm-btn adm-btn-save">Сохранить</button>
                    </div>
                </div>

            </div>
        </div>
    </form>

<?=BeginNote()?>
    <p>Вы можете запустить полную выгрузку, чтобы добавить в rss-файл существующие элементы, которые были созданы до установки этого модуля, либо если выбрали ручной режим работы.</p>
    <p><span class="required">Внимание!</span> Эта операция создаст чрезмерную нагрузку, все существующие данные будут удалены и созданы заново.</p>
    <form method="POST" action="<?=$APPLICATION->GetCurPage()?>">
        <div class="adm-detail-content-btns">
            <input type="button" class="adm-btn" name="disallow_url" value="Запустить выгрузку вручную">
        </div>
    </form>
<?php echo EndNote(); ?>

<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
