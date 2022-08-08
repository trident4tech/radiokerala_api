<?php
 
namespace App\Console\Commands;
use Maatwebsite\Excel\Facades\Excel; 
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
 
class CreateExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:excel';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create excel for users..';
 
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
 
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        $result=DB::table('downloads')->select('*')->where('download_status' , '=', '2')->get();
        foreach($result as $re){
            $user=$re->download_usr_id;
            $id=$re->download_id;
            $filename=$re->download__filename;
            $str= File::get(public_path($filename));
            echo $str;
            $fileNameNew=$user.'_'.rand().'.xls';
            file_put_contents('public/'.$fileNameNew, $str);
            $array['download_url']=public_path().'/'.$fileNameNew;
            $array['download_status']='1';
            DB::table('downloads' )->where('download_id', $id)->update($array);

        }
    }
}
