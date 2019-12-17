<?php

namespace Varrcan\Yaturbo\Actions;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\IO\File;
use Error;
use Varrcan\Yaturbo\Api\YandexApi;
use Varrcan\Yaturbo\GenerateFeed;
use Varrcan\Yaturbo\Items;
use Varrcan\Yaturbo\Orm\YaTurboFeedTable;
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
        try {
            //TODO: Добавить условие выполнения, при добавлении новости
            (new GenerateFeed())->generate($id);

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
        }

        return $this->getAgentName(['execute' => [$id]]); // метод обязательно должен вернуть имя агента
    }
}
