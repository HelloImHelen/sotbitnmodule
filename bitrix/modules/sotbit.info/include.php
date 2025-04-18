<?php

use Bitrix\Main\Loader;

class SotbitInfo
{
    const MODULE_ID = 'sotbit.info';
    private static $demo = false;
    protected $success;


    private static function setDemo()
    {
        static::$demo = CModule::IncludeModuleEx(self::MODULE_ID);
    }

    public static function getDemo()
    {
        if (self::$demo === false)
            self::setDemo();
        return !(static::$demo == 0 || static::$demo == 3);
    }

    public static function returnDemo()
    {
        if (self::$demo === false)
            self::setDemo();
        return static::$demo;
    }
}
