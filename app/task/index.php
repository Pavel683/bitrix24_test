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
    "my_components:new.tasks.list",
    "",
    [],
    false
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');