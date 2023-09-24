<?php

// PHP
declare(strict_types=1);

// Application
define('APP_VERSION', '2.0.0');
define('API_VERSION', APP_VERSION);

// Init environment
if (!file_exists(__DIR__ . '/.env'))
{
  if ($handle = fopen(__DIR__ . '/.env', 'w+'))
  {
    fwrite($handle, 'default');
    fclose($handle);

    chmod(__DIR__ . '/.env', 0770);
  }

  else exit (_('Could not init environment file. Please check permissions.'));
}

define('PHP_ENV', file_get_contents(__DIR__ . '/.env'));

// Init config
if (!file_exists(__DIR__ . '/env.' . PHP_ENV . '.php'))
{
  if (copy(__DIR__ . '/../../example/environment/env.example.php',
           __DIR__ . '/env.' . PHP_ENV . '.php'))
  {
     chmod(__DIR__ . '/env.' . PHP_ENV . '.php', 0770);
  }

  else exit (_('Could not init configuration file. Please check permissions.'));
}

// Load environment
require_once __DIR__ . '/env.' . PHP_ENV . '.php';

// Autoload vendors
require_once __DIR__ . '/../../vendor/autoload.php';

// Route
parse_str($_SERVER['QUERY_STRING'], $request);

if (isset($request['_route_']))
{
  switch ($request['_route_'])
  {
    case 'stars':

      require_once(__DIR__ . '/../app/controller/stars.php');

      $controller = new AppControllerStars();

    break;

    case 'views':

      require_once(__DIR__ . '/../app/controller/views.php');

      $controller = new AppControllerViews();

    break;

    case 'downloads':

      require_once(__DIR__ . '/../app/controller/downloads.php');

      $controller = new AppControllerDownloads();

    break;

    case 'comments':

      require_once(__DIR__ . '/../app/controller/comments.php');

      $controller = new AppControllerComments();

    break;

    case 'editions':

      require_once(__DIR__ . '/../app/controller/editions.php');

      $controller = new AppControllerEditions();

    break;

    case 'welcome':

      require_once(__DIR__ . '/../app/controller/welcome.php');

      $controller = new AppControllerWelcome();

    break;

    case 'submit':

      require_once(__DIR__ . '/../app/model/validator.php');

      $validator = new AppModelValidator(
        json_decode(
          file_get_contents(
            __DIR__ . '/../config/validator.json'
          )
        )
      );

      require_once(__DIR__ . '/../app/controller/submit.php');

      $controller = new AppControllerSubmit(
        $validator
      );

    break;

    default:

      require_once(__DIR__ . '/../app/controller/response.php');

      $controller = new AppControllerResponse(
        sprintf(
          _('404 - Not found - %s'),
          WEBSITE_NAME
        ),
        _('404'),
        _('Page not found'),
        404
      );
  }
}

else
{
  require_once(__DIR__ . '/../app/controller/index.php');

  $controller = new AppControllerIndex();
}

$controller->render();