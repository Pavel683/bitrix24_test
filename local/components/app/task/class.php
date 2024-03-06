<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class TaskList extends CBitrixComponent
{


    protected function prepareTasksValue(): object
    {
        $taskOrder = ['UF_TASKS_PRIORITY' => 'DESC'];
        $taskParams = ['TITLE', 'CREATED_DATE', 'RESPONSIBLE_ID', 'CREATED_BY', 'UF_TASKS_PRIORITY'];
        return CTasks::GetList($taskOrder, [], $taskParams);
    }

    protected function prepareUserValue(int $userID): string
    {
        $user =  CUser::GetByID($userID)->Fetch();
        return $user['NAME'] . '(' .$user['LOGIN']. ')';

    }

    protected function prepareTaskTitle(int $taskID): string
    {
        $task = CTasks::GetByID($taskID)->Fetch();
        return $task['TITLE'];

    }

    protected function prepareTaskPriority(int $taskID): int
    {
        $task = CTasks::GetByID($taskID)->Fetch();
        return $task['UF_TASKS_PRIORITY'];

    }

    public function prepareTasksListData(): array
    {
        $tasksList = [];
        if (CModule::IncludeModule("tasks")) {
            $res = $this->prepareTasksValue();
            while ($arTask = $res->GetNext()) {
                $arTask['RESPONSIBLE_ID'] = $this->prepareUserValue($arTask['RESPONSIBLE_ID']);
                $arTask['CREATED_BY'] = $this->prepareUserValue($arTask['CREATED_BY']);
                $tasksList[] = $arTask;
            }
        }
        return $tasksList;
    }

    protected function prepareHistoryChangeValue()
    {
        $elChangePriority = new \Loc\ChangePriority();
        return $elChangePriority->get();
    }

    public function prepareHistoryChangeListData(): array
    {
        $changePriorityList = [];
        $historyChangePriority = $this->prepareHistoryChangeValue();
        while ($arrChangePriority = $historyChangePriority->Fetch()) {
            $arrChangePriority['UF_USER'] = $this->prepareUserValue($arrChangePriority['UF_USER']);
            $arrChangePriority['UF_TASK_ID'] = $this->prepareTaskTitle($arrChangePriority['UF_TASK_ID']);
            $changePriorityList[] = $arrChangePriority;
        }
        return $changePriorityList;
    }

    public function updateTaskFields(int $taskID, array $taskFields)
    {
        $obTask = new CTasks;
        $obTask->Update($taskID, $taskFields);
    }

    protected function addHistoryChangePriority(int $taskID, string $priorityLine)
    {
        global $USER;
        $arFields = [
            'UF_USER' => $USER->GetID(),
            'UF_DATA_TIME' => date('d.m.Y H:i:s'),
            'UF_TASK_ID' => $taskID,
            'UF_PRIORITY' => $priorityLine,
        ];
        $elChangePriority = new \Loc\ChangePriority();
        $elChangePriority->add($arFields);
    }

    protected function setLinePriorityTask(int $taskID, string $action)
    {
        $taskPriority = $this->prepareTaskPriority($taskID);
        if ($action == 'up') {
            $taskPriorityVal = $taskPriority + 1;
            $priorityLine = '↑';
        }else {
            $taskPriorityVal = $taskPriority - 1;
            $priorityLine = '↓';
        }
        $taskFields = ['UF_TASKS_PRIORITY' => $taskPriorityVal];
        $this->updateTaskFields($taskID, $taskFields);
        $this->addHistoryChangePriority($taskID, $priorityLine);
    }


    public function executeComponent()
    {
        global $APPLICATION;
        if ($_REQUEST) {
            foreach ($_REQUEST as $action=>$taskID){
                if (CModule::IncludeModule("tasks")) {
                    $this->setLinePriorityTask($taskID, $action);
                }
            }
            LocalRedirect($APPLICATION->GetCurPageParam("", ['up', 'down']));
        }

        if($this->startResultCache())
        {
            $this->arResult["TASKS"] = $this->prepareTasksListData();
            $this->arResult["PRIORITY"] = $this->prepareHistoryChangeListData();
            $this->includeComponentTemplate();
        }
        return $this->arResult;
    }

}
