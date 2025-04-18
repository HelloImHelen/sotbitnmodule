<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Sotbit\Info\Helper\Tools;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Page\Asset;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
global $APPLICATION;

Asset::getInstance()->addJs("/bitrix/js/sotbit.info/script.js");
$id_module = 'sotbit.info';
$POST_RIGHT = $APPLICATION->GetGroupRight($id_module);
if ($POST_RIGHT < "R") {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

if (!Loader::includeModule($id_module)) {
    die();
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

$aTabs = [
    [
        "DIV" => "edit1",
        "TAB" => Loc::getMessage($id_module . '_MAIN_TAB'),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage($id_module . '_MAIN_TAB')
    ],

];

$APPLICATION->SetTitle(Loc::getMessage($id_module . '_TITLE'));
$data = Tools::getData();
?>
    <div class="info-main-aria" id="main-aria">
        <div id="loader">
            <div class="loader" id="loader-open"></div>
        </div>
        <iframe allowtransparency scrolling="no" id="main-iframe" onload="onLoad()" onerror="onError()"
                src="<?= Option::get("sotbit.info", 'MAIN_SITE_LINK') . "?" . $data ?>"></iframe>
    </div>
<?php
if (SotbitInfo::returnDemo() == 2) {
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?= Loc::getMessage($id_module . "_DEMO") ?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?php
}
if (SotbitInfo::returnDemo() == 3 || SotbitInfo::returnDemo() == 0) {
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?= Loc::getMessage($id_module . "_DEMO_END") ?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?php
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
    return '';
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
