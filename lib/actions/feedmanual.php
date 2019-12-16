<?php

namespace Varrcan\Yaturbo\Actions;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Diag\Debug;
use Error;
use Varrcan\Yaturbo\GenerateFeed;
use Varrcan\Yaturbo\Orm\YaTurboFeedTable;
use Webpractik\Agent\AgentTrait;

/**
 * Class FeedManual
 * @package Varrcan\Yaturbo
 */
class FeedManual
{
    use AgentTrait;

    /**
     * Сгенерировать rss и удалить агент
     *
     * @param $id
     */
    public function execute($id):void
    {
        try {
            (new GenerateFeed())->generate($id);
        } catch (ArgumentTypeException | Error $e) {
            YaTurboFeedTable::update($id, ['status' => 'Ошибка']);
            Debug::writeToFile($e, 'FeedManual', 'yandex-turbo.log');
        } finally {
            YaTurboFeedTable::update($id, ['status' => 'RSS-канал сформирован']);
            \CAgent::RemoveAgent(
                $this->getAgentName(['execute' => [$id]]),
                'varrcan.yaturbo'
            );
        }
    }
}
