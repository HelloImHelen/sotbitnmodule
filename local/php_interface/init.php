<?php

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Main\Context;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Sotbit\Multibasket\Models\MBasket;
use Sotbit\Multibasket\Entity\MBasketTable;
use Sotbit\Multibasket\Entity\MBasketItemTable;
use Sotbit\Multibasket\Entity\MBasketItemPropsTable;
use Sotbit\Multibasket\Models\MBasketCollection;

Loc::loadMessages(__FILE__);

Loader::includeModule('catalog');
Loader::includeModule('sale');
Loader::includeModule('multibasket');
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/autoload.php');

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler('sale', 'OnSaleBasketBeforeSaved', function (&$fields) {
    if (!Loader::includeModule('multibasket')) {
        return true;
    }

    $productId = (int)$fields['PRODUCT_ID'];
    $quantity = (float)$fields['QUANTITY'];

    $context = Context::getCurrent();
    $siteId = $context->getSite();

    $fuser = new \Sotbit\Multibasket\Fuser();
    $mBasketTable = new MBasketTable();
    $mBasketItemTable = new MBasketItemTable();
    $mBasketItemPropsTable = new MBasketItemPropsTable();

    $mBasketCollection = MBasketCollection::getObject($fuser, $mBasketTable, $context);
    $currentMBasket = $mBasketCollection->getCurrentMBasket();
    $currentWarehouseId = $currentMBasket->getId();

    $storeProduct = StoreProductTable::getList([
        'filter' => [
            'PRODUCT_ID' => $productId,
            'STORE_ID' => $currentWarehouseId,
            '>AMOUNT' => 0
        ],
        'select' => ['AMOUNT']
    ])->fetch();

    if ($storeProduct && $storeProduct['AMOUNT'] >= $quantity) {
        return true;
    }

    $standardWarehouseId = 0;
    $product = \Bitrix\Iblock\ElementTable::getList([
        'filter' => ['ID' => $productId],
        'select' => ['ID', 'IBLOCK_ID']
    ])->fetch();

    if ($product) {
        $property = \CIBlockElement::GetProperty(
            $product['IBLOCK_ID'],
            $product['ID'],
            [],
            ['CODE' => 'STANDARD_STORE']
        )->Fetch();

        if ($property) {
            $standardWarehouseId = (int)$property['VALUE'];
        }
    }

    if ($standardWarehouseId > 0) {
        $storeProduct = StoreProductTable::getList([
            'filter' => [
                'PRODUCT_ID' => $productId,
                'STORE_ID' => $standardWarehouseId,
                '>AMOUNT' => 0
            ],
            'select' => ['AMOUNT']
        ])->fetch();

        if ($storeProduct && $storeProduct['AMOUNT'] >= $quantity) {
            $standardMBasket = null;
            foreach ($mBasketCollection->getAll() as $basket) {
                if ($basket->getStoreId() == $standardWarehouseId) {
                    $standardMBasket = $basket;
                    break;
                }
            }

            if (!$standardMBasket) {
                $standardMBasket = $mBasketCollection->createEmptyBasket(
                    false,
                    MBasketCollection::getNotWhiteColor($mBasketCollection->getColorList()),
                    Loc::getMessage('SOTBIT_MBASKET_STANDARD_WAREHOUSE_BASKET_NAME')
                );
                $standardMBasket->setStoreId($standardWarehouseId);
                $mBasketCollection->add($standardMBasket);
                $mBasketCollection->save();
            }


            $basketItem = $mBasketItemTable::createObject();
            $basketItem->setProductId($productId);
            $basketItem->setQuantity($quantity);
            $standardMBasket->addToItems($basketItem);
            $standardMBasket->save();

            $GLOBALS['APPLICATION']->ThrowException(
                Loc::getMessage('PRODUCT_ADDED_TO_STANDARD_WAREHOUSE_BASKET')
            );
            return false;
        }
    }

    $GLOBALS['APPLICATION']->ThrowException(
        Loc::getMessage('PRODUCT_NOT_AVAILABLE_ON_WAREHOUSES')
    );
    return false;
});