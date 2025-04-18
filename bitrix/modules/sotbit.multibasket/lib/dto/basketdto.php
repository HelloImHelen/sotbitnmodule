<?php

namespace Sotbit\Multibasket\DTO;

use Bitrix\Main\Type\Contract\Arrayable;
/**
 * @property null|int $ID
 * @property null|string $COLOR
 * @property null|bool $CURRENT_BASKET
 * @property null|string $NAME
 * @property null|bool $MAIN
 */

class BasketDTO implements Arrayable {

    use DtoTrait;

    const FILD_TYPES = [
        'ID' => 'integer',
        'COLOR' => 'string',
        'CURRENT_BASKET' => 'boolean',
        'MAIN' => 'boolean',
        'NAME' => 'string',
        'STORE_ID' => 'integer',
        'SORT' => 'integer'
    ];

    protected $ID;
    protected $COLOR;
    protected $CURRENT_BASKET;
    protected $MAIN;
    protected $NAME;
    protected $STORE_ID;
    protected $SORT;

    /**
     * @param (array)BasketDTO $requestData
     */
    public function __construct(array $requestData)
    {
        $this->construct($requestData, self::FILD_TYPES);
    }
}