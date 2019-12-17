<?php

namespace Varrcan\Yaturbo;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\TypeLanguageTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Context;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SiteTable;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use InvalidArgumentException;
use Varrcan\Yaturbo\Actions\FeedAgent;
use Varrcan\Yaturbo\Actions\FeedExport;
use Varrcan\Yaturbo\Actions\FeedManual;
use Varrcan\Yaturbo\Orm\YaTurboFeedTable;
use Webpractik\Agent\AgentHelper;
use Webpractik\Agent\AgentTask;
use Webpractik\Agent\AgentTrait;

/**
 * Класс для работы с элементами настроек rss каналов
 *
 * @package Varrcan\Yaturbo
 */
class Items
{
    use AgentTrait;

    public static $workDir = '/upload/ya_turbo/';
    public $module_id = 'varrcan.yaturbo';
    public $response = [
        'message' => false,
        'error'   => false,
        'status'  => true,
    ];

    /**
     * Сохранение элемента
     *
     * @param $fields
     */
    public function saveItem($fields)
    {
        $jsonFields = $this->getJsonFields();
        $checkbox   = $this->getCheckbox();

        try {
            foreach ($fields as $key => $value) {
                if (\in_array($key, $jsonFields, true)) {
                    $fields[$key] = \GuzzleHttp\json_encode($value);
                }
            }
            $checkbox = array_diff_key($checkbox, $fields);

            $user = CurrentUser::get()->getId();
            $date = new DateTime();

            $result = $fields['id'] ?
                YaTurboFeedTable::update(
                    $fields['id'],
                    \array_merge(
                        $fields,
                        $checkbox,
                        ['modified_by' => $user, 'modified_date' => $date]
                    )
                ) :
                YaTurboFeedTable::add(
                    \array_merge(
                        $fields,
                        ['create_by' => $user, 'create_date' => $date]
                    )
                );

            if ($result->isSuccess()) {
                $this->response = ['message' => 'Запись успешно сохранена'];
            } else {
                $this->response = ['error' => self::setNote($result->getErrorMessages(), 'ERROR')];
            }
        } catch (\Exception | InvalidArgumentException $e) {
            Debug::writeToFile($e, 'saveItem', 'yandex-turbo.log');
            $this->response = ['error' => self::setNote($e->getMessage(), 'ERROR')];
        } finally {
            $this->sendResponse();
        }
    }

    /**
     * Получение элемента
     *
     * @param $id
     *
     * @return array
     */
    public function getItemConfig($id):array
    {
        $item = YaTurboFeedTable::query()
            ->setSelect(['*'])
            ->addFilter('id', (int)$id)
            ->exec()
            ->fetch();

        if ($item) {
            $jsonFields = $this->getJsonFields();

            try {
                foreach ($jsonFields as $key) {
                    $itemKeys = array_keys($item);
                    if ($item[$key] && \in_array($key, $itemKeys, true)) {
                        $item[$key] = \GuzzleHttp\json_decode($item[$key], true);
                    }
                }
            } catch (InvalidArgumentException $e) {
                Debug::writeToFile($e, 'getItemConfig', 'yandex-turbo.log');

                return ['error' => $e->getMessage()];
            }
        }

        return $item ?? [];
    }

    /**
     * JSON поля
     *
     * @return array
     */
    public function getJsonFields():array
    {
        return [
            'content_config',
            'menu',
            'share_block',
            'feedback_block',
            'files',
            'analytics',
        ];
    }

    /**
     * Костыль для Checkbox полей (удаляются через getPostList)
     *
     * @return array
     */
    public function getCheckbox():array
    {
        return [
            'active'        => false,
            'author'        => false,
            'category'      => false,
            'show_feedback' => false,
        ];
    }

    /**
     * Получить список всех инфоблоков
     *
     * @return array
     */
    public function getAllIblock():array
    {
        $arResult = [];
        $query    = [];

        try {
            $query = IblockTable::query()
                ->addSelect('ID')
                ->addSelect('NAME')
                ->addSelect('IBLOCK_TYPE.NAME', 'TYPE_NAME')
                ->where('ACTIVE', 'Y')
                ->registerRuntimeField(new ReferenceField(
                    'IBLOCK_TYPE',
                    TypeLanguageTable::class,
                    Join::on('this.IBLOCK_TYPE_ID', 'ref.IBLOCK_TYPE_ID')->where('ref.LANGUAGE_ID', LANGUAGE_ID ?? 'ru')
                ))
                ->exec()
                ->fetchAll();
        } catch (ArgumentException | SystemException $e) {
            Debug::writeToFile($e, 'getAllIblock', 'yandex-turbo.log');
        }

        if ($query) {
            foreach ($query as $item) {
                $arResult[$item['TYPE_NAME']][] = $item;
            }
        }

        return $arResult;
    }

    /**
     * Текущий сайт с протоколом
     *
     * @param bool $port
     *
     * @return string
     */
    public static function getHost($port = false):string
    {
        $server = Context::getCurrent()->getServer();

        $scheme     = $server->get('HTTP_X_FORWARDED_PROTO') ?? $server->get('REQUEST_SCHEME');
        $httpHost   = $server->getHttpHost() ?? $server->getServerName();
        $schemePort = $scheme === 'https' ? 443 : 80;

        return $port ? "$scheme://$httpHost:$schemePort" : "$scheme://$httpHost";
    }

    /**
     * Получить данные зарегистрированных сайтов
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getSites()
    {
        return SiteTable::query()
            ->setSelect(['*'])
            ->where('ACTIVE', 'Y')
            ->addOrder('SORT', 'ASC')
            ->exec()
            ->fetchAll();
    }

    /**
     * Параметры инфоблока
     *
     * @param $params
     */
    public function iblockType(array $params)
    {
        $iblock = new Iblock();

        if (!$params['iblock']) {
            $this->sendResponse(['error' => self::setNote('Выберите инфоблок', 'ERROR')]);
        }

        switch ($params['type']) {
            case 'element':
                $this->response = ['data' => $iblock->getDefaultProperty($params['iblock'])];
                break;
            case 'property':
                $this->response = ['data' => $iblock->getCustomProperty($params['iblock'], true)];
                break;
            case 'tag':
                $this->response = ['data' => $iblock->getMetaProperty($params['iblock'])];
                break;
        }

        $this->sendResponse();
    }

    /**
     * Получить шаблоны на детальную страницу и категорию
     *
     * @param $iblockId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getIblockTemplateUrl($iblockId)
    {
        $iblock = new Iblock();

        $result = $iblock->getIblockInfo($iblockId);

        $this->response = [
            'data' => [
                'detail'  => \str_replace('#SITE_DIR#', '', $result['DETAIL_PAGE_URL']),
                'section' => $result['SECTION_PAGE_URL'],
            ],
        ];

        $this->sendResponse();
    }

    /**
     * Значения кнопки поделиться
     *
     * @return array
     */
    public function getShareBlockItem()
    {
        return [
            'facebook'      => 'Facebook',
            'google'        => 'Google',
            'odnoklassniki' => 'Одноклассники',
            'telegram'      => 'Telegram',
            'twitter'       => 'Twitter',
            'vkontakte'     => 'Вконтакте',
        ];
    }

    /**
     * Значения списка обратной связи
     *
     * @return array
     */
    public function getFeedbackBlockItem()
    {
        return [
            'call'          => 'Номер телефона',
            'chat'          => 'Чат для бизнеса',
            'mail'          => 'Электронная почта',
            'callback'      => 'Форма обратной связи',
            'facebook'      => 'Facebook',
            'google'        => 'Google',
            'odnoklassniki' => 'Одноклассники',
            'telegram'      => 'Telegram',
            'twitter'       => 'Twitter',
            'viber'         => 'Viber',
            'vkontakte'     => 'Вконтакте',
            'whatsapp'      => 'WhatsApp',
        ];
    }

    /**
     * Значения списка аналитики
     *
     * @return array
     */
    public function getAnalyticsItem()
    {
        return [
            'Yandex',
            'Google',
            'MailRu',
            'Rambler',
            'Mediascope',
        ];
    }

    /**
     * Удаление элементов
     *
     * @param $id
     *
     * @throws \Exception
     */
    public function deleteItem($id)
    {
        if (\is_array($id)) {
            foreach ($id as $value) {
                YaTurboFeedTable::delete($value);
                $this->deleteItemFiles($value);
            }
        } else {
            YaTurboFeedTable::delete($id);
            $this->deleteItemFiles($id);
        }

        $this->response = ['message' => self::setNote('Операция успешно выполнена', 'OK')];

        $this->sendResponse();
    }

    /**
     * Удалить папку с файлами при удалении элемента
     *
     * @param $id
     */
    public function deleteItemFiles($id):void
    {
        $dir = new Directory(Application::getDocumentRoot() . self::$workDir . $id);
        $dir->delete();
    }

    /**
     * Отправить rss файлы в Яндекс
     *
     * @param $id
     *
     * @throws ArgumentTypeException
     */
    public function exportItem($id)
    {
        AgentTask::build()
            ->setClass(FeedExport::class)
            ->setCallChain(
                ['execute' => [$id]]
            )
            ->setModule('varrcan.yaturbo')
            ->setExecutionTime(DateTime::createFromTimestamp(time() + 5))
            ->create();

        YaTurboFeedTable::update($id, ['status' => 'Отправка в Яндекс']);
        $this->response = ['message' => self::setNote('Запущена отправка данных в Яндекс', 'OK')];

        $this->sendResponse();
    }

    /**
     * Сгенерировать rss вручную
     *
     * @param $id
     *
     * @throws ArgumentTypeException
     */
    public function generateRss($id)
    {
        AgentTask::build()
            ->setClass(FeedManual::class)
            ->setCallChain(
                ['execute' => [$id]]
            )
            ->setModule('varrcan.yaturbo')
            ->setExecutionTime(DateTime::createFromTimestamp(time() + 5))
            ->create();

        YaTurboFeedTable::update($id, ['status' => 'Формирование RSS-канала']);
        $this->response = ['message' => self::setNote('Генерация RSS успешно запущена', 'OK')];

        $this->sendResponse();
    }

    /**
     * Установка агента
     *
     * @param $params
     *
     * @throws ArgumentTypeException
     */
    public function setAgent($params)
    {
        if (!\is_array($params) && !$params['id'] && !$params['type']) {
            throw new InvalidArgumentException('Could not get agent parameters');
        }

        if ($params['type'] === 'agent') {
            $userId = CurrentUser::get()->getId();
            $config = (new Config())->getConfig();

            $interval = $config['agent'] ? (int)$config['agent'] * 60 : 86400;

            AgentTask::build()
                ->setClass(FeedAgent::class)
                ->setCallChain(
                    ['execute' => [$params['id']]]
                )
                ->setModule('varrcan.yaturbo')
                ->setExecutionTime(DateTime::createFromTimestamp(time() + 5))
                ->setPeriodically(true)
                ->setInterval($interval)
                ->create();

            $this->response = ['message' => self::setNote('Агент установлен', 'OK')];
        } else {
            $agentName = AgentHelper::createName('Varrcan\Yaturbo\Actions\FeedAgent', [], ['execute' => [$params['id']]]);

            \CAgent::RemoveAgent(
                $agentName,
                'varrcan.yaturbo'
            );
            $this->response = ['message' => self::setNote('Агент удален', 'OK')];
        }

        YaTurboFeedTable::update($params['id'], ['type' => $params['type']]);

        $this->sendResponse();
    }

    /**
     * Генерация уведомления
     *
     * @param $message
     * @param $type "ERROR"|"OK"|"PROGRESS"
     *
     * @return string
     */
    public static function setNote($message, $type = 'OK'):string
    {
        if (\is_array($message)) {
            $message = \implode('<br>', $message);
        }

        return (new \CAdminMessage(['MESSAGE' => $message, 'TYPE' => $type]))->Show();
    }

    /**
     * Отправка json ответа
     *
     * @param null $response
     */
    public function sendResponse($response = null)
    {
        header('Content-Type: application/json');
        die(\GuzzleHttp\json_encode($response ?? $this->response));
    }
}
