<?php

namespace Varrcan\Yaturbo\Actions;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\IO\File;
use Error;
use Varrcan\Yaturbo\Api\YandexApi;
use Varrcan\Yaturbo\Items;
use Varrcan\Yaturbo\Orm\YaTurboFeedTable;
use Webpractik\Agent\AgentTrait;

/**
 * Class FeedExport
 * @package Varrcan\Yaturbo
 */
class FeedExport
{
    use AgentTrait;

    /**
     * Отправить данные в Яндекс и удалить агент
     *
     * @param $id
     *
     * @throws ArgumentTypeException
     */
    public function execute($id)
    {
        try {
            $item = (new Items())->getItemConfig($id);

            if ($item && $item['files']) {
                $result = [];

                foreach ($item['files'] as $file) {
                    $uploadFile = new File(Application::getDocumentRoot() . Items::$workDir . $id . '/' . $file);
                    $result[]   = (new YandexApi())->uploadRss($uploadFile);
                }

                if (\in_array('error_message', $result, true)) {
                    YaTurboFeedTable::update($id, ['status' => 'Ошибка']);
                    throw new Error(\implode(', ', $result));
                }

                YaTurboFeedTable::update($id, ['status' => 'Отправлено']);
            }
        } catch (ArgumentTypeException | Error $e) {
            YaTurboFeedTable::update($id, ['status' => 'Ошибка']);
            Debug::writeToFile($e, 'FeedManual', 'yandex-turbo.log');
        } finally {
            \CAgent::RemoveAgent(
                $this->getAgentName(['execute' => [$id]]),
                'varrcan.yaturbo'
            );
        }
    }
}
