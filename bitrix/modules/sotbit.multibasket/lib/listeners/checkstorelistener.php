<?php

namespace Sotbit\Multibasket\Listeners;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;
use Sotbit\Multibasket\DeletedFuser;
use Sotbit\Multibasket\Entity\MBasketItemPropsTable;
use Sotbit\Multibasket\Entity\MBasketTable;
use Sotbit\Multibasket\Entity\MBasketItemTable;
use Sotbit\Multibasket\Models\MBasket;
use Sotbit\Multibasket\Models\MBasketCollection;
use Sotbit\Multibasket\Notifications\BasketChangeNotifications;
use Sotbit\Multibasket\Notifications\RecolorBasket;
use Sotbit\Multibasket\Helpers\Config;

class CheckStoreListener
{

    static $amountCache = [];

    /**
     * event handler OnSaleBasketItemRefreshData to check if the item being added
     *
     * @param Event $event
     */
    public static function checkAddedItems(Event $event)
    {
        $context = Context::getCurrent();

        if (!Config::moduleIsEnabled($context->getSite())) {
            return;
        }

        if (Config::getWorkMode($context->getSite()) === 'default') {
            return;
        }

        if (MBasketCollection::ignorEvent()) {
            return;
        }

        /** @var BasketItem */
        $basketItem = $event->getParameter('ENTITY');

        if ($basketItem->getProvider() === '\Bitrix\Sale\ProviderAccountPay') {
            return;
        }

        if (!$basketItem->getField('PRODUCT_ID')) {
            return;
        }

        $fuser = new Fuser;
        $mBasketTable = new MBasketTable;
        $mBasketItemTable = new MBasketItemTable;
        $mBasketItemPropsTable = new MBasketItemPropsTable;
        $mBasket = MBasket::getCurrent(
            $fuser,
            $mBasketTable,
            $mBasketItemTable,
            $mBasketItemPropsTable,
            $context
        );

        return self::checkProductsStore($basketItem, $mBasket->getStoreId());
    }

    private static function checkProductsStore(BasketItem $basketItem, int $storeId)
    {
        if (!$basketItem->canBuy()) {
            return;
        }

        $productId = (int)$basketItem->getField('PRODUCT_ID');
        if (!$productId) {
            return;
        }

        $storeAmount = self::getStoreAmount($productId, $storeId);

        if ((int)$storeAmount === 0) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR,
                new \Bitrix\Sale\ResultError(Loc::getMessage('SOTBIT_MULTIBASKET_ERROR_CHECK_QUANTITY')));
        }

        return true;
    }

    private static function getStoreAmount(int $productId, int $storeId)
    {
        if (self::$amountCache[$productId]) {
            return self::$amountCache[$productId];
        } else {
            $storeProduct = \Bitrix\Catalog\StoreProductTable::getList(array(
                'filter' => ['=PRODUCT_ID' => $productId, '=STORE.ID' => $storeId],
                'select' => ['AMOUNT']
            ))->fetch();

            $storeAmount = $storeProduct !== false ? $storeProduct['AMOUNT'] : 0;
            self::setHitAmountCache($productId, $storeAmount);
            return $storeAmount;
        }
    }

    private static function setHitAmountCache(int $productId, $amount)
    {
        self::$amountCache[$productId] = $amount;
    }

    /**
     * event handler OnSaleBasketBeforeSaved to check for updated items
     *
     * @param Event $event
     */
    public static function checkUpdateItems(Event $event)
    {
        //return;
        $context = Context::getCurrent();

        if (!Config::moduleIsEnabled($context->getSite())) {
            return;
        }

        if (Config::getWorkMode($context->getSite()) === 'default') {
            return;
        }

        if (MBasketCollection::ignorEvent()) {
            return;
        }

        /** @var Basket */
        $basket = $event->getParameter('ENTITY');

        $fuser = new Fuser;
        $mBasketTable = new MBasketTable;
        $mBasketItemTable = new MBasketItemTable;
        $mBasketItemPropsTable = new MBasketItemPropsTable;
        $mBasket = MBasket::getCurrent(
            $fuser,
            $mBasketTable,
            $mBasketItemTable,
            $mBasketItemPropsTable,
            $context
        );
        $errorList = [];

        foreach ($basket->getBasketItems() as $basketItem) {
            $keyCode = $basketItem->getBasketCode();
            if (gettype($keyCode) === 'string') {
                continue;
            } elseif ($basketItem->isChanged()) {
                $amount = self::getStoreAmount((int)$basketItem->getField('PRODUCT_ID'), $mBasket->getStoreId());
                if ((int)$amount === 0) {
                    $basketItem->setField('QUANTITY', 0);
                    $basketItem->setField('CAN_BUY', "N");
                    $basketItem->save();
                } elseif ($amount < $basketItem->getQuantity()) {
                    $basketItem->setField('QUANTITY', $amount);
                    $basketItem->save();
                    $errorList[] = Loc::getMessage('SOTBIT_MULTIBASKET_CHANGE_QUANTITY',
                        ['#PRODUCT#' => $basketItem->getField('NAME')]);
                }
            }
        }

        if (!empty($errorList)) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR,
                new \Bitrix\Sale\ResultError(implode("\n", $errorList), ['QUANTITY' => $amount]));
        }

    }

    private static function getHitAmountCache(int $productId)
    {
        return self::$amountCache[$productId] ?: null;
    }
}