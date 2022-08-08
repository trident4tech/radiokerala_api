<?php

namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Laminas\Config\Reader\Ini;
use App\Libraries\Inii;
use DB;

use Closure;

class Customedomain
{
    private $object;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $host = request()->getHttpHost();
        $this->reader = new Ini();
        $this->filename=storage_path("custome.ini");
        $this->ini   = $this->reader->fromFile($this->filename);
        $domain = str_replace(".", "-", $host);
        $this->object = (object)$this->ini[$domain];
        //DB::disconnect('pgsql');
        //DB::purge('mysql'); // purge instead of disconnect
       // DB::setDefaultConnection('database.connections.tvm');
      //  Config::set('database.connections.mysql.database', 'db_1');


        /*$path = base_path('.env');
        $data = array();
        $data['DB_DATABASE']['current'] = env('DB_DATABASE');
        $data['DB_DATABASE']['new'] = $this->object->dbname;
        $data['DB_USERNAME']['current'] = env('DB_USERNAME');
        $data['DB_USERNAME']['new'] = $this->object->dbusername;
        $data['DB_PASSWORD']['current'] = env('DB_PASSWORD');
        $data['DB_PASSWORD']['new'] = $this->object->dbpassword;
        $data['APP_URL']['current'] = env('APP_URL');
        $data['APP_URL']['new'] = $request->getSchemeAndHttpHost().'/';
        foreach ($data as $key => $value) {
             if (file_exists($path)) {
                // file_put_contents($path, str_replace(
                //     "$key=".$value['current'], "$key=".$value['new'], file_get_contents($path)
                // ));
            }
        }     */  
        return response()->json(array('Status' => OT_YES, 'Feedback' => 'Success'));
        return $next($request);
    }
}
