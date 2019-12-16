<?php

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\IO\Directory as BxDirectory;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ModuleManager;
use Varrcan\Yaturbo\Orm\YaTurboFeedTable;

/**
 * Class varrcan_yaturbo
 */
Class varrcan_yaturbo extends CModule
{
    const MODULE_ID = 'varrcan.yaturbo';

    /**
     * varrcan_yaturbo constructor.
     */
    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_NAME         = 'Yandex.Turbo';
        $this->MODULE_DESCRIPTION  = 'Модуль генерации канала для Турбо-страниц Яндекса и загрузки rss файла через API';
        $this->MODULE_ID           = self::MODULE_ID;
        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME        = 'Varrcan';
        $this->PARTNER_URI         = 'https://varrcan.me/';
    }

    /**
     * Действия при установке модуля
     *
     * @return bool|void
     */
    public function doInstall()
    {
        try {
            global $USER;
            if ($USER->IsAdmin()) {
                ModuleManager::registerModule(self::MODULE_ID);

                if (Loader::includeModule(self::MODULE_ID)) {
                    $this->InstallFiles();
                    $this->InstallDB();
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Действия при удалении модуля
     */
    public function DoUninstall()
    {
        try {
            global $USER;
            if ($USER->IsAdmin()) {
                Loader::includeModule(self::MODULE_ID);
                $this->UnInstallFiles();
                $this->UnInstallDB();

                ModuleManager::unRegisterModule(self::MODULE_ID);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Копирование файлов
     */
    public function InstallFiles()
    {
        BxDirectory::createDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/' . self::MODULE_ID);
        BxDirectory::createDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/varrcan/');
        BxDirectory::createDirectory($_SERVER['DOCUMENT_ROOT'] . '/upload/ya_turbo/');

        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/',
            true,
            true
        );
        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/components/',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/varrcan/',
            true,
            true
        );
        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/js/',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/',
            true,
            true
        );
    }

    /**
     * Создание таблицы
     *
     * @return bool
     * @throws LoaderException
     */
    public function InstallDB()
    {
        if (Loader::includeModule(self::MODULE_ID)) {
            // Обходим грязный хак в ядре /bitrix/modules/main/lib/db/mysqlcommonconnection.php:173, который делает все поля обязательными.
            // Валидация полей будет происходить на уровне ORM
            try {
                $entity = YaTurboFeedTable::getEntity();
                $sql    = $entity->compileDbTableStructureDump();

                if (is_array($sql)) {
                    $sql        = str_replace(' NOT NULL', '', $sql[0]);
                    $connection = $entity->getConnection()->query($sql);
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        return true;
    }

    /**
     * Удаление файлов
     *
     * @return bool|void
     */
    public function UnInstallFiles()
    {
        DeleteDirFilesEx('/bitrix/admin/yandex_turbo_item.php');
        DeleteDirFilesEx('/bitrix/admin/yandex_turbo_settings.php');
        DeleteDirFilesEx('/bitrix/js/varrcan.yaturbo/');
        DeleteDirFilesEx('/bitrix/components/varrcan/');
        DeleteDirFilesEx('/upload/ya_turbo/');
    }

    /**
     * Удаление таблицы
     *
     * @return bool|void
     */
    public function UnInstallDB()
    {
        try {
            $tableName = YaTurboFeedTable::getTableName();
            if ($tableName) {
                Application::getConnection()->query('DROP TABLE `' . $tableName . '`');
            }
        } catch (Error $e) {
        } catch (SqlQueryException $e) {
        }
    }
}
