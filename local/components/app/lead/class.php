<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class LeadList extends CBitrixComponent
{

    protected function prepareLeadsValue(): object
    {
        $leadOrder = ['ID' => 'DESC'];
        $leadParams = ['TITLE', 'DATE_CREATE', 'ASSIGNED_BY_ID', 'STATUS_ID', 'UF_CRM_LEAD_ACCEPT'];
        return CCrmLead::GetListEx($leadOrder, [], false, false, $leadParams);
    }

    protected function prepareUserValue(int $userID): string
    {
        $user =  CUser::GetByID($userID)->Fetch();
        return $user['NAME'] . '(' .$user['LOGIN']. ')';
    }

    protected function getActionVal(string $action): int
    {
        switch ($action){
            case 'accept':
                return 1;
            default:
                return 0;
        }
    }

    protected function executeActionLead(int $leadID, int $leadAcceptVal)
    {
        if (\Bitrix\Main\Loader::includeModule('crm')) {
            $entity = new CCrmLead(true);
            $fields = ['UF_CRM_LEAD_ACCEPT' => $leadAcceptVal];
            $entity->update($leadID, $fields);
        }
    }

    public function prepareLeadsListData(): array
    {
        $leadList = [];
        $obRes = $this->prepareLeadsValue();
        while($arLead = $obRes->GetNext()) {
            $arLead['ASSIGNED_BY_ID'] = $this->prepareUserValue($arLead['ASSIGNED_BY_ID']);
            $leadList[] = $arLead;
        }
        return $leadList;
    }

    public function executeComponent()
    {
        global $APPLICATION;
        if ($_REQUEST) {
            foreach ($_REQUEST as $action=>$leadID){
                $leadAcceptVal = $this->getActionVal($action);
                $this->executeActionLead($leadID, $leadAcceptVal);
            }
            LocalRedirect($APPLICATION->GetCurPageParam("", ['accept', 'reject', 'login']));
        }

        if($this->startResultCache())
        {
            $this->arResult["LEADS"] = $this->prepareLeadsListData();
            $this->includeComponentTemplate();
        }
        return $this->arResult["LEADS"];
    }
    
}
