<?php
\Bitrix\Main\Loader::registerAutoloadClasses(
   "rcm.purchase.limit",
   array(
      '\RCM\Purchase\Limit\Handlers' => "lib/handlers.php",
      '\RCM\Purchase\Limit\Internals\UsersTable' => "lib/internals/users.php",
   )
);