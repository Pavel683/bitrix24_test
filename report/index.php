<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Участие покупателя в торгах");
require_once $_SERVER["DOCUMENT_ROOT"].'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
//use PhpOffice\PhpSpreadsheet\Style\{Font, Border, Alignment, Fill};
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
global $DB;
$oRaoUser = new \Rao\User();
if(!$oRaoUser->IsSeller()){
    echo '<br><br><br><br><br><br><br>';
	die('Страница доступна только для продавцов!');
}

CModule::IncludeModule('iblock');?>
<h1>Участие покупателя в торгах</h1>
<?if(CSite::InDir('/lk/reports/')):?><div class="row wrapper align-left"><?$APPLICATION->IncludeComponent("bitrix:breadcrumb", 'rao', Array());?></div><?endif;?>

<?
$f_name = '/local/exel/buyer_activ_'.$USER->GetID().'.xlsx';
$uniq = uniqid();
if($_REQUEST['create_report'] && !$_REQUEST["user_id"]){
    $APPLICATION->RestartBuffer();
    echo json_encode(Array('status'=>'error', 'error'=>'Поле "Покупатель" не заполнено или такого покупателя не существует'));
    exit();
}
?>

<?if($_REQUEST['create_report']){

    $APPLICATION->RestartBuffer();

    if (!$_REQUEST["user_name"]){
        echo json_encode(Array('status'=>'error', 'error'=>'Поле "Покупатель" не заполнено'));
        exit();
    }

    $table_column_id = array();
    $table_column_name = array();

    // Создаем заголовки таблицы, с доп. или без
    if ($_REQUEST["dop_info"]){
        $table_column_id = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
        $table_column_name = ['№ п/п', 'Лот', 'Размер обеспечительного платежа', 'Нуменклатурная группа', 'Количество предложений', 'Максимальная сумма предложения', 'Статус предложения', 'Победитель', 'Сумма победителя', 'Форма реализации', 'Статус лота', 'Категория', 'Продавец', 'Организатор'];
    }else{
        $table_column_id = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $table_column_name = ['№ п/п', 'Лот', 'Размер обеспечительного платежа', 'Нуменклатурная группа', 'Количество предложений', 'Максимальная сумма предложения', 'Победитель', 'Сумма победителя'];
    }
    if ($_REQUEST["tender_dat"]){
        if ($_REQUEST["dop_info"]) {
            array_push($table_column_id, 'O', 'P');
            $table_column_name = array_merge(array_slice($table_column_name, 0, 3), array('Дата начала торгов', 'Дата подачи заявки на участие'), array_slice($table_column_name, 3, count($table_column_name)));
        }else{
            array_push($table_column_id, 'I', 'J');
            $table_column_name = array_merge(array_slice($table_column_name, 0, 3), array('Дата начала торгов', 'Дата подачи заявки на участие'), array_slice($table_column_name, 3, count($table_column_name)));
        }
    }




		$objPHPExcel = new Spreadsheet();
		$objPHPExcel->getProperties()->setCreator('user')
						->setLastModifiedBy('user')
						->setTitle('Buyer activ')
						->setSubject('Buyer activ')
						->setDescription('Buyer activ')
						->setKeywords('Buyer activ')
						->setCategory('Buyer activ');
					$objPHPExcel->setActiveSheetIndex(0);
    $styleArray = array(
        'borders' => [
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
        ],
    );
		$sheet = $objPHPExcel->getActiveSheet();

        for ($i = 0; $i != count($table_column_id); $i++){
            $sheet->setCellValue($table_column_id[$i].'2', $table_column_name[$i]);
            $sheet->getStyle($table_column_id[$i].'2')->applyFromArray($styleArray);
            $sheet->getStyle($table_column_id[$i].'2')->getFill()->setFillType(Fill::FILL_SOLID);
            $sheet->getStyle($table_column_id[$i].'2')->getFill()->getStartColor()->setRGB('86cefc');
            $sheet->getStyle($table_column_id[$i].'2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

    $key1 = 1;
    $key2 = 1;
    $key3 = 1;
    $dataArray = array();
    $dataArray_order_no_rates = array();
    $dataArray_all_rates = array();
    $dataArray_recalled_rates = array();
    $id_lot_Array_with_order = array();
    $id_lot_Array_no_order = array();

    // Фильтр типов лотов
    $argroup = array();

    if ($_REQUEST["group_type"]){
        if ($_REQUEST["group_type"]["realty"]){
            $argroup[] = 3;
        }
        if ($_REQUEST["group_type"]["goods"]){
            $argroup[] = 4;
        }
        if ($_REQUEST["group_type"]["enres"]){
            $argroup[] = 57;
        }
    }

    // Получаем все заявки покупателя
    $res_order = $DB->Query("select PROPERTY_40 as USER_ID, PROPERTY_41 as STATUS, PROPERTY_42 as LOT_ID from b_iblock_element_prop_s4 where PROPERTY_40 = '".$_REQUEST['user_id']."'");//order by IBLOCK_ELEMENT_ID desc
    while($ar = $res_order->GetNext()){
        // Получаем лоты по этим заявкам
        $arFilter = Array("IBLOCK_ID"=>LOT_IBLOCK_ID, "SECTION_ID" => $argroup, "ID" => intval($ar["LOT_ID"]), "PROPERTY_SELLER_ORGANIZER" => $oRaoUser->getOrg());
        $res_lot = CIBlockElement::GetList(false, $arFilter);
        while ($ob = $res_lot->GetNextElement()){
            $array = array();
            $arF = $ob->GetFields();
            $arP = $ob->GetProperties();
            // Записываем данные в массив
            $id_lot_Array_with_order[] = $arF["ID"];
//            $array[] = $key;
            $array[] = "(".$arF["ID"].") ".$arF["NAME"];

            if ($arP["AMOUNT_PAY"]["VALUE"]) {
                $array[] = $arP["AMOUNT_PAY"]["VALUE"];
            }else{
                $array[] = "-";
            }

            if ($_REQUEST["tender_dat"]){
                if ($arP["TYPE_PROCEDURE"]["VALUE_ENUM_ID"] == 1) {
                    if ($arP["BIDDING_DATE_START"]["VALUE"]) {
                        $array[] = $arP["BIDDING_DATE_START"]["VALUE"];
                    }else{
                        $array[] = "-";
                    }

                    if ($arP["PRESELECTION"]["VALUE_ENUM_ID"]) {
                        $res_order_for_dat = CIBlockElement::GetList(false, array('IBLOCK_ID' => LOTORDER_IBLOCK_ID, "PROPERTY_LOT_ID" => intval($ar["LOT_ID"]), "PROPERTY_USER_ID" => $_REQUEST['user_id']));
                        while ($ob_dat = $res_order_for_dat->fetch()) {
                            if ($ob_dat["DATE_CREATE"]) {
                                $array[] = $ob_dat["DATE_CREATE"];
                            } else {
                                $array[] = "-";
                            }
                        }
                    }else{
                        $array[] = "-";
                    }
                }else{
                    $array[] = "-";
                    $array[] = "-";
                }
            }

            $okpd = $arP["FILTER_OKPD_LEVEL1"]["VALUE"];
            if ($arP["FILTER_OKPD_LEVEL2"]["VALUE"]){
                $okpd = $okpd."/".$arP["FILTER_OKPD_LEVEL2"]["VALUE"];
                if ($arP["FILTER_OKPD_LEVEL3"]["VALUE"]){
                    $okpd = $okpd."/".$arP["FILTER_OKPD_LEVEL3"]["VALUE"];
                }
            }
            if (!$okpd){
                $okpd = "-";
            }
            $array[] = $okpd;

            // Получаем предложения по лоту
            $res_rates = CIBlockElement::GetList(false, Array('IBLOCK_ID'=>LOTRATE_IBLOCK_ID, "PROPERTY_LOT_ID" => intval($ar["LOT_ID"])));
            // количество
            $number_rates = $res_rates->SelectedRowsCount();
            if (!$number_rates){
                $number_rates = "-";
            }
            $array[] = $number_rates;

            $max_summ_user = 0;
            $max_summ_user_recalled = 0;
            $status_rate_user_cancel = "";
            $status_rate_user_winner = "";
            $winner_summ = 0;
            $winner = "";

            $all_rates_racalled = false;
            $rate_no_only_recalled = false;

            while ($rate = $res_rates->GetNextElement()) {
                $arF_rate = $rate->GetFields();
                $arP_rate = $rate->GetProperties();
                // Победитель
                if ($arP_rate["IS_WINNER"]["VALUE"]){
                    $resUser = CUser::GetByID($arP_rate["USER_ID"]["VALUE"]);
                    $arUser = $resUser->GetNext();
//                    echo "<pre>";print_r($arUser);echo"</pre>";
                    $winner = $arUser["UF_ORGANIZATION_NAME"]." (".$arUser["UF_INN"].")";
                    if ($arP_rate["SUMM_OWNER"]["VALUE"]){
                        $winner_summ = $arP_rate["SUMM_OWNER"]["VALUE"];
                    }else{
                        $winner_summ = $arP_rate["SUMM"]["VALUE"];
                    }
                    // если победитель покупатель
                    if ($arP_rate["USER_ID"]["VALUE"] == $_REQUEST['user_id']){
                        $status_rate_user_winner = "Принято";
                        $status_rate_user_cancel = "";
                    }
                }
                // Максимальная ставка покупателя
                if ($arP_rate["USER_ID"]["VALUE"] == $_REQUEST['user_id'] && $arP_rate["SUMM"]["VALUE"] > $max_summ_user && $arP_rate["STATUS"]["VALUE_ENUM_ID"] != 100){
                    $max_summ_user = $arP_rate["SUMM"]["VALUE"];
                    $status_rate_user_cancel = $arP_rate["STATUS"]["VALUE"];
                }

                // Сортируем по блокам
                if ($arP_rate["USER_ID"]["VALUE"] == $_REQUEST['user_id'] && $arP_rate["STATUS"]["VALUE_ENUM_ID"] == 100){
                    $all_rates_racalled = true;
                    if ($arP_rate["SUMM"]["VALUE"] > $max_summ_user_recalled){
                        $max_summ_user_recalled = $arP_rate["SUMM"]["VALUE"];
//                        $status_rate_user_cancel = $arP_rate["STATUS"]["VALUE"];
                    }
                }
                if ($arP_rate["USER_ID"]["VALUE"] == $_REQUEST['user_id'] && $arP_rate["STATUS"]["VALUE_ENUM_ID"] != 100){
                    $rate_no_only_recalled = true;
                }

            }

            if ($winner_summ == 0 && $winner == ""){
                $winner_summ = "-";
                $winner = "-";
            }else{
                $winner_summ = number_format($winner_summ, 2, ',', ' ');
            }
            if ($max_summ_user == 0){
                $max_summ_user = "Не учавствовал";
                $status_rate_user_cancel = "-";
            }else{
                $max_summ_user = number_format($max_summ_user, 2, ',', ' ');
            }
            if ($max_summ_user_recalled == 0){
                $max_summ_user_recalled = "-";
            }else{
                $max_summ_user_recalled = number_format($max_summ_user_recalled, 2, ',', ' ');
            }

            // Есть предложения
            if (($all_rates_racalled == true && $rate_no_only_recalled == true) || ($all_rates_racalled == false && $rate_no_only_recalled == true) || ($all_rates_racalled == false && $rate_no_only_recalled == false)) {
                $array[] = $max_summ_user;
            }

            // Все предложения отозванны
            if ($all_rates_racalled == true && $rate_no_only_recalled == false){
                $array[] = $max_summ_user_recalled;
                $status_rate_user_cancel = "Отозвано";
            }


            if ($_REQUEST["dop_info"]){
                if ($status_rate_user_cancel == "отклонена") {
                    $status_rate_user_cancel = "Отклонено";
                    $array[] = $status_rate_user_cancel;
                }elseif ($status_rate_user_cancel == "Отозвано"){
//                    $status_rate_user_cancel = "Отозвано";
                    $array[] = $status_rate_user_cancel;
                }elseif ($status_rate_user_winner){
                    $array[] = $status_rate_user_winner;
                }elseif ($winner_summ != "-" && $winner != "-" && $max_summ_user != "Не учавствовал"){
                    $status_rate_user = "Проиграло";
                    $array[] = $status_rate_user;
                }else{
                    $status_rate_user = "-";
                    $array[] = $status_rate_user;
                }
            }

            $array[] = $winner;
            $array[] = $winner_summ;


            if ($_REQUEST["dop_info"]){
                $array[] = $arP["TYPE_PROCEDURE"]["VALUE"];
                $array[] = $arP["STATUS"]["VALUE"];

                if ($arF["IBLOCK_SECTION_ID"] == 3){
                    $section_name = "Имущество";
                }elseif ($arF["IBLOCK_SECTION_ID"] == 4){
                    $section_name = "МТР";
                }elseif ($arF["IBLOCK_SECTION_ID"] == 57){
                    $section_name = "Энергоресурсы";
                }
                $array[] = $section_name;

                $resS = $DB->Query("select * from b_hl_directory_counterparties where ID=" . $arP['SELLER_ORG']['VALUE']);
                $arS = $resS->GetNext();
                $array[] = $arS["UF_NAME_SHORT"];



                $resOR = $DB->Query("select * from b_hl_directory_counterparties where ID=" . $arP['SELLER_ORGANIZER']['VALUE']);
                $arOR = $resOR->GetNext();
                $array[] = $arOR["UF_NAME_SHORT"];

            }

            // Есть заявки, но нет предложений
            if ($all_rates_racalled == false && $rate_no_only_recalled == false){
                array_unshift($array,$key1);
                $dataArray_order_no_rates[] = $array;
                $key1++;
            }

            // Есть предложения
            if (($all_rates_racalled == true && $rate_no_only_recalled == true) || ($all_rates_racalled == false && $rate_no_only_recalled == true)){
                array_unshift($array,$key2);
                $dataArray_all_rates[] = $array;
                $key2++;
            }

            // Все предложения отозванны
            if ($all_rates_racalled == true && $rate_no_only_recalled == false){
                array_unshift($array,$key3);
                $dataArray_recalled_rates[] = $array;
                $key3++;
            }
        }
    }


    // Получаем список предложений без подачи заявок, исключая уже записанные
    $res_rates_no_order = CIBlockElement::GetList(false, Array('IBLOCK_ID'=>LOTRATE_IBLOCK_ID, "!PROPERTY_LOT_ID" => $id_lot_Array_with_order, "PROPERTY_USER_ID" => $_REQUEST['user_id']));
    while ($rate_no_order = $res_rates_no_order->GetNextElement()){
        $arF_rate = $rate_no_order->GetFields();
        $arP_rate = $rate_no_order->GetProperties();
        if (!in_array($arP_rate["LOT_ID"]["VALUE"], $id_lot_Array_no_order)){
            $id_lot_Array_no_order[] = $arP_rate["LOT_ID"]["VALUE"];
        }
    }


    // Получвем список лотов и записываем данные
    foreach ($id_lot_Array_no_order as $lot){
        $arFilter = ["IBLOCK_ID"=>LOT_IBLOCK_ID, "SECTION_ID" => $argroup, "ID" => intval($lot), "PROPERTY_SELLER_ORGANIZER" => $oRaoUser->getOrg()];
        $res_lot = CIBlockElement::GetList(false, $arFilter);
        while ($ob = $res_lot->GetNextElement()){
            $array = array();
            $arF = $ob->GetFields();
            $arP = $ob->GetProperties();
            $id_lot_Array_with_order[] = $arF["ID"];
//            $array[] = $key;
            $array[] = "(".$arF["ID"].") ".$arF["NAME"];

            if ($arP["AMOUNT_PAY"]["VALUE"]) {
                $array[] = $arP["AMOUNT_PAY"]["VALUE"];
            }else{
                $array[] = "-";
            }

            if ($_REQUEST["tender_dat"]){
                if ($arP["TYPE_PROCEDURE"]["VALUE_ENUM_ID"] == 1) {
                    if ($arP["BIDDING_DATE_START"]["VALUE"]) {
                        $array[] = $arP["BIDDING_DATE_START"]["VALUE"];
                    }else{
                        $array[] = "-";
                    }

                    if ($arP["PRESELECTION"]["VALUE_ENUM_ID"]) {
                        $res_order_for_dat = CIBlockElement::GetList(false, array('IBLOCK_ID' => LOTORDER_IBLOCK_ID, "PROPERTY_LOT_ID" => intval($ar["LOT_ID"]), "PROPERTY_USER_ID" => $_REQUEST['user_id']));
                        while ($ob_dat = $res_order_for_dat->fetch()) {
                            if ($ob_dat["DATE_CREATE"]) {
                                $array[] = $ob_dat["DATE_CREATE"];
                            } else {
                                $array[] = "-";
                            }
                        }
                    }else{
                        $array[] = "-";
                    }
                }else{
                    $array[] = "-";
                    $array[] = "-";
                }
            }

            $okpd = $arP["FILTER_OKPD_LEVEL1"]["VALUE"];
            if ($arP["FILTER_OKPD_LEVEL2"]["VALUE"]){
                $okpd = $okpd."/".$arP["FILTER_OKPD_LEVEL2"]["VALUE"];
                if ($arP["FILTER_OKPD_LEVEL3"]["VALUE"]){
                    $okpd = $okpd."/".$arP["FILTER_OKPD_LEVEL3"]["VALUE"];
                }
            }
            if (!$okpd){
                $okpd = "-";
            }
            $array[] = $okpd;

            $res_rates = CIBlockElement::GetList(false, Array('IBLOCK_ID'=>LOTRATE_IBLOCK_ID, "PROPERTY_LOT_ID" => intval($lot)));
//            echo "<pre>";print_r($res_rates->SelectedRowsCount());echo"</pre>";
            $number_rates = $res_rates->SelectedRowsCount();
            if (!$number_rates){
                $number_rates = "-";
            }
            $array[] = $number_rates;

            $max_summ_user = 0;
            $max_summ_user_recalled = 0;
            $status_rate_user_cancel = "";
            $status_rate_user_winner = "";
            $winner_summ = 0;
            $winner = "";

            $all_rates_racalled = false;
            $rate_no_only_recalled = false;

            while ($rate = $res_rates->GetNextElement()) {
                $arF_rate = $rate->GetFields();
                $arP_rate = $rate->GetProperties();
                if ($arP_rate["IS_WINNER"]["VALUE"]){
                    $resUser = CUser::GetByID($arP_rate["USER_ID"]["VALUE"]);
                    $arUser = $resUser->GetNext();
                    $winner = $arUser["UF_ORGANIZATION_NAME"]." (".$arUser["UF_INN"].")";
                    if ($arP_rate["SUMM_OWNER"]["VALUE"]){
                        $winner_summ = $arP_rate["SUMM_OWNER"]["VALUE"];
                    }else{
                        $winner_summ = $arP_rate["SUMM"]["VALUE"];
                    }
                    if ($arP_rate["USER_ID"]["VALUE"] == $_REQUEST['user_id']){
                        $status_rate_user_winner = "Принято";
                        $status_rate_user_cancel = "";
                    }
                }
                if ($arP_rate["USER_ID"]["VALUE"] == $_REQUEST['user_id'] && $arP_rate["SUMM"]["VALUE"] > $max_summ_user && $arP_rate["STATUS"]["VALUE_ENUM_ID"] != 100){
                    $max_summ_user = $arP_rate["SUMM"]["VALUE"];
                    $status_rate_user_cancel = $arP_rate["STATUS"]["VALUE"];
                }

                // Сортируем по блокам
                if ($arP_rate["USER_ID"]["VALUE"] == $_REQUEST['user_id'] && $arP_rate["STATUS"]["VALUE_ENUM_ID"] == 100){
                    $all_rates_racalled = true;
                    if ($arP_rate["SUMM"]["VALUE"] > $max_summ_user_recalled){
                        $max_summ_user_recalled = $arP_rate["SUMM"]["VALUE"];
//                        $status_rate_user_cancel = $arP_rate["STATUS"]["VALUE"];
                    }
                }
                if ($arP_rate["USER_ID"]["VALUE"] == $_REQUEST['user_id'] && $arP_rate["STATUS"]["VALUE_ENUM_ID"] != 100){
                    $rate_no_only_recalled = true;
                }

            }
            if ($winner_summ == 0 && $winner == ""){
                $winner_summ = "-";
                $winner = "-";
            }else{
                $winner_summ = number_format($winner_summ, 2, ',', ' ');
            }
            if ($max_summ_user == 0){
                $max_summ_user = "Не учавствовал";
            }else{
                $max_summ_user = number_format($max_summ_user, 2, ',', ' ');
            }

            if ($max_summ_user_recalled == 0){
                $max_summ_user_recalled = "-";
            }else{
                $max_summ_user_recalled = number_format($max_summ_user_recalled, 2, ',', ' ');
            }

            // Есть предложения
            if (($all_rates_racalled == true && $rate_no_only_recalled == true) || ($all_rates_racalled == false && $rate_no_only_recalled == true)) {
                $array[] = $max_summ_user;
            }

            // Все предложения отозванны
            if ($all_rates_racalled == true && $rate_no_only_recalled == false){
                $array[] = $max_summ_user_recalled;
                $status_rate_user_cancel = "Отозвано";
            }

            if ($_REQUEST["dop_info"]){
                if ($status_rate_user_cancel == "отклонена") {
                    $status_rate_user_cancel = "Отклонено";
                    $array[] = $status_rate_user_cancel;
                }elseif ($status_rate_user_cancel == "Отозвано"){
//                    $status_rate_user_cancel = "Отозвано";
                    $array[] = $status_rate_user_cancel;
                }elseif ($status_rate_user_winner){
                    $array[] = $status_rate_user_winner;
                }elseif ($winner_summ != "-" && $winner != "-" && $max_summ_user != "Не учавствовал"){
                    $status_rate_user = "Проиграло";
                    $array[] = $status_rate_user;
                }else{
                    $status_rate_user = "-";
                    $array[] = $status_rate_user;
                }
            }

            $array[] = $winner;
            $array[] = $winner_summ;

            if ($_REQUEST["dop_info"]){
                $array[] = $arP["TYPE_PROCEDURE"]["VALUE"];
                $array[] = $arP["STATUS"]["VALUE"];

                if ($arF["IBLOCK_SECTION_ID"] == 3){
                    $section_name = "Имущество";
                }elseif ($arF["IBLOCK_SECTION_ID"] == 4){
                    $section_name = "МТР";
                }elseif ($arF["IBLOCK_SECTION_ID"] == 57){
                    $section_name = "Энергоресурсы";
                }
                $array[] = $section_name;

                $resS = $DB->Query("select * from b_hl_directory_counterparties where ID=" . $arP['SELLER_ORG']['VALUE']);
                $arS = $resS->GetNext();
                $array[] = $arS["UF_NAME_SHORT"];



                $resOR = $DB->Query("select * from b_hl_directory_counterparties where ID=" . $arP['SELLER_ORGANIZER']['VALUE']);
                $arOR = $resOR->GetNext();
                $array[] = $arOR["UF_NAME_SHORT"];

            }

            // Есть предложения
            if (($all_rates_racalled == true && $rate_no_only_recalled == true) || ($all_rates_racalled == false && $rate_no_only_recalled == true)){
                array_unshift($array,$key2);
                $dataArray_all_rates[] = $array;
                $key2++;
            }

            // Все предложения отозванны
            if ($all_rates_racalled == true && $rate_no_only_recalled == false){
                array_unshift($array,$key3);
                $dataArray_recalled_rates[] = $array;
                $key3++;
            }

//            $dataArray[] = $array;
//            $key++;
        }
    }


    if ($dataArray_order_no_rates) {
        array_unshift($dataArray_order_no_rates, array("Лоты с заявками, но без предложений"));
    }
    if ($dataArray_all_rates) {
        array_unshift($dataArray_all_rates, array("Лоты с предложениями"));
    }
    if ($dataArray_recalled_rates) {
        array_unshift($dataArray_recalled_rates, array("Лоты только с отозванными предложениями"));
    }

    $dataArray = array_merge($dataArray_order_no_rates, $dataArray_all_rates, $dataArray_recalled_rates);

    if (!$dataArray){
        $dataArray[] = "";
    }

    // Создаем шапку и стили
    $sheet->mergeCells("A1:".end($table_column_id)."1");
    $sheet->getRowDimension(1)->setRowHeight(40);
    $sheet->setCellValue('A1', 'Отчет об участии покупателя "'.$_REQUEST["user_name"].'" в торгах ');


    if ($_REQUEST["dop_info"]) {
        $step = 1;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(30);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(32);$step++;
        if ($_REQUEST["tender_dat"]){
            $sheet->getColumnDimension($table_column_id[$step])->setWidth(20);$step++;
            $sheet->getColumnDimension($table_column_id[$step])->setWidth(30);$step++;
        }
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(30);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(24);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(33);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(20);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(24);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(20);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(30);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(20);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(15);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(30);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(30);$step++;
        $sheet->getRowDimension(2)->setRowHeight(30);
    }else{
        $step = 1;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(30);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(32);$step++;
        if ($_REQUEST["tender_dat"]){
            $sheet->getColumnDimension($table_column_id[$step])->setWidth(20);$step++;
            $sheet->getColumnDimension($table_column_id[$step])->setWidth(30);$step++;
        }
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(30);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(24);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(33);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(24);$step++;
        $sheet->getColumnDimension($table_column_id[$step])->setWidth(22);$step++;
        $sheet->getRowDimension(2)->setRowHeight(30);
    }

    $sheet->fromArray($dataArray, null, 'A3');

    $sharedStyle = array(
        [
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN],
                'top' => ['borderStyle' => Border::BORDER_THIN],
                'left' => ['borderStyle' => Border::BORDER_THIN],
                'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
//                'wrapText' => true,
            ]
        ]
    );

    $overStyleForTable = [
        "row_color"=>[
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'D9D9D9')
            ),
            'font' => array(
                'bold' => true,
            ),
        ]
    ];

    foreach ($sheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        // Перебираем ячейки в текущей строке
        foreach ($cellIterator as $cell) {
//            var_dump(
//                $cell->getValue(),
//                $cell->getRow(),         // Выведет номер строки, например 1
//                $cell->getColumn(),      // Выведет букву колонки, например 'A'
//                $cell->getCoordinate()   // Выведет Excel-координату ячейки, например 'A1'
//            );
            if ($cell->getValue() == "Лоты с заявками, но без предложений" || $cell->getValue() == "Лоты с предложениями" || $cell->getValue() == "Лоты только с отозванными предложениями"){
                $sheet->getRowDimension($cell->getRow())->setRowHeight(30);
                $sheet->getStyle($cell->getCoordinate())->getFill()->setFillType(Fill::FILL_SOLID);
                $sheet->getStyle($cell->getCoordinate())->getFont()->applyFromArray(array('bold' => TRUE,));
                $sheet->getStyle($cell->getCoordinate())->getFill()->getStartColor()->setRGB('D9D9D9');
                $sheet->getStyle($cell->getCoordinate())->applyFromArray($styleArray);

            }elseif ($cell->getValue()){
                $sheet->getStyle($cell->getCoordinate())->applyFromArray($styleArray);
            }else{
                $sheet->getStyle($cell->getCoordinate())->getFill()->setFillType(Fill::FILL_SOLID);
                $sheet->getStyle($cell->getCoordinate())->getFill()->getStartColor()->setRGB('D9D9D9');
            }
            $sheet->getStyle('A1')->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ]
            ]);
        }
    }

		$objWriter = new Xlsx($objPHPExcel);
		$objWriter->save($_SERVER["DOCUMENT_ROOT"] .$f_name);
		echo json_encode(Array('status'=>'completed'));
		exit();
		?>		
		
	<?}else{?>

    <div class="error"></div>
    <div class="row">
        <form method="POST" id="buyer_activ_v1">
            <input type="hidden" name="create_report" value="Y">

            <div class="form-field col">

                <div class="form-field">
                    <label>Покупатель</label>
                    <input type="text" name="user_name" style="width: 500px">
                    <input type="hidden" name="user_id">
                </div>


                <div class="form-field" id="group_type">
                    <label>Лоты</label>
                    <label class="checkbox__container">Лоты Имущества
                        <input checked type="checkbox" value="realty" name="group_type[realty]" style="display:none;">
                        <span class="checkbox__checkmark"></span>
                    </label>
                    <label class="checkbox__container">Лоты МТР
                        <input checked type="checkbox" value="goods" name="group_type[goods]" style="display:none;">
                        <span class="checkbox__checkmark"></span>
                    </label>
                    <label class="checkbox__container">Лоты Энергоресурсов
                        <input checked type="checkbox" value="enres" name="group_type[enres]" style="display:none;">
                        <span class="checkbox__checkmark"></span>
                    </label>

                </div>

                <div class="form-field" id="dop_info">
                    <label></label>
                    <label class="checkbox__container">Выводить дополнительную информацию
                        <input type="checkbox" value="dop_info" name="dop_info" style="display:none;">
                        <span class="checkbox__checkmark"></span>
                    </label>
                </div>

                <div class="form-field" id="tender_dat">
                    <label class="checkbox__container">Выводить информацию о датах для тендеров
                        <input type="checkbox" value="tender_dat" name="tender_dat" style="display:none;">
                        <span class="checkbox__checkmark"></span>
                    </label>
                </div>



                <br>
                <a style="width: 300px" href="javascript:void(0);" class="btn btn-big btn-blue bold get_report">Сформировать отчет</a>

            </div>
        </form>
    </div>
    <div id="report_result2" class="wrapper"></div>
    <script>
        function get_report(i) {
            $.ajax({
                url: '<?=$APPLICATION->GetCurPage()?>?'+$('#buyer_activ_v1').serialize(),
                type: 'get'
            }).done(function (res) {
                console.log(res)
                ar_res = $.parseJSON(res);
                if(ar_res.status=='run'){
                    setTimeout(function(){ get_report(ar_res.page);}, 100);
                    $('#report_result2').html('Формируется файл отчета...'+ar_res.percent+'%');
                }else if(ar_res.status=='completed'){
                    $('#report_result2').html('Скачивание файла должно начаться автоматически. Если этого не произошло, нажмите кнопку: <a href="<?=$f_name?>" class="btn btn-small btn-blue bold" id="download_report">скачать файл</a>');
                    $(".get_report").show();
                    window.location.href = '<?=$f_name?>';
                }else{
                    $('#report_result2').html('');
                    $('.error').html('Произошла ошибка: <font color="red">'+ar_res.error+'</font>');
                    $('.error').show();

                    $(".get_report").show();
                }

            });
        }
        $(".get_report").click(function() {
            $(this).hide();
            $('.error').hide();
            $('#report_result2').html('Формируется файл отчета, ожидайте. <img src="/bitrix/js/im/images/loading.gif" width="24">');
            get_report(0);
        });

    </script>
    <script type="text/javascript">
        // init select
        $(document).ready(function(){
            InitSelect(".modal-form-<?=$uniq?>");
            InitMask(".modal-form-<?=$uniq?>");

            $('input[name="user_name"]').autocomplete({
                serviceUrl: '/local/components/rao/spayment.add/ajax.php',
                onSelect: function (suggestion) {
                    $('input[name="user_id"]').val(suggestion.data);
                    $('input[name="user_name"]').val(suggestion.name);
                }
            });

        });

        $(document).ready(function() {
            $('#buyer_activ_v1').keydown(function(event){
                if(event.keyCode == 13) {
                    event.preventDefault();
                    return false;
                }
            });
        });

    </script>
<?}?>
    </div>
    </div>



<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>