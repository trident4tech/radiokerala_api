<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laminas\Config\Reader\Ini;
use Illuminate\Http\Request;
use App\User;
use App\Library\SimpleXLSX;
use App\Libraries\Inii;
use App\Usergroup;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
class MessageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request) {
    }

    public function send(Request $request){
        $this->validate($request, ['type' => 'required|in:1,2','message' => 'required']);
        if($request->input('type') == OT_NO){
            /*email*/
            $this->validate($request, ['to' => 'required|email','subject' => 'required']);
            $dbArray['smq_recipient']=$request->input('to');
            $dbArray['subject']=$request->input('subject');

        }
        else if($request->input('type') == OT_YES){
            /*sms*/
            $this->validate($request, ['to' => 'required|regex:/[0-9]{9}/']);
            $dbArray['smq_to']=$request->input('to');
        }
        $dbArray['message']=$request->input('message');
        DB::table('sms_mail_que')->insert($dbArray);
        return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Message added to queue']);
    }

}
