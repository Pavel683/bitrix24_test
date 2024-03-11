<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
global $APPLICATION;

if ($_GET) {
    foreach ($_GET as $action=>$value) {

        if ($action == 'accept') {
            $leadAcceptVal = 1;
        }else{
            $leadAcceptVal = 0;
        }

        if (\Bitrix\Main\Loader::includeModule('crm')) {
            $entity = new CCrmLead(true);
            $fields = ['UF_CRM_LEAD_ACCEPT' => $leadAcceptVal];
            $entity->update($value, $fields);
        }
    }
    LocalRedirect($APPLICATION->GetCurPageParam("", array($action)));
}
