<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

//$urlAccept = $APPLICATION->GetCurPageParam("action=lead-accept&id=".'www', $arParams["DELETE_URL_PARAMS"]);
if ($arResult['TASKS']){

    $tableData = '';
    foreach ($arResult['TASKS'] as $task){


        $tableData = $tableData.'<tr>'.
                '<th>'.$task['TITLE'].'</th>'.
                '<th>'.$task['CREATED_DATE'].'</th>'.
                '<th>'.$task['RESPONSIBLE_ID'].'</th>'.
                '<th>'.$task['CREATED_BY'].'</th>'.
                '<th>'.$task['UF_TASKS_PRIORITY'].'
                    <button name="priority_btn" data-action="up" value="'.$task["ID"].'" style="margin: 5px">↑</button>
                    <button name="priority_btn" data-action="down" value="'.$task["ID"].'" style="margin: 5px">↓</button>
                </th>'.
            '</tr>';
    }

    echo '<div style="background-color: white">
            <table class="my_table">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Дата постановки</th>
                        <th>Ответственный</th>
                        <th>Постановщик</th>
                        <th>Приоритет</th>
                    </tr>
                </thead>
                <tbody>
                   '.$tableData.'
               </tbody>
            </table>
        </div>';
}

if ($arResult['PRIORITY']) {
    $tablePriority = '';
    foreach ($arResult['PRIORITY'] as $priority){


        $tablePriority = $tablePriority.'<tr>'.
            '<th>'.$priority['UF_USER'].'</th>'.
            '<th>'.$priority['UF_DATA_TIME'].'</th>'.
            '<th>'.$priority['UF_TASK_ID'].'</th>'.
            '<th>'.$priority['UF_PRIORITY'].'</th>'.
            '</tr>';
    }

    echo '<div style="background-color: white">
            <table class="my_table">
                <thead>
                    <tr>
                        <th>Кто изменил</th>
                        <th>Время изменения</th>
                        <th>Задача</th>
                        <th>Направление изменения</th>
                    </tr>
                </thead>
                <tbody>
                   '.$tablePriority.'
               </tbody>
            </table>
        </div>';
}

?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script>
    $('button[name="priority_btn"]').on('click', function (){
        var url = window.location.href;
        var action = $(this).data('action');
        url += `?${action}=${this.value}`;
        window.location.href = url;
    });
</script>
<style>
    table {
        width: 1200px;
        border-collapse: collapse;
    }
    th {
        padding: 5px;
        border: 1px solid black;
    }
</style>
