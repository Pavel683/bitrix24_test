<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$taskOrder = array("UF_TASKS_PRIORITY" => "DESC");

$taskParams = array(
        'TITLE', 'CREATED_DATE', 'RESPONSIBLE_ID', 'CREATED_BY', 'UF_TASKS_PRIORITY',
);

if (CModule::IncludeModule("tasks"))
{
    $res = CTasks::GetList($taskOrder, array(), $taskParams);
}

$tasksList = array();
while($arTask = $res->GetNext())
{

    $rsUser = CUser::GetByID($arTask['RESPONSIBLE_ID'])->Fetch();
    $arTask['RESPONSIBLE_ID'] = $rsUser['NAME'] . '(' .$rsUser['LOGIN']. ')';
    $rsUser = CUser::GetByID($arTask['CREATED_BY'])->Fetch();
    $arTask['CREATED_BY'] = $rsUser['NAME'] . '(' .$rsUser['LOGIN']. ')';
//    echo "<pre>";print_r($rsUser);echo"</pre>";//
    $tasksList[] = $arTask;
}
$arResult['TASKS'] = $tasksList;

$el_change_priority = new \Loc\ChangePriority();
$history_change_priority = $el_change_priority->get();
$change_priority_list = array();
while ($arr_change_priority = $history_change_priority->Fetch()){

    $rsUser = CUser::GetByID($arr_change_priority['UF_USER'])->Fetch();
    $arr_change_priority['UF_USER'] = $rsUser['NAME'] . '(' .$rsUser['LOGIN']. ')';
    $task = CTasks::GetByID($arr_change_priority["UF_TASK_ID"])->Fetch();
    $arr_change_priority['UF_TASK_ID'] = $task["TITLE"];
    $change_priority_list[] = $arr_change_priority;
}
$arResult['PRIORITY'] = $change_priority_list;

$this->IncludeComponentTemplate();
?>