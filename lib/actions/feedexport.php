<?php

namespace Varrcan\Yaturbo\Actions;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Diag\Debug;
use Error;
use Varrcan\Yaturbo\GenerateFeed;
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
            //TODO
        } catch (ArgumentTypeException | Error $e) {
            YaTurboFeedTable::update($id, ['status' => 'Ошибка']);
            Debug::writeToFile($e, 'FeedManual', 'yandex-turbo.log');
        } finally {
            YaTurboFeedTable::update($id, ['status' => 'Отправлено']);
            \CAgent::RemoveAgent(
                $this->getAgentName(['execute' => [$id]]),
                'varrcan.yaturbo'
            );
        }
    }
}
