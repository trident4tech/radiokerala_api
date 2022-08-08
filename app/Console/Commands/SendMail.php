<?php

namespace App\Console\Commands;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;

class SendMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send mail from mail queue';

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
        $result=DB::table('sms_mail_que')->select('*')->where('mail_status' , '=', '1')->get();
        foreach($result as $re){
            $to=$re->smq_recipient;
            $subject=$re->subject;
            $message=$re->message;
            try{
                $id=$re->smq_id;
                $fileNameArray=DB::select('select * from  mail_file where mf_smq_id= '.$id);
                $mailer = app()['mailer'];
                $data=array('to' => $to,'subject' => $subject,'message' => $message);
                $mailer->send([],[], function ($message) use($data, $fileNameArray){
                    $message->from(OT_MAIL_FROM);
                    $message->to($data['to'])->subject($data['subject']);
                    $message->setBody($data['message']);
                    foreach($fileNameArray as $file){
                        $message->attach($file->mf_file, array());
                    }
                });
                DB::table('sms_mail_que')->where('smq_id', $id)->update(['mail_status' => '2']);
            }
            catch(\Exception $e){
                //echo $e;
                DB::table('sms_mail_que')->where('smq_id', $id)->update(['mail_status' => '3']);
            }
        }
    }
}

