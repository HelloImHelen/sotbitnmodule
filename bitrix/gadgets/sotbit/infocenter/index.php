<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
global $APPLICATION;
require_once($_SERVER['DOCUMENT_ROOT'] . $arGadget['PATH_SITEROOT'] . '/result_modifier.php');
$APPLICATION->SetAdditionalCSS($arGadget['PATH_SITEROOT'] . '/styles.css');

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

?>
<div class="main-wrap">
    <div class="title-block-wrap">
        <div>
            <img src="<?= $arGadget['PATH_SITEROOT'] ?>/img/icon.png" width="31" height="24" alt="icon">
            <a href="/bitrix/admin/sotbit.info_main_page.php" title="<?= Loc::getMessage("GD_IC_SOTBIT_TITLE") ?>">
                <?= Loc::getMessage("GD_IC_SOTBIT_TITLE") ?>
            </a>
        </div>
    </div>
    <?php
    if (!empty($modules)) { ?>
        <div class="items-wrap">
            <?php foreach ($modules as $key => $val) { ?>
                <div class="solution-item">
                    <div>
                        <?php
                        if (!empty($val['LOGO'])) { ?>
                            <img src="<?= $val['LOGO'] ?>" alt="icon">
                        <?php } ?>
                        <div>
                            <a href="https://www.sotbit.ru/solutions/<?= $key ?>.html" target="_blank"
                               class="item-title" title="<?= $val['NAME'] ?>"><?= $val['NAME'] ?></a>
                            <?php if ($val['IS_DEMO'] === "Y") { ?>
                                <?php if ($val['IS_DEMO_END'] === "Y") { ?>
                                    <p>
                                        <span><?= Loc::getMessage("GD_IC_DEMO_THE_END") ?></span><?= Loc::getMessage("GD_IC_DEMO_NEED_TO_BUY") ?>
                                    </p>
                                <?php } else { ?>
                                    <p><?= Loc::getMessage("GD_IC_DEMO_NOT_END") ?><?= $val['DEMO_DATE_TO'] ?></p>
                                <?php } ?>
                            <?php } else { ?>
                                <p>
                                    <span><?= Loc::getMessage("GD_IC_VARNING") ?></span><?= Loc::getMessage("GD_IC_LICENSE_END") ?> <?= $val['DATE_TO'] ?>
                                </p>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="links-wrap">
                        <?php if ($val['IS_DEMO'] === "Y") { ?>
                            <a href="<?= $val['URL_TO_BUY'] ?>" target="_blank"
                               title="<?= Loc::getMessage("GD_IC_TITLE_BUY_BUT_MARKET") ?>">
                                <?= Loc::getMessage("GD_IC_BUY_LICENSE_BUT_MARKET") ?>
                            </a>
                            <a href="https://www.sotbit.ru/api/addbasket/?action=ADD2BASKET&code=<?= $key ?>&gobasket=Y"
                               target="_blank" title="<?= Loc::getMessage("GD_IC_TITLE_BUY_BUT_DEVELOP") ?>">
                                <?= Loc::getMessage("GD_IC_BUY_LICENSE_BUT_DEVELOP") ?>
                            </a>
                        <?php } else { ?>
                            <a href="<?= $val['URL_TO_BUY'] ?>&prolong_period=12" target="_blank"
                               title="<?= Loc::getMessage("GD_IC_TITLE_BUY_BUT_MARKET") ?>">
                                <?= Loc::getMessage("GD_IC_CONTINUE_LICENSE_BUT_MARKET") ?>
                            </a>
                            <a href="https://www.sotbit.ru/api/addbasket/?action=ADD2BASKET&addcontinue=standart&code=<?= $key ?>&gobasket=Y"
                               target="_blank" title="<?= Loc::getMessage("GD_IC_TITLE_BUY_BUT_DEVELOP") ?>">
                                <?= Loc::getMessage("GD_IC_CONTINUE_LICENSE_BUT_DEVELOP") ?>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="none-info">
            <img src="<?= $arGadget['PATH_SITEROOT'] ?>/img/module_icon.png" alt="module_icon">
            <p><?= Loc::getMessage("GD_IC_NONE_INFO") ?></p>
        </div>
    <?php } ?>
</div>
