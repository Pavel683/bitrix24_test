<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class LeadList extends CBitrixComponent
{
    protected $leadOrder = ['ID' => 'DESC'];
    protected $leadParams = ['TITLE', 'DATE_CREATE', 'ASSIGNED_BY_ID', 'STATUS_ID', 'UF_CRM_LEAD_ACCEPT'];
    protected $leadList = [];

    protected function prepareLeadsValue()
    {
        return CCrmLead::GetListEx($this->leadOrder, [], false, false, $this->leadParams);
    }

    protected function prepareUserValue($userID)
    {
        $this->user =  CUser::GetByID($userID)->Fetch();
        return $this->user['NAME'] . '(' .$this->user['LOGIN']. ')';

    }

    public function prepareLeadsListData()
    {
        $this->obRes = $this->prepareLeadsValue();
        while($this->arLead = $this->obRes->GetNext()) {
            $this->arLead['ASSIGNED_BY_ID'] = $this->prepareUserValue($this->arLead['ASSIGNED_BY_ID']);
            $this->leadList[] = $this->arLead;
        }
        return $this->leadList;
    }

    public function executeComponent()
    {
        if($this->startResultCache())
        {
            $this->arResult["LEADS"] = $this->prepareLeadsListData();
            $this->includeComponentTemplate();
        }
        return $this->arResult["LEADS"];
    }
    
}
