<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class TaskList extends CBitrixComponent
{
    protected $taskOrder = ['UF_TASKS_PRIORITY' => 'DESC'];
    protected $taskParams = ['TITLE', 'CREATED_DATE', 'RESPONSIBLE_ID', 'CREATED_BY', 'UF_TASKS_PRIORITY'];
    protected $tasksList = [];
    protected $changePriorityList = [];

    protected function prepareTasksValue()
    {
        return CTasks::GetList($this->taskOrder, [], $this->taskParams);
    }

    protected function prepareUserValue($userID)
    {
        $this->user =  CUser::GetByID($userID)->Fetch();
        return $this->user['NAME'] . '(' .$this->user['LOGIN']. ')';

    }

    protected function prepareTaskValue($taskID)
    {
        $this->task = CTasks::GetByID($taskID)->Fetch();
        return $this->task['TITLE'];

    }

    public function prepareTasksListData()
    {
        if (CModule::IncludeModule("tasks")) {
            $this->res = $this->prepareTasksValue();

            while ($this->arTask = $this->res->GetNext()) {
                $this->arTask['RESPONSIBLE_ID'] = $this->prepareUserValue($this->arTask['RESPONSIBLE_ID']);
                $this->arTask['CREATED_BY'] = $this->prepareUserValue($this->arTask['CREATED_BY']);
                $this->tasksList[] = $this->arTask;
            }
        }
        return $this->tasksList;
    }

    protected function prepareHistoryChangeValue()
    {
        $this->elChangePriority = new \Loc\ChangePriority();
        return $this->elChangePriority->get();
    }

    public function prepareHistoryChangeListData()
    {
        $this->historyChangePriority = $this->prepareHistoryChangeValue();
        while ($this->arrChangePriority = $this->historyChangePriority->Fetch()) {

            $this->arrChangePriority['UF_USER'] = $this->prepareUserValue($this->arrChangePriority['UF_USER']);


            $this->arrChangePriority['UF_TASK_ID'] = $this->prepareTaskValue($this->arrChangePriority['UF_TASK_ID']);
            $this->changePriorityList[] = $this->arrChangePriority;
        }
        return $this->changePriorityList;
    }


    public function executeComponent()
    {
        if($this->startResultCache())
        {
            $this->arResult['TASKS'] = $this->prepareTasksListData();
            $this->arResult['PRIORITY'] = $this->prepareHistoryChangeListData();
            $this->includeComponentTemplate();
        }
        return $this->arResult;
    }

}
