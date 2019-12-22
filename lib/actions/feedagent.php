<?php

namespace Varrcan\Yaturbo\Actions;

use Bitrix\Main\ArgumentTypeException;
use Error;
use Varrcan\Yaturbo\Items;

/**
 * Class FeedAgent
 * @package Varrcan\Yaturbo
 */
class FeedAgent extends FeedAbstract
{
    /**
     * @param $id
     *
     * @return string
     * @throws ArgumentTypeException
     */
    public function execute($id)
    {
        $item = new Items();

        try {
            //TODO: Добавить условие выполнения, при добавлении новости
            $this->generateRss($id);

            $config = $item->getItemConfig($id);

            if ($config) {
                $this->trySend($id, $config);
            }
        } catch (ArgumentTypeException | Error $e) {
            $item::setError($id, $e->getMessage(), $e);
        }

        return $this->getAgentName(['execute' => [$id]]); // метод обязательно должен вернуть имя агента
    }
}
