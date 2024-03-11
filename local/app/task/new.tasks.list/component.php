<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$taskOrder = ['UF_TASKS_PRIORITY' => 'DESC'];

$taskParams = ['TITLE', 'CREATED_DATE', 'RESPONSIBLE_ID', 'CREATED_BY', 'UF_TASKS_PRIORITY'];

if (CModule::IncludeModule("tasks")) {
    $res = CTasks::GetList($taskOrder, [], $taskParams);
    $tasksList = [];
    while ($arTask = $res->GetNext()) {
        $rsUser = CUser::GetByID($arTask['RESPONSIBLE_ID'])->Fetch();
        $arTask['RESPONSIBLE_ID'] = $rsUser['NAME'] . '(' . $rsUser['LOGIN'] . ')';
        $rsUser = CUser::GetByID($arTask['CREATED_BY'])->Fetch();
        $arTask['CREATED_BY'] = $rsUser['NAME'] . '(' . $rsUser['LOGIN'] . ')';
        $tasksList[] = $arTask;
    }
    $arResult['TASKS'] = $tasksList;
}

$elChangePriority = new \Loc\ChangePriority();
$historyChangePriority = $elChangePriority->get();
$changePriorityList = [];
while ($arrChangePriority = $historyChangePriority->Fetch()) {
    $rsUser = CUser::GetByID($arrChangePriority['UF_USER'])->Fetch();
    $arrChangePriority['UF_USER'] = $rsUser['NAME'] . '(' .$rsUser['LOGIN']. ')';
    $task = CTasks::GetByID($arrChangePriority['UF_TASK_ID'])->Fetch();
    $arrChangePriority['UF_TASK_ID'] = $task['TITLE'];
    $changePriorityList[] = $arrChangePriority;
}
$arResult['PRIORITY'] = $changePriorityList;

$this->IncludeComponentTemplate();
?>