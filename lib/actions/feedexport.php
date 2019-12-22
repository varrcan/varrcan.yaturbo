<?php

namespace Varrcan\Yaturbo\Actions;

use Bitrix\Main\ArgumentTypeException;
use Error;
use Varrcan\Yaturbo\Items;

/**
 * Class FeedExport
 * @package Varrcan\Yaturbo
 */
class FeedExport extends FeedAbstract
{
    /**
     * Отправить данные в Яндекс и удалить агент
     *
     * @param $id
     *
     * @throws ArgumentTypeException
     */
    public function execute($id)
    {
        $item = new Items();

        try {
            $config = $item->getItemConfig($id);

            if ($config) {
                $this->trySend($id, $config);
            }
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
