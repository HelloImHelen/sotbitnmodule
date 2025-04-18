<?php

use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\EventManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;

class sotbit_info extends CModule
{
    const MODULE_ID = 'sotbit.info';

    var $MODULE_ID = 'sotbit.info';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;

    function __construct()
    {
        $arModuleVersion = [];
        include(dirname(__FILE__) . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("sotbit.info_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("sotbit.info_MODULE_DESC");

        $this->PARTNER_NAME = Loc::getMessage("sotbit.info_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("sotbit.info_PARTNER_URI");
        $this->MODULE_GROUP_RIGHTS = 'Y';
    }

    function InstallDB($arParams = [])
    {
        return true;
    }

    function UnInstallDB($arParams = [])
    {
        return true;
    }

    function InstallEvents()
    {
        EventManager::getInstance()->registerEventHandler('main', 'OnBuildGlobalMenu', self::MODULE_ID, '\Sotbit\Info\EventHandlers', 'onBuildGlobalMenuHandler');
        EventManager::getInstance()->registerEventHandler('main', 'OnEndBufferContent', self::MODULE_ID, '\Sotbit\Info\EventHandlers', 'insertModuleInfoHandler');
        Option::set(self::MODULE_ID, 'MAIN_SITE_LINK', 'https://www.sotbit.ru/infocenter/');
        return true;
    }

    function UnInstallEvents()
    {
        EventManager::getInstance()->unregisterEventHandler('main', 'OnBuildGlobalMenu', self::MODULE_ID, '\Sotbit\Info\EventHandlers', 'onBuildGlobalMenuHandler');
        EventManager::getInstance()->unregisterEventHandler('main', 'OnEndBufferContent', self::MODULE_ID, '\Sotbit\Info\EventHandlers', 'insertModuleInfoHandler');
        Option::delete(self::MODULE_ID, ['name' => 'MAIN_SITE_LINK']);
        return true;
    }

    function InstallFiles($arParams = [])
    {
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . self::MODULE_ID . "/install/themes/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/themes/", true, true);
        CopyDirFiles( $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/js', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js', true , true);
        CopyDirFiles( $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/gadgets', $_SERVER['DOCUMENT_ROOT'].'/bitrix/gadgets', true , true);
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
        DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . self::MODULE_ID . "/install/themes/.default", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/themes/.default");
        DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . self::MODULE_ID . "/install/themes/.default/icons/" . self::MODULE_ID, $_SERVER["DOCUMENT_ROOT"] . "/bitrix/themes/.default/icons/" . self::MODULE_ID);
        DeleteDirFiles( $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/js/' . self::MODULE_ID, $_SERVER['DOCUMENT_ROOT'].'/bitrix/js/' . self::MODULE_ID);
        DeleteDirFiles( $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/gadgets', $_SERVER['DOCUMENT_ROOT'].'/bitrix/gadgets');
        return true;
    }

    function DoInstall()
    {
        $this->InstallFiles();
        $this->InstallEvents();
        $this->InstallDB();
        RegisterModule(self::MODULE_ID);
    }

    function DoUninstall()
    {
        UnRegisterModule(self::MODULE_ID);
        $this->UnInstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();
    }
}
