<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

 $app->withFacades();

 $app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

//Sentry registration
//$app->register('Sentry\Laravel\ServiceProvider');


$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);


$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/
$app->configure('app');
$app->configure('database');
/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/
//my contribution
//my contribution
$app->bind(Illuminate\Session\SessionManager::class, function ($app) {
    return $app->make('session');
});

/*$app->singleton('cookie', function () use ($app) {
    return $app->loadComponent('session', 'Illuminate\Cookie\CookieServiceProvider', 'cookie');
});
//my contribuion
$app->bind('Illuminate\Contracts\Cookie\QueueingFactory', 'cookie');
$app->middleware([
    'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
    'Illuminate\Session\Middleware\StartSession',
    'Illuminate\View\Middleware\ShareErrorsFromSession',
]);
*/


$app->configure('session');


 $app->middleware([
     App\Http\Middleware\ExampleMiddleware::class
 ]);

 $app->routeMiddleware([
     'auth' => App\Http\Middleware\Authenticate::class,
 ]);
 $app->middleware([
    'Illuminate\Session\Middleware\StartSession'
]);

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class
 ]);
/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/
$app->withFacades(true, [
    'Illuminate\Support\Facades\Mail' => 'Mail',
]);
//excel registration
//$app->register(Maatwebsite\Excel\ExcelServiceProvider::class);
//$excel = App::make('excel');

//$app->register(Cyberduck\LaravelExcel\ExcelLegacyServiceProvider::class);
//$app->register(Cyberduck\LaravelExcel\ExcelServiceProvider::class);
//$app->register(Intervention\Image\ImageServiceProvider::class);
//$app->register(Illuminate\Foundation\AliasLoader::class);
//$app->Image(Intervention\Image\Facades\Image::class);
//$app->register(Maatwebsite\Excel\ExcelServiceProvider::class);
//$app->register(illuminate\Felixkiss\UniqueWithValidator\ServiceProvider::class);
//$app->register(\Barryvdh\DomPDF\ServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);
$app->configure('mail');
$app->register(Nexmo\Laravel\NexmoServiceProvider::class);
$app->alias('mailer', Illuminate\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\MailQueue::class);
//$app->register(App\Providers\AppServiceProvider::class);
//$app->register(App\Http\Middleware\Role::class);
//$app->register(Spatie\Permission\PermissionServiceProvider::class);
 $app->register(App\Providers\AuthServiceProvider::class);
 $app->register(App\Providers\EventServiceProvider::class);
 $app->register(Illuminate\Session\SessionServiceProvider::class);
 $app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
 $app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
]);
$app->routeMiddleware([
    'acl' => App\Http\Middleware\Acl::class,
]);
$app->routeMiddleware([
    'custome' => App\Http\Middleware\Customedomain::class,
]);
//$app->middleware([
  //  App\Http\Middleware\FileCreate::class
 //]);
 $app->routeMiddleware([
    'create' => App\Http\Middleware\FileCreate::class,
]);
//$app->routeMiddleware([
  //  'auth1' => App\Http\Middleware\AuthRoles::class,
//]);
$app->singleton(Illuminate\Session\SessionManager::class, function () use ($app) {
    return $app->loadComponent('session', Illuminate\Session\SessionServiceProvider::class, 'session');
});

$app->singleton('session.store', function () use ($app) {
    return $app->loadComponent('session', Illuminate\Session\SessionServiceProvider::class, 'session.store');
});
/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {

    require __DIR__.'/../routes/web.php';
});
require_once __DIR__.'/Constants.php';
return $app;
