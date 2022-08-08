<?php
namespace App\Http\Middleware;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Permission;
use App\Role;
use Closure;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
//use Illuminate\Contracts\Auth\Factory as Auth;
class FileCreate extends Controller{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
  public function handle($request, Closure $next) {
    $response = $next($request);
    if($request->input('is_download') == 'yes'){
      $this->validate($request, ['id' => 'integer|required' ]); //basic validation
      $fileName=$request->input('id').'_'.rand().'.html';
      $id=$request->input('id');
      $mytime = Carbon::now();
      $time=$mytime->toDateTimeString();
      $data = $response->getData();
      //$data=(object)$data;
      $rows = array();
      $i=OT_ZERO;
      $data = $response->getData();
      //create heading of table
      $object=(object)$data;
      $dataArray=$object->Data;
      $dataArray=(array)$dataArray;
      //dd($dataArray);
      $heading='<tr>';
      
         
      foreach($dataArray[0] as $key => $value){
        $heading = $heading."<th>{$key}</th>";
      } 
      $heading=$heading.'</tr>';
      //end heading
      //create table data fields
      foreach ($dataArray as  $row) {
          foreach ($row as $cell) {
             $cells[] = "<td>{$cell}</td>";
          }
          $rows[] = "<tr>" . implode('', $cells) . "</tr>";
          $cells=array();          
      }
      $str= "<table border=1>".$heading. implode('', $rows) . "</table>";
      DB::table('downloads')->insert(['download_url' => 'p', 'download__filename' => $fileName, 'download_usr_id' => $id, 'download_time' => $time]);
      file_put_contents($fileName, $str);
    }
    return $response;   
  }
 }