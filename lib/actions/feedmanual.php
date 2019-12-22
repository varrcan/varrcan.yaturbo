<?php

namespace Varrcan\Yaturbo\Actions;

use Bitrix\Main\ArgumentTypeException;
use Error;
use Varrcan\Yaturbo\Items;
use Varrcan\Yaturbo\Orm\YaTurboFeedTable;

/**
 * Class FeedManual
 * @package Varrcan\Yaturbo
 */
class FeedManual extends FeedAbstract
{
    /**
     * Сгенерировать rss и удалить агент
     *
     * @param $id
     */
    public function execute($id):void
    {
        $item = new Items();

        try {
            $this->generateRss($id);
            YaTurboFeedTable::update($id, [
                'status'    => 'RSS-канал сформирован',
                'is_upload' => false,
            ]);
        } catch (ArgumentTypeException | Error $e) {
            $item::setError($id, $e->getMessage(), $e);
        } finally {
            \CAgent::RemoveAgent(
                $this->getAgentName(['execute' => [$id]]),
                'varrcan.yaturbo'
            );
        }
    }
}
