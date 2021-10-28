<?php
\Bitrix\Main\Loader::registerAutoloadClasses(
   "ac.api",
   array(
      '\AC\Api\Internals\ProjectsTable' => "lib/internals/projects.php",
      '\AC\Api\Internals\RequestsTable' => "lib/internals/requests.php",
      '\AC\Api\Projects' => "lib/projects.php",
      '\AC\Api\Handlers' => "lib/handlers.php",
      '\AC\Api\Error' => "lib/error.php",
   )
);