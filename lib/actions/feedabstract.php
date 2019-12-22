<?php

namespace Varrcan\Yaturbo\Actions;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\ConfigurationException;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileNotFoundException;
use Error;
use GuzzleHttp\Exception\GuzzleException;
use Varrcan\Yaturbo\Api\YandexApi;
use Varrcan\Yaturbo\GenerateFeed;
use Varrcan\Yaturbo\Items;
use Varrcan\Yaturbo\Orm\YaTurboFeedTable;
use Webpractik\Agent\AgentTrait;

/**
 * Class FeedAbstract
 * @package Varrcan\Yaturbo\Actions
 */
abstract class FeedAbstract
{
    use AgentTrait;

    /**
     * Сформировать rss файл
     *
     * @param $id
     *
     * @return $this
     */
    public function generateRss($id)
    {
        (new GenerateFeed())->generate($id);

        return $this;
    }

    /**
     * Отправка запроса
     *
     * @param $id
     * @param $config
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws ConfigurationException
     * @throws FileNotFoundException
     * @throws GuzzleException
     */
    public function trySend($id, $config)
    {
        $errors = [];
        $taskId = [];
        $result = [];

        $api = new YandexApi();

        if (!$config['files']) {
            throw new Error('No files found');
        }

        foreach ($config['files'] as $file) {
            $uploadFile = new File(Application::getDocumentRoot() . Items::$workDir . $id . '/' . $file);
            $result     = $api->setHost($config['site_url'])->uploadRss($uploadFile);
            $taskId[]   = $result['task_id'];

            if ($result['errors']) {
                $errors = $result['errors'];
                if (\is_array($errors)) {
                    $errors = \implode("\n", $errors);
                }
                throw new Error($errors);
            }
        }

        YaTurboFeedTable::update($id, [
            'status'    => 'Отправлено',
            'is_upload' => true,
            'task_id'   => \GuzzleHttp\json_encode($taskId),
        ]);
    }
}
