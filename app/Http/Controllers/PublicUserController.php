<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\PublicUser;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\DecryptException;
use File;

class PublicUserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void

     */
    private $paginator = 'p';
    public function __construct(Request $request)
    {
        $this->validate($request, [$this->paginator => 'integer']);
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/11/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <public user register>
    * Name: API public user register;
    * Description: public user user registration Function;
    * Action Type: Application;
    * Category: Manage;
    * </ public user register>

    */
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|String',
            'email' => 'required|email|unique:public_users,pusr_email',
            'password' => 'required',
            'usermobile' => 'unique:public_users,pusr_mobile|required|regex:/[0-9]{9}/',
        ]);
        try {
            $user = new PublicUser;
            $user->pusr_name = $request->input('name');
            $user->pusr_email = $request->input('email');
            $user->pusr_uname = $request->input('usermobile');
            $user->pusr_mobile = $request->input('usermobile');
            $password = $request->input('password');
            $user->pusr_pass = app('hash')->make($password);
            if ($user->save())
                return response(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Public user created successfully']);
            else
                return response(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Error while Creating User']);
        } catch (Exception $e) {
            return response(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'RunTime Exception occured']);
        }
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/11/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <Login>
    * Name: Login;
    * Description: User Login here;
    * Action Type: Application;
    * Category: Manage;
    * </Login>
    */
    public function Login(Request $request)
    {
        $this->validate($request, ['uname' => 'required|exists:public_users,pusr_uname', 'password' => 'required']); //basic validation
        $password1 = app('hash')->make($request->input('password'));
        $password = PublicUser::select('pusr_pass', 'pusr_id', 'pusr_name', 'pusr_email', 'pusr_mobile')
            ->where('pusr_uname', '=', $request->input('uname'))
            ->first();

        if (Hash::check($request->input('password'), $password->pusr_pass)) {
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success', 'userId' => $password->pusr_id, 'userName' => $password->pusr_name, 'useremail' => $password->pusr_email, 'userContact' => $password->pusr_mobile]);
        } else {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Credential missmatch']);
        }
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 05/11/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <public user logout>
    * Name: public user logout;
    * Description: all api logout request execute here;
    * Action Type: Application;
    * Category: Manage;
    * </logout>

    */
    public function Logout(Request $request)
    {
        $session = $request->session(); //create session variable
        $user_email = $session->get('userid'); //strore email for display success message
        return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Logout Successfull']);
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/11/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <edit public user>
    * Name: Edit;
    * Description: Edit public user;
    * Action Type: Application;
    * Category: Manage;
    * </Edit >

    */

    public function edit(Request $request)
    {
        $this->validate($request, ['userid' => 'required|integer|exists:public_users,pusr_id',]);
        $user = PublicUser::find($request->input('userid'));
        if ($user != '') {
            if ($request->input('name') != '')
                $user->pusr_name = $request->input('name');
            if ($request->input('email') != '')
                $user->pusr_email = $request->input('email');
            $user->save();
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Successfully Updated']);
        } else {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'User_ID Not Valid']);
        }
    }


    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/11/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <forget password>
    * Name: Status;
    * Description: forget password
    * Action Type: Application;
    * Category: Manage;
    * </forgetpassword>

    */


    public function forget(Request $request)
    {
        $this->validate($request, ['email' => 'email|required|exists:public_users,pusr_email']); //basic validation
        try {
            $userId = PublicUser::select('pusr_id')->where('pusr_email', '=', $request->input('email'))->first();
            if ($userId != NULL) {
                $userId = $userId->pusr_id;
                $user = PublicUser::find($userId);
                $data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
                $password = substr(str_shuffle($data), 0, 9);
                $password1 = app('hash')->make($password);
                $user->pusr_pass = $password1;
                $user->save();
                $emailData['email'] = $request->input('email');
                $emailData['subject'] = 'New Password';
                $emailData['body'] = 'Your new password for login to DTPC wayanad tourism is :' . $password;
                sendMail($emailData);
                return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'reset password send']);
            } else {
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'email  valid']);
            }
        } catch (Exception $e) {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Id is not  valid']);
        }
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/11/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <reset password >
    * Name: Status;
    * Description: resetpassword
    * Action Type: Application;
    * Category: Manage;
    * </reset password>

    */

    public function reset(Request $request)
    {
        $this->validate($request, ['userid' => 'required|exists:public_users,pusr_id', 'password' => 'required|min:6', 'newpassword' => 'required|min:6']);
        try {
            $userInfo = PublicUser::select('pusr_pass', 'pusr_id')
                ->where('pusr_id', '=', $request->input('userid'))
                ->first();

            if (Hash::check($request->input('password'), $userInfo->pusr_pass)) {

                $password = $request->input('newpassword');
                $password1 = app('hash')->make($password);
                $dbArray['pusr_pass'] = $password1;
                DB::table('public_users')->where('pusr_id', $request->input('userid'))->update($dbArray);
                return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'successfully updated']);
            } else {
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'You have enetered incorrect password']);
            }
        } catch (\Exception $e) {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'RunTime Exception Occured']);
        }
    }


    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 05/08/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <public user view >
    * Name: Status;
    * Description: resetpassword
    * Action Type: Application;
    * Category: Manage;
    * </public user view>

    */

    public function view(Request $request)
    {
        $this->validate($request, ['id' => 'required|exists:public_users,pusr_id']); //basic validation
        try {

            $userData = PublicUser::select('*')->where('pusr_id', '=', $request->input('id'))->first();
            $bookingInfo = array();
            $summaryDetails = DB::table('ticket_print')
                ->leftjoin('tickets', 'tickets.ticket_id', '=', 'ticket_print.tp_ticket_id')
                ->leftjoin('public_users', 'public_users.pusr_id', '=', 'tickets.ticket_pusr_id')
                ->leftjoin('destination', 'destination.dest_id', '=', 'ticket_print.tp_dest_id')
                ->where('ticket_pusr_id', '=', $request->input('id'))
                ->orderBy('ticket_id', 'desc')
                ->orderBy('tp_id')
                ->select('ticket_print.*', 'tickets.*', 'public_users.*', 'destination.*')->get();
            $i = -1;
            $bookings = array();
            foreach ($summaryDetails as $data) {
                if (!in_array($data->ticket_id, $bookings)) {
                    $i++;
                    $bookings[] = $data->ticket_id;
                    $j = 0;
                }
                $bookingInfo[$i]['ticket'] = $data->ticket_id;
                $bookingInfo[$i]['bdate'] = date('d-m-Y', strtotime($data->ticket_book_date));
                $bookingInfo[$i]['vdate'] = date('d-m-Y', strtotime($data->date));
                //                   $date1 = Carbon::createFromFormat('Y/m/d H:i:s', $data->date);
                //                   $date2 = Carbon::createFromFormat('d/m/Y H:i:s', date('d/m/Y H:i:s'));
                //                   $result = $date1->lt($date2);
                //                   if ($result)
                //                     $bookingInfo[$i]['vstatus'] = 'Completed';
                //                   else
                $bookingInfo[$i]['vstatus'] = 'upcoming';
                $bookingInfo[$i]['cost'] = $data->total_rate;
                $bookingInfo[$i]['bstatus'] = $data->ticket_payment_status;
                $paymentValues = array(1 => 'Pending', '2' => 'Paid', '3' => 'Failed');
                $bookingInfo[$i]['bstatustext'] = $paymentValues[$data->ticket_payment_status];
                $bookingInfo[$i]['ticketfile'] = $data->ticket_file;
                $bookingInfo[$i]['name'] = $data->customer_name;
                $bookingInfo[$i]['contact'] = $data->customer_mobile;
                $bookingInfo[$i]['email'] = $data->customer_email;
                $bookingInfo[$i]['data'] = $data;
                $bookingInfo[$i]['ticketdata'][$j]['tpid'] = $data->tp_id;
                $bookingInfo[$i]['ticketdata'][$j]['dest'] = $data->dest_name;
                $bookingInfo[$i]['ticketdata'][$j]['amount'] = $data->tp_rate;
                $bookingInfo[$i]['ticketdata'][$j]['ticket'] = $data->tp_actual_number;
                $bookingInfo[$i]['ticketdata'][$j]['content'] = $data->tp_content;
                $bookingInfo[$i]['ticketdata'][$j]['file'] = $data->tp_file;
                $j++;
            }
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success', 'Data' => $userData, 'Summary' => $bookingInfo]);
        } catch (Exception $e) {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'RunTime Exception Occured']);
        }
    }
    /*
    * @author Arya Krishnan
    * @copyright Origami Technologies
    * @created 05/07/2021
    * @license http://www.origamitechnologies.com
    * @aclinfo <destinations>
    * Name: Destinations;
    * Description: Show the list of destinations;
    * Action Type: Application;
    * Category: Manage;
    * </destinations>

    */

    public function destinations(Request $request)
    {
        $this->validate($request, ['bookdate' => 'required', 'time' => 'required']); //basic validation
        try {
            $bookdate = $request->input('bookdate');
            $time = $request->input('time');
            $destData = DB::table('destination')
                ->leftjoin('attraction', 'attraction.attr_dest_id', '=', 'dest_id')
                ->leftjoin('class', 'class.class_attr_id', '=', 'attraction.attr_id')
                ->where('destination.status', '=', OT_YES)
                ->where('attraction.status', '=', OT_YES)
                ->where('class.status', '=', OT_YES)
                ->whereNull('attraction.deleted_at')
                ->whereNull('destination.deleted_at')
                ->where('dest_is_public', '=', OT_YES)
                ->where('dest_book_start_time', '<=', $time)
                ->where('dest_book_end_time', '>=', $time)
                ->where('attr_is_allow_public', '=', OT_YES)
                ->orderBy('dest_name')->whereNull('class.deleted_at')
                ->select('destination.*', 'attraction.*', 'class.*')->get();
            $details = array();
            $i = 0;
            $dest = array();
            foreach ($destData as $data) {
                if (!in_array($data->dest_id, $dest)) {
                    $dest[$i] = $data->dest_id;
                    $j = 0;
                    $k = $i;
                    $i++;
                }
                $details[$k]['dest_id'] = $data->dest_id;
                $details[$k]['dest_code'] = $data->dest_code;
                $details[$k]['dest_name'] = $data->dest_name;
                $details[$k]['dest_place'] = $data->dest_place;
                $details[$k]['dest_desc'] = $data->dest_desc;
                $details[$k]['dest_timing'] = $data->dest_timing;
                $details[$k]['dest_img'] = $data->dest_img;
                $details[$k]['dest_type'] = $data->dest_type;
                $details[$k]['dest_terms'] = $data->dest_terms;
                $details[$k]['attr'][$j]['attr_id'] = $data->attr_id;
                $details[$k]['attr'][$j]['attr_name'] = $data->attr_name;
                $details[$k]['attr'][$j]['attr_ticket_config'] = $data->attr_ticket_config;
                $details[$k]['attr'][$j]['attr_time'] = $data->attr_time;
                $details[$k]['attr'][$j]['class_name'] = $data->class_name;
                $details[$k]['attr'][$j]['name'] = $data->attr_name . '-' . $data->class_name;
                $details[$k]['attr'][$j]['class_id'] = $data->class_id;
                $details[$k]['attr'][$j]['class_rate'] = $data->class_rate;
                $details[$k]['attr'][$j]['class_time'] = $data->class_time;
                $details[$k]['attr'][$j]['class_number'] = $data->class_number;
                $details[$k]['attr'][$j]['available_numbers'] = $data->available_numbers;
                $details[$k]['attr'][$j]['pax'] = $data->dest_max_pax;
                $j++;
            }
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success', 'Data' => $details, 'actualdata' => $destData]);
        } catch (Exception $e) {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'RunTime Exception Occured']);
        }
    }


    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/Nov/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <create ticket>
    * Name: create ticket;
    * Description: create ticket
    * Action Type: Application;
    * Category: Manage;
    * </create ticket>
    */
    public function ticketing(Request $request)
    {
        $this->validate($request, [
            'customerid' => 'required|integer'/*|exists:users,usr_id|required_without:customername',*/
            /*'counterid' => 'integer|exists:counter,counter_id',
                                ,'dest_id' =>'integer|exists:destination,dest_id|required'*/,
            'customername' => 'string|required',
            'customermobile' => 'required|max:12|min:10',
            'customeremail' => 'required|email'/*,'dest_id' =>'integer|exists:destination,dest_id|required'*/,
            'classid' => 'array|required',
            'classid.*'  => 'integer|exists:class,class_id',
            'attrid'  => 'required|integer|exists:attraction,attr_id',
            'number' => 'required|array', 'number.*' => 'integer'
        ]);
        if (count($request->input('classid')) != count($request->input('number')))
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Ticket count and class count missmatch']);
        if ($request->input('counterid') != NULL) $dbArray['ticket_counter_id'] = $request->input('counterid');
        //if($request->input('dest_id') != NULL)$dbArray['ticket_dest_id']=$request->input('dest_id');
        if ($request->input('customerid') != NULL) $dbArray['ticket_usr_id'] = $request->input('customerid');
        $destId = (DB::table('attraction')->where('attr_id', '=', $request->input('attrid'))->pluck('attr_dest_id'));
        $dbArray['ticket_dest_id'] = $destId[0];
        //if(DB::table('users')->where('usr_id', '=',$request->input('userid'))->whereIn('dest_id',$destId)->get() == '[]')
        //return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'User destination missmatch']);
        if ($request->input('customername') != NULL) $dbArray['customer_name'] = $request->input('customername');
        if ($request->input('customermobile') != NULL) $dbArray['customer_mobile'] = $request->input('customermobile');
        if ($request->input('customeremail') != NULL) $dbArray['customer_email'] = $request->input('customeremail');
        if ($request->input('attrid') != NULL) $dbArray['ticket_attr_id'] = $request->input('attrid');
        $dbArray['date'] = date('m-d-y');
        $dbArray['category'] = OT_NO;
        $classArray = $request->input('classid');
        $numberArray = $request->input('number');
        $classArrayDetails = DB::table('class')->select('*')->get();
        $i = OT_ZERO;
        $grandTotal = OT_ZERO;
        foreach ($classArrayDetails as $class) {
            if (in_array($class->class_id, $classArray)) {
                $rateArray[] = $class->class_rate;
                $classNameArray[] = $class->class_name;
                $rate = (int)$class->class_rate;
                $number = (int)$numberArray[$i];
                $total = $rate * $number;
                $grandTotal = $grandTotal + $total;
                $i++;
            }
        }
        $rand = mt_rand();
        $dbArray['ticket_number'] = $rand;
        $dbArray['total_rate'] = $grandTotal;
        if (DB::table('tickets')->insert($dbArray) != 1)
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'error in inserting data into ticket table']);
        $id = DB::getPdo()->lastInsertId();
        $dbArray = array();
        $destinationName = (DB::table('destination')->where('dest_id', '=', $destId[0])->pluck('dest_name'));
        $attractionName = DB::table('attraction')->where('attr_id', '=', $request->input('attrid'))->pluck('attr_name');
        $counter = count($classArray);
        $i = OT_ZERO;
        while ($counter > $i) {
            $dbArray['ticket_id'] = $id;
            $dbArray['tc_class_id'] = $classArray[$i];
            $dbArray['tc_number'] = $numberArray[$i];
            $dbArray['attraction_name'] = $attractionName[0];
            $dbArray['dest_name'] = $destinationName[0];
            $dbArray['class_name'] = $classNameArray[$i];
            $rate = (int)$rateArray[$i];
            $number = (int)$numberArray[$i];
            $total = $rate * $number;
            $dbArray['tc_rate_per_class'] = $rate;
            $dbArray['total_rate'] = $total;
            $dbFinalArray[] = $dbArray;
            $i++;
        }
        if (DB::table('ticket_class')->insert($dbFinalArray) == 1)
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'successfully inserted', 'Ticket number' => $rand]);
        else
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Error in insrtion']);
    }


    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 17/06/2021
    * @license http://www.origamitechnologies.com
    * @aclinfo <booking>
    * Name: dataSync;
    * Description: booking
    * Action Type: Application;
    * Category: Manage;
    * </booking>
    */
    public function booking(Request $request)
    {
        $ticketClass = $request->json()->all();
        $ticketArray = json_decode($ticketClass['data']);

        $this->validate($request, [
            'name' => 'required|String',
            'email' => 'required|email',
            'mobile' => 'required|regex:/[0-9]{9}/',
            'date' => 'required',
            'bookdate' => 'required',
            'total' => 'required',
            'data' => 'required',
        ]);

        $dbArray['date']     = $request->input('date');
        //$dbArray['ticket_pusr_id'] = $request->input('publicuserid');
        $dbArray['customer_name'] = $request->input('name');
        $dbArray['customer_mobile'] = $request->input('mobile');
        $dbArray['customer_email'] = $request->input('email');
        $dbArray['total_rate'] = $request->input('total');
        $dbArray['ticket_data']    = $request->input('data');
        $dbArray['ticket_book_date'] = $request->input('bookdate');
        DB::table('tickets')->insert($dbArray);
        $ticketId = DB::getPdo()->lastInsertId();
        if ($ticketId) {
            //$ticketId = DB::getPdo()->lastInsertId();
            $ticketData = DB::table('ticket_print')->where('tp_counter_id', '=', 0)->groupBy('tp_attr_id')->get(['tp_attr_id', DB::raw('coalesce(count(*),0) as maxno')]);
            $ticketSeq = array();
            foreach ($ticketData as $data) {
                $ticketSeq[$data->tp_attr_id] = $data->maxno;
            }
            $classData = array();
            $ticketNumberArray = array();
            foreach ($ticketArray as $ticket) {
                $attr = "tno_" . $ticket->attrId;
                if (!isset($$attr)) {
                    $$attr = (array_key_exists($ticket->attrId, $ticketSeq)) ? $ticketSeq[$ticket->attrId] : 0;
                }
                $gno = $ticketId;
                $$attr++;
                $tno = $$attr;
                $tpData['tp_attr_id'] =  $ticket->attrId;
                $tpData['tp_rate'] =  $ticket->rate;
                $tpData['tp_content'] =  substr($ticket->content, 0, strlen($ticket->content) - 5);
                $tpData['tp_time'] =  $ticket->time;
                $tpData['tp_number'] =  $tno;
                $tpData['tp_gno'] =  $gno;
                $tpData['tp_counter_id'] =  $ticket->counter;
                $tpData['tp_actual_number'] =  $ticket->prefix . '.' . $tno;
                $tpData['tp_actual_number'] = $tno;
                $tpData['tp_prefix'] =  $ticket->prefix;
                $tpData['tp_date'] =  $ticket->dbdate;
                $tpData['tp_ticket_id'] =  $ticketId;
                $tpData['tp_pay_mode'] =  4;
                $tpData['tp_is_public'] =  OT_YES;
                $tpData['tp_data'] =  json_encode($ticket);
                $tpData['tp_dest_id'] =   $ticket->destId;
                DB::table('ticket_print')->insert($tpData);
                $tpId = DB::getPdo()->lastInsertId();

                /**
                 * generate email and pdf
                 */
                //$pdffilepath = generatepdfforpublicbooking ( $ticketId , $request->input('name') , $request->input('email') );
                $ticketNumberArray[] = $tpId;
                $classIds = explode("|", $ticket->classIds);
                $classNames = explode("|", $ticket->className);
                $classRates = explode("|", $ticket->classRate);
                $classQuantities = explode("|", $ticket->classQuantity);
                $classDet = array();
                if (count($classIds) > 1) {
                    for ($i = 0; $i < count($classIds) - 1; $i++) {
                        $classDet['tc_class_id'] = $classIds[$i];
                        $classDet['tc_number'] = $classQuantities[$i];
                        $classDet['tc_rate_per_class'] = $classRates[$i];
                        $classDet['tc_tp_id'] = $tpId;
                        $classDet['total_rate'] = $classQuantities[$i] * $classRates[$i];
                        $classDet['attraction_name'] = $ticket->attrName;
                        $classDet['class_name'] = $classNames[$i];
                        $classDet['dest_name'] = $ticket->destName;
                        $classDet['rate_class_original'] = $classRates[$i];
                        $classData[] = $classDet;
                    }
                } else if (count($classIds) == 1) {
                    $i = 0;
                    $classDet['tc_class_id'] = $classIds[$i];
                    $classDet['tc_number'] = $classQuantities[$i];
                    $classDet['tc_rate_per_class'] = $classRates[$i];
                    $classDet['tc_tp_id'] = $tpId;
                    $classDet['total_rate'] = $classQuantities[$i] * $classRates[$i];
                    $classDet['attraction_name'] = $ticket->attrName;
                    $classDet['class_name'] = $classNames[$i];
                    $classDet['dest_name'] = $ticket->destName;
                    $classDet['rate_class_original'] = $classRates[$i];
                    $classData[] = $classDet;
                }
            }
            if (count($classData)) {
                $pdffilepath = NULL;
                DB::table('ticket_class')->insert($classData);
                if ($request->input('paymentmode') != OT_YES) {  //online payment disable
                    $pdffilepath = generatepdfforpublicbooking($ticketId);
                    $pdffilepath = generatepdfforpublicbooking($ticketId, $request->input('name'), $request->input('email'));
                    /**Send mail */

                    $result = DB::table('sms_mail_que')->select('*')->where('mail_status', '=', '1')->get();
                    foreach ($result as $re) {
                        $to = $re->smq_recipient;
                        $subject = $re->subject;
                        $message = $re->message;
                        try {
                            $id = $re->smq_id;
                            $fileNameArray = DB::select('select * from  mail_file where mf_smq_id= ' . $id);
                            $mailer = app()['mailer'];
                            $data = array('to' => $to, 'subject' => $subject, 'message' => $message);
                            $mailer->send([], [], function ($message) use ($data, $fileNameArray) {
                                $message->from(OT_MAIL_FROM);
                                $message->to($data['to'])->subject($data['subject']);
                                $message->setBody($data['message']);
                                foreach ($fileNameArray as $file) {
                                    $message->attach($file->mf_file, array());
                                }
                            });
                            DB::table('sms_mail_que')->where('smq_id', $id)->update(['mail_status' => '2']);
                        } catch (\Exception $e) {
                            //echo $e;
                            //dd($e);
                            DB::table('sms_mail_que')->where('smq_id', $id)->update(['mail_status' => '3']);
                        }
                    }
                }

                /**End */


                // $dbPay['payment_ticket_id'] = $ticketId;
                // $dbPay['payment_date'] = date('Y/m/d');
                // $dbPay['payment_amount'] = $request->input('total');
                // $dbPay['payment_pusr_id'] = $request->input('publicuserid');
                // if (DB::table('payment')->insert($dbPay)) {
                //     $url =  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                //     $merchant_data = '';
                //     $refId = 10;//Crypt::encryptString($ticketId);
                //     $working_key = 'AA592619567680789814663F73069C01'; //Shared by CCAVENUES
                //     $access_code = 'AVHN83GA18CL04NHLC'; //Shared by CCAVENUES
                //     $merchant_data .= 'merchant_id' . '=' . urlencode('201285') . '&';
                //     $merchant_data .= 'amount' . '=' . urlencode($request->input('total')) . '&';
                //     $merchant_data .= 'currency' . '=' . urlencode('INR') . '&';
                //     $merchant_data .= 'redirect_url' . '=' . urlencode($url . 'v1/public/user/payment/reference/' . $refId) . '&';
                //     $merchant_data .= 'cancel_url' . '=' . urlencode('https://dtpc-booking.web.app/mybooking') . '&';
                //     $merchant_data .= 'order_id' . '=' . urlencode($ticketId) . '&';
                //     $merchant_data .= 'language' . '=' . urlencode('EN') . '&';

                //     $encrypted_data = '1'; //$this->paymentencrypt($merchant_data,$working_key);

                return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Successfully Added', 'ticket' => $ticketId, 'filepath' => $pdffilepath]);
            } else {
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Booking Payment has been failed']);
            }
        } else {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Booking has been failed']);
        }
    }
    function paymentencrypt($plainText, $key)
    {
        $secretKey = $this->hextobin(md5($key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        $blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
        $plainPad = $this->pkcs5_pad($plainText, $blockSize);
        if (mcrypt_generic_init($openMode, $secretKey, $initVector) != -1) {
            $encryptedText = mcrypt_generic($openMode, $plainPad);
            mcrypt_generic_deinit($openMode);
        }
        return bin2hex($encryptedText);
    }
    //*********** Padding Function *********************

    function pkcs5_pad($plainText, $blockSize)
    {
        $pad = $blockSize - (strlen($plainText) % $blockSize);
        return $plainText . str_repeat(chr($pad), $pad);
    }

    //********** Hexadecimal to Binary function for php 4.0 version ********

    function hextobin($hexString)
    {
        $length = strlen($hexString);
        $binString = "";
        $count = 0;
        while ($count < $length) {
            $subString = substr($hexString, $count, 2);
            $packedString = pack("H*", $subString);
            if ($count == 0) {
                $binString = $packedString;
            } else {
                $binString .= $packedString;
            }

            $count += 2;
        }
        return $binString;
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/Nov/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <view tickets>
    * Name: view tickets;
    * Description: view tickets
    * Action Type: Application;
    * Category: Manage;
    * </view tickets>
    */
    public function mytickets(Request $request)
    {
        $this->validate($request, ['customerid' => 'required|integer']);
        $ticketNumberArray = DB::table('tickets')->where('ticket_usr_id', '=', $request->input('customerid'))
            ->where('category', '=', OT_NO)
            ->select('*')->get();
        $ticketClassArray = DB::table('ticket_class')->select('*')->get();
        $tempArray = [];
        $resultArray = [];
        foreach ($ticketNumberArray as $ticketClass) {
            foreach ($ticketClassArray as $ticketNumber) {
                if ($ticketNumber->ticket_id == $ticketClass->ticket_id) {
                    //$tempArray['id']=$ticketClass->ticket_id;
                    $tempArray['ticketnumber'] = $ticketClass->ticket_number;
                    $tempArray['status'] = $ticketClass->ticket_status;
                    //$tempArray['grandtotalrate']=$ticketClass->total_rate;
                    $tempArray['attrid'] = $ticketClass->ticket_attr_id;
                    $tempArray['userid'] = $ticketClass->ticket_usr_id;
                    $tempArray['destid'] = $ticketClass->ticket_dest_id;
                    $tempArray['date'] = $ticketClass->date;
                    $tempArray['attraction'] = $ticketNumber->attraction_name;
                    $tempArray['destname'] = $ticketNumber->dest_name;
                    $tempArray['classname'] = $ticketNumber->class_name;
                    $tempArray['rateperclass'] = $ticketNumber->tc_rate_per_class;
                    $tempArray['ticketcount'] = $ticketNumber->tc_number;
                    $tempArray['totalrate'] = $ticketNumber->total_rate;
                    $resultArray[] = $tempArray;
                }
            }
        }

        if ($resultArray != NULL)
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success', 'Data' => $resultArray]);
        else
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'no records found..!']);
    }

    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/Nov/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <view tickets>
    * Name: view tickets;
    * Description: view tickets
    * Action Type: Application;
    * Category: Manage;
    * </view tickets>
    */

    public function viewtickets(Request $request)
    {
        $this->validate($request, ['ticketnumber' => 'required|integer|exists:tickets,ticket_number']);
        $ticketNumberArray = DB::table('tickets')->where('ticket_number', '=', $request->input('ticketnumber'))->select('*')->get();
        $ticketClassArray = DB::table('ticket_class')->select('*')->get();
        $tempArray = [];
        $resultArray = [];
        $customerArray = [];
        foreach ($ticketNumberArray as $ticketClass) {
            foreach ($ticketClassArray as $ticketNumber) {
                if ($ticketNumber->ticket_id == $ticketClass->ticket_id) {
                    if ($customerArray == []) {
                        $customerArray['name'] = $ticketClass->customer_name;
                        $customerArray['mobile'] = $ticketClass->customer_mobile;
                        $customerArray['email'] = $ticketClass->customer_email;
                        $customerArray['id'] = $ticketClass->ticket_usr_id;
                    }
                    $tempArray['ticketnumber'] = $ticketClass->ticket_number;
                    $tempArray['status'] = $ticketClass->ticket_status;
                    //$tempArray['grandtotalrate']=$ticketClass->total_rate;
                    $tempArray['attrid'] = $ticketClass->ticket_attr_id;
                    $tempArray['userid'] = $ticketClass->ticket_usr_id;
                    $tempArray['destid'] = $ticketClass->ticket_dest_id;
                    $tempArray['date'] = $ticketClass->date;
                    $tempArray['attraction'] = $ticketNumber->attraction_name;
                    $tempArray['destname'] = $ticketNumber->dest_name;
                    $tempArray['classname'] = $ticketNumber->class_name;
                    $tempArray['rateperclass'] = $ticketNumber->tc_rate_per_class;
                    $tempArray['ticketcount'] = $ticketNumber->tc_number;
                    $tempArray['totalrate'] = $ticketNumber->total_rate;
                    $resultArray[] = $tempArray;
                }
            }
        }

        if ($resultArray != NULL)
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success', 'Data' => $resultArray, 'customer' => $customerArray]);
        else
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'no records found..!']);
    }


    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 12/Nov/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <online payment details>
    * Name: online payment;
    * Description: view tickets
    * Action Type: Application;
    * Category: Manage;
    * </online payment>
    */
    /*public function payments(Request $request) {
        $this->validate($request, [ 'bookingid' =>'required|integer',
                                    'transactionid'=>'required',
                                    'response'=>'required',
                                    'status'=>'required|in:1,2',
                                    'amount' =>'required|integer',
                                    'customerid'=>'required|exists:public_users,pusr_id']);
        $dbArray['booking_id']=$request->input('bookingid');
        $dbArray['date']=Carbon::now();
        $dbArray['transaction_id']=$request->input('transactionid');
        $dbArray['response']=$request->input('response');
        $dbArray['status']=$request->input('status');
        $dbArray['amount']=$request->input('amount');
        $dbArray['cutomerid']=$request->input('customerid');
        if(DB::table('payment')->insert($dbArray) == 1)
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Payment successfully added...!']);
        return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'error in inserting data into DB']);
    }*/


    function paymentdecrypt($encryptedText, $key)
    {
        $secretKey = $this->hextobin(md5($key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $encryptedText = $this->hextobin($encryptedText);
        $openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        mcrypt_generic_init($openMode, $secretKey, $initVector);
        $decryptedText = mdecrypt_generic($openMode, $encryptedText);
        $decryptedText = rtrim($decryptedText, "\0");
        mcrypt_generic_deinit($openMode);
        return $decryptedText;
    }

    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 12/Nov/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <paymentAction>
    * Name: online payment;
    * Description: view tickets
    * Action Type: Application;
    * Category: Manage;
    * </paymentAction>
    */
    public function payments(Request $request)
    {
        /*Pdf Helper*/
        generateReport(526);

        $workingKey = 'AA592619567680789814663F73069C01';        //Working Key should be provided here.
        $encResponse = $request->input('encResp'); //This is the response sent by the CCAvenue Server
        $rcvdString = $this->paymentdecrypt($encResponse, $workingKey);
        //Crypto Decryption used as per the specified working key.
        $order_status = "";
        $decryptValues = explode('&', $rcvdString);
        $dataSize = sizeof($decryptValues);
        $result = array();
        for ($i = 0; $i < $dataSize; $i++) {
            $information = explode('=', $decryptValues[$i]);
            $result[$information[0]] = urldecode($information[1]);
        }
        $status = 1;
        if ($result['order_status'] === "Success") {
            $status = 2;
        } else {
            $status = 3;
        }
        $summaryId = Crypt::decryptString($request->input("reference")); //$result['order_id'];
        $refId =  $result['order_id'];
        if ($summaryId) {

            //print_r($_POST);die();
            //echo "<br>Thank you for shopping with us. Your credit card has been charged and your transaction is successful. We will be shipping your order to you soon.";

            // else if($order_status==="Aborted") {
            // 	//echo "<br>Thank you for shopping with us.We will keep you posted regarding the status of your order through e-mail";
            // } else if($order_status==="Failure") {
            // 	//echo "<br>Thank you for shopping with us.However,the transaction has been declined.";
            // } else {
            // 	//echo "<br>Security Error. Illegal access detected";
            // }


            $paymentData = array();
            $date = Carbon::createFromFormat('d/m/Y', $result['trans_date'])->format('Y-m-d');
            $paymentData['payment_res_id'] = $result['order_id'];
            $paymentData['payment_amount'] = $result['amount'];
            $paymentData['payment_order_status'] = $result['order_status'];
            $paymentData['payment_status'] = $status;
            $paymentData['payment_transaction_date'] = $date;
            $paymentData['payment_tracking_id'] = $result['tracking_id'];
            $paymentData['payment_bank_ref_no'] = $result['bank_ref_no'];
            $paymentData['payment_mode'] = $result['payment_mode'];
            $paymentData['payment_card_name'] = $result['card_name'];
            $paymentData['payment_status_message'] = $result['status_message'];
            $paymentData['payment_response_code'] = $result['response_code'];
            $affected = DB::table('payment')
                ->where('payment_ticket_id', $refId)
                ->where('payment_status', 1)
                ->whereNull('deleted')
                ->update($paymentData);
            $bookingData['ticket_payment_status'] = $status;
            if ($affected && $status == OT_YES) {
                $summaryDetails = DB::table('ticket_print')
                    ->leftjoin('tickets', 'tickets.ticket_id', '=', 'ticket_print.tp_ticket_id')
                    ->leftjoin('public_users', 'public_users.pusr_id', '=', 'tickets.ticket_pusr_id')
                    ->leftjoin('destination', 'destination.dest_id', '=', 'ticket_print.tp_dest_id')
                    ->where('tp_ticket_id', '=', $refId)
                    ->orderBy('tp_id')
                    ->select('ticket_print.*', 'tickets.*', 'public_users.*', 'destination.*')->get();


                // $statusArray = array(PAYMENT_PENDING=>PAYMENT_PENDING_TEXT,PAYMENT_SUCCESS=>PAYMENT_SUCCESS_TEXT,PAYMENT_FAIL=>PAYMENT_FAIL_TEXT);

                $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                        <html xmlns="http://www.w3.org/1999/xhtml">
                        <head>
                            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                            <meta name="viewport" content="width=device-width, initial-scale=1">
                                <title>DTPC</title>
                                <style type="text/css">
                                    body {
                                        font-family: Arial, Helvetica, sans-serif;
                                        margin: 0px;
                                        padding: 0px;
                                    }
                                    p,h1,h2,h3,h4,h5,h6{
                                        margin: 0px;
                                        padding: 0px;
                                    }
                                    hr {
                                        color: #CCC;
                                    }
                                    </style>
                                </head>

                            <body>';
                foreach ($summaryDetails as $data) {
                    $qrData = $data->tp_actual_number . ';' . $data->tp_date;
                    $email = $data->pusr_email;
                    $name = $data->pusr_name;
                    $body .= '<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr>
    <td><img src="https://dtpc-ticket-pwa.web.app/assets/images/logo.png"  width=250></td>
  </tr>
   <tr>
    <td align="center" style="font-size:14px;"><strong>DTPC Wayanad</strong></td>
  </tr>
  <tr>
    <td align="center" style="font-size:13px;"><strong>Wayanad Adventure Camp, ' . $data->dest_name . '</strong><br/>
      ' . $data->dest_place . ' - ' . $data->dest_pincode . ' Ph: ' . $data->dest_phone . '</td>
  </tr>
  <tr>
    <td align="center"><h3 style=" border:solid 3px #000000; padding-top:10px; padding-bottom:10px;" >' . $data->tp_content . '</h3></td>
  </tr>
  <tr>
    <td align="center" ><h4 class="tno">Ticket No. ' . $data->tp_actual_number . '<span style="font-weight: normal;">|' . $data->tp_date . ' ' . $data->tp_time . '</span></h4></td>
  </tr>
  <tr>
    <td align="center"><h3></h3></td>
  </tr>
  <tr>
    <td align="center"><strong>Price ----- Rs. ' . $data->tp_rate . '/-</strong></td>
  </tr>
  <tr>
    <td align="center"><hr/></td>
  </tr>
  <tr>
    <td align="center" style="font-size:14px;">*This ticket is not retainable or refundable<br/>
    **Keep this ticket till you leave the destination</td>
  </tr>    
  <tr>
    <td align="center" style="font-size:15px;">
    Email: info@dtpcwayanad.com<br/>
  Website: www.wayanadtourism.org<br/></td>
  </tr>
  <tr><td align="center"><span style="font-size:14px;">Sponsored by</span><br/><img src="https://dtpc-ticket-pwa.web.app/assets/images/canara_logo.png" width=180/></td>
  </tr>
   <tr><td align="center"><br/><br/><img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . $qrData . '">'; //.//QrCode::size(120)->generate($data->tp_actual_number.';'.$data->tp_date).'  
                    $body .= '</td>
  </tr>
</table><div style="page-break-before: always;"></div>';
                }

                $body .= '</body>
</html>
';
                $content = "Hi " . $name . ", Your payment of booking has been successfully completed. Please check the attachment of tickets for visitng. Thank You";

                try {
                    $filename = 'tickets/ticket_' . $refId . '.pdf';
                    // PDF::loadHTML($body)->setWarnings(false)->save($filename);//setPaper('a4', 'landscape')
                    $emailData['email'] = $email;
                    $emailData['subject'] = 'DTPC - Reservation Confirmation';
                    $emailData['body'] = $content;
                    return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Payment has been successfully completed']);
                } catch (Zend_Exception $e) {
                    return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Mail sending error']);
                }
            } else {
                $affected = DB::table('tickets')
                    ->where('ticket_id', $refId)
                    ->update($bookingData);
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Payment has been failed']);
            }
        } else {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Payment has been failed']);
        }
    }

    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 12/Nov/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <online payment details>
    * Name: online payment;
    * Description: view tickets
    * Action Type: Application;
    * Category: Manage;
    * </online payment>
    */
    public function getPayments(Request $request)
    {
        $this->validate($request, [
            'bookingid' => 'required|integer|exists:payment,booking_id',
        ]);
        $result = DB::table('payment')->select('*')
            ->where('booking_id', '=', $request->input('bookingid'))
            ->get();
        return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success', 'Data' => $result]);
    }


    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 28-Jan-2022
    * @license http://www.origamitechnologies.com
    * @aclinfo <sendemail>
    * Name: online payment;
    * Description: view tickets
    * Action Type: Application;
    * Category: Manage;
    * </send email>
    */
    public function sendemail(Request $request)
    {


        $result = DB::table('sms_mail_que')->select('*')->where('mail_status', '=', '1')->get();
        foreach ($result as $re) {
            $to = $re->smq_recipient;
            $subject = $re->subject;
            $message = $re->message;
            try {
                $id = $re->smq_id;
                $fileNameArray = DB::select('select * from  mail_file where mf_smq_id= ' . $id);
                $mailer = app()['mailer'];
                $data = array('to' => $to, 'subject' => $subject, 'message' => $message);
                $mailer->send([], [], function ($message) use ($data, $fileNameArray) {
                    $message->from(OT_MAIL_FROM);
                    $message->to($data['to'])->subject($data['subject']);
                    $message->setBody($data['message']);
                    foreach ($fileNameArray as $file) {
                        $message->attach($file->mf_file, array());
                    }
                });
                DB::table('sms_mail_que')->where('smq_id', $id)->update(['mail_status' => '2']);
                echo "mail send success";
            } catch (\Exception $e) {
                //echo $e;
                dd($e);
                DB::table('sms_mail_que')->where('smq_id', $id)->update(['mail_status' => '3']);
            }
        }
    }



    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 28-Jan-2022
    * @license http://www.origamitechnologies.com
    * @aclinfo <sendemail>
    * Name: online payment;
    * Description: view tickets
    * Action Type: Application;
    * Category: Manage;
    * </send email>
    */
    public function downloadticket(Request $request, $ticketname)
    {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION["skipcron"] = 1;
        $headers = ['Content-Type' => 'application/pdf',];
        return response()->download(storage_path("") . '/' . $ticketname . '.pdf', 'filename.pdf', $headers);
    }


    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 28-Jan-2022
    * @license http://www.origamitechnologies.com
    * @aclinfo <sendemail>
    * Name: online payment;
    * Description: view tickets
    * Action Type: Application;
    * Category: Manage;
    * </send email>
    */
    public function genratesampleemail(Request $request, $id, $email)
    {
        if ( $email == 'paytm' ){
           $data = checktransactionstatus( $id );
            dd($data);
        }

        generatepdfforpublicbooking($id, "Deamo User", $email);
        die("Success");
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 17/06/2021
    * @license http://www.origamitechnologies.com
    * @aclinfo <makepayment>
    * Name: makepayment;
    * Description: makepayment
    * Action Type: Application;
    * Category: Manage;
    * </makepayment>
    */
    public function makepayment(Request $request)
    {
        $dbArray = array();

        $this->validate($request, [
            'amount' => 'required',
            'ticket'  => 'required|integer|exists:tickets,ticket_id'
        ]);
        $amount = $request->input('amount');
        $ticketId = $request->input('ticket');
        $dbArray['payment_transaction_date']     = date('Y/m/d');
        $dbArray['payment_ticket_id'] = $ticketId;
        $dbArray['payment_amount'] = $request->input('amount');
        $dbArray['payment_type'] = OT_NO;
        //$dbArray['payment_redirect_url'] =  $request->input('redirecturl');
        DB::table('payment')->insert($dbArray);
        $tranId = DB::getPdo()->lastInsertId();
        $PAYTM_MERCHANT_KEY_WASTE = PAYTM_MURCHANT_KEY;//'D%#NTjhk0PVsoo91'; //'kWDhLTxGEzPjrklK';   q
        $PAYTM_MERCHANT_MID_WASTE = PAYTM_MID;//'Cochin79132462319127'; //'KALAMA86133868928245';
        $PAYTM_MERCHANT_WEBSITE_WASTE = WEB_SITE;//'APPSTAGING';
        $ABSOLUTEURL = "https://" . $_SERVER['SERVER_NAME'];
        $PAYTM_CUST_ID_WASTE = 'CUST001';
        if ($tranId) {
            $paytmParams = array();

            $paytmParams["body"] = array(
                "requestType" => "Payment",
                "mid" => $PAYTM_MERCHANT_MID_WASTE,
                "WEBSITE" => $PAYTM_MERCHANT_WEBSITE_WASTE,
                "orderId" => $tranId,
                "callbackUrl" => $ABSOLUTEURL . "/v1/public/processpaytm",
                "txnAmount" => array(
                    "value" => $amount,
                    "currency" => "INR",
                ),
                "userInfo" => array(
                    "custId" => $PAYTM_CUST_ID_WASTE,
                    'tiketId' => $ticketId,
                    'redirecturl' => $request->input('redirecturl'),
                ),

            );
            $responses = initialtransaction($paytmParams);

            $response = $responses['response'];
            $res = json_decode($response, true);
            extract($res);
            $token = $body['txnToken'];
            $money = $paytmParams['body']['txnAmount']['value'];


            $result = array();
            $result['paytmParams'] = $paytmParams;
            $result['token'] = $token;
            $result['money'] = $money;
            $result['orderId'] = $tranId;
            $_SESSION['returnurl'] = $request->input('redirecturl');
        return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Payment has been initiated', 'result' => $result , /*'paytmresponse' => $responses*/]);
        } else {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Payment has been failed']);
        }
    }








    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 26-Feb-2022
    * @license http://www.origamitechnologies.com
    * @aclinfo <processpaytm>
    * Name: API public user register;
    * Description: public user user registration Function;
    * Action Type: Application;
    * Category: Manage;
    * </ processpaytm>

    */
    public function processpaytm()
    {
        $responseString = json_encode($_POST);
        $filePath = '876488AD5464dfsg454sdfs7fd8';
        $formData = $_POST;
        if ($formData['STATUS'] == 'TXN_SUCCESS') {
            $transactionStatus = OT_YES;
            $responseKey = '1313ef866Q885456a4sdXC54AWD';
        } else {
            $transactionStatus = OT_THREE;
            $responseKey = '876488AD5464dfsg454sdfs7fd8';
        }
        $orderId = $formData['ORDERID'];
        $dbArray['payment_response'] = $responseString;
        $dbArray['payment_trans_status'] = $transactionStatus;
        DB::table('payment')->where('payment_id', $orderId)->update($dbArray);
        $dataArray = DB::table('payment')->where('payment_id', $orderId)->first();

        if ($transactionStatus == OT_YES) { //Generate email for success transaction

            /*update payment status in ticket table */
            $ticketNumberArray = DB::table('tickets')->where('ticket_id', '=', $dataArray->payment_ticket_id)->first();
            $filePath = generatepdfforpublicbooking($ticketNumberArray->ticket_id, $ticketNumberArray->customer_name, $ticketNumberArray->customer_email);
            $ticketId = $ticketNumberArray->ticket_id;
            $dbArray = NULL;
            $dbArray['ticket_payment_status'] = $transactionStatus;
            DB::table('tickets')->where('ticket_id', $ticketId)->update($dbArray);


            $result = DB::table('sms_mail_que')->select('*')->where('mail_status', '=', '1')->where('smq_recipient', $ticketNumberArray->customer_email)->get();
            foreach ($result as $re) {
                $to = $re->smq_recipient;
                $subject = $re->subject;
                $message = $re->message;
                try {
                    $id = $re->smq_id;
                    $fileNameArray = DB::select('select * from  mail_file where mf_smq_id= ' . $id);
                    $mailer = app()['mailer'];
                    $data = array('to' => $to, 'subject' => $subject, 'message' => $message);
                    $mailer->send([], [], function ($message) use ($data, $fileNameArray) {
                        $message->from(OT_MAIL_FROM);
                        $message->to($data['to'])->subject($data['subject']);
                        $message->setBody($data['message']);
                        foreach ($fileNameArray as $file) {
                            $message->attach($file->mf_file, array());
                        }
                    });
                    DB::table('sms_mail_que')->where('smq_id', $id)->update(['mail_status' => '2']);
                } catch (\Exception $e) {
                    DB::table('sms_mail_que')->where('smq_id', $id)->update(['mail_status' => '3']);
                }
            }
        }

        $ABSOLUTEURL = "https://" . $_SERVER['SERVER_NAME'];
        $redirectUrl = str_replace('api', 'booking', $ABSOLUTEURL);
        //$redirectUrl = 'http://localhost:4200';
        return redirect($redirectUrl . '?status=' . $responseKey . '&file=' . $filePath);
    }


    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 04-May-2022
    * @license http://www.origamitechnologies.com
    * @aclinfo <getserviceendpont>
    * Name: get service end poimd list for native android;
    * Description: get service end poimd list for native android;
    * Action Type: Application;
    * Category: Manage;
    * </ getserviceendpont>

    */
    public function getserviceendpont()
    {
        $dataArray[0]['key'] = "Ticket-Buddy Wayanad";
        $dataArray[1]['key'] = "Ticket-Buddy TVM";
        $dataArray[2]['key'] = "Ticket-Buddy Science Park";
        $dataArray[3]['key'] = "Ticket-Buddy Demo";
        

        return response()->json(['Status' => OT_YES,'Feedback' => 'Api success response' , 'data' => $dataArray]);

    }



        /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 04-May-2022
    * @license http://www.origamitechnologies.com
    * @aclinfo <setserviceendpoint>
    * Name: get service end poimd list for native android;
    * Description: get service end poimd list for native android;
    * Action Type: Application;
    * Category: Manage;
    * </ setserviceendpoint>

    */
    public function setserviceendpoint()
    {
        $dataArray ["Ticket-Buddy Wayanad"] = 'http://api.sandbox.ticketbuddy.in/v1';
        $dataArray ["Ticket-Buddy TVM"] = 'http://api.sandbox.ticketbuddy.in/v1';
        $dataArray ["Ticket-Buddy Science Park"] = 'http://api.sandbox.ticketbuddy.in/v1';
        $dataArray ["Ticket-Buddy Demo"] = 'http://api.sandbox.ticketbuddy.in/v1';
        return response()->json(['Status' => OT_YES,'Feedback' => 'Api success response' , 'data' => $dataArray [ $_POST['selected'] ]]);

    }

            /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 08-July-2022
    * @license http://www.origamitechnologies.com
    * @aclinfo <createorupdateuer>
    * Name: Used for otp based login;
    * Description: use for generate otp;
    * Action Type: Application;
    * Category: Manage;
    * </ createorupdateuer>

    */
    public function createorupdateuer( Request $request )
    {
        try{
            $userData = PublicUser::select('*')->where('pusr_mobile', '=', $request->input('uname'))->get();
            $otp = NULL;
            if ( count( $userData ) > 0 ){
                //User already exist , update password to new otp
                $userId = $userData[0]->pusr_id;
                $user = PublicUser::find( $userId );
                $otp = rand( 1111 , 9999 );
                $password = $otp;
                $user->pusr_pass = app('hash')->make($password);
                $user->save();

            }else{
                //Create User
                $user = new PublicUser;
                $user->pusr_name = "User_".$request->input('uname');
                $user->pusr_uname = $request->input('uname');
                $user->pusr_mobile = $request->input('uname');
                $user->pusr_email = $request->input('uname')."@temp.com";
                $otp = rand( 1111 , 9999 );
                $password = $otp;
                $user->pusr_pass = app('hash')->make($password);
                $user->save();

            }
            return response(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'OTP Send Successfully..' , 'otp' => $otp]);
        } catch (Exception $e) {
            return response(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'RunTime Exception occured']);
        }
    }
    
    
}
