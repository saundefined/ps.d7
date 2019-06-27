<?php

use Bitrix\Main\Application;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Ps\D7\ORM\EntityTable;
use Ps\D7\ORM\VersionTable;

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

        $this->MODULE_NAME = Loc::getMessage('PS_D7_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('PS_D7_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('PS_D7_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('PS_D7_PARTNER_URI');
    }

    public function DoInstall() {
        if (CheckVersion('17.0.11', ModuleManager::getVersion('main'))) {
            global $APPLICATION;
            $APPLICATION->ThrowException(Loc::getMessage('PS_D7_VERSION_ERROR'));
            return false;
        }

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
        $eventManager->registerEventHandler($this->MODULE_ID, 'onGetEntityList', $this->MODULE_ID,
            '\\Ps\\D7\\Events\\Entity', 'onGetEntityList');
    }

    public function InstallDB() {
        Loader::includeSharewareModule($this->MODULE_ID);

        if (!Application::getConnection()->isTableExists('b_ps_d7_entities')) {
            try {
                EntityTable::getEntity()->createDbTable();

                EntityTable::add([
                    'ENTITY' => 'Ps\\D7\\ORM\\EntityTable',
                    'NAME' => Loc::getMessage('PS_D7_ENTITIES'),
                    'SORT' => 100
                ]);
            } catch (Exception $e) {
                global $APPLICATION;
                $APPLICATION->ThrowException($e->getMessage());
                return false;
            }
        }

        if (!Application::getConnection()->isTableExists('b_ps_d7_versions')) {
            try {
                VersionTable::getEntity()->createDbTable();
            } catch (Exception $e) {
                global $APPLICATION;
                $APPLICATION->ThrowException($e->getMessage());
                return false;
            }
        }

        return true;
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
        $eventManager->unRegisterEventHandler($this->MODULE_ID, 'onGetEntityList', $this->MODULE_ID,
            '\\Ps\\D7\\Events\\Entity', 'onGetEntityList');
    }

    public function UnInstallDB() {
        if (Application::getConnection()->isTableExists('b_ps_d7_entities')) {
            try {
                Application::getConnection()->dropTable('b_ps_d7_entities');
            } catch (SqlQueryException $e) {
                global $APPLICATION;
                $APPLICATION->ThrowException($e->getMessage());
                return false;
            }
        }

        if (Application::getConnection()->isTableExists('b_ps_d7_versions')) {
            try {
                Application::getConnection()->dropTable('b_ps_d7_versions');
            } catch (SqlQueryException $e) {
                global $APPLICATION;
                $APPLICATION->ThrowException($e->getMessage());
                return false;
            }
        }

        return true;
    }
}
