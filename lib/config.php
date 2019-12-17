<?php

namespace Varrcan\Yaturbo;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;

/**
 * Class Config
 * @package Varrcan\Yaturbo
 */
class Config
{
    public $module_id = 'varrcan.yaturbo';
    public $response = [
        'message' => false,
    ];

    /**
     * Запись данных в базу
     *
     * @param      $name
     * @param      $option
     * @param bool $serialize
     */
    public function setOption($name, $option, $serialize = true)
    {
        Option::set($this->module_id, $name, $serialize ? \GuzzleHttp\json_encode($option) : $option);

        $this->response['message'] = self::setNote('Настройки сохранены', 'OK');
    }

    /**
     * Сохранение настроек
     *
     * @param $fields
     *
     */
    public function saveConfig($fields)
    {
        if ($fields) {
            $this->setOption('turbo-settings', $fields);

            if ($fields['mode'] === '1') {
                //TODO: Активировать событие
            }
        }

        $this->sendResponse();
    }

    /**
     * Получение настроек
     *
     * @param string $optionName
     *
     * @param bool   $json
     *
     * @return array|bool
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    public function getConfig($optionName = 'turbo-settings', $json = true)
    {
        $config = Option::get($this->module_id, $optionName, null);

        if ($json) {
            $config = \GuzzleHttp\json_decode($config, true);
        }

        return $config ?? false;
    }

    /**
     * Генерация уведомления
     *
     * @param $message
     * @param $type "ERROR"|"OK"|"PROGRESS"
     *
     * @return string
     */
    public static function setNote($message, $type):string
    {
        return (new \CAdminMessage(['MESSAGE' => $message, 'TYPE' => $type]))->Show();
    }

    /**
     * Отправка json ответа
     */
    public function sendResponse()
    {
        header('Content-Type: application/json');
        die(\GuzzleHttp\json_encode($this->response));
    }
}
