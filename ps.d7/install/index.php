<?php

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Ps\D7\ORM\EntityTable;

Loc::loadMessages(__FILE__);

class ps_d7 extends CModule
{
    var $MODULE_ID = 'ps.d7';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;
    var $MODULE_CSS;

    public function ps_d7() {
        $version = [];
        include __DIR__ . '/version.php';
        if (is_array($version) && array_key_exists('VERSION', $version)) {
            $this->MODULE_VERSION = $version['VERSION'];
            $this->MODULE_VERSION_DATE = $version['VERSION_DATE'];
        }

        $this->MODULE_NAME = 'Генератор админки';
        $this->MODULE_DESCRIPTION = 'Модуль автоматически соберет страницу в админке для ваших таблиц D7';
        $this->PARTNER_NAME = 'Пантелеев Сергей';
        $this->PARTNER_URI = 'https://s-panteleev.ru';
    }

    public function DoInstall() {
        $this->InstallFiles();
        $this->InstallEvents();

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallDB();

        return true;
    }

    public function InstallFiles() {
        CopyDirFiles(__DIR__ . '/admin/', Application::getDocumentRoot() . '/bitrix/admin', true);
    }

    public function InstallEvents() {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler('main', 'OnBuildGlobalMenu', $this->MODULE_ID,
            '\\Ps\\D7\\Events\\UI', 'onGlobalMenu');
    }

    public function InstallDB() {
        Loader::includeSharewareModule($this->MODULE_ID);

        if (!Application::getConnection()->isTableExists('b_ps_d7_entities')) {
            EntityTable::getEntity()->createDbTable();
        }
    }

    public function DoUninstall() {
        $this->UnInstallFiles();
        $this->UnInstallEvents();
        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function UnInstallFiles() {
        DeleteDirFiles(__DIR__ . '/admin/', Application::getDocumentRoot() . '/bitrix/admin');
    }

    public function UnInstallEvents() {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler('main', 'OnBuildGlobalMenu', $this->MODULE_ID,
            '\\Ps\\D7\\Events\\UI', 'onGlobalMenu');
    }

    public function UnInstallDB() {
        if (Application::getConnection()->isTableExists('b_ps_d7_entities')) {
            Application::getConnection()->dropTable('b_ps_d7_entities');
        }
    }
}
