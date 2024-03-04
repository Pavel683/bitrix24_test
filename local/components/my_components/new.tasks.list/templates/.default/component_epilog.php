<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
global $APPLICATION;

if ($_GET){
    foreach ($_GET as $action=>$value){


        if (CModule::IncludeModule("tasks"))
        {
            $rsTask = CTasks::GetByID($value);
            if ($arTask = $rsTask->GetNext())
            {

                if ($action == "up"){
                    $task_priority_val = $arTask["UF_TASKS_PRIORITY"] + 1;
                    $priority_line = '↑';
                }else{
                    $task_priority_val = $arTask["UF_TASKS_PRIORITY"] - 1;
                    $priority_line = '↓';
                }

                $arFields = Array(
                    "UF_TASKS_PRIORITY" => $task_priority_val,
                );
                $obTask = new CTasks;
                $obTask->Update($value, $arFields);


                $arFields = array(
                    "UF_USER" => $USER->GetID(),
                    "UF_DATA_TIME" => date('d.m.Y H:i:s'),
                    "UF_TASK_ID" => $value,
                    "UF_PRIORITY" => $priority_line,
                );
                $el_change_priority = new \Loc\ChangePriority();
                $el_change_priority->add($arFields);

            }


        }
    }
    LocalRedirect($APPLICATION->GetCurPageParam("", array($action)));
}
