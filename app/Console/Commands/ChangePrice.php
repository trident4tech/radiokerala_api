<?php
 
namespace App\Console\Commands;
use Maatwebsite\Excel\Facades\Excel; 
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Nexmo\Laravel\Facade\Nexmo; 
use Carbon\Carbon;
class ChangePrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:price';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change price in each day';
 
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
        $priceDataFrom = DB::table('price')->select('*')->where('price_from_date', '<=', Carbon::now())->get();
        $priceDataTo = DB::table('price')->select('*')->where('price_to_date', '>=', Carbon::now())->orwhere('price_to_date', '=', NULL)->get();
        //dd($priceData1);
        foreach($priceDataFrom as $from){
            foreach($priceDataTo as $to){
                if($from->price_id == $to->price_id){
                    $dbArray['class_rate']=$to->price_base_rate;
                    DB::table('class')->where('class_id', $to->price_class_id)->update($dbArray);
                }

            }
        }
    }
    

}

