<?php
 
namespace App\Console\Commands;
use Maatwebsite\Excel\Facades\Excel; 
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Nexmo\Laravel\Facade\Nexmo; 
use Carbon\Carbon;
//use Ixudra\Curl\Facades\Curl;
class CollectionDeposit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collection:deposit';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collection deposit every day';
 
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

       $result =  DB::select('SELECT to_char(ticket_print.tp_date, '."'DD/MM/YYYY'".') AS date,destination.dest_name AS branch,attraction.attr_name AS "attraction",class.class_name AS "ticketclass",class.class_id AS "ticket7classid",SUM(ticket_class.tc_number::bigint) AS "ticket",SUM(ticket_class.total_rate::numeric(13,2)) AS "amount"
        FROM ticket_print 
            LEFT JOIN ticket_class ON ticket_class.tc_tp_id=ticket_print.tp_id
            LEFT JOIN class ON class.class_id=ticket_class.tc_class_id  
            LEFT JOIN attraction ON attraction.attr_id=class.class_attr_id  
            LEFT JOIN destination ON destination.dest_id=attraction.attr_dest_id
            WHERE ticket_print.deleted_at IS NULL AND tp_actual_number IS NOT NULL AND ticket_print.tp_is_cancelled=1 AND ticket_print.tp_date='.Carbon::yesterday()->format('Y-m-d').'
            GROUP BY ticket_print.tp_date,class.class_name,class.class_id,attraction.attr_name,destination.dest_name ORDER BY ticket_print.tp_date');
        $data = array();
        $data['date'] = Carbon::yesterday()->format('Y-m-d');
        $data['key'] = '12asr78sa45hy67';
        foreach($result as $re){
            $data['result'][] = array('collectiondate'=>$re->date,'branch'=>$re->branch,'amount'=>$re->amount,'attraction'=>$re->attraction,'class'=>$re->ticketclass,'ticket'=>$re->ticket,'ticketclassid'=>$re->ticketclassid);
        }

        $ch = curl_init(FMIS_URL.'/cronforbranch/collection');
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch); 
        curl_close($ch);
        $resultData = json_decode($response);
        if ($resultData['Status'] == 2) {
            foreach ($resultData['data'] as $key => $value) {
                $dbArray = array('class_fmis_id'=>$value);
                DB::table('class')->where('class_id', $key)->update($dbArray);       
            }
        }

    }
    

}

