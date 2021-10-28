<?php
$eventManager->addEventHandler('main', 'OnPageStart', array('Custom\PublicSection\Jsinit', 'addJsLibrary'), false, 100);