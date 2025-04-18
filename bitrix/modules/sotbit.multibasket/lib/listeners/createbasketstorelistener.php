<?php

namespace Sotbit\Multibasket\Listeners;


use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Sale\Fuser;
use Sotbit\Multibasket\Controllers\InstallController;
use Sotbit\Multibasket\Helpers\Config;
use Sotbit\Multibasket\Models\MBasketCollection;

Loader::includeModule('sale');

class CreateBasketStoreListener
{
    public static function handle()
    {
        $context = Context::getCurrent();

        if (!Config::moduleIsEnabled($context->getSite())) {
            return;
        }

        if (Config::getWorkMode($context->getSite()) === 'default') {
            return;
        }

        if (!empty(Fuser::getId(true))) {
            return;
        }

        if (MBasketCollection::ignorEvent()) {
            return;
        }

        $control = new InstallController();
        $control->createMBasketStoreForFuserAction(Fuser::getId(), $context->getSite());
    }

    public static function handleUpdateMbasket($id, &$arFields) {
        $config = Config::getConfig();
        $control = new InstallController();
        if (empty($arFields['SITE_ID'])){
            foreach($config as $site_id => $configSite) {
                $control->updateBasketByStoreIdAction($id, $site_id, $arFields);
            }
        } else {
            $control->updateBasketByStoreIdAction($id, $arFields['SITE_ID'], $arFields);
        }
    }
}