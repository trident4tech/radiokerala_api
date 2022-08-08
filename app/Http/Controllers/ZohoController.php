<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laminas\Config\Reader\Ini;
use Illuminate\Http\Request;
use App\User;
use Carbon\Carbon;
use App\Library\SimpleXLSX;
use App\Libraries\Inii;
use App\Usergroup;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Ticketprint;
class ZohoController extends Controller
{
    public function __construct(Request $request) {

    }

    public function index(Request $request,$key){
      
        // $id = Ticketprint::create(['tp_rate'=>12,'u_createdby'=>12]);
        // return response()->json($id->tp_id);
        if ($key != ZOHO_KEY)
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Invalid Key']);
        $baseSelectQuery = DB::table('ticket_class');
        $baseSelectQuery ->leftjoin('tickets', 'tickets.ticket_id', '=', 'ticket_class.ticket_id');
        $baseSelectQuery ->leftjoin('counter', 'counter.counter_id', '=', 'tickets.ticket_counter_id');
        $baseSelectQuery->orderBy('tickets.date','DESC');
        $dataArray =  $baseSelectQuery->select('ticket_class.*','tickets.*','counter.*')->get();

        $destName = DB::table('destination')->where('deleted_at', '=', NULL)->pluck('dest_name');
        $dateArray = DB::table('tickets')->where('deleted_at', '=', NULL)->pluck('date'); //dd($dateArray);
        $classArray = DB::table('class')->where('deleted_at', '=', NULL)->distinct('class_name')->pluck('class_name'); //dd($classArray);
        $attractionArray = DB::table('attraction')->where('deleted_at', '=', NULL)->pluck('attr_name');
        $tempArray = array();

        $finalArray = array();
        $finalTicketArray = array();
        $ticketArray = array();
        $finalResultArray = array();
        foreach ($dateArray as $date){
            foreach ($dataArray as $ticketData){
                if ($ticketData->date != NULL){
                if ($ticketData->date == $date){
                    $tempArray['date'][] = $ticketData->date;
                    $tempArray['tickets'][] = $ticketData->tc_number;
                    $tempArray['amount'][] = $ticketData->total_rate;
                    $tempArray['class'][] = $ticketData->class_name;
                    $tempArray['category'][] = $ticketData->attraction_name;
                    $tempArray['counter'][] = $ticketData->counter_name;
                    $tempArray['destination'][] = $ticketData->dest_name;
                    $tempArray['ticketno'][] = $ticketData->tc_number;
                    $tempArray['rateperclass'][] = $ticketData->tc_number;
                }
            }
            }
            $finalArray[$date] = $tempArray;
            $tempArray = [];
        }////dd($finalArray);
        foreach ($classArray as $dest){
            foreach ($finalArray as $ticket){// dd($ticket);
                if ($ticket != NULL){
                $i=0;
                $todayTicketSold = 0;
                $todayAmount = 0;
                foreach ($ticket['class'] as $destArr){
                    if ($dest == $destArr){
                        $finalTicketArray['Destination Name'] = $ticket['destination'][$i];
                        $finalTicketArray['Date'] = $ticket['date'][$i];
                        $finalTicketArray['Time'] = $ticket['date'][$i];
                        $finalTicketArray['counter'] = $ticket['counter'][$i];
                        $finalTicketArray['clasaas'] = $dest;
                        $todayTicketSold = $todayTicketSold + $ticket['tickets'][$i];
                        $todayAmount = $todayTicketSold + $ticket['amount'][$i];
                        $class[$ticket['class'][$i]][] =  $ticket['ticketno'][$i];


                        $attr[ $ticket['category'][$i]][] =  $ticket['ticketno'][$i];

                        }
            $i++;
        }
        //$finalTicketArray['class'] = $class;
        //$class = [];
        //$finalTicketArray['category'] = $attr;
        //$attr = [];
        $finalTicketArray['todayticket'] = $todayTicketSold;
        $finalTicketArray['todayamount'] = $todayAmount;
        $ticketArray[] = $finalTicketArray;
        $finalTicketArray = [];
        }
    }
    }//dd($ticketArray);
        foreach ($ticketArray as $tickets){
            if ($tickets['todayticket'] != 0 ){
                $resultArray['Destination Name'] = $tickets['Destination Name'];
                $resultArray['Date'] = $tickets['Date'];
          //      $resultArray['Time Slot'] = '08:00 AM to 09:00 AM';
                $resultArray['Ticket Category '] = $tickets['clasaas'];
                $resultArray['No of Tickets Sold'] = $tickets['todayticket'];
                $resultArray['No of Tickets Sold'] = $tickets['todayticket'];
                $resultArray['Sold Amount '] = $tickets['todayamount'];
                $finalResultArray[] = $resultArray;
            }
        }


        if ($finalResultArray == NULL){
            $resultArray['Destination Name'] = '';
            $resultArray['Date'] = '';
            $resultArray['Ticket Category '] = '';
            $resultArray['No of Tickets Sold'] = '';
            $resultArray['No of Tickets Sold'] = '';
            $resultArray['Sold Amount '] = '';
            $finalResultArray[] = $resultArray;

        }
        return response()->json($finalResultArray);


  }

  public function summaryold(Request $request,$key){
      
     $this->validate($request, ['user' => 'required|integer|exists:users,usr_id']);
    if ($key != ZOHO_KEY)
        return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Invalid Key']);
    $today = Carbon::now();
       $dest =  0;
    $userid = $request->input('user');
        $date = date('Y/m/d');
      if ($request->input('date'))
          $date = $request->input('date');
      if ($request->input('dest'))
          $dest = $request->input('dest');
      //Get all transaction summary based on heirarchy
     // DB::enableQueryLog(); 
    $baseSelectQuery = DB::table('attraction');
    $baseSelectQuery ->leftjoin('class', 'class.class_attr_id', '=', 'attraction.attr_id');
    $baseSelectQuery ->leftjoin('ticket_class', 'ticket_class.tc_class_id', '=', 'class.class_id');
    $baseSelectQuery ->leftjoin('ticket_print', 'ticket_print.tp_id', '=', 'ticket_class.tc_tp_id');
    $baseSelectQuery ->leftjoin('counter', 'counter.counter_id', '=', 'ticket_print.tp_counter_id');    
    $baseSelectQuery ->leftjoin('destheirarchy', 'destheirarchy.destid', '=', 'counter.counter_dest_id');
    $baseSelectQuery ->leftjoin('users', 'users.dest_id', '=', 'destheirarchy.mainparent');
    $baseSelectQuery->where('users.usr_id','=',$userid);
    $baseSelectQuery->whereNull('ticket_print.deleted_at');
    //$baseSelectQuery->where('ticket_print.tp_is_cancelled','=',OT_NO);
    $baseSelectQuery->where('class.status','=',OT_YES);
    $baseSelectQuery->where('attraction.status','=',OT_YES);
    $baseSelectQuery->whereNull('attraction.deleted_at');
    $baseSelectQuery->whereNull('class.deleted_at');
    if ($dest) {
        $baseSelectQuery->where('counter.counter_dest_id','=',$dest);
    }
    $ticketDetails = $baseSelectQuery->where('tp_number','>',0)->whereNull('ticket_print.deleted_at')->whereDate('tp_date', '=', $date)->get();  
  //dd(DB::getQueryLog());
//     $ticketId = DB::table('ticket_print')->where('tp_number','>',0)->whereNull('deleted_at')->whereDate('tp_date', '=', 'today')->pluck('tp_ticket_id')->toArray();
//     $ticketId = DB::table('ticket_print')->where('tp_number','>',0)->whereNull('deleted_at')->whereDate('tp_date', '=', 'today')->pluck('tp_ticket_id')->toArray();
   // $ticketDetails = DB::table('ticket_class')->whereIn('ticket_id', $ticketId)->get(); //dd($ticketDetails);
    $noofTicket = 0;
    $noofTicketCancel = 0;
    $totalAmount = 0;
    $totalCancelAmount = 0;
    $entryTicketId = 0;
    $totalEntryTicket = 0;
      $totalTheatreTicket = 0;
      $totalTheatreTicketa = 0;
      $totalBoatingTicket = 0;
      $totalCycleTicket = 0;
      $totalEntryAmount = 0;
      $totalTheatreAmount = 0;
      $totalTheatreAmounta = 0;
      $totalBoatingAmount = 0;
      $totalCycleAmount = 0;
      $theatreAmount = 0;
      $theatreAmounta = 0;
      $cycleAmount = 0;
      $boatingAmount = 0;
      $entryAmount = 0;
      $totalcash = 0;
      $totalupi = 0;
      $totalonline = 0;
      $totalpos = 0;
  $entryTicketArray = ['75','77'/*,'11','12'*/];
  $result = array();
  $attrResult = array();
  $cancelResult = array();
    foreach ($ticketDetails as $ticket){
        if ($ticket->tp_is_cancelled==OT_NO) {
            if ($ticket->tp_pay_mode==OT_NO) {
                $totalcash+=$ticket->tc_rate_per_class*$ticket->tc_number;
            }
            else if ($ticket->tp_pay_mode==4) {
                $totalonline+=$ticket->tc_rate_per_class*$ticket->tc_number;
            }
            else if ($ticket->tp_pay_mode==3) {
                $totalpos+=$ticket->tc_rate_per_class*$ticket->tc_number;
            }
            else {
                $totalupi+=$ticket->tc_rate_per_class*$ticket->tc_number;
            }
            $noofTicket+=$ticket->tc_number;
            $attrResult[$ticket->tp_attr_id]['name'] = $ticket->attr_name;
            $attrResult[$ticket->tp_attr_id]['no'] = (isset($attrResult[$ticket->tp_attr_id]['no'])?$attrResult[$ticket->tp_attr_id]['no']:0)+$ticket->tc_number;
            $result[$ticket->tc_class_id]['name'] = $ticket->class_name;        
            $result[$ticket->tc_class_id]['no'] = (isset($result[$ticket->tc_class_id]['no'])?$result[$ticket->tc_class_id]['no']:0)+$ticket->tc_number;
            $result[$ticket->tc_class_id]['rate'] = $ticket->tc_rate_per_class;
            $result[$ticket->tc_class_id]['amount'] = (isset($result[$ticket->tc_class_id]['amount'])?$result[$ticket->tc_class_id]['amount']:0)+$ticket->total_rate;
        }
        if ($ticket->tp_is_cancelled==OT_YES) {
            $cancelResult[$ticket->tc_class_id]['name'] = $ticket->class_name;
            $cancelResult[$ticket->tc_class_id]['no'] = (isset($cancelResult[$ticket->tc_class_id]['no'])?$cancelResult[$ticket->tc_class_id]['no']:0)+$ticket->tc_number;
            $cancelResult[$ticket->tc_class_id]['rate'] = $ticket->tc_rate_per_class;
            $cancelResult[$ticket->tc_class_id]['amount'] = (isset($cancelResult[$ticket->tc_class_id]['amount'])?$cancelResult[$ticket->tc_class_id]['amount']:0)+$ticket->total_rate;
            $noofTicketCancel+=$ticket->tc_number;
        }
    }
    foreach ($result as $data) {
      $response['data'][] = $data;
      $totalAmount+= $data['amount'];
    }
     foreach ($cancelResult as $data) {
      $response['canceldata'][] = $data;
      $totalCancelAmount+= $data['amount'];
    }
    foreach ($attrResult as $data) {
      $response['attrData'][] = $data;
    }
    $response['No.of Tickets'] = $noofTicket;
    $response['No.of Tickets Cancel'] = $noofTicketCancel;
    $response['No.of Entry Tickets'] = $totalEntryTicket;
      $response['TheatreTickets'] = $totalTheatreTicket;
      $response['TheatreTicketsa'] = $totalTheatreTicketa;
      $response['CycleTickets'] = $totalCycleTicket;
      $response['BoatingTickets'] = $totalBoatingTicket;
      $response['TheatreAmount'] = $totalTheatreAmount;
      $response['TheatreAmounta'] = $totalTheatreAmounta;
      $response['CycleAmount'] = $totalCycleAmount;
      $response['BoatingAmount'] = $totalBoatingAmount;
      $response['EntryAmount'] = $totalEntryAmount;
      $response['TheatrePerAmount'] = $theatreAmount;
      $response['TheatrePerAmounta'] = $theatreAmounta;
      $response['CyclePerAmount'] = $cycleAmount;
      $response['BoatingPerAmount'] = $boatingAmount;
      $response['EntryPerAmount'] = $entryAmount;
      $response['Total Amount'] = $totalAmount;
      $response['Total Amount Cancel'] = $totalCancelAmount;
      $response['cash'] = $totalcash;
      $response['upi'] = $totalupi;
      $response['online'] = $totalonline;
      $response['pos'] = $totalpos;
      
    return response()->json($response);
  }

  public function summary(Request $request,$key){
      
     $this->validate($request, ['user' => 'required|integer|exists:users,usr_id']);
    if ($key != ZOHO_KEY)
        return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Invalid Key']);
    $today = Carbon::now();
       $dest =  0;
    $userid = $request->input('user');
        $date = date('Y/m/d');
      if ($request->input('date'))
          $date = $request->input('date');
      if ($request->input('dest'))
          $dest = $request->input('dest');
      //Get all transaction summary based on heirarchy
     // DB::enableQueryLog(); 
    $baseSelectQuery = DB::table('ticket_class');
    $baseSelectQuery ->leftjoin('class', 'class.class_id', '=', 'ticket_class.tc_class_id');
    $baseSelectQuery ->leftjoin('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
    $baseSelectQuery ->leftjoin('ticket_print', 'ticket_print.tp_id', '=', 'ticket_class.tc_tp_id');
   // $baseSelectQuery ->leftjoin('counter', 'counter.counter_id', '=', 'ticket_print.tp_counter_id');    
    $baseSelectQuery ->leftjoin('destheirarchy', 'destheirarchy.destid', '=', 'ticket_print.tp_dest_id');
    $baseSelectQuery ->leftjoin('users', 'users.dest_id', '=', 'destheirarchy.mainparent');
    $baseSelectQuery->where('users.usr_id','=',$userid);
    if ($request->input('searchUsr') && $request->input('searchUsr')!='')
      $baseSelectQuery->where('ticket_print.tp_usr_id','=',$request->input('searchUsr'));
    //$baseSelectQuery->where('ticket_print.tp_is_cancelled','=',OT_NO);
    // $baseSelectQuery->where('class.status','=',OT_YES);
    // $baseSelectQuery->where('attraction.status','=',OT_YES);
    // $baseSelectQuery->whereNull('attraction.deleted_at');
    // $baseSelectQuery->whereNull('class.deleted_at');
    if ($dest) {
        $baseSelectQuery->where('tp_dest_id','=',$dest);
    }
    $ticketDetails = $baseSelectQuery->where('tp_number','>',0)->whereNull('ticket_print.deleted_at')->orderBy('destheirarchy.dest_name')->orderBy('attraction.attr_name')->orderBy('class.class_name')->whereDate('tp_date', '=', $date)->get();
  // dd(\DB::getQueryLog());
    $noofTicket = array();
    $noofTicketCancel = array();
    $totalAmount = array();
    $totalCancelAmount = array();
    $totalcash = array();
    $totalupi = array();
    $totalonline = array();
    $totalpos = array();
    $result = array();
    $attrResult = array();
    $cancelResult = array();
    $destinations = array();
    $i = 0;
    $grantTicketNo = 0;
    foreach ($ticketDetails as $ticket){
        $destName = $ticket->dest_name;
        if (!in_array($destName,$destinations)) {
          $destinations[] = $destName;
          $totalcash[$i] = 0;
          $totalonline[$i] = 0;
          $totalpos[$i] = 0;
          $totalupi[$i] = 0;
          $noofTicket[$i] = 0;
          $noofTicketCancel[$i] = 0;
          $response['canceldata'][$i] = array();
          $response['data'][$i] = array();
          $i++;
        }
        $dest = array_search($destName,$destinations);
        if ($ticket->tp_is_cancelled==OT_NO) {
            if ($ticket->tp_pay_mode==OT_NO) {
                $totalcash[$dest]+=$ticket->tc_rate_per_class*$ticket->tc_number;
            }
            else if ($ticket->tp_pay_mode==4) {
                $totalonline[$dest]+=$ticket->tc_rate_per_class*$ticket->tc_number;
            }
            else if ($ticket->tp_pay_mode==3) {
                $totalpos[$dest]+=$ticket->tc_rate_per_class*$ticket->tc_number;
            }
            else {
                $totalupi[$dest]+=$ticket->tc_rate_per_class*$ticket->tc_number;
            }
            $noofTicket[$dest]+=$ticket->tc_number;
            $grantTicketNo += $ticket->tc_number;
            $attrResult[$dest][$ticket->tp_attr_id]['name'] = $ticket->attr_name;
            $attrResult[$dest][$ticket->tp_attr_id]['no'] = (isset($attrResult[$dest][$ticket->tp_attr_id]['no'])?$attrResult[$dest][$ticket->tp_attr_id]['no']:0)+$ticket->tc_number;
            $result[$dest][$ticket->tc_class_id]['name'] = $ticket->class_name; 
            $result[$dest][$ticket->tc_class_id]['classid'] = $ticket->class_id;
            $result[$dest][$ticket->tc_class_id]['dest'] = $ticket->dest_name;        
            $result[$dest][$ticket->tc_class_id]['no'] = (isset($result[$dest][$ticket->tc_class_id]['no'])?$result[$dest][$ticket->tc_class_id]['no']:0)+$ticket->tc_number;
            $result[$dest][$ticket->tc_class_id]['rate'] = $ticket->tc_rate_per_class;
            $result[$dest][$ticket->tc_class_id]['amount'] = (isset($result[$dest][$ticket->tc_class_id]['amount'])?$result[$dest][$ticket->tc_class_id]['amount']:0)+$ticket->total_rate;
        }
        if ($ticket->tp_is_cancelled==OT_YES) {
            $cancelResult[$dest][$ticket->tc_class_id]['name'] = $ticket->class_name;
            $cancelResult[$dest][$ticket->tc_class_id]['classid'] = $ticket->class_id;
            $cancelResult[$dest][$ticket->tc_class_id]['no'] = (isset($cancelResult[$dest][$ticket->tc_class_id]['no'])?$cancelResult[$dest][$ticket->tc_class_id]['no']:0)+$ticket->tc_number;
            $cancelResult[$dest][$ticket->tc_class_id]['rate'] = $ticket->tc_rate_per_class;
            $cancelResult[$dest][$ticket->tc_class_id]['amount'] = (isset($cancelResult[$dest][$ticket->tc_class_id]['amount'])?$cancelResult[$dest][$ticket->tc_class_id]['amount']:0)+$ticket->total_rate;
            $noofTicketCancel[$dest]+=$ticket->tc_number;
        }
    }
    $grantTotal = 0;
    foreach ($result as $key=>$datas) {
      $totalAmount[$key] = 0;      
      foreach ($datas as $data) {
        $response['data'][$key][] = $data;
        $totalAmount[$key]+= $data['amount'];
        $grantTotal += $data['amount'];
      }
    }
    
    foreach ($cancelResult as $key=>$datas) {
      $totalCancelAmount[$key] = 0;      
      foreach ($datas as $data) {
        $response['canceldata'][$key][] = $data;        
        $totalCancelAmount[$key]+= $data['amount'];
      }
    }
    foreach ($attrResult as $key=>$datas) {
      foreach ($datas as $data) {
        $response['attrData'][$key][] = $data;      
      }
    }
    //foreach ($attrResult as $data) {
      //$response['attrData'] = $attrResult;
    //}
    $response['destinations'] = $destinations;
    $response['No.of Tickets'] = $noofTicket;
    $response['No.of Tickets Cancel'] = $noofTicketCancel;
    $response['Total Amount'] = $totalAmount;
    $response['Total Amount Cancel'] = $totalCancelAmount;
    $response['cash'] = $totalcash;
    $response['upi'] = $totalupi;
    $response['online'] = $totalonline;
    $response['pos'] = $totalpos;
    $response['grantTicketNo'] = $grantTicketNo;
    $response['grantTotal'] = $grantTotal;
    $response['version'] = VERSION; 
    $response['Status'] = OT_YES; 
    return response()->json($response);
  }
   /* @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/Nov/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <getcollection>
    * Name: getcollection;
    * Description: Get counter based info for zoho
    * Action Type: Application;
    * Category: Manage;
    * </getcollection>
    */
    public function getdepcollection(Request $request){
      $this->validate($request, ['date' => 'required','dest'=>'required|integer']);
      if ($request->input('date'))
          $date = $request->input('date');
      if ($request->input('dest'))
          $dest = $request->input('dest');
      $prev = DB::table("ticket_print")
              ->where('tp_date','<',$date)
              ->where('tp_dest_id','=',$dest)
              ->where('tp_is_cancelled','=',OT_NO)
              ->whereNull('ticket_print.deleted_at')
              ->sum('tp_rate');
      $current = DB::table("ticket_print")
              ->where('tp_date','=',$date)
              ->where('tp_dest_id','=',$dest)
              ->where('tp_is_cancelled','=',OT_NO)
              ->whereNull('ticket_print.deleted_at')
              ->sum('tp_rate'); 
      $result['prev'] = $prev; 
      $result['collection'] = number_format((float)$current, 2, '.', '');
      $data = array();
      $data['date'] = $date;
      $destName = DB::table('destination')->where('deleted_at', '=', NULL)->where('dest_id','=',$dest)->pluck('dest_name');
      $data['branch'] = $destName[0];
      $data['key'] = '12asr78sa45hy67';
      $ch = curl_init(FMIS_URL.'/cronforbranch/getdeposit');
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($ch);
      curl_close($ch);
      $resultData = json_decode($response,true);
      $ob = 0;
      $prevdep = 0;
      $todaydep = 0;
      if ($resultData['Status'] == OT_YES) {
         $prevdep = $resultData['data']['prev'];
         $todaydep = $resultData['data']['today'];
         $ob = ($resultData['data']['ob'])?$resultData['data']['ob']:0;
      }  
      //cash ob = 350
      $result['prevdep'] = $prevdep;
      $result['todaydep'] = $todaydep;
      $result['ob'] = $ob+$prev-$result['prevdep']; 
      $result['ob'] = number_format((float)$result['ob'], 2, '.', '');
      $result['dep'] = number_format((float)$result['todaydep'], 2, '.', '');
      $result['cb'] = $result['ob']+$result['collection']-$result['todaydep'];
      $result['cb'] = number_format((float)$result['cb'], 2, '.', '');

      return response()->json(array('Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success','data'=>$result,'response'=>$response,'input'=>$data));
    }
    
    /* @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/Nov/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <counterinfo>
    * Name: counterinfo;
    * Description: Get counter based info for zoho
    * Action Type: Application;
    * Category: Manage;
    * </counterinfo>
    */
    public function counterinfo(Request $request,$key){
        //get counter data based on date
        if ($key != ZOHO_KEY)
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Invalid Key']);
        $result =  DB::select('SELECT to_char(ticket_print.tp_date, '."'DD/MM/YYYY'".') AS "Date",destination.dest_name AS "Destination Name",counter.counter_name AS "Counter Name",attraction.attr_name AS "Attraction",class.class_name AS "Ticket Class",SUM(ticket_class.tc_number::bigint) AS "Ticket Count",SUM(ticket_class.total_rate::numeric(13,2)) AS "Amount Collected",
        CASE WHEN tp_pay_mode=1 THEN '."'CASH'".' WHEN tp_pay_mode=2 THEN '."'UPI'".' WHEN tp_pay_mode=3 THEN '."'POS'".' ELSE '."'ONLINE'".' END AS "Payment Mode",destparent.dest_name AS "Parent Destination",usr.usr_user_name AS "Counter Staff"
        FROM ticket_print 
            LEFT JOIN counter ON counter.counter_id=tp_counter_id
            LEFT JOIN ticket_class ON ticket_class.tc_tp_id=ticket_print.tp_id
            LEFT JOIN class ON class.class_id=ticket_class.tc_class_id	
            LEFT JOIN attraction ON attraction.attr_id=class.class_attr_id	
            LEFT JOIN destination ON destination.dest_id=attraction.attr_dest_id
            LEFT JOIN destination as destparent ON destparent.dest_id=destination.dest_parent 
            LEFT JOIN users as usr ON usr.usr_id=ticket_print.tp_usr_id 		
            WHERE ticket_print.deleted_at IS NULL AND tp_actual_number IS NOT NULL AND ticket_print.tp_is_cancelled=1
            GROUP BY usr.usr_user_name,ticket_print.tp_date,counter.counter_name,class.class_name,attraction.attr_name,destination.dest_name,destparent.dest_name,tp_pay_mode ORDER BY ticket_print.tp_date');
        
        return response()->json($result); 
    }
    /* @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/Nov/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <getsettings>
    * Name: getsettings;
    * Description: Get settings based info 
    * Action Type: Application;
    * Category: Manage;
    * </getsettings>
    */
    public function getsettings(Request $request){        
        $constData = get_defined_constants(true);
        $constData = $constData['user'];
      return response()->json(array('Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success','data'=>$constData));
    }
     /* @author Pratheesh
    * @copyright Origami Technologies
    * @created 11/Nov/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <getcollection>
    * Name: getcollection;
    * Description: Get collection based info 
    * Action Type: Application;
    * Category: Manage;
    * </getcollection>
    */
    public function getcollection(Request $request,$key){
        //get counter data based on date
        $apiKey = "587469F4er78a";
        if ($key != $apiKey) //API_KEY
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Invalid Key']); 
        $date = date('Y/m/d');
        if ($request->input('date')) {
          $date = $request->input('date');
        }
        $result=DB::select("select SUM(tp_rate) AS amount,dest_name AS branch,tp_date AS collectiondate from  ticket_print JOIN destination ON dest_id=tp_dest_id where tp_date= '".$date."' AND ticket_print.deleted_at IS NULL AND tp_is_cancelled=".OT_NO." GROUP BY tp_date,dest_name");
        $data = array();
        foreach($result as $re){
            $data['result'][] = array('collectiondate'=>$re->collectiondate,'amount'=>$re->amount,'branch'=>$re->branch);
        }
      return response()->json(array('Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success','data'=>$data));
    }
}
