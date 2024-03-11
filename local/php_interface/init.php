<?php

use \Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;

Loader::registerAutoLoadClasses(null, array(
    "\Loc\ChangePriority" => "/local/classes/change_priority.php",
));