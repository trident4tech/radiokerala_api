<?php
use Illuminate\Support\Str;
use Laminas\Config\Reader\Ini;




try{
    $serverUrlWithdots = $_SERVER['SERVER_NAME'];
    $serverUrldotRemoved = str_replace( '.' , '' , $serverUrlWithdots );
    $this->reader = new Ini();
    $this->filename = storage_path("dbconfig.ini");
    $this->ini = $this->reader->fromFile($this->filename);
    $configArray = $this->ini [ $serverUrldotRemoved ];
    $DB_CONNECTION = $configArray [ 'DB_CONNECTION'];
    $DB_HOST = $configArray [ 'DB_HOST'];
    $DB_PORT = $configArray [ 'DB_PORT'];
    $DB_DATABASE = $configArray [ 'DB_DATABASE'];
    $DB_USERNAME = $configArray [ 'DB_USERNAME'];
    $DB_PASSWORD = $configArray [ 'DB_PASSWORD'];
}catch( \Exception $e ){
    $DB_CONNECTION = env('DB_CONNECTION');
    $DB_HOST = env('DB_HOST');
    $DB_PORT = env('DB_PORT');
    $DB_DATABASE = env('DB_DATABASE');
    $DB_USERNAME = env('DB_USERNAME');
    $DB_PASSWORD = env('DB_PASSWORD');

}

//$dataBaseName = 

return [

    'default' => $DB_CONNECTION,
    'connections' => [
        $DB_CONNECTION => [
            'driver' => $DB_CONNECTION,
            'host' => $DB_HOST,
            'port' => $DB_PORT,
            'database' => $DB_DATABASE,
            'username' => $DB_USERNAME,
            'password' => $DB_PASSWORD,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

    ],









    
    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];


  