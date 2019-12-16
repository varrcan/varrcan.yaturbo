<?php

namespace Varrcan\Yaturbo\Actions;

use Bitrix\Main\ArgumentTypeException;
use Varrcan\Yaturbo\GenerateFeed;
use Webpractik\Agent\AgentTrait;

/**
 * Class FeedAgent
 * @package Varrcan\Yaturbo
 */
class FeedAgent
{
    use AgentTrait;

    /**
     * @param $id
     *
     * @return string
     * @throws ArgumentTypeException
     */
    public function execute($id)
    {
        (new GenerateFeed())->generate($id);

        //TODO

        return $this->getAgentName(['execute' => [$id]]); // метод обязательно должен вернуть имя агента
    }
}
