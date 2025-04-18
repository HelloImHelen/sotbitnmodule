<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$arDescription = array(
    "NAME" => GetMessage("GD_SOTBIT_CABINET_ACCOUNT_NAME"),
    "DESCRIPTION" => GetMessage("GD_SOTBIT_CABINET_ACCOUNT_DESC"),
    "GROUP" => array("ID" => "personal"),
    "NOPARAMS" => "Y",
    "COLOURFUL" => true,
    "TITLE_ICON_CLASS" => "infocenter-no-padding",
    "AI_ONLY" => true
);
