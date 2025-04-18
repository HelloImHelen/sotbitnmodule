<?php

namespace Sotbit\Multibasket\Models;

use Bitrix\Bizproc\Activity\Condition;
use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Context;
use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;
use Bitrix\Sale\Fuser;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Engine\CurrentUser;
use Sotbit\Multibasket\Entity\EO_MBasket;
use Sotbit\Multibasket\Models\MBasketItem;
use Sotbit\Multibasket\Entity\MBasketTable;
use Sotbit\Multibasket\Entity\MBasketItemTable;
use Sotbit\Multibasket\Entity\MBasketItemPropsTable;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Order;
use Sotbit\Multibasket\DTO\BasketItemDTO;
use Sotbit\Multibasket\DTO\CurrentBasketDTO;
use Sotbit\Multibasket\DTO\ViewSettingsDTO;

class MBasket extends EO_MBasket
{
    /** @var Fuser $fuser */
    protected $fuser;

    /** @var MBasketItemTable $MBasketItemTable */
    protected $mBasketItemTable;

    /** @var MBasketItemTable $MBasketTable */
    protected $mBasketTable;

    /** @var Context $contex */
    protected $context;

    /** @var  MBasketItemPropsTable */
    protected $mBasketItemPropsTable;

    public static function getCurrent(
        Fuser $fuser,
        MBasketTable $mBasketTable,
        MBasketItemTable $mBasketItemTable,
        MBasketItemPropsTable $mBasketItemPropsTable,
        Context $context
    ): self
    {
        /** @var MBasket */
        $basketQuery = $mBasketTable::query()
            ->addSelect('*')
            ->addSelect('ITEMS')
            ->addSelect('ITEMS.PROPS')
            ->where('FUSER_ID', $fuser->getId())
            ->where('LID', $context->getSite())
            ->where('CURRENT_BASKET', true);

        $basket = $basketQuery->fetchObject();

        if(!$basket) {
            MBasketCollection::getObject($fuser, $mBasketTable, $context);
            $basket = $basketQuery->fetchObject();
        }

        $basket->fuser = $fuser;
        $basket->mBasketTable = $mBasketTable;
        $basket->mBasketItemTable = $mBasketItemTable;
        $basket->mBasketItemPropsTable = $mBasketItemPropsTable;
        $basket->context = $context;

        return $basket;
    }

    public static function getById(int $id, MBasketTable $mBasketTable, ?Fuser $fuser=null, ?Context $context=null): self
    {
        /** @var MBasket */
        $mBasket = $mBasketTable::query()
            ->addSelect('*')
            ->addSelect('ITEMS')
            ->addSelect('ITEMS.PROPS')
            ->where('ID', $id)
            ->fetchObject();

        $mBasket->fuser = $fuser;
        $mBasket->context = $context;
        $mBasket->mBasketTable = $mBasketTable;

        return $mBasket;
    }

    /** @param  BasketItem[] $basketItems */
    public function addItem(array $basketItems): void
    {

        foreach ($basketItems as $item) {
            /** @var MBasketItem */
            $mBasketItem = $this->mBasketItemTable::createObject();
            $mBasketItem->mapingFromBasketItem($item, $this->getId());
            $this->setProps($item, $mBasketItem);
            $this->getItems()->add($mBasketItem);
        }
        $this->setDateRefresh(DateTime::createFromTimestamp(time()));
        $this->save();
    }

    /** @param  BasketItem[] $basketItems */
    public function changeItems(array $basketItems): void
    {
        foreach ($basketItems as $item) {
            /** @var MBasketItem|null */
            $mutableItem = array_reduce(
                $this->getItems()->getAll(),
                function(?MBasketItem $carry, MBasketItem $i) use ($item) {
                    return $i->getBasketId() === $item->getId() ? $i : $carry;
                },
                null,
            );

            if (empty($mutableItem)) {
                continue;
            }

            $mutableItem->mapingFromBasketItem($item, $this->getId());
            $this->setProps($item, $mutableItem);
        }

        $this->setDateRefresh(DateTime::createFromTimestamp(time()));
        $this->save();
    }

    public function removeItems(Basket $basket): void
    {

        $removable = array_filter(
            $this->getItems()->getAll(),
            function (MBasketItem $i) use ($basket) {
               $item = $basket->getItemById($i->getBasketId());
               return empty($item) ?: false;
            }
        );

        foreach ($removable as $item) {
            foreach ($item->getProps() as $prop) {
                $prop->delete();
            }
            $item->delete();
        }
    }

    public static function getFakeBasket(): CurrentBasketDTO
    {
        return new CurrentBasketDTO([
            'ITEMS_QUANTITY' => 0,
            'TOTAL_PRICE' => 0,
            'TOTAL_WEIGHT' => 0,
            'ITEMS' => [],
        ]);
    }

    public function getResponse(ViewSettingsDTO $viewSettings): CurrentBasketDTO
    {
        $ITEMS_QUANTITY = count($this->getItems());
        $TOTAL_WEIGHT = array_sum($this->getItems()->getWeightList());
        $TOTAL_PRICE = 0;
        $CURRENCY = '';
        $ITEMS = [];

        if ($ITEMS_QUANTITY === 0) {
            return new CurrentBasketDTO(
                compact('ITEMS_QUANTITY', 'TOTAL_WEIGHT', 'TOTAL_PRICE', 'CURRENCY', 'ITEMS'),
            );
        }

        $condition = $viewSettings->SHOW_PRODUCTS && $viewSettings->SHOW_SUMMARY
            || $viewSettings->SHOW_PRODUCTS && $viewSettings->SHOW_PRICE
            || $viewSettings->SHOW_TOTAL_PRICE;

        if ($condition) {
            $discontResult = $this->getDiscount();
            $itemsData = $discontResult->getData()['BASKET_ITEMS'];
            $this->checkDataConsistency($itemsData ?? [], $ITEMS_QUANTITY);

            foreach ($this->getItems() as $item) {
                $item->setDiscont(
                    isset($itemsData) ? $itemsData[$item->getBasketId()]['PRICE'] : 0,
                    isset($itemsData) ? $itemsData[$item->getBasketId()]['DISCOUNT_PRICE'] : 0,
                );
            }

            $priceList = array_map(
                function (MBasketItem $i) {return $i->getFinalPrice();},
                $this->getItems()->getAll(),
            );

            $TOTAL_PRICE = isset($discontResult->getData()['CURRENCY'])
                ? \CCurrencyLang::CurrencyFormat(array_sum($priceList), $discontResult->getData()['CURRENCY'])
                : array_sum($priceList);
            $orderCurrency = isset($discontResult->getData()['CURRENCY'])
                ? $discontResult->getData()['CURRENCY']
                : '';
            $CURRENCY = $this->getFormatCurrencie($orderCurrency);
        }

        if ($viewSettings->SHOW_PRODUCTS) {
            $ITEMS = array_map(
                function (MBasketItem $i) {return $i->getResponse();},
                $this->getItems()->getAll(),
            );
        }

        if ($viewSettings->SHOW_IMAGE && $viewSettings->SHOW_PRODUCTS) {
            $pictureIdList = $this->getPictureId($this->getItems()->getProductIdList());
            $picturePathList = $this->getPicturePath($pictureIdList);
            $items = [];
            foreach ($ITEMS as $item) {
                $newItem = $item->toArray();
                $newItem['PICTURE'] = $picturePathList[$item->PRODUCT_ID];
                $items[] = new BasketItemDTO($newItem);
            }
            $ITEMS = $items;
        }

        $curentBasket = new CurrentBasketDTO(
            compact('ITEMS_QUANTITY', 'TOTAL_WEIGHT', 'TOTAL_PRICE', 'CURRENCY', 'ITEMS'),
        );

        return $curentBasket;
    }

    public function getItemByBasketId(int $baksetId): ?MBasketItem
    {
        foreach ($this->getItems() as $item) {
            if ($item->getBasketId() === $baksetId) {
                return $item;
            }
        }
    }

    public function getItemByProductId(int $productId): ?MBasketItem
    {
        foreach ($this->getItems() as $item) {
            if ($item->getProductId() === $productId) {
                return $item;
            }
        }
    }

    public function combineSameProducts(): void
    {
        $sameProducts = [];

        foreach ($this->getItems() as $item) {
            $sameProducts[$item->getProductId()][] = $item->getId();
        }

        $sameProducts = array_filter($sameProducts, function($i) {
            return count($i) > 1;
        });

        foreach ($sameProducts as $prods) {
            $sum = array_reduce($prods, function($curry, $i) {
                $curry += $this->getItems()->getByPrimary($i)->getQuantity();
                return $curry;
            });
            $firesProdId = array_shift($prods);

            foreach ($prods as $id) {
                $this->getItems()->getByPrimary($id)->delete();
                $this->getItems()->removeByPrimary($id);
            }

            $this->getItems()->getByPrimary($firesProdId)->setQuantity($sum);
        }
    }

    protected function getDiscount(): \Bitrix\Sale\Result
    {
        $basket = Basket::loadItemsForFUser($this->fuser->getId(), $this->context->getSite());
        $order = Order::create($this->context->getSite(), CurrentUser::get()->getId());
        $order->appendBasket($basket);
        $discount = $order->getDiscount();
        return $discount->calculate();
    }

    protected function setProps(BasketItem $basketItem, MBasketItem &$mbasketItem): void
    {
        $props = $mbasketItem->getProps();
        if (isset($props) && count($props) > 0) {
            $props = $basketItem->getPropertyCollection()->getPropertyValues();
            foreach ($mbasketItem->getProps() as $oldProp) {
                foreach ($props[$oldProp->getCode()] as $key => $value) {
                    if ($key === 'ID') {
                        continue;
                    }
                    $oldProp->set($key, $value);
                }
            }
            return;
        }

        $props = $basketItem->getPropertyCollection()->toArray();
        foreach ($props as $prop) {
            unset($prop['ID'], $prop['BASKET_ID']);
            $newProp = $this->mBasketItemPropsTable::createObject();
            foreach ($prop as $key => $value) {
                $newProp->set($key, $value);
            }
            $mbasketItem->addToProps($newProp);
        }
    }

    protected function getFormatCurrencie(string $currentCurrency): string
	{
		if (Loader::includeModule('currency'))
		{
            $formatCurrentcy = \CCurrencyLang::GetFormatDescription(
                $currentCurrency
            )["TEMPLATE"]["PARTS"][1];
            return isset($formatCurrentcy) ? $formatCurrentcy : $currentCurrency;
		}
        return $currentCurrency;
	}

    protected function getPictureId(array $product_id): array
    {
        $result = [];

        if (count($product_id) === 0) {
            return [];
        }

        $iblockElements = ElementTable::query()
            ->addSelect('PREVIEW_PICTURE')
            ->addSelect('DETAIL_PICTURE')
            ->addSelect('IBLOCK_ID')
            ->addSelect('ID')
            ->whereIn('ID', $product_id)
            ->fetchAll();

        foreach ($iblockElements as $key => $element) {
            if (isset($element['PREVIEW_PICTURE']) || isset($element['DETAIL_PICTURE'])) {
                $result[$element['ID']] = isset($element['PREVIEW_PICTURE'])
                    ? $element['PREVIEW_PICTURE']
                    : $element['DETAIL_PICTURE'];
                unset($iblockElements[$key]);
            }
        }

        if (count($iblockElements) === 0) {
            return $result;
        }

        $itemsWithOffers = CatalogIblockTable::query()
            ->addSelect('IBLOCK_ID')
            ->addSelect('PRODUCT_IBLOCK_ID')
            ->addSelect('SKU_PROPERTY_ID')
            ->whereIn('IBLOCK_ID' , array_column($iblockElements, 'IBLOCK_ID'))
            ->fetchAll();

        array_walk($iblockElements, function (&$value) use ($itemsWithOffers) {
            foreach ($itemsWithOffers as $item) {
                if ($item['IBLOCK_ID'] === $value['IBLOCK_ID']) {
                    $value = array_merge($value, $item);
                }
            }
        });

        $offerID_productID = ElementPropertyTable::query()
            ->addSelect('IBLOCK_ELEMENT_ID')
            ->addSelect('VALUE')
            ->whereIn('IBLOCK_PROPERTY_ID', array_column($iblockElements, 'SKU_PROPERTY_ID'))
            ->whereIn('IBLOCK_ELEMENT_ID', array_column($iblockElements, 'ID'))
            ->fetchAll();

        array_walk($iblockElements, function (&$value) use ($offerID_productID) {
            foreach ($offerID_productID as $item) {
                if ($item['IBLOCK_ELEMENT_ID'] === $value['ID']) {
                    $value = array_merge($value, $item);
                }
            }
        });

        $ar = array_column($iblockElements, 'VALUE');
        $iblockOffersElements = ElementTable::query()
            ->addSelect('PREVIEW_PICTURE')
            ->addSelect('DETAIL_PICTURE')
            ->addSelect('ID')
            ->whereIn('ID', $ar)
            ->fetchAll();

        array_walk($iblockElements, function (&$value) use ($iblockOffersElements) {
            foreach ($iblockOffersElements as $item) {
                if ($value['VALUE'] === $item['ID']) {
                    $value['PREVIEW_PICTURE'] = $item['PREVIEW_PICTURE'];
                    $value['DETAIL_PICTURE'] = $item['DETAIL_PICTURE'];
                }
            }
        });

        foreach ($iblockElements as $key => $element) {
            if (isset($element['PREVIEW_PICTURE']) || isset($element['DETAIL_PICTURE'])) {
                $result[$element['ID']] = isset($element['PREVIEW_PICTURE'])
                    ? $element['PREVIEW_PICTURE']
                    : $element['DETAIL_PICTURE'];
                unset($iblockElements[$key]);
            }
        }

        return $result;
    }

    protected function getPicturePath(array $pictureId): array
    {
        $image_path_unprepared = FileTable::query()
            ->setSelect(['FILE_NAME', 'SUBDIR', 'ID'])
            ->whereIn('ID', array_values($pictureId))
            ->fetchAll();

        $result = [];
        foreach ($image_path_unprepared as $i) {
            $id = array_search($i['ID'], $pictureId);
            $result[$id] = "/upload/{$i['SUBDIR']}/{$i['FILE_NAME']}";
        }

        return $result;
    }

    private function checkDataConsistency(?array $orderData, int &$ITEMS_QUANTITY): void
    {
        $itemsIdFromMBasket = $this->getItems()->getBasketIdList();
        $itemsIdFromBasket = array_keys($orderData);
        $arrDiff = array_diff($itemsIdFromMBasket, $itemsIdFromBasket);

        if (count($arrDiff) === 0) {
            return;
        }

        $ITEMS_QUANTITY -= count($arrDiff);

        $mbasketItem = array_filter($this->getItems()->getAll(), function (MBasketItem $i) use($arrDiff) {
            return in_array($i->getBasketId(), $arrDiff);
        });


        foreach ($mbasketItem as $item) {
            $this->getItems()->removeByPrimary($item->getId());
            foreach ($item->getProps()->getAll() as $prop) {
                $prop->delete();
            }
            $item->delete();
        }
    }
}