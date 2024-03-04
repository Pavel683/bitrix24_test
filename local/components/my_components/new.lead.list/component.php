<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$leadOrder = array("ID" => "DESC");

$leadParams = array(
        'TITLE', 'DATE_CREATE', 'ASSIGNED_BY_ID', 'STATUS_ID', 'UF_CRM_LEAD_ACCEPT',
);

$obRes = CCrmLead::GetListEx($leadOrder, array(), false, false, $leadParams);

$leadList = array();
while($arLead = $obRes->GetNext())
{
//    echo "<pre>";print_r($arLead);echo"</pre>";die();
    $rsUser = CUser::GetByID($arLead['ASSIGNED_BY_ID'])->Fetch();
    $arLead['ASSIGNED_BY_ID'] = $rsUser['NAME'] . '(' .$rsUser['LOGIN']. ')';
//    echo "<pre>";print_r($rsUser);echo"</pre>";//
    $leadList[] = $arLead;
}

$arResult['LEADS'] = $leadList;
$this->IncludeComponentTemplate();
?>