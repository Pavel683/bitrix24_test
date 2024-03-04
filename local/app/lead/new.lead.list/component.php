<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$leadOrder = ['ID' => 'DESC'];

$leadParams = ['TITLE', 'DATE_CREATE', 'ASSIGNED_BY_ID', 'STATUS_ID', 'UF_CRM_LEAD_ACCEPT',];

$obRes = CCrmLead::GetListEx($leadOrder, [], false, false, $leadParams);
$leadList = [];
while($arLead = $obRes->GetNext()) {
    $rsUser = CUser::GetByID($arLead['ASSIGNED_BY_ID'])->Fetch();
    $arLead['ASSIGNED_BY_ID'] = $rsUser['NAME'] . '(' .$rsUser['LOGIN']. ')';
    $leadList[] = $arLead;
}

$arResult['LEADS'] = $leadList;
$this->IncludeComponentTemplate();
?>