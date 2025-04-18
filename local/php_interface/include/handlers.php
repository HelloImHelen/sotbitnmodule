<?php
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Event;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;
use Bitrix\Main\Loader,
    Bitrix\Main\EventManager,
    App\Handlers;

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->registerEventHandlerCompatible("sale", "OnSaleBasketBeforeSaved", "CheckBasketItems", "OnBeforeBasketAddHandler");

class CheckBasketItems
{
    /**
     * Событие проверки остатков на складах перед cохранением БД в корзине
     */
    public static function OnBeforeBasketAddHandler(Event $event)
    {
        /** @var Basket */
        $basket = $event->getParameter('ENTITY');

        if ($basket->getId() > 0) {
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
        }
        try {
            $productId = $basket->getProductId();
            $quantity = $basket->getQuantity();
            $fUserId = $basket->getFUserId();
            $currentBasket = Sale\Basket::loadItemsForFUser($fUserId, $basket->getSiteId());
            $basketStoreId = getCurrentBasketStoreId($currentBasket);
            $currentStoreQuantity = getProductQuantityOnStore($productId, $basketStoreId);
            if ($currentStoreQuantity >= $quantity) {
                return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
            }

            $defaultStoreId = getDefaultStoreForProduct($productId);
            if ($defaultStoreId <= 0) {
                return new \Bitrix\Main\EventResult(
                    \Bitrix\Main\EventResult::ERROR,
                    new Sale\ResultError(
                        Loc::getMessage('PRODUCT_NOT_AVAILABLE'),
                        'PRODUCT_NOT_AVAILABLE'
                    )
                );
            }
            $defaultStoreQuantity = getProductQuantityOnStore($productId, $defaultStoreId);

            if ($defaultStoreQuantity < $quantity) {
                return new \Bitrix\Main\EventResult(
                    \Bitrix\Main\EventResult::ERROR,
                    new Sale\ResultError(
                        Loc::getMessage('PRODUCT_NOT_AVAILABLE'),
                        'PRODUCT_NOT_AVAILABLE'
                    )
                );
            }

        } catch (\Exception $e) {
            return new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::ERROR,
                new Sale\ResultError(
                    $e->getMessage(),
                    'EXCEPTION'
                )
            );
        }
        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS);
    }


    /**
     * Получение ID склада текущей корзины
     *
     * @param \Bitrix\Sale\Basket $basket
     * @return int ID склада
     */
    function getCurrentBasketStoreId($basket)
    {
        $order = $basket->getOrder();
        if ($order) {
            $propertyCollection = $order->getPropertyCollection();
            $storeProp = $propertyCollection->getItemByOrderPropertyCode('STORE_ID');
            if ($storeProp) {
                return (int)$storeProp->getValue();
            }
        }
        return 0;
    }

    /**
     * Получение количества товара на указанном складе
     *
     * @param int $productId ID товара
     * @param int $storeId ID склада
     * @return float Количество товара на складе
     */
    function getProductQuantityOnStore($productId, $storeId)
    {
        if ($storeId <= 0) {
            return 0;
        }

        $result = 0;
        $rsStoreProduct = Catalog\StoreProductTable::getList([
            'filter' => [
                'PRODUCT_ID' => $productId,
                'STORE_ID' => $storeId
            ],
            'select' => ['AMOUNT']
        ]);

        if ($storeProduct = $rsStoreProduct->fetch()) {
            $result = (float)$storeProduct['AMOUNT'];
        }

        return $result;
    }

    /**
     * Получение ID стандартного склада для товара
     *
     * @param int $productId ID товара
     * @return int ID стандартного склада
     */
    function getDefaultStoreForProduct($productId)
    {
        $defaultStoreId = 0;
        $rsProduct = \CIBlockElement::GetList(
            [],
            ['ID' => $productId],
            false,
            false,
            ['ID', 'IBLOCK_ID']
        );

        if ($product = $rsProduct->Fetch()) {
            $rsProps = \CIBlockElement::GetProperty(
                $product['IBLOCK_ID'],
                $productId,
                [],
                ['CODE' => 'STANDARD_STORE']
            );

            if ($prop = $rsProps->Fetch()) {
                $defaultStoreId = (int)$prop['VALUE'];
            }
        }

        return $defaultStoreId;
    }
}