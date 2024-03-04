<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\User;
use App\Weatheralert;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	private $paginator = 'p';
	public function __construct(Request $request) {
		$this->validate($request, [$this->paginator => 'integer']);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 21/07/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <register>
		    * Name: API Register;
		    * Description: user registration Function;
		    * Action Type: Application;
		    * Category: Manage;
		    * </register>
	*/
	public function register(Request $request) {

		$this->validate($request, ['destid' => 'integer|exists:destination,dest_id',
			'name' => 'required|String',
			'uname' => 'required|string',
			'password' => 'required',
			'usermobile' => 'required|regex:/[0-9]{9}/',
			'group_id' => 'required|integer|exists:usergroups,ugrp_id',
			'mainroleid' => 'required|integer|exists:roles,id',
			'userid' => 'required|exists:users,usr_id']);
		try {
			$userEx = DB::table('users')->where('usr_user_name', $request->input('uname'))->wherenull('deleted_at')->first();
			if (!is_null($userEx)) {
				return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Username already exists']);
			}
			$user = new User;
			$user->usr_name = $request->input('name');
			$user->email = $request->input('email');
			$user->usr_user_name = $request->input('uname');
			$user->usr_mobile = $request->input('usermobile');
			$user->ugrp_id = $request->input('group_id');
			$user->usr_admin = $request->input('usradmin');
			$user->role_id = $request->input('mainroleid');
			if ($request->input('group_id') == 18) //counter staff
			{
				$user->usr_is_ticket_staff = OT_YES;
			}

			if ($request->input('rec')) {
				$user->usr_is_rec_mandorty = $request->input('rec');
			}

			//$groupId=DB::table('users')->where('usr_id', '=', $request->input('userid'))->where('deleted_at',NULL)->pluck('ugrp_id');
			if ($request->input('usradmin') == OT_NO) {
				$checkDestinationAllowed = DB::table('usergroups')->where('ugrp_id', '=', $request->input('group_id'))->where('deleted_at', NULL)->pluck('ugrp_destination_allowed');
				if ($checkDestinationAllowed[0] == OT_NO) {
				} else {
					return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'User registration Failed,Note:  Destination-Not-Allowed']);
				}
			}
			//$user->ugrp_id = $groupId[0];
			if ($request->input('destid')) {
				$user->dest_id = $request->input('destid');
			}
			$password = $request->input('password');
			$user->password = app('hash')->make($password);
			if ($user->save($request->input('counter'))) {
				$usrId = DB::getPdo()->lastInsertId();
				if (count($request->input('counter'))) {
					foreach ($request->input('counter') as $counter) {
						$data[] = array('uc_usr_id' => $usrId, 'uc_counter_id' => $counter);
					}
					DB::table('user_counters')->insert($data);
				}
				return response(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'User registration has been success', 'id' => $usrId, 'counter' => $request->input('counter')]);
			} else {
				return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error while Creating User']);
			}

		} catch (Exception $e) {
			return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'RunTime Exception occured']);
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 21/07/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <Login>
		    * Name: Login;
		    * Description: User Login here;
		    * Action Type: Application;
		    * Category: Manage;
		    * </Login>
	*/
	public function Login(Request $request) {

		$this->validate($request, ['usr_user_name' => 'required|exists:users,usr_user_name', 'password' => 'required']); //basic validation
		try {

			$credentials = $request->only(['usr_user_name', 'password']);
			//$myTTL = 1; //minutes
			$myTTL = 10000; //minutes
			Auth::factory()->setTTL($myTTL);
			$token = '';
			if (!$token = Auth::attempt($credentials)) {
				return response()->json(['Status' => OT_NO, 'a'=>Auth::attempt($credentials),'version' => VERSION, 'Feedback' => 'Credential missmatch']);
			}
			$user = User::where('usr_user_name', '=', $request->input('usr_user_name'))->firstOrFail();
			if ($user->usr_is_logged_in == OT_YES && $user->usr_is_ticket_staff == OT_YES) {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'User Already logged in']);
			}
			$dbArray['usr_token'] = $token;
			$dbArray['usr_is_logged_in'] = OT_YES;
			$singleloginhash = (int) strtotime("now") . $user->usr_id;
			$dbArray['usr_login_seq_no'] = $singleloginhash;
			DB::table('users')->where('usr_user_name', '=', $request->input('usr_user_name'))->update($dbArray);
			$logArray = array();
			$logArray['ul_usr_id'] = $user['usr_id'];
			$logArray['ul_log_status'] = OT_NO;
			$logArray['ul_login_seq_no'] = $singleloginhash;
			$logArray['ul_log_ip'] = $request->ip();
			$logArray['ul_log_ua'] = $request->userAgent();
			DB::table('user_login_log')->insert($logArray);

			$session = $request->session();
			$session->put('userid', $user['usr_id']);
			$session->put('roleid', $user['role_id']);
			$attractionData = array();
			// DB::enableQueryLog();
			$baseSelectQuery = DB::table('users');
			$baseSelectQuery->leftjoin('usergroups', 'usergroups.ugrp_id', '=', 'users.ugrp_id');
			$baseSelectQuery->leftjoin('destination as child', 'child.dest_id', '=', 'users.dest_id');
			// $baseSelectQuery ->leftjoin('destination as parent', 'parent.dest_id', '=', 'child.dest_parent');
			$baseSelectQuery->leftjoin('attraction', 'attraction.attr_dest_id', '=', 'child.dest_id');
			$baseSelectQuery->leftjoin('class', 'class.class_attr_id', '=', 'attraction.attr_id');

			//if($destinationId != '[]')
			//   $baseSelectQuery->where('users.dest_id', '=', $destinationId[0]);
			$baseSelectQuery->where('users.usr_id', '=', $user['usr_id']);
			$baseSelectQuery->where('users.deleted_at', NULL);
			// $baseSelectQuery->where('attraction.status','=',OT_YES);
			// $baseSelectQuery->where('class.status','=',OT_YES);
			$dataArray = $baseSelectQuery->select('users.*', 'attraction.*', 'class.*', 'child.*', 'usergroups.*', 'child.dest_name as parentdest')->orderBy('attr_name')->get();
			//dd(DB::getQueryLog());
			$count = 0;
			$attrIds = array();
			$destName = '';
			$destCode = '';
			$destType = 0;
			$destPlace = '';
			$destPin = '';
			$destPhone = '';
			$destNo = '';
			$destWeb = '';
			$destGST = '';
			$destMail = '';
			$dest_terms = '';
			$dest_display_terms_ticket = '';
			$dest_allow_scan_verify = '';
			$rec = OT_NO;
			$parent = '';
			$usrToken = '';
			$paymode = '';
			$users = array();
			foreach ($dataArray as $data) {
				$destGST = $data->dest_gstin;
				$destName = $data->dest_name;
				$destType = $data->dest_type;
				$destNo = $data->dest_code;
				$paymode = $data->dest_paymode;
				$destPlace = $data->dest_place;
				$destPin = $data->dest_pincode;
				$destPhone = $data->dest_phone;
				$destWeb = $data->dest_website;
				$destMail = $data->dest_email;
				$dest_terms = $data->dest_terms;
				$parent = $data->dest_name;
				if ($data->parentdest) {
					$parent = $data->parentdest;
				}

				$dest_allow_scan_verify = $data->dest_allow_scan_verify;
				$rec = $data->usr_is_rec_mandorty;
				$dest_display_terms_ticket = $data->dest_display_terms_ticket;
				if (!in_array($data->attr_id, $attrIds)) {
					$attrIds[$count] = $data->attr_id;
					$count++;
				}
				$key = array_search($data->attr_id, $attrIds);
				$attractionData[$key]['attrName'] = $data->attr_name;
				$attractionData[$key]['attrId'] = $data->attr_id;
				$attractionData[$key]['ticketconfig'] = $data->attr_ticket_config;
				$attractionData[$key]['classes'][] = array('className' => $data->class_name, 'classId' => $data->class_id, 'classRate' => $data->class_rate, 'noticket' => $data->available_numbers);
				$count++;
			}
			$baseSelectQuery = DB::table('destination');
			$baseSelectQuery->leftjoin('destheirarchy', 'destheirarchy.destid', '=', 'destination.dest_id');
			$baseSelectQuery->where('destheirarchy.mainparent', '=', $user['dest_id']);
			$dataArray = $baseSelectQuery->select('destination.*')->orderBy('dest_name')->get();
			$baseSelectQuery = DB::table('users');
			$baseSelectQuery->leftjoin('destheirarchy', 'destheirarchy.destid', '=', 'users.dest_id');
			$baseSelectQuery->where('destheirarchy.mainparent', '=', $user['dest_id']);
			$baseSelectQuery->where('users.role_id', '=', '5');
			$baseSelectQuery->whereNull('users.deleted_at');
			$dataUsrArray = $baseSelectQuery->select('users.*', 'destheirarchy.*')->orderBy('usr_user_name')->get();

			return response()->json(['Status' => OT_YES, 'Feedback' => 'Success', 'Token' => $token, 'destType' => $destType, 'destId' => $user['dest_id'], 'userId' => $user['usr_id'], 'roleId' => $user['role_id'], 'usr_admin' => $user['usr_admin'], 'userattractionData' => $attractionData, 'destName' => $destName, 'destNo' => $destNo, 'destPlace' => $destPlace, 'destPin' => $destPin, 'destPhone' => $destPhone, 'username' => $user['usr_name'], 'destWeb' => $destWeb, 'destMail' => $destMail, 'destTerms' => $dest_terms, 'displyTermsTicket' => $dest_display_terms_ticket, 'verifyScan' => $dest_allow_scan_verify, 'amountMandaroty' => $rec, 'parentDest' => $parent, 'usrtoken' => $usrToken, 'singleloginhash' => $singleloginhash, 'destinations' => $dataArray, 'usrData' => $dataUsrArray, 'version' => VERSION, 'paymode' => $paymode, 'destgstin' => $destGST]);
		} catch (Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'RunTime Exception Occured']);
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 21/07/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <Logout>
		    * Name: Logout;
		    * Description: all api logout request execute here;
		    * Action Type: Application;
		    * Category: Manage;
		    * </logout>
	*/
	public function Logout(Request $request) {

		/**Update User Status */
		// $updateArray ['usr_login_seq_no'] = NULL;
		$updateArray['usr_is_logged_in'] = OT_NO;
		if (DB::table('users')->where('usr_id', $request->input('userid'))->update($updateArray)) {

			$logArray = array();
			$logArray['ul_usr_id'] = $request->input('userid');
			$logArray['ul_log_status'] = OT_YES;
			$logArray['ul_log_ip'] = $request->ip();
			$logArray['ul_log_ua'] = $request->userAgent();
			DB::table('user_login_log')->insert($logArray);
			/**-------------- */

			/*if($request->session()->has('userid')) {
				            $session = $request->session(); //create session variable
				            $user_email = $session->get('userid'); //strore email for display success message
				            $request->session()->forget('userid'); //unset the u_id session
				            $request->session()->forget('roleid'); //unset the u_id session
				            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Logout Successfull']);
				        }
				        else
				            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'no logged in User exist']); //return false if no user currenly logged in
			*/
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success']); //return false if no user currenly logged in
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 21/07/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <List all User>
		    * Name: List;
		    * Description: List all user executed here;
		    * Action Type: Application;
		    * Category: Manage;
		    * </List>
	*/
	public function list(Request $request) {
		$baseSelectQuery = DB::table('users');
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'users.dest_id');
		$baseSelectQuery->leftjoin('usergroups', 'usergroups.ugrp_id', '=', 'users.ugrp_id');
		$baseSelectQuery->leftjoin('roles', 'roles.id', '=', 'users.role_id');
		if ($request->input('searchTerm') != '') {
			$search = $request->input('searchTerm');
			$baseSelectQuery->where(function ($query) use ($search) {
				$query->where('usr_user_name', 'ilike', '%' . $search . '%')
					->orWhere('usr_mobile', 'ilike', '%' . $search . '%');
			});
			// $baseSelectQuery->where('usr_user_name', 'ilike','%'.$request->input('searchTerm').'%');
			//$baseSelectQuery->orWhere('usr_mobile', 'ilike','%'.$request->input('searchTerm').'%');
		}
		if ($request->input('destId') != '') {
			$baseSelectQuery->where('users.dest_id', '=', $request->input('destId'));
		}

		if ($request->input('role') != '') {
			$baseSelectQuery->where('users.role_id', '=', $request->input('role'));
		}

		//  $baseSelectQuery->where('usr_user_name ilike '%".$request->input('searchTerm')."%' OR usr_mobile ilike '%".$request->input('searchTerm')."%'");
		if ($request->input($this->paginator) != '') {
			$user = $baseSelectQuery->select('users.*', 'destination.dest_name', 'usergroups.ugrp_name', 'roles.name')->whereNull('users.deleted_at')->orderBy('usr_name')->paginate($request->input($this->paginator));
		} else {
			$user = $baseSelectQuery->select('users.*', 'destination.dest_name', 'usergroups.ugrp_name', 'roles.name')->whereNull('users.deleted_at')->orderBy('usr_name')->get();
		}

		return response()->json(['Status' => OT_YES, 'Feedback' => 'Success', 'Data' => $user]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 21/07/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <List all User>
		    * Name: Edit;
		    * Description: Edit all user executed here;
		    * Action Type: Application;
		    * Category: Manage;
		    * </List>
	*/

	public function edit(Request $request) {
		$this->validate($request, ['email' => 'email']);
		//if($request->session()->has('userid')){
		$this->validate($request, ['id' => 'required|integer']);
		//if($request->input('name') != ''){
		$user = User::find($request->input('id'));
		if ($user != '') {
			if ($request->input('name') != '') {
				$user->usr_name = $request->input('name');
			}

			if ($request->input('email') != '') {
				$user->email = $request->input('email');
			}

			if ($request->input('uname') != '') {
				$user->usr_user_name = $request->input('uname');
			}

			if ($request->input('mobile') != '') {
				$user->usr_mobile = $request->input('mobile');
			}

			if ($request->input('groupid') != '') {
				$user->ugrp_id = $request->input('groupid');
			}

			if ($request->input('destid') != '') {
				$user->dest_id = $request->input('destid');
			}

			if ($request->input('mainroleid') != '') {
				$user->role_id = $request->input('mainroleid');
			}

			if ($request->input('rec') != '') {
				$user->usr_is_rec_mandorty = $request->input('rec');
			}

			if ($request->input('mainroleid') == 5) //counter staff
			{
				$user->usr_is_ticket_staff = OT_YES;
			} else if ($request->input('mainroleid') != 5) {
				$user->usr_is_ticket_staff = OT_NO;
			}

			$user->save();
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Successfully Updated']);
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'User_ID Not Valid']);
		}
		//}
		//else{
		//  return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Name not enter ']);
		//}
		// }
		//else{
		//return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'User Not logged IN ']);
		//}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 21/07/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <List Single User>
		    * Name: list Single user;
		    * Description: Listing of single user execute here
		    * Action Type: Application;
		    * Category: Manage;
		    * </listsingle>
	*/
	public function getUser(Request $request) {
		//if(!$request->session()->has('userid'))
		//return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'User not loggedin']);
		$this->validate($request, ['id' => 'required|integer|exists:users,usr_id']);
		try {
			$destinationId = (DB::table('users')->where('usr_id', '=', $request->input('id'))->where('deleted_at', NULL)->pluck('dest_id'));
			$baseSelectQuery = DB::table('users');
			$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'users.dest_id');
			$baseSelectQuery->leftjoin('usergroups', 'usergroups.ugrp_id', '=', 'users.ugrp_id');
			$baseSelectQuery->leftjoin('roles', 'roles.id', '=', 'users.role_id');
			$baseSelectQuery->where('users.usr_id', '=', $request->input('id'));
			$dataArray = $baseSelectQuery->select('users.*', 'usergroups.*', 'destination.*', 'roles.*')->get();

			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);
		} catch (\Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Run time exception occured']);
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 21/07/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <List Single User>
		    * Name: list Single user;
		    * Description: Listing of single user execute here
		    * Action Type: Application;
		    * Category: Manage;
		    * </listsingle>
	*/
	public function getusertoken(Request $request) {
		//if(!$request->session()->has('userid'))
		//return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'User not loggedin']);
		$this->validate($request, ['userName' => 'required|exists:users,usr_user_name', 'token' => 'required']);
		try {
			$usrId = DB::table('users')->where('usr_user_name', '=', $request->input('userName'))->where('usr_token', '=', $request->input('token'))->pluck('usr_id');
			if ($usrId != '[]') {
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success']);
			}
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Login Failed']);
		} catch (\Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Run time exception occured']);
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 23/07/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <List Single User>
		    * Name: Delete user;
		    * Description: Delete Single user
		    * Action Type: Application;
		    * Category: Manage;
		    * </listsingle>
	*/
	public function delUser(Request $request) {
		try {
			//if(!$request->session()->has('userid'))
			//return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'User not loggedin']);
			$this->validate($request, ['id' => 'required|integer']); //basic validation
			$userDetails = User::find($request->input('id'));
			if ($userDetails != '') {
				$dataArray['deleted_at'] = Carbon::now();
				$user = User::whereusr_id($request->input('id'))->update($dataArray) /*->delete()*/;
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Deleted', 'Data' => $userDetails]);
			} else {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'User_id Not Valid']);
			}

		} catch (\Illuminate\Database\QueryException $ex) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Foreign key violation']);
		}

	}

/*
 * @author Pratheesh
 * @copyright Origami Technologies
 * @created 27/07/2020
 * @license http://www.origamitechnologies.com
 * @aclinfo <Change Status>
 * Name: Status;
 * Description: Change Status of user
 * Action Type: Application;
 * Category: Manage;
 * </listsingle>
 */

	public function status(Request $request) {
		$this->validate($request, ['id' => 'required', 'status' => 'required']); //basic validation
		try {
			//$id = Crypt::decrypt($request->input('id'));
			$id = $request->input('id');
			$user = User::find($id);
			if ($user != NULL) {
				$user->status = $request->input('status');
				$user->save();
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'id: ' . $id . 'Status in changed']);
			} else {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'id: ' . $id . 'Not  Valid']);
			}
		} catch (DecryptException $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Faild in decryption']);
		}
	}

//Test function to create encrypted inputs..

	public function encrypt(Request $request) {

		// echo "hai";
		// $data[] = Schema::getColumnListing('users'); // users table
		$data = User::all();
		return response()->json($data);

		//createExcel($data);
		//echo Crypt::encrypt($request->input('id'));

	}

/*
 * @author Pratheesh
 * @copyright Origami Technologies
 * @created 05/08/2020
 * @license http://www.origamitechnologies.com
 * @aclinfo <forget password>
 * Name: Status;
 * Description: forget password
 * Action Type: Application;
 * Category: Manage;
 * </forgetpassword>
 */

	public function forget(Request $request) {
		$this->validate($request, ['email' => 'email|required']); //basic validation
		try {
			$userId = User::select('usr_id')->where('email', '=', $request->input('email'))->first();
			if ($userId != NULL) {
				$userId = $userId->usr_id;
				$user = User::find($userId);
				$data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
				$password = substr(str_shuffle($data), 0, 9);
				$password1 = app('hash')->make($password);
				$user->password = $password1;
				$user->save();
				$emailData['email'] = $request->input('email');
				$emailData['subject'] = 'Reset Password';
				$emailData['body'] = $password;
				sendMail($emailData);
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'reset password send']);
			} else {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'email  valid']);
			}
		} catch (Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Id is not  valid']);
		}
	}

/*
 * @author Pratheesh
 * @copyright Origami Technologies
 * @created 05/08/2020
 * @license http://www.origamitechnologies.com
 * @aclinfo <reset password >
 * Name: Status;
 * Description: resetpassword
 * Action Type: Application;
 * Category: Manage;
 * </reset password>
 */

	public function reset(Request $request) {
		if ($request->session()->has('userid')) {
			$user = new User;
			$this->validate($request, ['email' => 'required|email', 'password' => 'required|confirmed|min:6']); //basic validation
			try {
				if (User::where('email', $request->input('email'))->first()) {
					$userId = User::select('usr_id')->where('email', '=', $request->input('email'))->first();
					$session = $request->session(); //create session variable
					$userIdFromSession = $session->get('userid');
					if ($userIdFromSession != ($request->input('id'))) {
						return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'User id and email missmatch']);
					}

					$userId = $userId->usr_id;
					$user = User::find($userId);
					$password = $request->input('password');
					$password1 = app('hash')->make($password);
					$user->password = $password1;
					$user->save();
					return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'successfully updated']);
				} else {
					return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Email-id Not exist in DB']);
				}
			} catch (Exception $e) {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'RunTime Exception Occured']);
			}
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'no logged in user exist']);
		}
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 05/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <reset password >
		    * Name: Status;
		    * Description: resetpassword password
		    * Action Type: Application;
		    * Category: Manage;
		    * </reset password>
	*/

	public function resetPassword(Request $request) {
		$user = new User;
		$this->validate($request, ['id' => 'required|integer', 'password' => 'required|confirmed|min:6']); //basic validation
		try {
			if (User::where('usr_id', $request->input('id'))->first()) {
				$userId = User::select('usr_id')->where('usr_id', '=', $request->input('id'))->first();
				$userId = $userId->usr_id;
				$user = User::find($userId);
				$password = $request->input('password');
				$password1 = app('hash')->make($password);
				$user->password = $password1;
				$user->save();
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'successfully updated']);
			} else {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'id Not exist in DB']);
			}
		} catch (Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'RunTime Exception Occured']);
		}
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/19/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <getclassorder>
		    * Name: Give PErmission;
		    * Description: Give permission to users
		    * Action Type: Application;
		    * Category: Manage;
		    * </getclassorder>
	*/

	public function getclassorder(Request $request) {
		$this->validate($request, ['userId' => 'required|integer|exists:users,usr_id']);
		$user = $request->input('userId');
		$dataArray = array();
		$baseSelectQuery = DB::table('class');
		$baseSelectQuery->join('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
		$baseSelectQuery->join('counter_attractions', 'counter_attractions.ca_attr_id', '=', 'attraction.attr_id');
		$baseSelectQuery->join('counter', 'counter.counter_id', '=', 'counter_attractions.ca_counter_id');
		$baseSelectQuery->join('user_counters', 'user_counters.uc_counter_id', '=', 'counter.counter_id');
		$baseSelectQuery->leftjoin("class_order", function ($join) {
			$join->on("class_order.co_class_id", "=", "class.class_id")
				->on("class_order.co_usr_id", "=", "user_counters.uc_usr_id")
				->whereNull('class_order.deleted_at');
		});
		$baseSelectQuery->where('user_counters.uc_usr_id', '=', $user);
		$baseSelectQuery->whereNull('class.deleted_at');
		$baseSelectQuery->whereNull('attraction.deleted_at');
		$baseSelectQuery->whereNull('counter_attractions.deleted_at');
		$baseSelectQuery->whereNull('user_counters.deleted_at');
		$baseSelectQuery->whereNull('counter.deleted_at');
		$baseSelectQuery->orderBy('class_order.co_order');
		$baseSelectQuery->orderBy('class.class_number');
		$dataArray = $baseSelectQuery->select('class.*', 'class_order.*', 'attraction.*')->get();

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);

	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/19/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <classorder>
		    * Name: Give PErmission;
		    * Description: Give permission to users
		    * Action Type: Application;
		    * Category: Manage;
		    * </classorder>
	*/

	public function classorder(Request $request) {
		$this->validate($request, ['userId' => 'required|integer|exists:users,usr_id']);
		$user = $request->input('userId');
		$optionsData = $request->input('options');
		$dbArray = array();
		$i = 1;
		$delData = array();
		$dat = array();
		$options = json_decode($optionsData);
		foreach ($options as $data) {
			$dat['co_usr_id'] = $user;
			$dat['co_class_id'] = $data;
			$dat['co_order'] = $i;
			$dbArray[] = $dat;
			$i++;
		}
		$delData['deleted_at'] = Carbon::now();
		if (count($dbArray)) {
			DB::table('class_order')->where('co_usr_id', $user)->update($delData);
			DB::table('class_order')->insert($dbArray);
		}
		$dataArray = array();
		$baseSelectQuery = DB::table('class');
		$baseSelectQuery->join('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
		$baseSelectQuery->join('counter_attractions', 'counter_attractions.ca_attr_id', '=', 'attraction.attr_id');
		$baseSelectQuery->join('counter', 'counter.counter_id', '=', 'counter_attractions.ca_counter_id');
		$baseSelectQuery->join('user_counters', 'user_counters.uc_counter_id', '=', 'counter.counter_id');
		$baseSelectQuery->leftjoin("class_order", function ($join) {
			$join->on("class_order.co_class_id", "=", "class.class_id")
				->on("class_order.co_usr_id", "=", "user_counters.uc_usr_id")
				->whereNull('class_order.deleted_at');
		});
		$baseSelectQuery->where('user_counters.uc_usr_id', '=', $user);
		$baseSelectQuery->whereNull('class.deleted_at');
		$baseSelectQuery->whereNull('attraction.deleted_at');
		$baseSelectQuery->whereNull('counter_attractions.deleted_at');
		$baseSelectQuery->whereNull('user_counters.deleted_at');
		$baseSelectQuery->whereNull('counter.deleted_at');
		$baseSelectQuery->orderBy('class_order.co_order');
		$baseSelectQuery->orderBy('class.class_number');
		$dataArray = $baseSelectQuery->select('class.*', 'class_order.*', 'attraction.*')->get();
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Order changed.!', 'Data' => $dataArray]);

	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/19/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <Give permmision >
		    * Name: Give PErmission;
		    * Description: Give permission to users
		    * Action Type: Application;
		    * Category: Manage;
		    * </give permission>
	*/

	public function givePermission(Request $request) {
		$this->validate($request, ['permissionid' => 'required|integer|exists:permissions,id', 'mainroleid' => 'required|integer|exists:roles,id', 'status' => 'required|integer']);
		$role_id = $request->input('mainroleid');
		$permissionId = $request->input('permissionid');
		$status = $request->input('status');
		$dbArray['permission_id'] = $permissionId;
		$dbArray['role_id'] = $role_id;
		$dbArray['u_createdby'] = $request->input('userid');
		$finalDbArray[] = $dbArray;
		$roleData['pu_permission_id'] = $permissionId;
		$roleData['pu_status'] = $status;
		$roleData['u_modifiedby'] = $request->input('userid');
		$roleData['modified_at'] = Carbon::now();
		$dataArray['deleted_at'] = Carbon::now();

		if ($status == OT_YES) {
			DB::table('permission_role')->insert($finalDbArray);
		} else {
			$dataArray['u_deletedby'] = $request->input('userid');
			$user = DB::table('permission_role')->where('permission_id', $permissionId)
				->where('role_id', '=', $role_id)->update($dataArray) /*->delete()*/;
		}

		// DB::table('permission_user')
		// 	->whereExists(function ($query) use ($role_id,$permissionId) {
		// 		$query->select(DB::raw(1))
		// 			->from('users')
		// 			->where('users.role_id', '=', $role_id)
		// 			->whereRaw('users.usr_id = permission_user.pu_usr_id');
		// 	})
		//           ->where('pu_permission_id','=',$permissionId)
		// 	->update($roleData);
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Permission changed.!']);

	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/19/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <assignPermission>
		    * Name: Give PErmission;
		    * Description: Give permission to users
		    * Action Type: Application;
		    * Category: Manage;
		    * </assignPermission>
	*/

	public function assignPermission(Request $request) {
		$this->validate($request, ['permissionid' => 'required|integer|exists:permissions,id', 'usrid' => 'required|integer|exists:users,usr_id', 'status' => 'required|integer']);
		$usrid = $request->input('usrid');
		$permissionId = $request->input('permissionid');
		$status = $request->input('status');
		$dataArray['pu_permission_id'] = $permissionId;
		$dataArray['pu_usr_id'] = $usrid;
		$dataArray['pu_status'] = $status;

		$permission = DB::table('permission_user')->where('pu_permission_id', '=', $permissionId)->where('pu_usr_id', '=', $usrid)->where('deleted_at', NULL)->pluck('pu_id');
		if ($permission != '[]') {
			$dataArray['u_modifiedby'] = $request->input('acluserid');
			$dataArray['modified_at'] = Carbon::now();
			$user = DB::table('permission_user')->where('pu_permission_id', $permissionId)
				->where('pu_usr_id', '=', $usrid)->update($dataArray);
		} else {
			$dataArray['u_createdby'] = $request->input('acluserid');
			DB::table('permission_user')->insert($dataArray);
		}

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Permission changed.!']);

	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/19/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <Create Role>
		    * Name: Create Role;
		    * Description: Create role
		    * Action Type: Application;
		    * Category: Manage;
		    * </create role>
	*/
	public function roleCreate(Request $request) {
		$this->validate($request, ['name' => 'required', 'description' => 'required']);
		$roleName = DB::table('roles')->where('name', '=', $request->input('name'))->where('deleted_at', NULL)->pluck('name');
		if ($roleName != '[]') {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Role name already exist']);
		}

		$dbArray = [];
		$dbArray['name'] = $request->input('name');
		$dbArray['description'] = $request->input('description');
		DB::table('roles')->insert($dbArray);
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Role inserted successfully..!']);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/19/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <Delete Role>
		    * Name: Delete Role;
		    * Description: Delete role
		    * Action Type: Application;
		    * Category: Manage;
		    * </Delete role>
	*/
	public function roleDelete(Request $request) {
		$this->validate($request, ['roleId' => 'required|integer']);
		$checkStatus = DB::table('users')->where('role_id', '=', $request->input('roleId'))->pluck('usr_id');
		if ($checkStatus != '[]') {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Canot delete Role.Role already assigned for users..!']);
		}

		DB::table('permission_role')->where('role_id', '=', $request->input('roleId'))->delete();
		$dbArray['deleted_at'] = Carbon::now();
		if (DB::table('roles')->where('id', '=', $request->input('roleId'))->update($dbArray) /*->delete()*/) {
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Role deleted Successfully..!']);
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Role not exist..!']);
		}

	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/19/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <List Role>
		    * Name: List Role;
		    * Description: List Role
		    * Action Type: Application;
		    * Category: Manage;
		    * </List Role>
	*/
	public function roleList(Request $request) {

		$dataArray = DB::table('roles')->select('*')->where('deleted_at', NULL)->orderBy('name')->get();
		return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Success..!', 'Data' => $dataArray]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/19/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <View permision of role>
		    * Name: View permission of role;
		    * Description: View permission of role
		    * Action Type: Application;
		    * Category: Manage;
		    * </View permission of role>
	*/
	public function viewPermissionRole(Request $request) {
		$this->validate($request, ['mainroleid' => 'required|integer|exists:roles,id']);
		$role_id = $request->input('mainroleid');
		$baseSelectQuery = DB::table('permissions')->select("*");
		$baseSelectQuery->leftjoin('permission_role', function ($join) use ($role_id) {
			$join->where('permission_role.deleted_at', NULL);
			$join->where('permission_role.role_id', '=', $role_id);
			$join->on('permission_role.permission_id', '=', 'permissions.id');
		})
			->where('permissions.category', '=', OT_YES);
		$baseSelectQuery->where('permissions.deleted_at', NULL);
		if ($request->input('searchdata')) {
			$baseSelectQuery->where('permissions.name', 'ILIKE', '%' . $request->input('searchdata') . '%');
		}
		if ($request->input('category')) {
			$baseSelectQuery->where('permissions.search_category', '=', $request->input('category'));
		}
		$permissionArray = $baseSelectQuery->orderBy('permissions.name')->get();
		$tempArray = [];
		$i = 0;
		$baseSelectQuery = DB::table('roles')->select("*");
		$roledata = $baseSelectQuery->where('roles.id', '=', $role_id)->get();
		foreach ($permissionArray as $permission) {
			$exist = false;
			$tempArray[$i]['permissionId'] = $permission->id;
			$tempArray[$i]['name'] = $permission->name;
			$tempArray[$i]['url'] = $permission->per_pwa_url;
			$tempArray[$i]['category'] = $permission->search_category;
			if ($permission->pr_id) {
				$exist = true;
			}

			$tempArray[$i]['status'] = $exist;
			$tempArray[$i]['orgstatus'] = $exist;
			$i++;
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success..!', 'Data' => $tempArray, 'Roledata' => $roledata]);

	}
	public function searchpermission(Request $request) {
		$this->validate($request, ['roleid' => 'required|integer|exists:roles,id']);
		$role_id = $request->input('roleid');
		$permissionArray = DB::table('permissions')->where('name', 'ILIKE', '%' . $request->input('searchdata'))
			->orwhere('name', 'ILIKE', $request->input('searchdata') . '%')
			->orwhere('name', 'ILIKE', '%' . $request->input('searchdata') . '%')
			->orwhere('search_category', 'ILIKE', $request->input('category') . '%')
			->orwhere('search_category', 'ILIKE', '%' . $request->input('category') . '%')
			->orwhere('search_category', 'ILIKE', '%' . $request->input('category'))
			->where('deleted_at', NULL)
			->select('*')->get();
		$assignedPermissionArray = app('db')->select("SELECT * from permissions WHERE id in(SELECT permission_id FROM permission_role  WHERE role_id='$role_id');");
		$tempArray = [];
		$resultArray = [];
		foreach ($permissionArray as $permission) {
			$exist = false;
			$tempArray['name'] = $permission->name;
			$tempArray['url'] = $permission->Url;
			$tempArray['category'] = $permission->search_category;
			foreach ($assignedPermissionArray as $assigned) {
				if ($permission->Url == $assigned->Url) {
					$exist = true;
				}
			}
			if ($exist == true) {
				$tempArray['status'] = true;
			} else {
				$tempArray['status'] = false;
			}

			$resultArray[] = $tempArray;
		}
		return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Success..!', 'Data' => $resultArray]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/19/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <List permission>
		    * Name: List permission;
		    * Description: List permission
		    * Action Type: Application;
		    * Category: Manage;
		    * </List permission>
	*/
	public function permissionList(Request $request) {
		$this->validate($request, ['mainroleid' => 'required|integer|exists:roles,id', 'usrid' => 'required|integer|exists:users,usr_id']);
		$role_id = $request->input('mainroleid');
		$usrid = $request->input('usrid');
		$baseSelectQuery = DB::table('permissions')->select("*");
		$baseSelectQuery->leftjoin('permission_role', function ($join) use ($role_id) {
			$join->where('permission_role.deleted_at', NULL);
			$join->where('permission_role.role_id', '=', $role_id);
			$join->on('permission_role.permission_id', '=', 'permissions.id');
		})
			->where('permissions.category', '=', OT_YES);
		$baseSelectQuery->leftjoin('permission_user', function ($join) use ($usrid) {
			$join->where('permission_user.deleted_at', NULL);
			$join->where('permission_user.pu_usr_id', '=', $usrid);
			$join->on('permission_user.pu_permission_id', '=', 'permissions.id');
		});
		$baseSelectQuery->where('permissions.deleted_at', NULL);
		if ($request->input('searchdata')) {
			$baseSelectQuery->where('permissions.name', 'ILIKE', '%' . $request->input('searchdata') . '%');
		}
		if ($request->input('category')) {
			$baseSelectQuery->where('permissions.search_category', '=', $request->input('category'));
		}
		$permissionArray = $baseSelectQuery->orderBy('permissions.name')->get();
		$tempArray = [];
		$i = 0;
		$baseSelectQuery = DB::table('roles')->select("*");
		$roledata = $baseSelectQuery->where('roles.id', '=', $role_id)->get();
		foreach ($permissionArray as $permission) {
			$exist = false;
			$tempArray[$i]['permissionId'] = $permission->id;
			$tempArray[$i]['name'] = $permission->name;
			$tempArray[$i]['url'] = $permission->per_pwa_url;
			$tempArray[$i]['category'] = $permission->search_category;
			if ($permission->pr_id) {
				$exist = true;
			}

			if ($permission->pu_id && $permission->pu_status == OT_YES) {
				$exist = true;
			}

			if ($permission->pu_id && $permission->pu_status == OT_NO) {
				$exist = false;
			}

			$tempArray[$i]['status'] = $exist;
			$tempArray[$i]['orgstatus'] = $exist;
			$i++;
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success..!', 'Data' => $tempArray, 'Roledata' => $roledata]);

	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/19/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <Remove permission>
		    * Name: Remove permission;
		    * Description: Remove permission
		    * Action Type: Application;
		    * Category: Manage;
		    * </Remove permission>
	*/
	public function removePermssion(Request $request) {
		$this->validate($request, ['permissionId' => 'required|integer|exists:permissions,id', 'roleId' => 'required|integer|exists:roles,id']);
		/*$dbArray['deleted_at'] = Carbon::now();*/
		if (DB::table('permission_role')->where('permission_id', '=', $request->input('permissionId'))->where('role_id', '=', $request->input('roleId')) /*->update($dbArray)*/->delete()) {
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Deleted..!']);
		} else {
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Role has no permmsion..!']);
		}

	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/28/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <getData>
		    * Name: getData;
		    * Description: Settings
		    * Action Type: Application;
		    * Category: Manage;
		    * </Get getData>
	*/
	public function getData(Request $request) {
		$this->validate($request, ['id' => 'required|integer|exists:users,usr_id']);

		/*---*/
		$destArray = [];
		$attractions = [];
		$count = 0;
		$destId = DB::table('users')->where('usr_id', '=', $request->input('id'))->where('deleted_at', NULL)->pluck('dest_id');
		$destinationId = $destId[0];
		/*if($destinationId != null){
			            $destName=DB::table('destination')->where('dest_id', '=', $destinationId)->where('status','=',OT_YES)->get();
			            $attractionArray = DB::table('attraction')->select('*')->where('attr_dest_id',$destinationId)->where('deleted_at',NULL)->where('status','=',OT_YES)->get();
		*/
		$destName = DB::table('destination')->select('*')->where('deleted_at', NULL)->where('status', '=', OT_YES)->get();
		$attractionArray = DB::table('attraction')->select('*')->where('deleted_at', NULL)->where('status', '=', OT_YES)->get();
		// }

		foreach ($destName as $dest) {
			$destArray[$count]['destName'] = $dest->dest_name;
			$destArray[$count]['destId'] = $dest->dest_id;
			$count++;
		}
		foreach ($attractionArray as $dest) {
			$attractions[$dest->attr_dest_id][] = array('attrName' => $dest->attr_name, 'attrId' => $dest->attr_id);
		}
		/*end attraction fetch */
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success...!', 'attrData' => $attractions, 'destData' => $destArray]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/28/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <getMenu>
		    * Name: getMenu;
		    * Description: Get Accessed Menu Items
		    * Action Type: Application;
		    * Category: Manage;
		    * </Get getMenu>
	*/
	public function getMenu(Request $request) {
		$this->validate($request, ['roleid' => 'required|integer|exists:roles,id', 'userid' => 'required|integer|exists:users,usr_id']);

		/*---*/
		$menuArray = [];
		$menuUrls = [];
		$count = 0;
		$counta = 0;

		/*SELECT * from permission_role join permissions on permissions.id=permission_role.permission_id AND permission_role.deleted_at IS NULL
                                            WHERE permission_role.deleted_at IS NULL AND  permission_role.role_id=".$request->input('roleid'));*/

		$results = DB::select("
                                SELECT permissions.*,permission_user.* ,permission_role.*   FROM permissions
                                LEFT JOIN  permission_user on permission_user.pu_permission_id = permissions.id AND permission_user.pu_usr_id = " . $request->input('userid') . " AND permission_user.deleted_at IS NULL
                                LEFT JOIN  permission_role on permission_role.permission_id = permissions.id AND permission_role.role_id = " . $request->input('roleid') . " and permission_role.deleted_at IS NULL
                                WHERE permissions.deleted_at IS NULL ");
		foreach ($results as $res) {
			$isPermision = OT_NO;
			if ($res->pu_permission_id != NULL) {
				//Check User Permission
				if ($res->pu_status == OT_YES) {
					$isPermision = OT_YES;
				}

			} else if ($res->pr_id != NULL) {
				//Check Role Permission
				//if ( $res->status == OT_YES  )
				$isPermision = OT_YES;
			}
			if ($res->per_menu && $isPermision == OT_YES) {
				$menuArray[$count++] = $res->per_menu;
			}
			$menuUrls[$counta++] = $res->per_pwa_url;

		}
		/*end attraction fetch */
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success...!', 'menuData' => $menuArray, 'menuUrls' => $menuUrls]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/28/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <getUgrpData>
		    * Name: getUgrpData;
		    * Description: Settings
		    * Action Type: Application;
		    * Category: Manage;
		    * </Get getUgrpData>
	*/
	public function getUgrpData(Request $request) {
		/*---*/
		$ugrptArray = [];
		$destArray = [];
		$count = 0;
		$roles = [];
		if ($request->input('ugrpAllow')) {
			$destName = DB::table('destination')->select('*')->where('deleted_at', NULL)->where('status', '=', OT_YES)->where('dest_type', $request->input('destAllow'))->get();
			$usergrp = DB::table('usergroups')->select('*')->where('deleted_at', NULL)->where('status', '=', OT_YES)->where('ugrp_destination_allowed', $request->input('ugrpAllow'))->get();
		} else {
			$destName = DB::table('destination')->select('*')->where('deleted_at', NULL)->where('status', '=', OT_YES)->get();
			$usergrp = DB::table('usergroups')->select('*')->where('deleted_at', NULL)->where('status', '=', OT_YES)->get();
		}
		foreach ($usergrp as $ug) {
			$ugrptArray[$count]['ugrpName'] = $ug->ugrp_name;
			$ugrptArray[$count]['ugrpId'] = $ug->ugrp_id;
			$count++;
		}
		$count = 0;
		foreach ($destName as $dest) {
			$destArray[$count]['destName'] = $dest->dest_name;
			$destArray[$count]['destId'] = $dest->dest_id;
			$count++;
		}

		/*end attraction fetch */
		/*--fetch the roles..*/
		$count = 0;
		$rolesData = DB::table('roles')->select('*')->where('deleted_at', NULL)->where('status', '=', OT_YES)->get();
		foreach ($rolesData as $role) {
			$roles[$count]['rolename'] = $role->name;
			$roles[$count]['roleid'] = $role->id;
			$count++;
		}

		$alertArray = array();
		try {
			$alertObj = DB::table('alert')->select('*')->where('deleted_at', NULL)->where('alert_type', '=', 1)->get();
			foreach ($alertObj as $role) {
				$temp['alertId'] = $role->alert_id;
				$temp['alertName'] = $role->alert_name;
				$alertArray[] = $temp;
				$temp = NULL;
			}
		} catch (\Exception $e) {}

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success...!', 'ugrpData' => $ugrptArray, 'destinations' => $destArray, 'roles' => $roles, 'alert' => $alertArray]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/28/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <Settings>
		    * Name: Settings;
		    * Description: Settings
		    * Action Type: Application;
		    * Category: Manage;
		    * </Get settings>
	*/
	public function settings(Request $request) {
		$this->validate($request, ['id' => 'required|integer|exists:users,usr_id']);
		/*---Dynamic settings---*/
		$today = date('Y-m-d');
		$dateArray = [];
		$constNameArray = [];
		$dateArray = [];
		$resultArray = [];
		$tempArray = [];
		$constNameArray = DB::table('core_constants')->whereDate('const_affective_date', '<=', $today)
			->select('*')
			->where('deleted_at', NULL)
			->distinct('const_name')->get();
		$dateArray = DB::table('core_constants')->whereDate('const_affective_date', '<=', $today)
			->select('*')
			->where('deleted_at', NULL)
			->orderBy('const_affective_date', 'DESC')->get();

		foreach ($constNameArray as $conName) {
			$match = false;
			foreach ($dateArray as $data) {
				if ($match == false) {
					if ($conName->const_name == $data->const_name) {
						$tempArray[] = $data;
						$match = true;
					}
				}
				$resultArray = $tempArray;
			}
		}

		/*---*/
		$tempArray = [];
		$destId = DB::table('users')->where('usr_id', '=', $request->input('id'))->pluck('dest_id');
		$responseArray['destId'] = $destId[0];
		if ($responseArray['destId'] != null) {
			$destName = DB::table('destination')->where('dest_id', '=', $destId[0])->pluck('dest_name');
			$responseArray['destName'] = $destName[0];

		} else {
			$destName = DB::table('destination')->select('*')->where('deleted_at', NULL)->get();
			$count = 0;
			foreach ($destName as $dest) {
				$tempArray[$count]['destName'] = $dest->dest_name;
				$tempArray[$count]['destId'] = $dest->dest_id;
				$count++;
			}
			$responseArray['destName'] = $tempArray;
		}
		$groupId = DB::table('users')->where('usr_id', '=', $request->input('id'))->pluck('ugrp_id');
		$responseArray['ugrpId'] = $groupId[0];
		if ($groupId[0] != NULL) {
			$destiNationAllowed = DB::table('usergroups')->where('ugrp_id', '=', $groupId[0]) /*->where('deleted_at',NULL)*/->pluck('ugrp_destination_allowed');
			if ($destiNationAllowed[0] == OT_YES) {
				$groupName = DB::table('usergroups')->select('*')->where('ugrp_destination_allowed', '=', OT_YES)->where('deleted_at', NULL)->get();

				$count = 0;
				$tempArray = [];
				foreach ($groupName as $grp) {
					$tempArray[$count]['ugrp_id'] = $grp->ugrp_id;
					$tempArray[$count]['ugrp_name'] = $grp->ugrp_name;
					$count++;
				}
				$responseArray['destinationAllowed'] = true;
				$responseArray['usrGroup'] = $tempArray;
			} else {
				$groupName = DB::table('usergroups')->select('*')->where('deleted_at', NULL)->get();

				$count = 0;
				$tempArray = [];
				foreach ($groupName as $grp) {
					$tempArray[$count]['ugrp_id'] = $grp->ugrp_id;
					$tempArray[$count]['ugrp_name'] = $grp->ugrp_name;
					$count++;
				}
				$responseArray['usrGroup'] = $tempArray;
				$responseArray['destinationAllowed'] = false;
			}

		}

		/* fetch counter data */
		$counter = array();
		$destFetcher = DB::table('destination');
		$destFetcher->leftjoin('counter', 'counter.counter_dest_id', '=', 'destination.dest_id');
		$destFetcher->where('destination.deleted_at', NULL);
		$destFetcher->where('counter.deleted_at', NULL);
		$counterData = $destFetcher->select('destination.*', 'counter.*')->get();
		foreach ($counterData as $data) {
			$counter[$data->dest_id][] = array('counterId' => $data->counter_id, 'counterName' => $data->counter_name);
		}

		////fetch the linked counters of the login user
		$linkedCounters = array();
		$counterFetcher = DB::table('user_counters');
		$counterFetcher->leftjoin('counter', 'counter.counter_id', '=', 'user_counters.uc_counter_id');
		$counterFetcher->where('user_counters.deleted_at', NULL);
		$counterFetcher->where('counter.deleted_at', NULL);
		$counterFetcher->where('user_counters.uc_usr_id', '=', $request->input('id'));
		$counterLinkData = $counterFetcher->select('user_counters.*', 'counter.*')->get();
		foreach ($counterLinkData as $data) {
			$status = false;
			if ($data->counter_active_status == OT_YES) {
				$status = true;
			}

			$linkedCounters[] = array('counterId' => $data->counter_id, 'counterName' => $data->counter_name, 'counterStatus' => $status);
		}
		//counter linking end

/*
/*attractions fetch
$tempArray = [];
$tempAttr = [];
if ($destId[0] == NULL){
$baseSelectQuery = DB::table('class');
$baseSelectQuery ->leftjoin('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
$baseSelectQuery ->leftjoin('destination', 'destination.dest_id', '=', 'class.class_dest_id');
$baseSelectQuery->where('class.deleted_at',NULL);
$baseSelectQuery->where('attraction.deleted_at',NULL);
$baseSelectQuery->where('destination.deleted_at',NULL);
$dataArray=  $baseSelectQuery->select('class.*','attraction.*','destination.*')->get();
$destinationArray = DB::table('destination')->select('*')->where('deleted_at',NULL)->get();
$attractionArray = DB::table('attraction')->select('*')->where('deleted_at',NULL)->get();
foreach ( $destinationArray as $destArr ){
foreach ( $attractionArray as $attArr){
foreach ( $dataArray as $dataArr){
if ( $destArr->dest_id == $dataArr->dest_id){
if ( $attArr->attr_id == $dataArr->attr_id){
$tempArray [ $dataArr->class_id ] = $dataArr->class_name;
}
}
}
$sortByAttr [ $attArr->attr_id ] = $tempArray;
$tempArray = [];
if ( $destArr->dest_id == $attArr->attr_dest_id ){
$tempAttr [ $attArr->attr_id ] = $attArr->attr_name;
}
}
$sortByDest [ $destArr->dest_id ]['class'] = $sortByAttr;
$sortByDest [ $destArr->dest_id ]['attraction'] = $tempAttr;
$sortByAttr = [];
$attractionSort = [];
$tempAttr = [];
}
}else{
$baseSelectQuery = DB::table('class');
$baseSelectQuery ->leftjoin('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
$baseSelectQuery ->leftjoin('destination', 'destination.dest_id', '=', 'class.class_dest_id');
$baseSelectQuery->where('class.deleted_at',NULL);
$baseSelectQuery->where('attraction.deleted_at',NULL);
$baseSelectQuery->where('destination.dest_id',$destId[0]);
$dataArray=  $baseSelectQuery->select('class.*','attraction.*','destination.*')->get();
$destinationArray = DB::table('destination')->select('*')->where('dest_id',$destId[0])->get();
$attractionArray = DB::table('attraction')->select('*')->where('deleted_at',NULL)->get();
foreach ( $destinationArray as $destArr ){
foreach ( $attractionArray as $attArr){
foreach ( $dataArray as $dataArr){
if ( $destArr->dest_id == $dataArr->dest_id){
if ( $attArr->attr_id == $dataArr->attr_id){
$tempArray [ $dataArr->class_id ] = $dataArr->class_name;
}
}
}
$sortByAttr [ $attArr->attr_id ] = $tempArray;
$tempArray = [];
if ( $destArr->dest_id == $attArr->attr_dest_id ){
$tempAttr [ $attArr->attr_id ] = $attArr->attr_name;
}
}
$sortByDest [ $destArr->dest_id ]['class'] = $sortByAttr;
$sortByDest [ $destArr->dest_id ]['attraction'] = $tempAttr;
$sortByAttr = [];
$attractionSort = [];
}
}
/*end attraction fetch */

		$sortByDest = array();
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success...!', 'counterData' => $counter, 'linkedCounters' => $linkedCounters, 'Data' => $responseArray, 'DynamicSettings' => $resultArray, 'classDetils' => $sortByDest]);
	}

	public function searchUser(Request $request) {
		$this->validate($request, ['data' => 'required']);
		if ($request->input($this->paginator) != '') {
			$dataArray = DB::table('users')->where('usr_name', 'ilike', '%' . $request->input('data') . '%')
				->orwhere('usr_name', 'ilike', $request->input('data') . '%')
				->orwhere('usr_name', 'ilike', '%' . $request->input('data'))
				->orwhere('usr_user_name', 'ilike', '%' . $request->input('data') . '%')
				->orwhere('usr_user_Name', 'ilike', $request->input('data') . '%')
				->orwhere('usr_user_Name', 'ilike', '%' . $request->input('data'))
				->orwhere('usr_mobile', 'ilike', '%' . $request->input('data') . '%')
				->orwhere('usr_mobile', 'ilike', $request->input('data') . '%')
				->orwhere('usr_mobile', 'ilike', '%' . $request->input('data'))
				->where('deleted_at', NULL)
				->paginate($request->input($this->paginator));
		} else {
			$dataArray = DB::table('users')->where('usr_name', 'ilike', '%' . $request->input('data') . '%')
				->orwhere('usr_name', 'ilike', $request->input('data') . '%')
				->orwhere('usr_name', 'ilike', '%' . $request->input('data'))
				->orwhere('usr_user_Name', 'ilike', '%' . $request->input('data') . '%')
				->orwhere('usr_user_name', 'ilike', $request->input('data') . '%')
				->orwhere('usr_user_name', 'ilike', '%' . $request->input('data'))
				->orwhere('usr_mobile', 'ilike', '%' . $request->input('data') . '%')
				->orwhere('usr_mobile', 'ilike', $request->input('data') . '%')
				->orwhere('usr_mobile', 'ilike', '%' . $request->input('data'))
				->where('deleted_at', NULL)
				->get();

		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);
	}
	public function createRout(Request $request) {
		try {
			$this->validate($request, ['root' => 'required', 'name' => 'required', 'category' => 'required|integer']);
			$dbArray['acl_rootes'] = $request->input('root');
			$dbArray['acl_name'] = $request->input('name');
			$dbArray['category'] = $request->input('category');
			DB::table('acl_rootes')->insert($dbArray);
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success']);
		} catch (Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'RunTime Exception Occured']);
		}
	}

	public function createPermission(Request $request) {
		try {
			$this->validate($request, ['name' => 'required', 'url' => 'required', 'type' => 'required|integer', 'category' => 'required']);
			$dbArray['name'] = $request->input('name');
			$dbArray['Url'] = $request->input('url');
			$dbArray['category'] = $request->input('type');
			$dbArray['search_category'] = $request->input('category');
			DB::table('permissions')->insert($dbArray);
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success']);
		} catch (Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'RunTime Exception Occured']);
		}

	}
	public function listcategory(Request $request) {
		$data = DB::table('permissions')->distinct()->where('deleted_at', NULL)->get(['search_category']);
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $data]);

	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 10/28/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <Settings>
		    * Name: Settings;
		    * Description: Settings
		    * Action Type: Application;
		    * Category: Manage;
		    * </Get settings>
	*/
	public function getcounterlist(Request $request) {
		$this->validate($request, ['id' => 'required|integer|exists:users,usr_id']);

		////fetch the linked counters of the login user
		$linkedCounters = array();
		$counterFetcher = DB::table('user_counters');
		$counterFetcher->leftjoin('counter', 'counter.counter_id', '=', 'user_counters.uc_counter_id');
		$counterFetcher->where('user_counters.deleted_at', NULL);
		$counterFetcher->where('counter.deleted_at', NULL);
		$counterFetcher->where('counter.status', '=', OT_YES);
		$counterFetcher->where('user_counters.uc_usr_id', '=', $request->input('id'));
		$user = $request->input('id');
		$counterFetcher->leftjoin('counter_history', function ($join) use ($user) {
			$join->on('counter_history.ch_counter_id', '=', 'counter.counter_id');
			/// $join->where('ch_is_latest',OT_YES);
			$join->where('ch_usr_id', $user);
		});
		$counterFetcher->leftjoin('users', 'users.usr_id', '=', 'counter_history.ch_usr_id');
		$counterFetcher->select('users.*', 'user_counters.*', 'counter.*', 'counter_history.*', DB::raw("(SELECT SUM(tp_rate) AS totalamount FROM  ticket_print WHERE  tp_is_cancelled=" . OT_NO . " AND  deleted_at IS NULL AND tp_ch_id=counter_history.ch_id Group BY tp_ch_id )"), DB::raw("(SELECT count(tp_id) AS totalticket FROM  ticket_print WHERE  tp_is_cancelled=" . OT_NO . " AND  deleted_at IS NULL AND tp_ch_id=counter_history.ch_id Group BY tp_ch_id )"));
		$counterLinkData = $counterFetcher->orderBy('counter_name')->get();
		$counterId = '';
		$ticketNumber = 0;
		$countArray = array();
		$userCounter = 0;
		foreach ($counterLinkData as $data) {
			$status = false;
			if ($data->counter_active_status == OT_YES) {
				$status = true;
				//if ($data->counter_active_usr_id==$request->input('id')) {
				$counterId = $data->counter_id;
				// $ticket = DB::table('ticket_print')->where('ticket_print.tp_usr_id','=',$request->input('id'))->max('tp_number');
				// $ticketNumber = $ticket;
				//}
			}
			$ticket = DB::table('ticket_print')->where('ticket_print.tp_usr_id', '=', $request->input('id'))->max('tp_number');
			$ticketNumber = $ticket;
			if (!in_array($data->counter_id, $countArray)) {
				$countArray[] = $data->counter_id;
				$linkedCounters[] = array('counterId' => $data->counter_id, 'counterName' => $data->counter_name, 'counterStatus' => $status, 'counterUser' => $data->counter_active_usr_id, 'rec_user' => $data->usr_name, 'rec_optime' => ($data->ch_opening_time) ? date('d/m/Y H:i:s', strtotime($data->ch_date . $data->ch_opening_time)) : '', 'rec_cltime' => ($data->ch_closing_time) ? date('d/m/Y H:i:s', strtotime($data->ch_date . $data->ch_closing_time)) : '', 'ticket' => $data->totalticket, 'cash' => $data->totalamount);
			}
			if ($data->ch_usr_id == $user) {
				$userCounter = $data->ch_counter_id;
			}
		}

		//counter linking end

		//fetch counter data by userid
		$cdata = array();
		$attractionData = array();
		$counterId = $userCounter;
		if ($counterId) {
			$baseSelectQuery = DB::table('counter');
			$baseSelectQuery->leftjoin('counter_history', 'counter_history.ch_counter_id', '=', 'counter.counter_id');
			$baseSelectQuery->where('counter.counter_id', '=', $counterId);
			$baseSelectQuery->where('counter_history.ch_usr_id', '=', $request->input('id'));
			$baseSelectQuery->whereNull('counter_history.deleted_at');
			$baseSelectQuery->whereNull('counter.deleted_at');
			$baseSelectQuery->whereNull('counter_history.ch_closing_time');
			$counterData = $baseSelectQuery->select('counter.*', 'counter_history.*')->latest('ch_id')->first();
			if ($counterData) {
				$cdata['opentime'] = date('H:i:s', strtotime('+5 hour +30 minutes', strtotime($counterData->ch_date . $counterData->ch_opening_time)));
				$cdata['cash'] = $counterData->ch_total_cash;
				$cdata['tickets'] = $counterData->ch_no_ticket;
				$cdata['counter'] = $counterData->counter_name;
				$cdata['counterId'] = $counterData->counter_id;
				$cdata['counterName'] = $counterData->counter_id;
				$cdata['counterNo'] = $counterData->counter_no;
			}

			//fetch the attraction info
			$baseSelectQuery = DB::table('counter_attractions');
			$baseSelectQuery->leftjoin('attraction', 'attraction.attr_id', '=', 'counter_attractions.ca_attr_id');
			$baseSelectQuery->leftjoin('class', 'class.class_attr_id', '=', 'attraction.attr_id');
			$baseSelectQuery->where('class.deleted_at', NULL);
			$baseSelectQuery->where('counter_attractions.ca_counter_id', '=', $counterId);
			$baseSelectQuery->where('counter_attractions.deleted_at', NULL);
			$baseSelectQuery->where('attraction.status', '=', OT_YES);
			$baseSelectQuery->where('class.status', '=', OT_YES);
			$dataArray = $baseSelectQuery->select('attraction.*', 'class.*')->orderBy('attr_name')->get();
			//dd(DB::getQueryLog());
			$count = 0;
			$attrIds = array();
			foreach ($dataArray as $data) {
				if (!in_array($data->attr_id, $attrIds)) {
					$attrIds[$count] = $data->attr_id;
					$count++;
				}
				$key = array_search($data->attr_id, $attrIds);
				$attractionData[$key]['attrName'] = $data->attr_name;
				$attractionData[$key]['attrId'] = $data->attr_id;
				$attractionData[$key]['ticketconfig'] = $data->attr_ticket_config;
				$attractionData[$key]['classes'][] = array('classno' => $data->class_number, 'className' => $data->class_name, 'classId' => $data->class_id, 'classRate' => $data->class_rate, 'noticket' => $data->available_numbers, 'cgst' => $data->class_cgst_per, 'sgst' => $data->class_sgst_per);
				$count++;
			}
		}
		//Get all counters of a destination
		$baseSelectQuery = DB::table('counter');
		$baseSelectQuery->leftjoin('users', 'users.dest_id', '=', 'counter.counter_dest_id');
		$baseSelectQuery->where('counter.status', '=', OT_YES);
		$baseSelectQuery->where('users.status', '=', OT_YES);
		$baseSelectQuery->where('users.usr_id', '=', $request->input('id'));
		$dataArray = $baseSelectQuery->select('users.*', 'counter.*')->orderBy('counter_name')->get();
		$count = 0;
		$attrIds = array();
		$allCounters = array();
		foreach ($dataArray as $data) {
			$allCounters[$data->counter_id] = array('name' => $data->counter_name, 'no' => $data->counter_no);
			$count++;
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success...!', 'linkedCounters' => $linkedCounters, 'activeCounter' => $counterId, 'data' => $cdata, 'attrData' => $attractionData, 'ticketNumber' => $ticketNumber, 'allCounter' => $allCounters]);
	}
	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 11/05/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <createdest>
		    * Name: createdest;
		    * Description: insert into destination
		    * Action Type: Application;
		    * Category: Manage;
		    * </createdest>
	*/

	public function createdest(Request $request) {

		$this->validate($request, [
			'name' => 'required|String',
			'code' => 'required|String',
			'place' => 'required|String',
			'desttype' => 'required|integer',
			'phoneno' => 'required|regex:/[0-9]{10}/',
			'pincode' => 'required|String',
			'booking' => 'required|integer',
			'description' => 'required|String',
			'timing' => 'required|String',
			'email' => 'required|email',
			'website' => 'required|String',
			'termsticket' => 'required|integer',
			'paymode' => 'required|integer']);

		try {
			$data = [];

			if ($request->input('endtime')) {
				$data['dest_book_end_time'] = $request->input('endtime');
			}

			if ($request->input('startday')) {
				$data['dest_book_start_day'] = $request->input('startday');
			}

			if ($request->input('maxbookday')) {
				$data['dest_book_limit_day'] = $request->input('maxbookday');
			}

			if ($request->input('maxpax')) {
				$data['dest_max_pax'] = $request->input('maxpax');
			}

			if ($request->input('destid')) {
				$data['dest_parent'] = $request->input('destid');
			}

			$data['dest_name'] = $request->input('name');
			$data['dest_code'] = $request->input('code');
			$data['dest_is_public'] = $request->input('booking');
			$data['dest_place'] = $request->input('place');
			$data['dest_pincode'] = $request->input('pincode');
			$data['dest_desc'] = $request->input('description');
			$data['dest_timing'] = $request->input('timing');
			$data['dest_email'] = $request->input('email');
			$data['dest_website'] = $request->input('website');
			$data['dest_phone'] = $request->input('phoneno');
			$data['dest_type'] = $request->input('desttype');
			$data['dest_display_terms_ticket'] = $request->input('termsticket');
			$data['dest_paymode'] = $request->input('paymode');
			$data['dest_gstin'] = $request->input('gstin');
			DB::table('destination')->insert($data);

			$destId = DB::getPdo()->lastInsertId();
			return response(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Destination registration has been success', 'id' => $destId, 'data' => $data]);

		} catch (Exception $e) {
			return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'RunTime Exception occured']);
		}

	}

	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 11/05/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <destinationList>
		    * Name: destinationList;
		    * Description: List Role
		    * Action Type: Application;
		    * Category: Manage;
		    * </destinationList>
	*/
	public function destinationList(Request $request) {

		$this->validate($request, ['userid' => 'required|integer|exists:users,usr_id']);

		$destId = DB::table('users')->where('usr_id', '=', $request->input('userid'))->pluck('dest_id');
		$destinationId = '';
		$destinationId = $destId[0];

		$message = ",User have no destination";
		$baseSelectQuery = DB::table('destination');
		$baseSelectQuery->leftjoin('destination as destparent', 'destparent.dest_id', '=', 'destination.dest_parent');
		$baseSelectQuery->where('destination.deleted_at', '=', NULL);
		$baseSelectQuery->where('destparent.deleted_at', '=', NULL);

		if ($request->input($this->paginator) != '') {
			$dataArray = $baseSelectQuery->select('destination.*', 'destparent.dest_name as parentname')->orderBy('destination.dest_name', 'asc')->paginate($request->input($this->paginator));
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success,' . $message, 'Data' => $dataArray]);
		} else {
			$dataArray = $baseSelectQuery->select('destination.*', 'destparent.dest_name as parentname')->orderBy('destination.dest_name', 'asc')->get();
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success,' . $message, 'Data' => $dataArray]);
		}
	}

	/*
		    * @author Sabin P V
		    * @copyright Origami Technologies
		    * @created 12/05/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <deleteDest>
		    * Name: deleteDest;
		    * Description: delete Destination
		    * Action Type: Application;
		    * Category: Manage;
		    * </deleteDest>
	*/

	public function deleteDest(Request $request) {
		$this->validate($request, ['destid' => 'required|integer']);
		try {
			$dbArray['deleted_at'] = Carbon::now();
			if (DB::table('destination')->where('dest_id', '=', $request->input('destid'))->update($dbArray) /*->delete()*/) {
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Action Succes']);
			} else {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'dest_id not exist']);
			}

		} catch (\Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'ForeignKey violation']);
		}
	}

	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 12/05/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <statusChange>
		    * Name: statusChange;
		    * Description: Change status filed in table;
		    * Action Type: Application;
		    * Category: Manage;
		    * </statusChange>
	*/
	public function statusChange(Request $request) {
		$this->validate($request, ['primary' => 'required', 'status' => 'required|in:1,2']);

		try {
			$primaryKeyData = $request->input('primary');
		} catch (DecryptException $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Faild in decryption']);
		}
		$statusValue = $request->input('status');
		$array[] = $statusValue;
		$array[] = $primaryKeyData;
		//check if the row is exist in database
		if (DB::table('destination')->where('dest_id', '=', $primaryKeyData)->get() == '[]') {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Class id not exist']);
		}

		//check the current value of status from databse,if it is same as input value return false
		$result = DB::table('destination')->where('dest_id', '=', $primaryKeyData)->get('status');
		foreach ($result as $re) {
			if ($re->status == $statusValue) {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Status is already ' . $statusValue]);
			} else {
				//update status field
				DB::update('update destination set status= ? where dest_id = ?', $array);
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Status changed ']);
			}
		}
	}

	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 12/05/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <editDest>
		    * Name: editDest;
		    * Description: Edit Destination
		    * Action Type: Application;
		    * Category: Manage;
		    * </editDest>
	*/

	public function editDest(Request $request) {
		$this->validate($request, [
			'destinationid' => 'required|integer|exists:destination,dest_id',
			'name' => 'required|String',
			'code' => 'required|String',
			'place' => 'required|String',
			'desttype' => 'required|integer',
			'phoneno' => 'required|regex:/[0-9]{10}/',
			'pincode' => 'required|String',
			'booking' => 'required|integer',
			'description' => 'required|String',
			'timing' => 'required|String',
			'email' => 'required|email',
			'website' => 'required|String',
			'termsticket' => 'required|integer',
			'paymode' => 'required|integer']);

		$dbArray = [];

		if ($request->input('endtime') != NULL) {
			$dbArray['dest_book_end_time'] = $request->input('endtime');
		}

		if ($request->input('startday') != NULL) {
			$dbArray['dest_book_start_day'] = $request->input('startday');
		}

		if ($request->input('maxbookday') != NULL) {
			$dbArray['dest_book_limit_day'] = $request->input('maxbookday');
		}

		if ($request->input('maxpax') != NULL) {
			$dbArray['dest_max_pax'] = $request->input('maxpax');
		}

		if ($request->input('destid') != NULL) {
			$dbArray['dest_parent'] = $request->input('destid');
		}

		if ($request->input('name') != NULL) {
			$dbArray['dest_name'] = $request->input('name');
		}

		if ($request->input('code') != NULL) {
			$dbArray['dest_code'] = $request->input('code');
		}

		if ($request->input('place') != NULL) {
			$dbArray['dest_place'] = $request->input('place');
		}

		if ($request->input('desttype') != NULL) {
			$dbArray['dest_type'] = $request->input('desttype');
		}

		if ($request->input('description') != NULL) {
			$dbArray['dest_desc'] = $request->input('description');
		}

		if ($request->input('booking') != NULL) {
			$dbArray['dest_is_public'] = $request->input('booking');
		}

		if ($request->input('timing') != NULL) {
			$dbArray['dest_timing'] = $request->input('timing');
		}

		if ($request->input('email') != NULL) {
			$dbArray['dest_email'] = $request->input('email');
		}

		if ($request->input('website') != NULL) {
			$dbArray['dest_website'] = $request->input('website');
		}

		if ($request->input('phoneno') != NULL) {
			$dbArray['dest_phone'] = $request->input('phoneno');
		}

		if ($request->input('desttype') != NULL) {
			$dbArray['dest_type'] = $request->input('desttype');
		}

		if ($request->input('termsticket') != NULL) {
			$dbArray['dest_display_terms_ticket'] = $request->input('termsticket');
		}

		if ($request->input('paymode') != NULL) {
			$dbArray['dest_paymode'] = $request->input('paymode');
		}

		if ($request->input('gstin') != NULL) {
			$dbArray['dest_gstin'] = $request->input('gstin');
		}

		if ($dbArray == NULL) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error in Updation', 'Error' => 'Values are required']);
		}

		if (DB::table('destination')->where('dest_id', $request->input('destinationid'))->update($dbArray) != 1) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error in insertion']);
		}

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dbArray]);
	}

	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 19/07/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <weatherList>
		    * Name: weatherList;
		    * Description: List weather alert
		    * Action Type: Application;
		    * Category: Manage;
		    * </weatherList>
	*/
	public function weatherList(Request $request) {

		$this->validate($request, ['userid' => 'required|integer|exists:users,usr_id']);
		$destId = DB::table('users')->where('usr_id', '=', $request->input('userid'))->pluck('dest_id');
		$destinationId = '';
		$destinationId = $destId[0];

		$message = ",User have no weather_alert";
		$baseSelectQuery = DB::table('weather_alert');

		$baseSelectQuery->leftjoin('core_files as files', 'files.file_id', '=', 'weather_alert.wa_file_id');

		$baseSelectQuery->leftjoin('weather_destinations as weatherdest', 'weatherdest.wd_wa_id', '=', 'weather_alert.wa_id');

		$baseSelectQuery->leftjoin('destination as dest', 'dest.dest_id', '=', 'weatherdest.wd_dest_id');

		$baseSelectQuery->leftjoin('destheirarchy as dh', 'dh.destid', '=', 'dest.dest_id');

		$baseSelectQuery->where('weather_alert.deleted_at', '=', NULL);

		$baseSelectQuery->where('dh.mainparent', '=', $destinationId);

		$baseSelectQuery->where('files.deleted_at', '=', NULL);
		$baseSelectQuery->where('weatherdest.deleted_at', '=', NULL);

		if ($request->input($this->paginator) != '') {
			$dataArray = $baseSelectQuery->select('weather_alert.*', 'files.*', 'weatherdest.*', 'dest.*')->orderBy('weather_alert.created_at', 'desc')->paginate($request->input($this->paginator));

			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success,' . $message, 'Data' => $dataArray]);
		} else {
			$dataArray = $baseSelectQuery->select('weather_alert.*', 'files.*', 'weatherdest.*', 'dest.*')->orderBy('weather_alert.created_at', 'desc')->get();
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success,' . $message, 'Data' => $dataArray]);
		}
	}

	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 19/07/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <createWeatheralert>
		    * Name: createWeatheralert;
		    * Description: insert into weather_alert
		    * Action Type: Application;
		    * Category: Manage;
		    * </createWeatheralert>
	*/

	public function createWeatheralert(Request $request) {

		$this->validate($request, ['userid' => 'required|integer|exists:users,usr_id', 'alertdate' => 'required']);
		$fileId = 0;
		if ($request->hasFile('file')) {
			$fileId = fileUpload($request);
		}
		$json_data = json_decode($request->input('destinationdata'));
		$destIds = array();
		$data = [];
		if ($request->input('alertdate')) {
			$data['wa_date'] = $request->input('alertdate');
		}
		if ($request->input('description')) {
			$data['wa_description'] = $request->input('description');
		}
		if ($request->input('general')) {
			$data['wa_is_general'] = $request->input('general');
		}
		$ip = $request->ip();
		$data['wa_file_id'] = $fileId;
		$data['u_createdby'] = $request->input('userid');
		$data['ip_created'] = $ip;
		$wData = Weatheralert::create($data);
		$weatherId = $wData->wa_id;
		if ($weatherId) {
			foreach ($json_data as $value) {
				$weatherdata['wd_wa_id'] = $weatherId;
				$weatherdata['wd_dest_id'] = $value;
				DB::table('weather_destinations')->insert($weatherdata);
			}

			return response(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Weather alert has been succesfully added']);
		} else {
			return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'RunTime Exception occured']);
		}

	}

	/*
		    * @author Sabin P V
		    * @copyright Origami Technologies
		    * @created 17/07/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <deleteWeather>
		    * Name: deleteWeather;
		    * Description: delete Destination
		    * Action Type: Application;
		    * Category: Manage;
		    * </deleteWeather>
	*/

	public function deleteWeather(Request $request) {
		$this->validate($request, ['waid' => 'required|integer']);
		try {
			$dbArray['deleted_at'] = Carbon::now();
			if (DB::table('weather_alert')->where('wa_id', '=', $request->input('waid'))->update($dbArray) /*->delete()*/) {
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Action Succes']);
			} else {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'dest_id not exist']);
			}

		} catch (\Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'ForeignKey violation']);
		}
	}
	/*
		    * @author Sabin P V
		    * @copyright Origami Technologies
		    * @created 17/07/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <deleteWeatherDest>
		    * Name: deleteWeatherDest;
		    * Description: delete Destination
		    * Action Type: Application;
		    * Category: Manage;
		    * </deleteWeatherDest>
	*/

	public function deleteWeatherDest(Request $request) {
		$this->validate($request, ['wdid' => 'required|integer|exists:weather_destinations,wd_id']);
		$dbArray['deleted_at'] = Carbon::now();
		if (DB::table('weather_destinations')->where('wd_id', '=', $request->input('wdid'))->update($dbArray) /*->delete()*/) {
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Action Succes']);
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'dest_id not exist']);
		}
	}

	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 20/07/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <getDestData>
		    * Name: getDestData;
		    * Description: Settings
		    * Action Type: Application;
		    * Category: Manage;
		    * </Get getDestData>
	*/
	public function getDestData(Request $request) {
		$this->validate($request, ['id' => 'required|integer|exists:users,usr_id']);

		$destArray = [];
		$attractions = [];
		$count = 0;
		$destId = DB::table('users')->where('usr_id', '=', $request->input('id'))->where('deleted_at', NULL)->pluck('dest_id');
		$destinationId = $destId[0];
		//$destinationId = '81';

		$destName = DB::table('destheirarchy')->select('*')->where('mainparent', $destinationId)->get();

		foreach ($destName as $dest) {
			$destArray[$count]['destName'] = $dest->dest_name;
			$destArray[$count]['destId'] = $dest->destid;
			$count++;
		}

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success...!', 'count' => $count, 'destData' => $destArray]);
	}

	/*
		    * @author Pratheesh K
		    * @copyright Origami Technologies
		    * @created 21/07/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <adddestinationalert>
		    * Name: getDestData;
		    * Description: Settings
		    * Action Type: Application;
		    * Category: Manage;
		    * </adddestinationalert>
	*/
	public function adddestinationalert(Request $request) {
		$destId = $request->input('destid');
		$remark = $request->input('remark');
		$alert = $request->input('alert');
		$lat = $request->input('lat');
		$lng = $request->input('lng');

		if ($request->input('isEdit') == 2) {
			//Edit Action , Delete existing alert
			$alertId = $request->input('alertId');
			$dbArray['deleted'] = 1;
			$dbArray['deleted_at'] = Carbon::now();
			$dbArray['u_deletedby'] = $request->input('userid');
			DB::table('danger_alert')->where('da_id', '=', $alertId)->update($dbArray);
		}

		$inserArray = array();
		if ($destId != NULL && $remark != NULL && $alert != NULL && $lat != NULL && $lng != NULL) {
			$inserArray['da_dest_id'] = $destId;
			$inserArray['da_lat'] = $lat;
			$inserArray['da_lng'] = $lng;
			$inserArray['da_description'] = $request->input('remark');
			$inserArray['u_createdby'] = $request->input('userid');
			DB::table('danger_alert')->insert($inserArray);
			$lastInsertId = DB::getPdo()->lastInsertId();

			$alertArray = json_decode($alert);
			$multiArray = array();
			foreach ($alertArray as $key => $value) {
				if ($value == true) {
					$tempArray['daa_da_id'] = $lastInsertId;
					$tempArray['daa_alert_id'] = $key;
					$multiArray[] = $tempArray;
				}
			}

			if (count($multiArray) > 0) {
				DB::table('danger_alert_alerts')->insert($multiArray);
			}
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Insert Successfully..']);

		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Please enter all fileds']);
		}

	}

	/*
		* @author Pratheesh
		* @copyright Origami Technologies
		* @created 21/07/2020
		* @license http://www.origamitechnologies.com
		* @aclinfo <List all User>
		* Name: List;
		* Description: List all user executed here;
		* Action Type: Application;
		* Category: Manage;
		* </List>
	*/
	public function listalert(Request $request) {
		$this->validate($request, ['destId' => 'required|integer|exists:destination,dest_id']);
		$baseSelectQuery = DB::table('danger_alert');
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'danger_alert.da_dest_id');
		$baseSelectQuery->leftjoin('destheirarchy', 'destheirarchy.destid', '=', 'destination.dest_id');
		if ($request->input('destId') != '') {
			$baseSelectQuery->where('destheirarchy.mainparent', '=', $request->input('destId'));
		}
		if ($request->input($this->paginator) != '') {
			$user = $baseSelectQuery->select('danger_alert.*', 'destination.dest_name')->whereNull('danger_alert.deleted_at')->orderBy('danger_alert.da_id')->paginate($request->input($this->paginator));
		} else {
			$user = $baseSelectQuery->select('danger_alert.*', 'destination.dest_name')->whereNull('danger_alert.deleted_at')->orderBy('danger_alert.da_id')->get();
		}

		return response()->json(['Status' => OT_YES, 'Feedback' => 'Success', 'Data' => $user]);
	}

	/*
		* @author Pratheesh
		* @copyright Origami Technologies
		* @created 21/07/2020
		* @license http://www.origamitechnologies.com
		* @aclinfo <List all User>
		* Name: List;
		* Description: List all user executed here;
		* Action Type: Application;
		* Category: Manage;
		* </List>
	*/
	public function viewalert(Request $request) {
		$baseSelectQuery = DB::table('danger_alert_alerts');
		$baseSelectQuery->leftjoin('alert', 'alert.alert_id', '=', 'danger_alert_alerts.daa_alert_id');
		$alertArray = $baseSelectQuery->select('danger_alert_alerts.*', 'alert.*')->where('daa_da_id', $request->input('alertId'))->whereNull('danger_alert_alerts.deleted_at')->get();
		$alertString = NULL;

		$baseSelectQuery = DB::table('alert');
		$coreAlertArray = $baseSelectQuery->select('alert.*')->get();
		$artCount = 0;
		$repArray = array();
		foreach ($coreAlertArray as $art) {
			$repArray[$art->alert_id] = 2;
		}
		$count = 0;
		foreach ($alertArray as $alrt) {
			if ($coreAlertArray[$count]->alert_id == $alrt->alert_id) {
				$repArray[$alrt->alert_id] = 1;
			}
			$count++;
			if ($alertString == NULL) {
				$alertString = $alrt->alert_name;
			} else {
				$alertString .= "," . $alrt->alert_name;
			}

		}
		return response()->json(['Status' => OT_YES, 'Feedback' => 'Success', 'Data' => $alertString, 'alert' => $repArray]);
	}
	/*
		* @author Pratheesh
		* @copyright Origami Technologies
		* @created 21/07/2020
		* @license http://www.origamitechnologies.com
		* @aclinfo <List all User>
		* Name: List;
		* Description: List all user executed here;
		* Action Type: Application;
		* Category: Manage;
		* </List>
	*/
	public function deletealert(Request $request) {
		$this->validate($request, ['alertId' => 'required|integer|exists:danger_alert,da_id']);
		$delArray['deleted_at'] = Carbon::now();
		if (DB::table('danger_alert')->where('da_id', '=', $request->input('alertId'))
			->update($delArray)) {
			if (DB::table('danger_alert_alerts')->where('daa_alert_id', '=', $request->input('alertId'))
				->update($delArray)) {
				return response()->json(['Status' => OT_YES, 'Feedback' => 'Alert has been removed']);
			}
		}

		return response()->json(['Status' => OT_NO, 'Feedback' => 'Alert deletion failed']);
	}

	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 29/07/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <destinationsData>
		    * Name: destinationsData;
		    * Description: List Role
		    * Action Type: Application;
		    * Category: Manage;
		    * </destinationsData>
	*/
	public function destinationsData(Request $request) {

		$message = ",Data destination";
		$baseSelectQuery = DB::table('destination');
		$baseSelectQuery->where('destination.deleted_at', '=', NULL);
		$baseSelectQuery->where('destination.dest_img_file', '!=', NULL);
		$baseSelectQuery->leftjoin('core_files', 'core_files.file_id', '=', 'destination.dest_img_file');

		if ($request->input($this->paginator) != '') {
			$dataArray = $baseSelectQuery->select('destination.*','core_files.*')->orderBy('destination.dest_name', 'asc')->paginate($request->input($this->paginator));
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success,' . $message, 'Data' => $dataArray]);
		} else {
			$dataArray = $baseSelectQuery->select('destination.*','core_files.*')->orderBy('destination.dest_name', 'asc')->get();
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success,' . $message, 'Data' => $dataArray]);
		}
	}

	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 29/07/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <destinationHotels>
		    * Name: destinationHotels;
		    * Description: List Role
		    * Action Type: Application;
		    * Category: Manage;
		    * </destinationHotels>
	*/
	public function destinationHotels(Request $request) {

		$this->validate($request, ['destId' => 'required|integer|exists:destination,dest_id']);

		$count = 0;

		$destinationId = $request->input('destId');

		$destName = DB::table('hotels')
		->select('hotels.*','core_files.*','destination.*','destfiles.file_name as destfile')
		->leftjoin('core_files', 'core_files.file_id', '=', 'hotels.hotel_img')
		->leftjoin('destination', 'destination.dest_id', '=', 'hotels.hotel_dest_id')
		->leftjoin('core_files as destfiles', 'destfiles.file_id', '=', 'destination.dest_img_file')
		->where('hotel_dest_id', $destinationId)
		->whereNull('hotels.deleted_at')
		->get();


		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success...!', 'Data' => $destName]);

	}
	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 02/08/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <destUpdates>
		    * Name: Destination Updates;
		    * Description: user registration Function;
		    * Action Type: Application;
		    * Category: Manage;
		    * </destUpdates>
	*/
	public function destUpdates(Request $request) {
		$this->validate($request, ['destid' => 'integer|exists:destination,dest_id',
			'des' => 'required',
			'lat' => 'required',
			'lng' => 'required',
			'roleid' => 'required|integer|exists:roles,id',
			'userid' => 'required|exists:users,usr_id']);
		$fileId = array();
		if ($request->hasFile('file')) {
			$fileId = fileUpload($request);
		}
		$baseSelectQuery = DB::table('destination');
		$baseSelectQuery->leftjoin('destination_files', 'destination_files.df_dest_id', '=', 'destination.dest_id');
		$baseSelectQuery->where('destination.dest_id', '=', $request->input('destid'));		
		$user = $baseSelectQuery->select('destination_files.*','destination.dest_img_file')->whereNull('destination.deleted_at')->whereNull('destination_files.deleted_at')->get();	

		$data = array();		
		$data['dest_long_desc'] = $request->input('des');
		$data['dest_latitude'] = $request->input('lat');
		$data['dest_longitude'] = $request->input('lng');
		if (count($user)==0)
			$data['dest_img_file'] = $fileId[0];
		if (DB::table('destination')->where('dest_id', '=', $request->input('destid'))->update($data) == 1) {
			$destId = $request->input('destid');
			if ($request->hasFile('file')) {
				$fileData = array();
				if (is_array($fileId)) {
					foreach ($fileId as $id) {	
						$files['df_dest_id'] = $destId;
						$files['df_file_id'] = $id;
						$fileData[] = $files;
					}
				}
				else {
					$files['df_dest_id'] = $destId;
					$files['df_file_id'] = $fileId;
					$fileData[] = $files;
				}
				DB::table('destination_files')->insert($fileData);
			}
			return response(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Destination has been updated successfully']);
		}else {
			return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error while Updating Destination']);
		}
		
	}
	/*
		* @author Pratheesh
		* @copyright Origami Technologies
		* @created 21/07/2020
		* @license http://www.origamitechnologies.com
		* @aclinfo <List all User>
		* Name: List;
		* Description: List all user executed here;
		* Action Type: Application;
		* Category: Manage;
		* </List>
	*/
	public function deletefile(Request $request) {
		$this->validate($request, ['fileid' => 'required|integer|exists:destination_files,df_id']);
		$delArray['deleted_at'] = Carbon::now();
		if (DB::table('destination_files')->where('df_id', '=', $request->input('fileid'))
			->update($delArray)) {
			return response()->json(['Status' => OT_YES, 'Feedback' => 'File has been removed']);
			
		}

		return response()->json(['Status' => OT_NO, 'Feedback' => 'File deletion failed']);

	}
	/*
		* @author Pratheesh
		* @copyright Origami Technologies
		* @created 21/07/2020
		* @license http://www.origamitechnologies.com
		* @aclinfo <List all User>
		* Name: List;
		* Description: List all user executed here;
		* Action Type: Application;
		* Category: Manage;
		* </List>
	*/
	public function destview(Request $request) {
		$this->validate($request, ['destid' => 'required|integer|exists:destination,dest_id']);
		$baseSelectQuery = DB::table('destination');
		$baseSelectQuery->leftjoin('destination_files', 'destination_files.df_dest_id', '=', 'destination.dest_id');
		$baseSelectQuery->leftjoin('core_files', 'core_files.file_id', '=', 'destination_files.df_file_id');

		if ($request->input('destid') != '') {
			$baseSelectQuery->where('destination.dest_id', '=', $request->input('destid'));
		}
		$user = $baseSelectQuery->select('destination_files.*','core_files.*')->whereNull('destination.deleted_at')->whereNull('destination_files.deleted_at')->
		orderBy('core_files.file_id')->get();
		$files = array();
		foreach ($user as $value) {
			$files[] = array('df_id'=>$value->df_id,'fileName'=>$value->file_name);
		}

		return response()->json(['Status' => OT_YES, 'Feedback' => 'Success', 'Data' => $files]);
	}
}
