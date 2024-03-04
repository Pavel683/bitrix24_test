<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


if ($arResult['LEADS']) {
    $tableData = '';
    foreach ($arResult['LEADS'] as $lead) {

        if ($lead['UF_CRM_LEAD_ACCEPT']) {
            $tableCheckbox = 'checked data-action=reject';
        }else {
            $tableCheckbox = 'data-action=accept';
        }
        $tableData = $tableData.'<tr>'.
                '<th>'.$lead['TITLE'].'</th>'.
                '<th>'.$lead['DATE_CREATE'].'</th>'.
                '<th>'.$lead['ASSIGNED_BY_ID'].'</th>'.
                '<th>'.$lead['STATUS_ID'].'</th>'.
                '<th><input type="checkbox" value="'.$lead['ID'].'" '.$tableCheckbox.'></th>'.
            '</tr>';
    }

    echo '<table class="my_table">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Дата создания</th>
                        <th>Ответственный</th>
                        <th>Статус</th>
                        <th>Принята</th>
                    </tr>
                </thead>
                <tbody>
                   '.$tableData.'
               </tbody>
            </table>';
}
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script>
    $('input[type="checkbox"]').on('click', function (){
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
