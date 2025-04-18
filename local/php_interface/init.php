<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
Loader::includeModule('sale');
Loader::includeModule('catalog');
Loader::includeModule('iblock');
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/autoload.php');

if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/handlers.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/handlers.php");
}