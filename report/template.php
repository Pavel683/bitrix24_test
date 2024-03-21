<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
global $USER;
$oRaoUser = new \Rao\User();

require_once $_SERVER["DOCUMENT_ROOT"].'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
?>
<?if(is_array($arResult["ITEMS"]) && count($arResult["ITEMS"]) <= 0):?>
	<div class="list__message">
		<p>Список пуст</p>
	</div>
<?else:

$user_all = $user_zam = $user_spis = $user_vozv = Array();
$total_all = $total_zam = $total_spis = $total_vozv = 0;
$users = Array();
foreach($arResult["ITEMS"] as $arItem):
if(!$users[$arItem['PROPERTIES']['USER_ID']['VALUE']]){
	$rsUser = $USER->GetByID($arItem['PROPERTIES']['USER_ID']['VALUE']);
	$arUser = $rsUser->GetNext();
	if($arUser['UF_INN'])
		$arUser['UF_ORGANIZATION_NAME'].= ' (ИНН '.$arUser['UF_INN'].')';		
	$users[$arItem['PROPERTIES']['USER_ID']['VALUE']] = $arUser['UF_ORGANIZATION_NAME'];
	
}
?>

		<?if($arItem["PROPERTIES"]["TYPE"]['VALUE_ENUM_ID']==SPAYMENT_TYPE_PRIHOD){
			$total_all+=$arItem["PROPERTIES"]["SUMM"]['VALUE'];			
			$user_all[$arItem["PROPERTIES"]["USER_ID"]['VALUE']]+=$arItem["PROPERTIES"]["SUMM"]['VALUE'];			
		}
		if($arItem["PROPERTIES"]["TYPE"]['VALUE_ENUM_ID']==SPAYMENT_TYPE_ZAM){
			$total_zam+=$arItem["PROPERTIES"]["SUMM"]['VALUE'];
			$user_zam[$arItem["PROPERTIES"]["USER_ID"]['VALUE']]+=$arItem["PROPERTIES"]["SUMM"]['VALUE'];			
		}
		if($arItem["PROPERTIES"]["TYPE"]['VALUE_ENUM_ID']==SPAYMENT_TYPE_RASHOD){
			$total_spis+=$arItem["PROPERTIES"]["SUMM"]['VALUE'];
			$user_spis[$arItem["PROPERTIES"]["USER_ID"]['VALUE']]+=$arItem["PROPERTIES"]["SUMM"]['VALUE'];			
		}
		if($arItem["PROPERTIES"]["TYPE"]['VALUE_ENUM_ID']==SPAYMENT_TYPE_VOZV){
			$total_vozv+=$arItem["PROPERTIES"]["SUMM"]['VALUE'];
			$user_vozv[$arItem["PROPERTIES"]["USER_ID"]['VALUE']]+=$arItem["PROPERTIES"]["SUMM"]['VALUE'];			
		}
	
	?>	
	<?endforeach;?>	
	<?
        //$f_name = '/local/exel/security_payment_'.$USER->GetID().'.csv';
//        $f_name = 'security_payment_'.$USER->GetID().'.xml';
//        $f_name = 'security_payment_'.$USER->GetID().'.xls';
        ?>
        <div id="report_result" style="margin-top:-90px; margin-bottom:50px;" class="wrapper">
            <a href="javascript:void(0);" class="get_report" title="Выгрузить данные в excel-файл">
                <img src="<?=SITE_TEMPLATE_PATH?>/img/icons/excel.png" alt="Выгрузить данные в excel-файл">
            </a>
        </div>

<?

foreach($users as $key=>$user){
	if($_GET['null_ost'] && in_array(1, $_GET['null_ost']) && ($user_all[$key]-$user_spis[$key]-$user_vozv[$key])){
		$total_all-=$user_all[$key];
		$total_zam-=$user_zam[$key];
		$total_spis-=$user_spis[$key];
		$total_vozv-=$user_vozv[$key];
		unset($users[$key]);
	}
	if($_GET['null_ost'] && in_array(-1, $_GET['null_ost']) && !($user_all[$key]-$user_spis[$key]-$user_vozv[$key])){
		$total_all-=$user_all[$key];
		$total_zam-=$user_zam[$key];
		$total_spis-=$user_spis[$key];
		$total_vozv-=$user_vozv[$key];
		unset($users[$key]);
	}
}

uasort($users, 'cmp');

    $f_name = '/local/exel/security_payment_'.$USER->GetID().'.xlsx';
    if($_REQUEST['create_report']){

        $APPLICATION->RestartBuffer();

        // Create new Spreadsheet object;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('interrao')
            ->setLastModifiedBy('interrao')
            ->setTitle('PhpSpreadsheet Table Test Document')
            ->setSubject('PhpSpreadsheet Table Test Document')
            ->setDescription('Test document for PhpSpreadsheet, generated using PHP classes.')
            ->setKeywords('office PhpSpreadsheet php')
            ->setCategory('Table');

        $styleArray = array(
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN],
                'top' => ['borderStyle' => Border::BORDER_THIN],
                'left' => ['borderStyle' => Border::BORDER_THIN],
                'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            ],
        );

        $table_column_id = ['A', 'B', 'C', 'D', 'E', 'F'];
        $table_column_name = ['Покупатель','Всего поступило', 'Всего списано', 'Всего возвратов', 'ИТОГО остаток', 'Из них заморожено'];
        $spreadsheet->setActiveSheetIndex(0);
        for ($i = 0; $i != count($table_column_id); $i++){
            $sheet->setCellValue($table_column_id[$i].'1', $table_column_name[$i]);
        }
        $dataArray = array();
        foreach($users as $key=>$user){
            $itemData = [];


            $itemData[] = $user;
            $itemData[] = number_format($user_all[$key], 2, ',', ' ');
            $itemData[] = number_format($user_spis[$key], 2, ',', ' ');
            $itemData[] = number_format($user_vozv[$key], 2, ',', ' ');
            $itemData[] = number_format($user_all[$key]-$user_spis[$key]-$user_vozv[$key], 2, ',', ' ');
            $itemData[] = number_format($user_zam[$key], 2, ',', ' ');

            $dataArray[] = $itemData;

        }

        $ar_str = [];
        $ar_str[] = 'ИТОГО';
        $ar_str[] = number_format($total_all, 2, ',', ' ');
        $ar_str[] = number_format($total_spis, 2, ',', ' ');
        $ar_str[] = number_format($total_vozv, 2, ',', ' ');
        $ar_str[] = number_format($total_all-$total_spis-$total_vozv, 2, ',', ' ');
        $ar_str[] = number_format($total_zam, 2, ',', ' ');
        $dataArray[] = $ar_str;

        $sheet->fromArray($dataArray, null, 'A2');

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            // Перебираем ячейки в текущей строке
            foreach ($cellIterator as $cell) {
                $sheet->getStyle($cell->getCoordinate())->applyFromArray($styleArray);

                if (is_numeric($cell->getValue()) || strpos($cell->getValue() ,',') !== false){
                    $sheet->getStyle($cell->getCoordinate())->getNumberFormat()
                        ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                    $sheet->getStyle($cell->getCoordinate())->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
            }
        }

        $sheet->getColumnDimension('A')->setWidth(60);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);

        // Create Table
        $tablerange = count($dataArray) + 1;
        $table = new Table('A1:'.end($table_column_id).$tablerange, 'spayment');
        $tableStyle = new TableStyle();
        $table->setStyle($tableStyle);
        $sheet->addTable($table);
        $writer = new Xlsx($spreadsheet);
        mb_internal_encoding('latin1');
        $writer->save($_SERVER["DOCUMENT_ROOT"].$f_name);//

        echo json_encode(Array('status'=>'completed'));

        die();
    }
    ?>
		<div  class="row" style="background-color:#fff; padding:13px; margin-top:10px">
		
		<table>		
			<tr>
				<th><span class="gray small">Покупатель</span></th>
				<th align="center"><span class="gray small">Всего поступило</span></th>				
				<th align="center"><span class="gray small">Всего списано</span></th>
				<th align="center"><span class="gray small">Всего возвратов</span></th>
				<th align="center"><span class="gray small">ИТОГО остаток</span></th>
				<th align="center"><span class="gray small">Из них заморожено</span></th>
			</tr>
			<?

			foreach($users as $key=>$user):
			//if(!($user_all[$key]-$user_zam[$key]-$user_spis[$key]-$user_vozv[$key])) continue;
			$link = '/lk/security_payment/?type=pds&arrFilterDS_148_'.abs(crc32($key)).'=Y&set_filter=Показать';
			?>
			<tr>
				<td><a href="<?=$link?>" target="_blank"><span class="gray small"><?=$user?></span></a></td>
				<td align="right"><?=number_format($user_all[$key], 2, ',', ' ');?></td>
				<td align="right"><?=number_format($user_spis[$key], 2, ',', ' ');?></td>
				<td align="right"><?=number_format($user_vozv[$key], 2, ',', ' ');?></td>
				<td align="right"><?=number_format($user_all[$key]-$user_spis[$key]-$user_vozv[$key], 2, ',', ' ');?></td>
				<td align="right"><?=number_format($user_zam[$key], 2, ',', ' ');?></td>
			</tr>
			<?endforeach;?>
			<tr>
				<th><span class="gray small">ИТОГО</span></th>
				<td align="right"><?=number_format($total_all, 2, ',', ' ');?></td>
				<td align="right"><?=number_format($total_spis, 2, ',', ' ');?></td>
				<td align="right"><?=number_format($total_vozv, 2, ',', ' ');?></td>
				<td align="right"><?=number_format($total_all-$total_spis-$total_vozv, 2, ',', ' ');?></td>
				<td align="right"><?=number_format($total_zam, 2, ',', ' ');?></td>
			</tr>
		</table>
		
		</div>
	
	<br>	
<?endif;?>

<script>
    function get_report(i) {
        $.ajax({
            url: '<?=$APPLICATION->GetCurPageParam('create_report=Y')?>',
            type: 'get',
            data: {
                page: i
            }
        }).done(function (res) {
            console.log(res);
            var ar_res = $.parseJSON(res);
            if(ar_res.status=='run'){
                setTimeout(function(){ get_report(ar_res.page);}, 100);
                $('#report_result').html('Формируется файл отчета...'+ar_res.percent+'%');
            }else if(ar_res.status=='completed'){
                $('#report_result').html('Скачивание файла должно начаться автоматически, если этого не произошло нажмите кнопку: <a download href="<?=$f_name?>" class="btn btn-small btn-blue bold" id="download_report">скачать файл</a>');
                $(".get_report").show();
                window.location.href = '<?=$f_name?>';
            }else{
                $('#report_result').html('Произошла ошибка: '+res);
            }

        });
    }
    $(".get_report").click(function() {
        get_report(0);
        $(this).remove();
        $('#report_result').css("margin-top", "-82px");
        $('#report_result').css("margin-bottom", "55px");
        $('#report_result').html('Формируется файл отчета...0%');
    });
</script>
