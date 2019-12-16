<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Varrcan\Yaturbo\Items;

Loader::includeModule('varrcan.yaturbo');

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$showMessage = Context::getCurrent()->getRequest()->getQuery('message');

if ($showMessage) {
    echo Items::setNote($showMessage, 'OK');
}

$APPLICATION->IncludeComponent(
    'varrcan:yaturbo.list',
    '.default',
    [],
    true
);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
