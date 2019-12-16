<?php

namespace Varrcan\Yaturbo;

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
     * @return array
     */
    public function getConfig()
    {
        $config = Option::get($this->module_id, 'turbo-settings', null);

        return $config ? \GuzzleHttp\json_decode($config, true) : [];
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
