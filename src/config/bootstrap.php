<?php

// PHP
declare(strict_types=1);

// Debug
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Application
define('APP_VERSION', '2.0.0');
define('API_VERSION', APP_VERSION);
define('CSS_VERSION', APP_VERSION);

// Environment
require_once __DIR__ . '/../library/environment.php';

// Autoload
require_once __DIR__ . '/../../vendor/autoload.php';

// Route
parse_str($_SERVER['QUERY_STRING'], $request);

if (isset($request['_route_']))
{
  switch ($request['_route_'])
  {
    case 'stars':

      require_once __DIR__ . '/../app/controller/stars.php';

      $appControllerStars = new AppControllerStars();

      $appControllerStars->render();

    break;

    case 'views':

      require_once __DIR__ . '/../app/controller/views.php';

      $appControllerViews = new AppControllerViews();

      $appControllerViews->render();

    break;

    case 'downloads':

      require_once __DIR__ . '/../app/controller/downloads.php';

      $appControllerDownloads = new AppControllerDownloads();

      $appControllerDownloads->render();

    break;

    case 'comments':

      require_once __DIR__ . '/../app/controller/comments.php';

      $appControllerComments = new AppControllerComments();

      $appControllerComments->render();

    break;

    case 'editions':

      require_once __DIR__ . '/../app/controller/editions.php';

      $appControllerEditions = new AppControllerEditions();

      $appControllerEditions->render();

    break;

    case 'submit':

      require_once __DIR__ . '/../app/model/database.php';
      require_once __DIR__ . '/../app/model/validator.php';
      require_once __DIR__ . '/../app/model/locale.php';
      require_once __DIR__ . '/../app/model/website.php';
      require_once __DIR__ . '/../app/model/session.php';
      require_once __DIR__ . '/../app/model/request.php';

      require_once __DIR__ . '/../app/controller/page.php';

      $appControllerPage = new AppControllerPage(
        new AppModelDatabase(
          Environment::config('database')
        ),
        new AppModelValidator(
          Environment::config('validator')
        ),
        new AppModelLocale(
          Environment::config('locales')
        ),
        new AppModelWebsite(
          Environment::config('website')
        ),
        new AppModelSession(
          $_SERVER['REMOTE_ADDR']
        ),
        new AppModelRequest(
          $_GET,
          $_POST,
          $_FILES
        )
      );

      $appControllerPage->renderFormSubmit();

    break;

    default:

      require_once __DIR__ . '/../app/controller/response.php';

      $appControllerResponse = new AppControllerResponse(
        sprintf(
          _('404 - Not found - %s'),
          Environment::config('website')->name
        ),
        _('404'),
        _('Page not found'),
        404
      );

      $appControllerResponse->render();
  }
}

else
{
  require_once __DIR__ . '/../app/model/database.php';
  require_once __DIR__ . '/../app/model/validator.php';
  require_once __DIR__ . '/../app/model/website.php';
  require_once __DIR__ . '/../app/model/session.php';

  require_once __DIR__ . '/../app/controller/index.php';

  $appControllerIndex = new AppControllerIndex(
    new AppModelDatabase(
      Environment::config('database')
    ),
    new AppModelValidator(
      Environment::config('validator')
    ),
    new AppModelWebsite(
      Environment::config('website')
    ),
    new AppModelSession(
      $_SERVER['REMOTE_ADDR']
    )
  );

  $appControllerIndex->render();
}
