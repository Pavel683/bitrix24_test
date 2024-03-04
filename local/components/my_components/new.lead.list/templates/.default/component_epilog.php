<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
global $APPLICATION;

if ($_GET){
    foreach ($_GET as $action=>$value){

        if ($action == 'accept'){
            $lead_accept_val = 1;
        }else{
            $lead_accept_val = 0;
        }


        if (\Bitrix\Main\Loader::includeModule('crm'))
        {
            $entity = new CCrmLead(true);//true - проверять права на доступ
            $fields = array(
                'UF_CRM_LEAD_ACCEPT' => $lead_accept_val
            );
            $entity->update($value, $fields);
        }
    }
    LocalRedirect($APPLICATION->GetCurPageParam("", array($action)));
}
