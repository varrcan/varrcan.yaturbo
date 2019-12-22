<?php

namespace Varrcan\Yaturbo\Api;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\ConfigurationException;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Varrcan\Yaturbo\Config;
use Varrcan\Yaturbo\Items;

/**
 * Class YandexApi
 *
 * @package Varrcan\Yaturbo\Api
 */
class YandexApi
{
    public const BASE_URI = 'https://api.webmaster.yandex.net/v4/';
    private $token;
    private $client;
    private $options = [];
    private $settings;
    private $config;
    private $host;
    private $error;

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

        $this->config = new Config();

        $this->getModuleSettings();
    }

    /**
     * Установить токен
     *
     * @return YandexApi
     */
    public function setToken()
    {
        $token = $this->settings['token'];

        if (!$token) {
            throw new ConfigurationException('Invalid token');
        }

        $this->options['headers']['Authorization'] = "OAuth $token";

        return $this;
    }

    /**
     * Установить режим отладки
     *
     * @return $this
     */
    public function setDebugMode()
    {
        $this->options['query']['mode'] = 'DEBUG';

        return $this;
    }

    /**
     * Получить настройки модуля
     *
     * @return $this
     */
    public function getModuleSettings()
    {
        $this->settings = $this->config->getConfig();

        if ($this->settings['debug']) {
            $this->setDebugMode();
        }

        return $this;
    }

    /**
     * Получить ID пользователя Яндекс
     *
     * @return array|bool|mixed
     * @throws GuzzleException
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    public function getYandexUserId()
    {
        $yandexUserId = $this->config->getConfig('turbo-user', false);

        if (!$yandexUserId) {
            $request = $this->sendRequest('GET', 'user');

            if ($request['user_id']) {
                $yandexUserId = $request['user_id'];

                $this->config->setOption('turbo-user', $yandexUserId, false);
            }
        }

        return $yandexUserId ?? false;
    }

    /**
     * Получить ссылку на загрузку
     *
     * @return bool|string
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws GuzzleException
     */
    public function getUploadAddress()
    {
        $upload = $this->config->getConfig('turbo-upload');

        if (!$upload || ($upload && $this->isLinkExpired($upload['valid_until']))) {
            $userId = $this->getYandexUserId();

            if ($userId) {
                $site    = $this->host ?? Items::getHost(true);
                $request = $this->sendRequest('GET', "user/$userId/hosts/$site/turbo/uploadAddress");

                if ($request['upload_address']) {
                    $uploadAddress = \str_replace(self::BASE_URI, '', $request['upload_address']);

                    $this->config->setOption('turbo-upload', ['upload_address' => $uploadAddress, 'valid_until' => $request['valid_until']]);
                }
            }
        } else {
            $uploadAddress = $upload['upload_address'];
        }

        return $uploadAddress ?? false;
    }

    /**
     * Установить адрес сайта
     *
     * @param $host
     *
     * @return $this
     */
    public function setHost($host)
    {
        $port = strstr($host, '://', true) === 'http' ? 80 : 443;

        $this->host = \str_replace('//', '', $host) . ":$port";

        return $this;
    }

    /**
     * Проверить время жизни ссылки
     *
     * @param $dateTime
     *
     * @return bool
     * @throws ObjectException
     */
    public function isLinkExpired($dateTime)
    {
        $now   = (new DateTime())->getTimestamp();
        $valid = DateTime::createFromPhp(new \DateTime($dateTime))->getTimestamp();

        return $valid < $now;
    }

    /**
     * Отправить файл в Яндекс
     *
     * @param File $file
     *
     * @return bool|mixed
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws ConfigurationException
     * @throws GuzzleException
     * @throws FileNotFoundException
     */
    public function uploadRss(File $file)
    {
        $response = null;

        $rssContent = $file->getContents();

        $this->options['headers']['Content-Type'] = 'application/rss+xml';
        $this->options['body']                    = $rssContent;

        $url = $this->getUploadAddress();

        if ($url) {
            $response = $this->sendRequest('POST', $url);
        }

        return ['task_id' => $response['task_id'] ?? '', 'errors' => $this->error];
    }

    /**
     * Отправка запроса
     *
     * @param       $method
     * @param       $url
     * @param array $options
     *
     * @return array|bool
     * @throws ConfigurationException
     */
    public function sendRequest($method, $url, $options = [])
    {
        $this->setToken();

        $options = \array_merge($options, $this->options);

        try {
            $response = $this->client->request($method, $url, $options);

            return $this->parsResponse($response);
        } catch (GuzzleException | ClientException | RequestException | InvalidArgumentException $e) {
            $this->error[] = [$e->getMessage(), $e->getFile() . ':' . $e->getLine()];
        }

        return false;
    }

    /**
     * Парсинг ответа сервера
     *
     * @param ResponseInterface $response
     *
     * @return array
     */
    public function parsResponse(ResponseInterface $response)
    {
        $body       = $response->getBody()->getContents();
        $statusCode = (int)$response->getStatusCode();

        $body = \GuzzleHttp\json_decode($body, true);

        switch ($statusCode) {
            case 200:
            case 202:
                break;
            case 400:
            case 403:
            case 404:
            case 410:
            case 413:
            case 429:
                $this->error[] = $body;
                break;
        }

        return $body;
    }
}
