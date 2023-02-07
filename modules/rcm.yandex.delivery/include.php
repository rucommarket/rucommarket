<?php
\Bitrix\Main\Loader::registerAutoloadClasses(
   "rcm.yandex.delivery",
   array(
      '\RCM\Yandex\Delivery\Handlers' => "lib/Handlers.php",
      '\RCM\Yandex\Delivery\DeliveryHandler' => "lib/DeliveryHandler.php",
      '\RCM\Yandex\Delivery\Connect' => "lib/Connect.php",
   )
);