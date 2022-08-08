<?php

namespace App\Console\Commands;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Nexmo\Laravel\Facade\Nexmo;
class SendSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send sms';

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
        $result=DB::table('sms_mail_que')->select('*')->where('sms_status' , '=', '1')->get();
        foreach($result as $re){
            echo $to=$re->smq_to;
            $subject=$re->message;
            $id=$re->smq_id;
            try{
                Nexmo::message()->send([
                    'to'   => "$to",
                    'from' => OT_FROM,
                    'text' =>  "$subject"
                ]);
                DB::table('sms_mail_que')->where('smq_id', $id)->update(['sms_status' => '2']);
            }
            catch (\Exception $e) {
                //echo $e;
                DB::table('sms_mail_que')->where('smq_id', $id)->update(['sms_status' => '3']);
            }
        }
    }
}
