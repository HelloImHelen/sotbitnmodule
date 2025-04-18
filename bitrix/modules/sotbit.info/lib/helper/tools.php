<?php

namespace Sotbit\Info\Helper;

class Tools
{
    public static function getData($moduleID = "")
    {
        $arData = $arModuleInfo = [];

        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/update_client.php')
            && file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/update_client_partner.php')) {
            include_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/update_client.php');
            include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/general/update_client_partner.php');
            $key = $arData['CLIENT']['LICENSE_KEY'] = \CUpdateClient::GetLicenseKey();

            global $USER;
            if ($USER->IsAuthorized() && !empty($moduleID) && $info = \CModule::CreateModuleObject($moduleID)) {
                $moduleMass = \CUpdateClientPartner::GetUpdatesList($e, false, "Y", [$info->MODULE_ID, "sotbit.info"])["MODULE"];
                foreach ($moduleMass as $module) {
                    $actualVarsion = "";
                    if ($module['@']['ID'] == $info->MODULE_ID) {
                        foreach ($module['#']['VERSION'] as $version) {
                            if (version_compare($version['@']['ID'], $actualVarsion) > 0) {
                                $actualVarsion = $version['@']['ID'];
                            }
                        }
                        foreach ($moduleMass as $moduleInfo) {
                            if ($moduleInfo['@']['ID'] == "sotbit.info") {
                                $infoModule = \CModule::CreateModuleObject("sotbit.info");
                                $actualVarsionInfo = $infoModule->MODULE_VERSION;
                                foreach ($moduleInfo['#']['VERSION'] as $version) {
                                    if (version_compare($version['@']['ID'], $actualVarsionInfo) > 0) {
                                        include_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sotbit.info/include/update_js.php');
                                        break 2;
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
                $arData['CLIENT']['MODULE_ACTUAL_VERSION'] = $actualVarsion;

                if (\Bitrix\Main\Config\Option::get("main", "stable_versions_only") === "N") {
                    $moduleMass = \CUpdateClientPartner::GetUpdatesList($e, false, "N", [$info->MODULE_ID])["MODULE"];
                    foreach ($moduleMass as $module) {
                        $actualVarsionBeta = "";
                        if ($module['@']['ID'] == $info->MODULE_ID) {
                            foreach ($module['#']['VERSION'] as $version) {
                                if (version_compare($version['@']['ID'], $actualVarsionBeta) > 0) {
                                    $actualVarsionBeta = $version['@']['ID'];
                                }
                            }
                            break;
                        }
                    }

                    if (!empty($actualVarsionBeta) && $actualVarsionBeta > $actualVarsion) {
                        $arData['CLIENT']['MODULE_BETA_VERSION'] = true;
                        $arData['CLIENT']['MODULE_ACTUAL_VERSION'] = $actualVarsionBeta;
                    }
                }

                $arData['CLIENT']['MODULE_ID'] = $info->MODULE_ID;
                $arData['CLIENT']['MODULE_VERSION'] = $info->MODULE_VERSION;
                if (!empty($GLOBALS["SiteExpireDate_" . str_replace(".", "_", $info->MODULE_ID)])){
                    $arData['CLIENT']['MODULE_DEMO_DATE'] = ConvertTimeStamp($GLOBALS["SiteExpireDate_" . str_replace(".", "_", $info->MODULE_ID)], "SHORT");
                }
                $arData['CLIENT']['URL'] = $_SERVER['REQUEST_SCHEME']. "://" . $_SERVER['HTTP_HOST'];
            }
            $key = base64_encode($key);
        }

        $result = $arData ? 'data=' . self::dataSerialize($arData, $key) . '&key=' . urlencode($key) : false;

        return $result;
    }

    public static function dataSerialize($data, $signKey = '')
    {
        $strData = serialize(self::dataConvEncod($data));
        $tmp = base64_encode($strData);

        if (strlen($signKey)) {
            $signer = new \Bitrix\Main\Security\Sign\Signer;
            $signer->setKey(hash('sha512', $signKey));
            $tmp .= '.' . $signer->getSignature($strData);
        }

        return urlencode($tmp);
    }

    public static function decodeData($hash, $signKey = '')
    {
        $data = false;

        if (is_string($hash) && strlen($hash)) {
            $tmp = urldecode($hash);

            if (($dotPos = strpos($tmp, '.')) === strrpos($tmp, '.')) {
                if ($bSigned = ($dotPos !== false)) {
                    $signature = substr($tmp, $dotPos + 1);
                    $tmp = substr($tmp, 0, $dotPos);
                }
                $strData = base64_decode($tmp);

                if ($bSigned && strlen($signKey)) {
                    try {
                        $signer = new \Bitrix\Main\Security\Sign\Signer;
                        $signer->setKey(hash('sha512', $signKey));
                        if ($signer->validate($strData, $signature)) {
                            $data = self::dataConvDecod(@unserialize($strData));
                        }
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                } elseif (!strlen($signKey)) {
                    $data = self::dataConvDecod(@unserialize($strData));
                }
            }
        }

        return $data;
    }

    public static function dataConvEncod($arData)
    {
        if (is_array($arData)) {
            $arResult = [];
            foreach ($arData as $key => $value) {
                $arResult[iconv(LANG_CHARSET, 'UTF-8', $key)] = self::dataConvEncod($value);
            }
        } else {
            $arResult = iconv(LANG_CHARSET, 'UTF-8', $arData);
        }

        return $arResult;
    }

    public static function dataConvDecod($arData)
    {
        if (is_array($arData)) {
            $arResult = [];
            foreach ($arData as $key => $value) {
                $arResult[iconv('UTF-8', LANG_CHARSET, $key)] = self::dataConvDecod($value);
            }
        } else {
            $arResult = iconv('UTF-8', LANG_CHARSET, $arData);
        }

        return $arResult;
    }
}
