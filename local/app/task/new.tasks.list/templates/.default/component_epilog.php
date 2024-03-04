<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
global $APPLICATION;

if ($_GET) {
    foreach ($_GET as $action=>$value) {

        if (CModule::IncludeModule("tasks")) {
            $rsTask = CTasks::GetByID($value);
            if ($arTask = $rsTask->GetNext()) {

                if ($action == 'up') {
                    $taskPriorityVal = $arTask['UF_TASKS_PRIORITY'] + 1;
                    $priorityLine = '↑';
                }else {
                    $taskPriorityVal = $arTask['UF_TASKS_PRIORITY'] - 1;
                    $priorityLine = '↓';
                }

                $arFields = ['UF_TASKS_PRIORITY' => $taskPriorityVal];
                $obTask = new CTasks;
                $obTask->Update($value, $arFields);

                $arFields = [
                    'UF_USER' => $USER->GetID(),
                    'UF_DATA_TIME' => date('d.m.Y H:i:s'),
                    'UF_TASK_ID' => $value,
                    'UF_PRIORITY' => $priorityLine,
                ];
                $elChangePriority = new \Loc\ChangePriority();
                $elChangePriority->add($arFields);
            }
        }
    }
    LocalRedirect($APPLICATION->GetCurPageParam("", array($action)));
}
