<?php

namespace Sotbit\Info\Helper;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Menu
{
    public static function getAdminMenu(&$arGlobalMenu, &$arModuleMenu)
    {
        $moduleInclude = Loader::includeModule('sotbit.info');
        global $APPLICATION;

        if ($APPLICATION->GetGroupRight(\SotbitInfo::MODULE_ID) != 'D') {

            if (!isset($arGlobalMenu['global_menu_sotbit'])) {
                $arGlobalMenu['global_menu_sotbit'] = [
                    'menu_id' => 'sotbit',
                    'text' => Loc::getMessage(\SotbitInfo::MODULE_ID . '_GLOBAL_MENU'),
                    'title' => Loc::getMessage(\SotbitInfo::MODULE_ID . '_GLOBAL_MENU'),
                    'sort' => 1000,
                    'items_id' => 'global_menu_sotbit_items',
                    "icon" => "",
                    "page_icon" => "",
                ];
            }

            if ($moduleInclude) {
                $menu = [
                    'section' => \SotbitInfo::MODULE_ID,
                    'sort' => 0,
                    'text' => Loc::getMessage(\SotbitInfo::MODULE_ID . '_MENU_TEXT'),
                    'title' => Loc::getMessage(\SotbitInfo::MODULE_ID . '_MENU_TITLE'),
                    'icon' => 'sotbit_info_menu_icon',
                    'page_icon' => 'sotbit_info_page_icon',
                    'items_id' => 'menu_sotbit.info',
                    'items' => [
                        'main' => [
                            'text' => Loc::getMessage(\SotbitInfo::MODULE_ID . '_MENU_SOTBIT_INFO_MAIN'),
                            'url' => 'sotbit.info_main_page.php?lang=' . LANGUAGE_ID,
                            'title' => Loc::getMessage(\SotbitInfo::MODULE_ID . '_MENU_SOTBIT_INFO_MAIN'),
                            'more_url' => [
                                'sotbit.info_main_page.php'
                            ],
                        ],
                    ]
                ];
            }
            $arGlobalMenu['global_menu_sotbit']['items']['sotbit.info'] = $menu;
        }
    }
}
