<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use Bitrix\Main\Routing\Controllers\PublicPageController;

return function (RoutingConfigurator $routes) {
    $routes->any('/catalog/{section}/*', new PublicPageController('/pages/catalog/index.php'))->where('section', '.*');
    $routes->post('/auth/', function() {
        global $APPLICATION;
        $APPLICATION->IncludeComponent(
            "bitrix:system.auth.authorize",
            "phone",
            Array(
            )
        );
    });
};