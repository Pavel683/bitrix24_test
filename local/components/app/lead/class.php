<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class LeadList extends CBitrixComponent
{
    protected array $leadOrder = ['ID' => 'DESC'];
    protected array $leadParams = ['TITLE', 'DATE_CREATE', 'ASSIGNED_BY_ID', 'STATUS_ID', 'UF_CRM_LEAD_ACCEPT'];
    protected array $leadList = [];

    protected function prepareLeadsValue(): object
    {
        return CCrmLead::GetListEx($this->leadOrder, [], false, false, $this->leadParams);
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
        $obRes = $this->prepareLeadsValue();
        while($arLead = $obRes->GetNext()) {
            $arLead['ASSIGNED_BY_ID'] = $this->prepareUserValue($arLead['ASSIGNED_BY_ID']);
            $this->leadList[] = $arLead;
        }
        return $this->leadList;
    }

    public function executeComponent()
    {
        global $APPLICATION;
        if ($_REQUEST) {
            foreach ($_REQUEST as $action=>$leadID){
                $leadAcceptVal = $this->getActionVal($action);
                $this->executeActionLead($leadID, $leadAcceptVal);
            }
            LocalRedirect($APPLICATION->GetCurPageParam("", ['accept', 'reject']));
        }

        if($this->startResultCache())
        {
            $this->arResult["LEADS"] = $this->prepareLeadsListData();
            $this->includeComponentTemplate();
        }
        return $this->arResult["LEADS"];
    }
    
}
