<?php
namespace Loc;

use \Bitrix\Main\Loader,
    \Bitrix\Highloadblock as HL;
class ChangePriority
{
    protected $arFields = [
        "UF_USER",
        "UF_DATA_TIME",
        "UF_TASK_ID",
        "UF_PRIORITY",
    ];
    protected $HL_ID = 	2;

    function add($arField = array())
    {

        Loader::includeModule("highloadblock");

        $arHLBlock = HL\HighloadBlockTable::getById($this->HL_ID)->fetch();
        $obEntity = HL\HighloadBlockTable::compileEntity($arHLBlock);
        $strEntityDataClass = $obEntity->getDataClass();
        $arElementFields = $arField;

        $obResult = $strEntityDataClass::add($arElementFields);
        $ID = $obResult->getID();
        $bSuccess = $obResult->isSuccess();
        if ($bSuccess) {
            return $ID;
        } else {
            return $obResult->getErrorMessages()[0];
        }

    }

    function update($ID = 0, $arField = array())
    {
        Loader::includeModule("highloadblock");

        $arHLBlock = HL\HighloadBlockTable::getById($this->HL_ID)->fetch();
        $obEntity = HL\HighloadBlockTable::compileEntity($arHLBlock);
        $strEntityDataClass = $obEntity->getDataClass();

        $arElementFields = $arField;

        $obResult = $strEntityDataClass::update($ID, $arElementFields);
        $ID = $obResult->getID();
        $bSuccess = $obResult->isSuccess();
        if ($bSuccess) {
            return $ID;
        } else {
            return 0;
        }

    }

    function delete($ID = 0)
    {
        Loader::includeModule("highloadblock");

        $arHLBlock = HL\HighloadBlockTable::getById($this->HL_ID)->fetch();
        $obEntity = HL\HighloadBlockTable::compileEntity($arHLBlock);
        $strEntityDataClass = $obEntity->getDataClass();

        $obResult = $strEntityDataClass::delete($ID);
    }

    function get($arField = array()){

        Loader::includeModule("highloadblock");
        $arHLBlock = HL\HighloadBlockTable::getById($this->HL_ID)->fetch();
        $obEntity = HL\HighloadBlockTable::compileEntity($arHLBlock);
        $strEntityDataClass = $obEntity->getDataClass();
        $arElementFields = $arField;

        $rsData = $strEntityDataClass::getList(array(
            "select" => array("*"),
            "order" => array("ID" => "DESC"),
            "filter" => $arElementFields,
            "limit" => 10,
        ));

        if ($rsData->getSelectedRowsCount() == 0){
            return 0;
        }

        return $rsData;

    }
}