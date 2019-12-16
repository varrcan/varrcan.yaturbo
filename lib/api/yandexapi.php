<?php

namespace Varrcan\Yaturbo\Api;

use Bitrix\Main\Diag\Debug;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;

/**
 * Class YandexApi
 *
 * @package Varrcan\Yaturbo\Api
 */
class YandexApi
{
    public const BASE_URI = 'https://api.webmaster.yandex.net/v4/user';
    private $token;
    private $client;

    /**
     * Api constructor.
     *
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri'         => self::BASE_URI,
            'verify'           => false,
            'debug'            => false,
            'timeout'          => 30,
            'connect_timeout'  => 30,
            'force_ip_resolve' => 'v4',
        ]);
    }

    /**
     * Стандартные заголовки запроса
     *
     * @return array
     */
    public function getDefaultHeader()
    {
        return [
            'Authorization' => 'OAuth oauth_token',
        ];
    }

    /**
     * Отправка запроса
     *
     * @param      $method
     * @param      $url
     * @param null $options
     *
     * @return array|bool
     * @throws GuzzleException
     */
    public function sendRequest($method, $url, $options = null)
    {
        $options['headers'] = $this->getDefaultHeader();

        try {
            $response = $this->client->request($method, $url, $options);
            $body     = $response->getBody()->getContents();

            return $this->parsResponse($body);
        } catch (RequestException | InvalidArgumentException $e) {
            Debug::writeToFile($e, '', 'yandex-api.log');
        }

        return false;
    }

    /**
     * Парсинг ответа сервера
     *
     * @param $data
     *
     * @return array
     */
    public function parsResponse($data)
    {
        $data = \GuzzleHttp\json_decode($data, false);

        return $data;
    }
}
