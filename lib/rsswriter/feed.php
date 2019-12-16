<?php

namespace Varrcan\Yaturbo\RssWriter;

/**
 * Class Feed
 * @package Varrcan\Yaturbo\RssWriter
 */
class Feed
{
    public $title;
    public $link;
    public $description;
    public $language = 'ru';
    public $analytics;
    public $adNetwork;
    private $arItems;

    /**
     * Инициализация параметров канала
     *
     * @param array $params
     *
     * @return $this
     */
    public function initChanel(array $params)
    {
        foreach ($params as $item => $value) {
            $this->$item = $value;
        }

        return $this;
    }

    /**
     * Рендер XML
     *
     * @param $arItem
     *
     * @return string
     */
    public function render(array $arItem)
    {
        if (!$arItem) {
            throw new \RuntimeException('Could not get items');
        }

        $this->arItems = $arItem;

        $xml = (new GenerateDom())->create('rss', $this->getRssContent());

        return $xml->saveXML();
    }

    /**
     * Сформировать массив
     *
     * @return array
     */
    public function getRssContent()
    {
        $channelParams = $this->getChannelParams();
        $channelParams = array_diff($channelParams, ['']);

        if (!$channelParams) {
            throw new \RuntimeException('Could not get channel parameters');
        }

        $channel = \array_merge($channelParams, ['item' => $this->arItems]);

        return [
            '@attributes' => [
                'xmlns:yandex' => 'http://news.yandex.ru',
                'xmlns:media'  => 'http://search.yahoo.com/mrss/',
                'xmlns:turbo'  => 'http://turbo.yandex.ru',
                'version'      => '2.0',
            ],
            'channel'     => $channel,
        ];
    }

    /**
     * Установить параметры канала
     *
     * @return array
     */
    public function getChannelParams()
    {
        // TODO: Сделать валидацию
        $params = [
            'title'           => $this->title,
            'link'            => $this->link,
            'description'     => $this->description,
            'language'        => $this->language,
            'turbo:adNetwork' => $this->adNetwork,
        ];

        if ($this->analytics) {
            $params = \array_merge($params, ['turbo:analytics' => $this->analytics]);
        }

        return $params;
    }
}
