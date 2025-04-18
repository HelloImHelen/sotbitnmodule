<?php

namespace Sotbit\Info;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Sotbit\Info\Helper\Tools;
use Sotbit\Info\Helper\Menu;

class EventHandlers
{
    public static function onBuildGlobalMenuHandler(&$arGlobalMenu, &$arModuleMenu)
    {
        Menu::getAdminMenu($arGlobalMenu, $arModuleMenu);
    }

    public static function insertModuleInfoHandler(&$content)
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        if ($request->isAdminSection()) {
            preg_match('/([\w-]{1,32}\.[\w-]{1,32}\/)/', file_get_contents($_SERVER["SCRIPT_FILENAME"]), $matches);
            if(!empty($matches[0])){
                $matches = substr($matches[0], 0, -1);
            } else {
                $matches = '';
                if (preg_match("/(sotbit|shs|sns)/", $_SERVER['SCRIPT_NAME'], $matchesResult)) {
                    $dir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/';
                    $finde = basename($_SERVER['SCRIPT_NAME']);
                    foreach (glob($dir . '*') as $val) {
                        if (is_dir($val)) {
                            $findePath = self::globTreeFiles($val, $finde);
                            if (is_array($findePath) && $findePath["stop"] == "Y") {
                                $matches = basename($val);
                                break;
                            }
                        }
                    }
                }
            }

            if (!empty($matches)) {
                if (
                    Loader::includeModule(Option::get('sotbit.marketplace', 'REDACTION_CODE', 'sotbit.marketplace'))
                    && method_exists('\SotbitMarketplace', "getMarketModulesList")
                ) {
                    if (!empty(\SotbitMarketplace::getMarketModulesList()) && in_array($matches, \SotbitMarketplace::getMarketModulesList())) {
                        $matches = Option::get('sotbit.marketplace', 'REDACTION_CODE', 'sotbit.marketplace');
                    }
                }
                if (!empty(preg_match("/^(sotbit.|sns.|shs.)/", $matches, $matchesResult))) {
                    $data = Tools::getData($matches);
                }
                if ($matches !== "sotbit.info" && !empty($data)) {
                    $titleAria = strpos($content, '</h1>', strpos($content, '<h1 class="adm-title" id="adm-title">'));
                    if ($titleAria != false) {
                        $content = substr_replace($content, '</h1><div class="info-detail-aria">
                            <iframe id="ifarme-banner" allowtransparency scrolling="no" src="' . Option::get("sotbit.info", 'MAIN_SITE_LINK') . 'detail/?' . $data . '"></iframe></div>
                            <script>
                                window.addEventListener("message", function (event) {
                                    if(event.data.swipe === "Y"){
                                        let swipeItem = document.getElementById("ifarme-banner");
                                        swipeItem.style.height = String(event.data.height) + "px";
                                            }
                                    if(event.data.sizeChange === "Y"){
                                    let swipeItem = document.getElementById("ifarme-banner");
                                    if (swipeItem.offsetHeight > 100) {
                                        swipeItem.style.height = String(event.data.height) + "px";
                                            }
                                        }
                                }, false);
                            </script>
                        ', $titleAria, 5);
                    }
                }
            }
        }
    }
    public static function globTreeFiles($path, $finde)
    {
        foreach (glob($path . '/*') as $file) {
            if (is_dir($file) && (basename($file) == "admin" || basename($file) == "install")) {
                $res = self::globTreeFiles($file, $finde);
                if (is_array($res) && $res["stop"] == "Y") {
                    return $res;
                }
            } elseif (!empty(strpos($file, "/install/admin/" . $finde)) && is_file($file)) {
                $res['dir'] = $file;
                $res['stop'] = "Y";
                return $res;
            }
        }
        return $res;
    }
}
