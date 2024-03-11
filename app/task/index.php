<?php

/**
 * @global  \CMain $APPLICATION
 * @global  \CUser $USER
 */

use Bitrix\Intranet\Integration\Wizards\Portal\Ids;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->SetPageProperty('NOT_SHOW_NAV_CHAIN', 'Y');
$APPLICATION->SetPageProperty('title', htmlspecialcharsbx(COption::GetOptionString('main', 'site_name', 'Bitrix24')));
\CModule::IncludeModule('intranet');

if (SITE_TEMPLATE_ID !== 'bitrix24')
{
    return;
}

$APPLICATION->IncludeComponent(
    "app:task",
    "",
    [],
    false
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');


/*

 Подключение Стилей и файлов JS
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/js/select2/select2.css");
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/test.js");
CJSCore::Init( array( 'fx', 'popup', 'date','window', 'ajax' ) );

Определение кодировки и встроенных стилей
<?$APPLICATION->ShowHead();?>

Вывод заголовка страницы
<title><?$APPLICATION->ShowTitle()?></title>

Вывод админской панели
<?=$APPLICATION->ShowPanel();?>



 */