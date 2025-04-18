<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/update_client_partner.php');
$isDemo = [];
$masMod = [];
$modules = [];

if ($handle = @opendir($_SERVER["DOCUMENT_ROOT"] . US_SHARED_KERNEL_PATH . "/modules")) {
    while (false !== ($dir = readdir($handle))) {
        if (is_dir($_SERVER["DOCUMENT_ROOT"] . US_SHARED_KERNEL_PATH . "/modules/" . $dir)
            && $dir != "." && $dir != ".." && $dir != "main") {
            $module_dir = $_SERVER["DOCUMENT_ROOT"] . US_SHARED_KERNEL_PATH . "/modules/" . $dir;
            if (file_exists($module_dir . "/install/index.php")) {
                $arInfo = CUpdateClientPartner::__GetModuleInfo($module_dir);
                if (!empty($arInfo["VERSION"]) && !$arInfo["VERSION"] == '') {
                    if (!empty(preg_match("/^(sotbit.|sns.|shs.)/", $dir, $matches))) {
                        if ($arInfo["IS_DEMO"] === "Y") {
                            $dateTo = '';
                            if (!empty($GLOBALS["SiteExpireDate_" . str_replace(".", "_", $dir)])) {
                                $dateTo = ConvertTimeStamp($GLOBALS["SiteExpireDate_" . str_replace(".", "_", $dir)], "SHORT");
                            }
                            $isDemo[] = $dir;
                            $modules[$dir] = [
                                "IS_DEMO" => "Y",
                                "DEMO_DATE_TO" => $dateTo,
                                "IS_DEMO_END" => (!empty($dateTo) && strtotime($dateTo) >= strtotime(date("d.m.y"))) ? "N" : "Y"
                            ];
                        }
                        $masMod[] = $dir;
                    }
                }
            }
        }
    }
    closedir($handle);
}

$arUpdateList = CUpdateClientPartner::GetUpdatesList($errorMessage, LANG, 'Y', $masMod, array('fullmoduleinfo' => 'Y'));
foreach ($arUpdateList["MODULE"] as $val) {
    if (
        $val["@"]["FREE_MODULE"] === "D"
        && !empty($val["@"]["DATE_TO"])
        && (is_array($masMod) && in_array($val["@"]["ID"], $masMod))
        && $val["@"]["UPDATE_END"] === "Y") {
        if (!empty(preg_match("/^(sotbit.|sns.|shs.)/", $val["@"]["ID"], $matches))) {
            if (array_key_exists($val["@"]["ID"], $modules)) {
                $modules[$val["@"]['ID']] = array_merge($modules[$val["@"]['ID']], $val["@"]);
            } else {
                $modules[$val["@"]['ID']] = $val["@"];
            }
        }
    }
}

$ht = new CHTTP();
$arModules = [];
foreach ($modules as $key => $val) {
    if ($res = $ht->Get("https://marketplace.1c-bitrix.ru/search/?update_sys_new=Y&q=" . $key)) {
        if (in_array($ht->status, array("200"))) {
            $res = \Bitrix\Main\Text\Encoding::convertEncoding($res, "windows-1251", SITE_CHARSET);

            $objXML = new CDataXML();
            $objXML->LoadString($res);
            $arResult = $objXML->GetArray();
            if (!empty($arResult) && is_array($arResult) && !empty($arResult["modules"]["#"]["items"][0]["#"]["item"][0])) {
                foreach ($arResult["modules"]["#"]["items"][0]["#"]["item"] as $arModule) {
                    if (!empty(preg_match("/^(sotbit.|sns.|shs.)/", $arModule['#']['code'][0]['#'], $matches))) {
                        $modules[$key]['URL_TO_BUY'] = $arModule['#']['url2basket'][0]['#'];
                        $modules[$key]['LOGO'] = $arModule['#']['logo'][0]['#']['src'][0]['#'];
                        $modules[$key]['NAME'] = $arModule['#']['name'][0]['#'];
                        $modules[$key]['ACTIVE'] = true;
                    }
                }
            }
        }
    }
    if ($modules[$key]['ACTIVE'] != true) {
        unset($modules[$key]);
    }
}
