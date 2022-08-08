<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Ticketprint;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

//use Storage;

class TicketController extends Controller
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
		    * @created 017/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <counter attraction >
		    * Name: counterAttraction;
		    * Description: insert into counter_attractions
		    * Action Type: Application;
		    * Category: Manage;
		    * </counter attractions>
	*/

	public function counterAttraction(Request $request)
	{
		$this->validate($request, [
			'attrid' => 'required|integer|exists:attraction,attr_id', /*unique:user_counters,uc_usr_id',*/
			'status' => 'required|boolean',
			'counterid' => 'required|integer|exists:counter,counter_id',
		]);

		$attrDestId = DB::table('attraction')->where('attr_id', '=', $request->input('attrid'))->pluck('attr_dest_id');
		$counterDestId = DB::table('counter')->where('counter_id', '=', $request->input('counterid'))->pluck('counter_dest_id');
		if ($attrDestId != $counterDestId) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Counter destination and attraction destination mismatching']);
		}

		$delArray['deleted_at'] = Carbon::now();
		if (DB::table('counter_attractions')->where('ca_attr_id', '=', $request->input('attrid'))
			->where('ca_counter_id', '=', $request->input('counterid'))
			/*->delete()*/->update($delArray)
		);
		if (!$request->input('status')) {
			$status = OT_NO;
			$dbArray['ca_attr_id'] = $request->input('attrid');
			$dbArray['ca_counter_id'] = $request->input('counterid');
			if (DB::table('counter_attractions')->insert($dbArray) == 1) {
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'insertion successfull']);
			}

			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'updation failed']);
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'updation success']);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <delete counter>
		    * Name: deleteCounter;
		    * Description: delete counter
		    * Action Type: Application;
		    * Category: Manage;
		    * </delete counter>
	*/

	public function cancel(Request $request)
	{
		$this->validate($request, ['tno' => 'required|integer|exists:ticket_print,tp_id', 'reason' => 'required']);
		$dbArray['tp_is_cancelled'] = OT_YES;
		$dbArray['tp_cancel_time'] = Carbon::now();
		$dbArray['tp_cancelled_by'] = $request->input('userid');
		$dbArray['tp_cancel_reason'] = $request->input('reason');
		if (DB::table('ticket_print')->where('tp_id', '=', $request->input('tno'))
			->update($dbArray)
		) {
			$baseSelectQuery = DB::table('ticket_print');
			$baseSelectQuery->leftjoin("change_history", function ($join) {
				$join->on("change_history.ch_dest_id", "=", "ticket_print.tp_dest_id")
					->on("change_history.ch_date", "=", "ticket_print.tp_date")
					->where("change_history.ch_is_imported", '=', OT_NO)
					->whereNull('change_history.deleted_at');
			});
			$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'ticket_print.tp_dest_id');
			$baseSelectQuery->where('tp_id', '=', $request->input('tno'));
			$dataArray = $baseSelectQuery->select('ticket_print.*', 'change_history.*', 'destination.*')->get()->first();
			if ($dataArray->ch_id == '' && $dataArray->tp_date != date('Y-m-d')) {
				$hisData['ch_dest_id'] = $dataArray->tp_dest_id;
				$hisData['ch_dest_name'] = $dataArray->dest_name;
				$hisData['ch_date'] = $dataArray->tp_date;
				DB::table('change_history')->insert($hisData);
			}
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Action Succes']);
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Invalid ticket number']);
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <changedate>
		    * Name: deleteCounter;
		    * Description: delete counter
		    * Action Type: Application;
		    * Category: Manage;
		    * </changedate>
	*/

	public function changedate(Request $request)
	{
		$this->validate($request, ['tno' => 'required|integer|exists:ticket_print,tp_id', 'bookeddate' => 'required', 'userid' => 'required|integer|exists:users,usr_id', 'cdate' => 'required']);
		$dateHistory['dh_tp_id'] = $request->input('tno');
		$dateHistory['dh_changed_by'] = $request->input('userid');
		$dateHistory['dh_changed_at'] = Carbon::now();
		$dateHistory['dh_current_date'] = $request->input('cdate');
		$dateHistory['dh_new_date'] = $request->input('bookeddate');
		DB::table('date_history')->insert($dateHistory);
		$dbArray['tp_date'] = $request->input('bookeddate');
		$baseSelectQuery = DB::table('ticket_print');
		$baseSelectQuery->leftjoin("change_history", function ($join) {
			$join->on("change_history.ch_dest_id", "=", "ticket_print.tp_dest_id")
				->on("change_history.ch_date", "=", "ticket_print.tp_date")
				->where("change_history.ch_is_imported", '=', OT_NO)
				->whereNull('change_history.deleted_at');
		});
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'ticket_print.tp_dest_id');
		$baseSelectQuery->where('tp_id', '=', $request->input('tno'));
		$dataArray = $baseSelectQuery->select('ticket_print.*', 'change_history.*', 'destination.*')->get()->first();
		if ($dataArray->ch_id == '' && $dataArray->tp_date != date('Y-m-d')) {
			$hisData['ch_dest_id'] = $dataArray->tp_dest_id;
			$hisData['ch_dest_name'] = $dataArray->dest_name;
			$hisData['ch_date'] = $dataArray->tp_date;
			DB::table('change_history')->insert($hisData);
		}
		if (DB::table('ticket_print')->where('tp_id', '=', $request->input('tno'))
			->update($dbArray)
		) {
			$baseSelectQuery = DB::table('ticket_print');
			$baseSelectQuery->leftjoin("change_history", function ($join) {
				$join->on("change_history.ch_dest_id", "=", "ticket_print.tp_dest_id")
					->on("change_history.ch_date", "=", "ticket_print.tp_date")
					->where("change_history.ch_is_imported", '=', OT_NO)
					->whereNull('change_history.deleted_at');
			});
			$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'ticket_print.tp_dest_id');
			$baseSelectQuery->where('tp_id', '=', $request->input('tno'));
			$dataArray = $baseSelectQuery->select('ticket_print.*', 'change_history.*', 'destination.*')->get()->first();
			if ($dataArray->ch_id == '' && $dataArray->tp_date != date('Y-m-d')) {
				$hisData['ch_dest_id'] = $dataArray->tp_dest_id;
				$hisData['ch_dest_name'] = $dataArray->dest_name;
				$hisData['ch_date'] = $dataArray->tp_date;
				DB::table('change_history')->insert($hisData);
			}

			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Action Succes']);
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Invalid ticket number']);
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 017/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <getDetails>
		    * Name: counterAttraction;
		    * Description: insert into counter_attractions
		    * Action Type: Application;
		    * Category: Manage;
		    * </getDetails>
	*/

	public function getDetails(Request $request)
	{
		$this->validate($request, ['ticketno' => 'required']);
		$tpNo = $request->input('ticketno');
		$status = OT_NO;
		$tpDetails = DB::table('ticket_print')->where('tp_actual_number', '=', $tpNo)->whereNull('deleted_at')->select('ticket_print.*')->get();
		if ($tpDetails->count()) {
			$status = OT_YES;
		}
		if ($status == OT_NO) {
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Invalid ticket number', 'ticketStatus' => $status]);
		}

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Valid ticket', 'data' => $tpDetails, 'ticketStatus' => OT_YES]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <link counter >
		    * Name: linkCounter;
		    * Description: link user to counter
		    * Action Type: Application;
		    * Category: Manage;
		    * </link counter>
	*/

	public function linkCounter(Request $request)
	{
		$this->validate($request, [
			'userid' => 'required|integer|exists:users,usr_id', /*unique:user_counters,uc_usr_id',*/
			'status' => 'required|boolean',
			'counterid' => 'required|integer|exists:counter,counter_id',
		]);

		$userDestId = DB::table('users')->where('usr_id', '=', $request->input('userid'))->pluck('dest_id');
		$counterDestId = DB::table('counter')->where('counter_id', '=', $request->input('counterid'))->pluck('counter_dest_id');
		if ($userDestId != $counterDestId) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Counter destination and user destination mismatching']);
		}

		$delArray['deleted_at'] = Carbon::now();
		if (DB::table('user_counters')->where('uc_usr_id', '=', $request->input('userid'))
			->where('uc_counter_id', '=', $request->input('counterid'))
			/*->delete()*/->update($delArray)
		);
		if (!$request->input('status')) {
			$status = OT_NO;
			$dbArray['uc_usr_id'] = $request->input('userid');
			$dbArray['uc_counter_id'] = $request->input('counterid');
			if (DB::table('user_counters')->insert($dbArray) == 1) {
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'insertion successfull']);
			}

			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'updation failed']);
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'updation success']);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <list counter >
		    * Name: list counter;
		    * Description: list counter details
		    * Action Type: Application;
		    * Category: Manage;
		    * </list counter>
	*/

	public function listCounter(Request $request)
	{
		$this->validate($request, ['userid' => 'required|integer|exists:user_counters,uc_usr_id|exists:users,usr_id']);
		$dest_id = DB::table('users')->where('usr_id', '=', $request->input('userid'))->where('deleted_at', NULL)->pluck('dest_id');
		$counter_id = DB::table('user_counters')->where('uc_usr_id', '=', $request->input('userid'))->where('deleted_at', NULL)->pluck('uc_counter_id');
		$baseSelectQuery = DB::table('user_counters');
		$baseSelectQuery->leftjoin('users', 'users.usr_id', '=', 'user_counters.uc_usr_id');
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'users.dest_id');
		$baseSelectQuery->leftjoin('counter', 'counter.counter_id', '=', 'user_counters.uc_usr_id');
		$baseSelectQuery->where('user_counters.uc_usr_id', '=', $request->input('userid'));
		//$baseSelectQuery->where('users.deleted_at',NULL);
		//$baseSelectQuery->where('destination.deleted_at',NULL);
		//$baseSelectQuery->where('counter.deleted_at',NULL);
		if ($request->input($this->paginator) != '') {
			$dataArray = $baseSelectQuery->select('users.*', 'user_counters.*', 'destination.*', 'counter.*')->paginate($request->input($this->paginator));
		} else {
			$dataArray = $baseSelectQuery->select('users.*', 'user_counters.*', 'destination.*', 'counter.*')->get();
		}

		return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <delete counter>
		    * Name: deleteCounter;
		    * Description: delete counter
		    * Action Type: Application;
		    * Category: Manage;
		    * </delete counter>
	*/

	public function deleteCounter(Request $request)
	{
		$this->validate($request, ['userid' => 'required|integer|exists:users,usr_id', 'counterid' => 'required|integer|exists:counter,counter_id']);
		$dbArray['deleted_at'] = Carbon::now();
		if (DB::table('user_counters')->where('uc_usr_id', '=', $request->input('userid'))
			->where('uc_counter_id', '=', $request->input('counterid'))
			/*->delete()*/->update($dbArray)
		) {
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Action Succes']);
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'user id and counter id miss-match']);
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <list users counter>
		    * Name: deleteCounter;
		    * Description: list users  counter
		    * Action Type: Application;
		    * Category: Manage;
		    * </list users counter>
	*/

	public function listUser(Request $request)
	{
		$this->validate($request, ['userid' => 'required|integer|exists:users,usr_id', 'counterid' => 'required|integer|exists:counter,counter_id']);
		$destIdArray = DB::table('users')->where('usr_id', '=', $request->input('userid'))->pluck('dest_id');
		$destId = '';
		$destId = $destIdArray[0];
		if ($destId != NULL) {
			$userArray = DB::table('users')->where('dest_id', '=', $destId)->select('*')->get();
			$counterIdArray = DB::table('counter')->where('counter_id', '=', $request->input('counterid'))->where('deleted_at', NULL)->pluck('counter_dest_id');
			if ($counterIdArray[0] != $destIdArray[0]) {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Counter destination and user destination missmatch']);
			}
		} else {
			$userArray = DB::table('users')->select('*')->get();
		}
		$counterUserArray = DB::table('user_counters')->where('uc_counter_id', '=', $request->input('counterid'))->select('uc_usr_id')->get();
		$resultUserArray = [];
		$tempArray = [];
		if ($counterUserArray != NULL) {
			foreach ($userArray as $user) {
				$match = false;
				foreach ($counterUserArray as $counterUser) {
					if ($counterUser->uc_usr_id == $user->usr_id) {
						$match = true;
					}
				}
				if ($match == false) {
					$tempArray['userid'] = $user->usr_id;
					$tempArray['username'] = $user->usr_name;
					$resultUserArray[] = $tempArray;
				}
			}
		} else {
			foreach ($userArray as $user) {
				$tempArray['userid'] = $user->usr_id;
				$tempArray['username'] = $user->usr_name;
				$resultUserArray[] = $tempArray;
			}
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $resultUserArray]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <create class >
		    * Name: create class;
		    * Description: create classess
		    * Action Type: Application;
		    * Category: Manage;
		    * </create classes>
	*/

	public function createClass(Request $request)
	{
		$this->validate($request, [
			'destid' => 'required|integer|exists:destination,dest_id',
			'attrid' => 'required|integer|exists:attraction,attr_id',
			'classname' => 'required|string',
			'classrate' => 'required|integer',
			'classnumber' => 'required|integer',
			'availability' => 'integer',
		]);
		$dbArray['class_dest_id'] = $request->input('destid');
		$dbArray['class_name'] = $request->input('classname');
		$dbArray['class_rate'] = $request->input('classrate');
		$dbArray['class_time'] = $request->input('timing');
		$dbArray['class_number'] = $request->input('classnumber');
		$dbArray['class_cgst_per'] = $request->input('cgst');
		$dbArray['class_sgst_per'] = $request->input('sgst');
		$dbArray['available_numbers'] = $request->input('availability');
		$dbArray['class_attr_id'] = $request->input('attrid');
		if (DB::table('class')->insert($dbArray) != 1) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'error in insertion:']);
		}

		$id = DB::getPdo()->lastInsertId();
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'insertion successfull id:' . $id]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <list classes>
		    * Name: list class;
		    * Description: list class
		    * Action Type: Application;
		    * Category: Manage;
		    * </list class>
	*/

	public function viewclass(Request $request)
	{
		$this->validate($request, ['classid' => 'required|integer|exists:class,class_id']);
		$baseSelectQuery = DB::table('class');
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'class.class_dest_id');
		$baseSelectQuery->leftjoin('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
		$baseSelectQuery->where('class.class_id', '=', $request->input('classid'));
		$baseSelectQuery->where('destination.deleted_at', NULL);
		$baseSelectQuery->where('attraction.deleted_at', NULL);
		$baseSelectQuery->where('class.deleted_at', NULL);
		if ($request->input($this->paginator) != '') {
			$dataArray = $baseSelectQuery->select('class.*', 'destination.*', 'attraction.*')->paginate($request->input($this->paginator));
		} else {
			$dataArray = $baseSelectQuery->select('class.*', 'destination.*', 'attraction.*')->get();
		}

		return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <list classes>
		    * Name: list class;
		    * Description: list class
		    * Action Type: Application;
		    * Category: Manage;
		    * </list class>
	*/

	public function listclass(Request $request)
	{
		$this->validate($request, ['userid' => 'required|integer|exists:users,usr_id']);
		$destId = DB::table('users')->where('usr_id', '=', $request->input('userid'))->pluck('dest_id');
		$destinationId = '';
		$destinationId = $destId[0];
		//         if($destinationId != ''){
		//             $message=",User have destination";
		//             $baseSelectQuery = DB::table('class');
		//             $baseSelectQuery ->leftjoin('destination', 'destination.dest_id', '=', 'class.class_dest_id');
		//             $baseSelectQuery ->leftjoin('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
		//             $baseSelectQuery->where('class.class_dest_id', '=', $destinationId);
		//             $baseSelectQuery->where('class.deleted_at', '=', NULL);
		//             $baseSelectQuery->where('destination.deleted_at', '=', NULL);
		//             $baseSelectQuery->where('attraction.deleted_at', '=', NULL);

		//         }
		//         else{
		$message = ",User have no destination";
		$baseSelectQuery = DB::table('class');
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'class.class_dest_id');
		$baseSelectQuery->leftjoin('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
		$baseSelectQuery->where('class.deleted_at', '=', NULL);
		$baseSelectQuery->where('destination.deleted_at', '=', NULL);
		$baseSelectQuery->where('attraction.deleted_at', '=', NULL);
		//$baseSelectQuery->where('class.class_dest_id', '=', $request->input($destId));
		//}
		//$baseSelectQuery = DB::table('class');
		//$baseSelectQuery ->leftjoin('destination', 'destination.dest_id', '=', 'class.class_dest_id');
		//$baseSelectQuery ->leftjoin('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
		//$baseSelectQuery->where('class.class_id', '=', $request->input('classid'));
		if ($request->input($this->paginator) != '') {
			$dataArray = $baseSelectQuery->select('class.*', 'attraction.attr_id', 'attraction.attr_name', 'destination.dest_id', 'destination.dest_name')->orderBy('destination.dest_name', 'asc')->orderby('class.class_name', 'asc')->paginate($request->input($this->paginator));
		} else {
			$dataArray = $baseSelectQuery->select('class.*', 'attraction.attr_id', 'attraction.attr_name', 'destination.dest_id', 'destination.dest_name')->orderBy('destination.dest_name', 'asc')->orderby('class.class_name', 'asc')->get();
		}

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success,' . $message, 'Data' => $dataArray]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <edit class>
		    * Name: edit class;
		    * Description: edit class
		    * Action Type: Application;
		    * Category: Manage;
		    * </edit class>
	*/

	public function editClass(Request $request)
	{
		$this->validate($request, [
			'classid' => 'required|integer|exists:class,class_id',
			'destid' => 'integer|exists:destination,dest_id',
			'classname' => 'string', 'class_rate' => 'integer',
			'availability' => 'integer',
			'classnumber' => 'required|integer',
			'attrid' => 'integer|exists:attraction,attr_id',
		]);
		$dbArray = [];
		if ($request->input('destid') != NULL) {
			$dbArray['class_dest_id'] = $request->input('destid');
		}

		if ($request->input('classname') != NULL) {
			$dbArray['class_name'] = $request->input('classname');
		}

		if ($request->input('classrate') != NULL) {
			$dbArray['class_rate'] = $request->input('classrate');
		}

		if ($request->input('classnumber') != NULL) {
			$dbArray['class_number'] = $request->input('classnumber');
		}

		if ($request->input('availability') != NULL) {
			$dbArray['available_numbers'] = $request->input('availability');
		}

		if ($request->input('attrid') != NULL) {
			$dbArray['class_attr_id'] = $request->input('attrid');
		}

		if ($request->input('timing') != NULL) {
			$dbArray['class_time'] = $request->input('timing');
		}

		if ($request->input('cgst') != NULL) {
			$dbArray['class_cgst_per'] = $request->input('cgst');
		}

		if ($request->input('sgst') != NULL) {
			$dbArray['class_sgst_per'] = $request->input('sgst');
		}

		if ($dbArray == NULL) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error in insertion', 'Error' => 'attrid,destid,classname or availability is required']);
		}

		if (DB::table('class')->where('class_id', $request->input('classid'))->update($dbArray) != 1) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error in insertion']);
		}

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dbArray]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <delete class>
		    * Name: delete class;
		    * Description: delete class
		    * Action Type: Application;
		    * Category: Manage;
		    * </delete class>
	*/

	public function deleteClass(Request $request)
	{
		$this->validate($request, ['classid' => 'required|integer']);
		try {
			$dbArray['deleted_at'] = Carbon::now();
			if (DB::table('class')->where('class_id', '=', $request->input('classid'))->update($dbArray) /*->delete()*/) {
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Action Succes']);
			} else {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'class_id not exist']);
			}
		} catch (\Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'ForeignKey violation']);
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 12/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <status change>
		    * Name: StatusChange;
		    * Description: Change status filed in table;
		    * Action Type: Application;
		    * Category: Manage;
		    * </statusChange>
	*/
	public function statusChange(Request $request)
	{
		$this->validate($request, ['primary' => 'required', 'status' => 'required|in:1,2']);
		//$primaryKeyData=$request->input($this->primary);
		try {
			//$primaryKeyData=Crypt::decrypt($request->input($this->primary));
			$primaryKeyData = $request->input('primary');
		} catch (DecryptException $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Faild in decryption']);
		}
		$statusValue = $request->input('status');
		$array[] = $statusValue;
		$array[] = $primaryKeyData;
		//check if the row is exist in database
		if (DB::table('class')->where('class_id', '=', $primaryKeyData)->get() == '[]') {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Class id not exist']);
		}

		//check the current value of status from databse,if it is same as input value return false
		$result = DB::table('class')->where('class_id', '=', $primaryKeyData)->get('status');
		foreach ($result as $re) {
			if ($re->status == $statusValue) {
				return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Status is already ' . $statusValue]);
			} else {
				//update status field
				DB::update('update class set status= ? where class_id = ?', $array);
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Status changed ']);
			}
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <delete tickets>
		    * Name: delete tickets;
		    * Description: delete tickets
		    * Action Type: Application;
		    * Category: Manage;
		    * </delete tickets>
	*/

	public function deleteTicket(Request $request)
	{
		$this->validate($request, [
			'ticketid' => 'required_without:ticketnumber|integer',
			'ticketnumber' => 'required_without:ticketid|integer',
		]);
		if ($request->input('ticketid') != '') {
			$column = 'ticketid';
			$col = 'ticket_id';
		} else {
			$column = 'ticketnumber';
			$col = 'ticket_number';
		}
		$ticketId = DB::table('tickets')->where($col, '=', $request->input($column))->pluck('ticket_id');
		$dbArray['deleted_at'] = Carbon::now();
		if (DB::table('tickets')->where($col, '=', $data = $request->input($column)) /*->delete()*/->update($dbArray)) {
			DB::table('ticket_class')->where('ticket_id', '=', $ticketId) /*->delete()*/->update($dbArray);
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Action Succes']);
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => $column . ' not exist']);
		}
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 18/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <create ticket and link with class,destination >
		    * Name: createticketclass;
		    * Description: link ticket with class
		    * Action Type: Application;
		    * Category: Manage;
		    * </createticketclass>
	*/

	public function createTicketClass(Request $request)
	{
		$this->validate($request, [
			'userid' => 'integer|exists:users,usr_id|required_without:customername',
			'dest_id' => 'integer|exists:destination,dest_id|required',
			'customername' => 'string|required_without:userid',
			'customermobile' => 'max:12|min:11',
			'ticketdata' => 'required|array',
			'customeremail' => 'email', 'dest_id' => 'integer|exists:destination,dest_id|required',
			'counterid' => 'integer|exists:counter,counter_id|required_without:customername',
		]);

		try {
			$printData = array();
			$dbArray['date'] = date('m-d-y');
			$ticketArray = $request->input('ticketdata');
			$attractionArray = $classArrayDetails = DB::table('attraction')->select('*')->get();
			$classArrayDetails = DB::table('class')->select('*')->get();
			//dd($classArrayDetails)
			$destinationName = DB::table('destination')->select('dest_name')->where('dest_id', $request->input('dest_id'))->pluck('dest_name');
			$destinationNo = DB::table('destination')->select('dest_code')->where('dest_id', $request->input('dest_id'))->value('dest_code');

			foreach ($attractionArray as $tempAttraction) {
				$finalAttractionArray[$tempAttraction->attr_id]['attractionName'] = $tempAttraction->attr_name;
				$finalAttractionArray[$tempAttraction->attr_id]['config'] = $tempAttraction->attr_ticket_config;
				$finalAttractionArray[$tempAttraction->attr_id]['time'] = $tempAttraction->attr_time;
			}
			$totalRate = 0;
			foreach ($classArrayDetails as $tempclass) {
				$finalClassArray[$tempclass->class_id]['attractionId'] = $tempclass->class_attr_id;
				$finalClassArray[$tempclass->class_id]['attractionDestination'] = $tempclass->class_dest_id;
				$finalClassArray[$tempclass->class_id]['className'] = $tempclass->class_name;
				$finalClassArray[$tempclass->class_id]['classRate'] = $tempclass->class_rate;
				$finalClassArray[$tempclass->class_id]['classAvailable'] = $tempclass->available_numbers;
				$finalClassArray[$tempclass->class_id]['classTime'] = $tempclass->class_time;
			}
			//dd($finalClassArray);
			$today = date("Y-m-d H:i:s", strtotime('+330 minutes'));

			if ($request->input('userid') != NULL) {
				$dbArray['ticket_usr_id'] = $request->input('userid');
			}

			if ($request->input('counterid') != NULL) {
				$dbArray['ticket_counter_id'] = $request->input('counterid');
			}

			if ($request->input('dest_id') != NULL) {
				$dbArray['ticket_dest_id'] = $request->input('dest_id');
			}

			if ($request->input('customername') != NULL) {
				$dbArray['customer_name'] = $request->input('customername');
			}

			if ($request->input('customermobile') != NULL) {
				$dbArray['customer_mobile'] = $request->input('customermobile');
			}

			if ($request->input('customeremail') != NULL) {
				$dbArray['customer_email'] = $request->input('customeremail');
			}

			$dbArray['date'] = $today;
			foreach ($ticketArray as $key => $value) {
				if (!($key == "''" || $key == "" || $key == NULL || $value == NULL || is_string($key))) {
					$totalRate = $totalRate + ($finalClassArray[$key]['classRate'] * $value);
				}
			}
			$dbArray['total_rate'] = $totalRate;
			$printTicket = array();
			$j = 0;
			if (DB::table('tickets')->insert($dbArray)) {
				$lastTicketIdArray = DB::table('tickets')->select('ticket_id')->orderBY('ticket_id', 'DESC')->pluck('ticket_id');
				$lastTicketId = $lastTicketIdArray[0];
				foreach ($ticketArray as $key => $value) {
					$proceed = OT_YES;
					if ($key == "''") {
						$proceed = OT_NO;
					}

					if ($key == "") {
						$proceed = OT_NO;
					}

					if ($key == NULL) {
						$proceed = OT_NO;
					}

					if ($value == NULL) {
						$proceed = OT_NO;
					}

					if (is_string($key)) {
						$proceed = OT_NO;
					}

					if ($proceed == OT_YES) {
						$rand = mt_rand();
						//$ticketNumberArray[] = $rand;
						//$dbArray['ticket_number'] = $rand; //dd($finalClassArray);

						$attrId = $finalClassArray[$key]['attractionId'];
						$prefix = $destinationNo . "." . $attrId . "." . $request->input('counterid');
						if ($finalAttractionArray[$attrId]['config'] == OT_YES) {
							if (!isset($printData[$attrId])) {
								$printData[$attrId]['classdata'] = '';
								$printData[$attrId]['rate'] = 0;
							}
							if ($printData[$attrId]['classdata'] == '') {

								if ($finalClassArray[$key]['classTime']) {
									$printData[$attrId]['classdata'] = $finalClassArray[$key]['className'] . '(' . $finalClassArray[$key]['classTime'] . ') -' . $value;
								} else {
									$printData[$attrId]['classdata'] = $finalClassArray[$key]['className'] . ' -' . $value;
								}
							} else {

								if ($finalClassArray[$key]['classTime']) {
									$printData[$attrId]['classdata'] = $printData[$attrId]['classdata'] . '<br/>' . $finalClassArray[$key]['className'] . '(' . $finalClassArray[$key]['classTime'] . ') -' . $value;
								} else {
									$printData[$attrId]['classdata'] = $printData[$attrId]['classdata'] . '<br/>' . $finalClassArray[$key]['className'] . ' -' . $value;
								}
							}
							$printData[$attrId]['rate'] += $finalClassArray[$key]['classRate'] * $value;
							$printData[$attrId]['time'] = $finalAttractionArray[$attrId]['time'];
						} else {
							for ($i = 1; $i <= $value; $i++) {

								if ($finalClassArray[$key]['classTime']) {
									$printTicket[$j]['tp_content'] = $finalClassArray[$key]['className'] . '(' . $finalClassArray[$key]['classTime'] . ') -1';
								} else {
									$printTicket[$j]['tp_content'] = $finalClassArray[$key]['className'] . ' -1';
								}

								$printTicket[$j]['tp_rate'] = $finalClassArray[$key]['classRate'];
								$printTicket[$j]['tp_date'] = date('Y/m/d');
								$printTicket[$j]['tp_ticket_id'] = $lastTicketId;
								$printTicket[$j]['tp_attr_id'] = $attrId;
								$printTicket[$j]['tp_time'] = $finalClassArray[$key]['classTime'];
								$printTicket[$j]['tp_prefix'] = $prefix;
								if ($request->input('userid') != NULL) {
									$printTicket[$j]['tp_usr_id'] = $request->input('userid');
								}

								if ($request->input('counterid') != NULL) {
									$printTicket[$j]['tp_counter_id'] = $request->input('counterid');
								}

								$j++;
							}
						}

						//$ticketFinalArray[] = $dbArray;

						$dbTicketClassArray['tc_class_id'] = $key;
						$dbTicketClassArray['tc_number'] = $value;
						$dbTicketClassArray['tc_rate_per_class'] = $finalClassArray[$key]['classRate'];
						$dbTicketClassArray['ticket_id'] = $lastTicketId;
						$dbTicketClassArray['total_rate'] = $finalClassArray[$key]['classRate'] * $value;
						$dbTicketClassArray['attraction_name'] = $finalAttractionArray[$finalClassArray[$key]['attractionId']]['attractionName'];

						$dbTicketClassArray['class_name'] = $finalClassArray[$key]['className'];
						$dbTicketClassArray['dest_name'] = $destinationName[0];
						$dbTicketClassArray['rate_class_original'] = $finalClassArray[$key]['classRate'];
						$dbTicketFinalClassArray[] = $dbTicketClassArray;
					}
				}

				foreach ($printData as $attr => $data) {
					$prefix = $destinationNo . "." . $attr . "." . $request->input('counterid');
					$printTicket[$j]['tp_content'] = $data['classdata'];
					$printTicket[$j]['tp_rate'] = $data['rate'];
					$printTicket[$j]['tp_ticket_id'] = $lastTicketId;
					$printTicket[$j]['tp_attr_id'] = $attr;
					$printTicket[$j]['tp_time'] = $data['time'];
					$printTicket[$j]['tp_prefix'] = $prefix;
					$printTicket[$j]['tp_date'] = date('Y/m/d');
					if ($request->input('userid') != NULL) {
						$printTicket[$j]['tp_usr_id'] = $request->input('userid');
					}

					if ($request->input('counterid') != NULL) {
						$printTicket[$j]['tp_counter_id'] = $request->input('counterid');
					}

					$j++;
				}

				if (DB::table('ticket_class')->insert($dbTicketFinalClassArray) == 1) {
				}
			}

			if (DB::table('ticket_print')->insert($printTicket) == 1) {
				return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Ticket addedd successfully', 'Ticket number' => $lastTicketId]);
			}
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error occured']);
		} catch (\Exception $e) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Runtime Exception', 'error' => $e->getMessage()]);
		}
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 19/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <view ticket >
		    * Name: viewTicket;
		    * Description: view sigle ticket
		    * Action Type: Application;
		    * Category: Manage;
		    * </viewTicket>
	*/

	public function viewTicket(Request $request)
	{
		$this->validate($request, ['ticketnumber' => 'required|integer|exists:tickets,ticket_id']);
		/* for generate ticketnumber */
		$ticketId = $request->input('ticketnumber');
		$noUpdates = 0;
		if ($request->input('addnumber')) {
			$printSelectQuery = DB::table('tickets');
			$printSelectQuery->leftjoin('ticket_print', 'ticket_print.tp_ticket_id', '=', 'tickets.ticket_id');
			$printSelectQuery->where('tickets.ticket_id', '=', $request->input('ticketnumber'));
			$printSelectQuery->whereNotNull('ticket_print.tp_number');
			$noUpdates = $printSelectQuery->select('tickets.*')->get()->count();
			DB::update("update ticket_print set tp_number  = nextval('ticket_print_tp_number_seq') where tp_ticket_id=$ticketId and tp_number is null");
		}
		$baseSelectQuery = DB::table('tickets');
		$baseSelectQuery->leftjoin('ticket_class', 'ticket_class.ticket_id', '=', 'tickets.ticket_id');
		$baseSelectQuery->leftjoin('class', 'class.class_id', '=', 'ticket_class.tc_class_id');
		$baseSelectQuery->leftjoin('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
		$baseSelectQuery->where('tickets.ticket_id', '=', $request->input('ticketnumber'));
		$baseSelectQuery->where('tickets.category', '=', OT_YES);
		$baseSelectQuery->where('ticket_class.deleted_at', NULL);
		$baseSelectQuery->where('attraction.deleted_at', NULL);
		$baseSelectQuery->where('tickets.deleted_at', NULL);

		$printSelectQuery = DB::table('tickets');
		$printSelectQuery->leftjoin('ticket_print', 'ticket_print.tp_ticket_id', '=', 'tickets.ticket_id');
		$printSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'tickets.ticket_dest_id');
		$printSelectQuery->where('tickets.ticket_id', '=', $request->input('ticketnumber'));
		$printSelectQuery->where('ticket_print.deleted_at', NULL);
		$dataArray = $baseSelectQuery->select('tickets.*', 'ticket_class.*', 'class.*', 'attraction.*')->get();
		$printDatas = $printSelectQuery->select('tickets.*', 'ticket_print.*', 'destination.*')->get();
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray, 'printData' => $printDatas, 'noUpdates' => $noUpdates]);
	}

	/*
 * @author Pratheesh
 * @copyright Origami Technologies
 * @created 19/08/2020
 * @license http://www.origamitechnologies.com
 * @aclinfo <list ticket >
 * Name: invalidTicket;
 * Description: list tickets
 * Action Type: Application;
 * Category: Manage;
 * </invalidTicket>
 */

	public function invalidTicket(Request $request)
	{
		// DB::enableQueryLog();
		$baseSelectQuery = DB::table('invalid_tickets');
		$baseSelectQuery->leftjoin('users', 'users.usr_id', '=', 'invalid_tickets.it_user');

		$baseSelectQuery->where('invalid_tickets.deleted_at', NULL);

		if ($request->input($this->paginator) != '') {
			$dataArray = $baseSelectQuery->select('invalid_tickets.*', 'users.*')
				->orderBy('it_id', 'desc')
				->paginate($request->input($this->paginator));
		} else {
			$dataArray = $baseSelectQuery->select('invalid_tickets.*', 'users.*')->orderBy('it_id', 'desc')->get();
		}

		// dd($baseSelectQuery);
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 19/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <list ticket >
		    * Name: listTicket;
		    * Description: list tickets
		    * Action Type: Application;
		    * Category: Manage;
		    * </listTicket>
	*/

	public function listTicket(Request $request)
	{
		/*$this->validate($request, ['date' =>'required_without_all:ticketdestid,ticketattrid|date_format:d-m-y',
			                                   'ticketattrid' =>'required_without_all:date,ticketdestid|integer|exists:attraction,attr_id',
		*/

		if ($request->input('date') != NULL) {
			$parts = explode('-', $request->input('date'));
			$date = $parts[2] . '/' . $parts[1] . '/' . $parts[0];
		} else {
			$date = date('Y/m/d');
		}

		if ($request->input('ticketattrid') != NULL) {
			$data = 'ticket_attr_id';
			$data1 = 'ticketattrid';
		}
		if ($request->input('ticketdestid') != NULL) {
			$data = 'ticket_dest_id';
			$data1 = 'ticketdestid';
		}

		// DB::enableQueryLog();
		$baseSelectQuery = DB::table('ticket_print');
		$baseSelectQuery->leftjoin('tickets', 'ticket_print.tp_ticket_id', '=', 'tickets.ticket_id');
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'ticket_print.tp_dest_id');
		$baseSelectQuery->leftjoin('destheirarchy', 'destheirarchy.destid', '=', 'destination.dest_id');
		$baseSelectQuery->leftjoin('counter', 'counter.counter_id', '=', 'ticket_print.tp_counter_id');
		$baseSelectQuery->leftjoin('users', 'users.usr_id', '=', 'ticket_print.tp_usr_id');
		$baseSelectQuery->where('destheirarchy.mainparent', '=', $request->input('usrdest'));
		//$baseSelectQuery ->leftjoin('ticket_class', 'ticket_class.tc_tp_id', '=', 'ticket_print.tp_id');
		// $baseSelectQuery ->leftjoin('attraction', 'attraction.attr_id', '=', 'class.class_attr_id');
		$baseSelectQuery->whereNotNull('ticket_print.tp_number');
		//         if ($request->input('bookeddate') != NULL) {
		//             $baseSelectQuery->where('ticket_print.tp_date','=',$request->input('bookeddate'));
		//         }
		if ($request->input('counter') != NULL) {
			$baseSelectQuery->where('ticket_print.tp_counter_id', '=', $request->input('counter'));
		}
		if ($request->input('tpNumber') != NULL) {
			$baseSelectQuery->where('ticket_print.tp_actual_number', 'like', $request->input('tpNumber') . '%');
		}
		if ($request->input('mode') != NULL) {
			$baseSelectQuery->where('ticket_print.tp_is_public', '=', $request->input('mode'));
		}
		if ($request->input('pmode') != NULL) {
			$baseSelectQuery->where('ticket_print.tp_pay_mode', '=', $request->input('pmode'));
		}
		if ($request->input('dest') != NULL) {
			$baseSelectQuery->where('destheirarchy.destid', '=', $request->input('dest'));
		}
		if ($date) {
			$baseSelectQuery->whereDate('ticket_print.tp_date', '=', $date);
		}

		/*$baseSelectQuery->where('class.deleted_at',NULL);
        $baseSelectQuery->where('attraction.deleted_at',NULL);*/
		$baseSelectQuery->where('ticket_print.deleted_at', NULL);

		/*else
            $baseSelectQuery->where('tickets.'.$data, '=', $request->input($data1));*/
		//$baseSelectQuery->where('tickets.category', '=', OT_YES);

		if ($request->input($this->paginator) != '') {
			$dataArray = $baseSelectQuery->select('ticket_print.*', 'destination.*', 'users.*', 'counter.*', 'tickets.*', DB::raw("ticket_print.tp_actual_number||';'||EXTRACT(DAY FROM  tp_date::TIMESTAMP)||'/'||EXTRACT(MONTH FROM  tp_date::TIMESTAMP)||'/'||EXTRACT(YEAR FROM  tp_date::TIMESTAMP)||';'||ticket_print.tp_content||';'||ticket_print.tp_rate AS newticket"))
				->orderBy('tp_id', 'desc')
				->paginate($request->input($this->paginator));
		} else {
			//$dataArray =  $baseSelectQuery->select('ticket_class.*', 'ticket_print.*', 'destination.*', 'users.*', 'counter.*')->orderBy('tp_id', 'desc')->get();
			// dd($baseSelectQuery);
			$dataArray = $baseSelectQuery->select('ticket_print.*', 'destination.*', 'users.*', 'counter.*', 'tickets.*', DB::raw("ticket_print.tp_actual_number||';'||EXTRACT(DAY FROM  tp_date::TIMESTAMP)||'/'||EXTRACT(MONTH FROM  tp_date::TIMESTAMP)||'/'||EXTRACT(YEAR FROM  tp_date::TIMESTAMP)||';'||ticket_print.tp_content||';'||ticket_print.tp_rate AS newticket"))
				->orderBy('tp_id', 'desc')
				->get();
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 20/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <open/close counter>
		    * Name: openCloseCounter;
		    * Description: open or close the counter
		    * Action Type: Application;
		    * Category: Manage;
		    * </openCloseCounter>
	*/
	public function openCloseCounter(Request $request)
	{
		$this->validate($request, [
			'type' => 'required|in:1,2',
			'counterid' => 'required|integer|exists:counter,counter_id',
			'userid' => 'required|integer|exists:users,usr_id',
		]);
		date_default_timezone_set('Indian/Mahe');
		if ($request->input('type') == OT_YES) {
			$dbArray['opening_time'] = date("h:i:sa");
			$dbArray['working_status'] = OT_YES;
			$feedback = 'Counter open successfull..';
			$feedback1 = "Counter already opened";
		} else {
			$dbArray['closing_time'] = date("h:i:sa");
			$dbArray['working_status'] = OT_NO;
			$feedback = "Counter close successfull";
			$feedback1 = "Counter is already closed";
		}
		$oc = (DB::table('counter')->where('counter_id', '=', $request->input('counterid'))->pluck('working_status'));
		if ($dbArray['working_status'] == $oc[0]) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => $feedback1]);
		}

		if (DB::table('counter')->where('counter_id', $request->input('counterid'))->update($dbArray) != 1) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error indatabase updation']);
		} else {
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => $feedback]);
		}
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 22/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <Offline Sync>
		    * Name: offlineSync;
		    * Description: OfflineSync
		    * Action Type: Application;
		    * Category: Manage;
		    * </offlinesync>
	*/

	public function offlineSync(Request $request)
	{
		$ticketClass = $request->json()->all();
		$ticketArray = (array) $ticketClass;
		//return $ticketArray;
		//dd($ticketArray);
		$classArrayDetails = DB::table('class')->select('*')->get();
		foreach ($ticketArray as $ticket) {
			$user_id = $ticket['user_id'];
			$dest_id = $ticket['dest_id'];
			$attr_id = $ticket['attr_id'];
			$counter_id = $ticket['counter_id'];
			$classArray = $ticket['class_id'];
			$numberArray = $ticket['numbers'];
			$classCount = count($classArray);
			$rateArray = $ticket['rate'];
			$dbArray['ticket_usr_id'] = $user_id;
			$dbArray['ticket_dest_id'] = $dest_id;
			$dbArray['ticket_attr_id'] = $attr_id;
			$dbArray['ticket_counter_id'] = $counter_id;
			DB::table('tickets')->insert($dbArray);
			$id = DB::getPdo()->lastInsertId();
			$dbArray = array();
			//$rateArray=array();
			$classNameArray = array();
			foreach ($classArrayDetails as $class) {
				if (in_array($class->class_id, $classArray)) {
					$rateFromDatabaseArray[] = $class->class_rate;
					$classNameArray[] = $class->class_name;
				}
			}
			$destinationName = (DB::table('destination')->where('dest_id', '=', $dest_id)->pluck('dest_name'));
			$attractionName = DB::table('attraction')->where('attr_id', '=', $attr_id)->pluck('attr_name');
			$counter = count($classArray);
			$i = OT_ZERO;
			$grandTotal = OT_ZERO;
			while ($counter > $i) {
				$dbArray['ticket_id'] = $id;
				$dbArray['tc_class_id'] = $classArray[$i];
				$dbArray['tc_number'] = $numberArray[$i];
				$dbArray['attraction_name'] = $attractionName[0];
				$dbArray['dest_name'] = $destinationName[0];
				$dbArray['class_name'] = $classNameArray[$i];
				$dbArray['rate_class_original'] = $rateFromDatabaseArray[$i];
				$rate = (int) $rateArray[$i];
				$number = (int) $numberArray[$i];
				$total = $rate * $number;
				$dbArray['tc_rate_per_class'] = $rate;
				$dbArray['total_rate'] = $total;
				$dbFinalArray[] = $dbArray;
				$i++;
				$grandTotal = $grandTotal + $total;
			}
			//return $grandTotal;
			DB::table('ticket_class')->insert($dbFinalArray);
			$dbArray = array();
			$dbArray['total_rate'] = $grandTotal;
			$rand = mt_rand();
			$dbArray['ticket_number'] = $rand;
			DB::table('tickets')->where('ticket_id', $id)->update($dbArray);
			$ticketNumberArray[] = $rand;
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'successfully inserted', 'Ticket number' => $ticketNumberArray]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 17/06/2021
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <localsync>
		    * Name: localsync;
		    * Description: localsync
		    * Action Type: Application;
		    * Category: Manage;
		    * </localsync>
	*/
	public function localsync(Request $request)
	{
		$data = $request->json()->all();
		$localData = array();
		$localData['ld_verdata'] = $data['verdata'];
		$localData['ld_ticketdata'] = $data['data'];
		$localData['ld_usr_id'] = $data['user'];
		DB::table('local_data')->insert($localData);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 17/06/2021
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <dataSync>
		    * Name: dataSync;
		    * Description: dataSync
		    * Action Type: Application;
		    * Category: Manage;
		    * </dataSync>
	*/
	public function dataSync(Request $request)
	{
		$agent = $request->userAgent();
		$ip = $request->ip();
		$ticketClass = $request->json()->all();
		$isMobile = OT_NO;
		try {
			$ticketArray = json_decode($ticketClass['data']);
		} catch (\Exception $e) {
			$isMobile = OT_YES;
		}
		if ($isMobile == OT_YES) {
			$ticketArray = $ticketClass;
			$isMobile = OT_YES;
		}
		if ($isMobile == OT_YES) {
			$isoffline = $ticketArray[0]['paymode'];
		} else {
			$isoffline = $ticketClass['isoffline'];
		}
		$classData = array();
		$ticketNumberArray = array();
		$ticketActual = array();
		$tickets = array();
		$dup = array();
		$users = array();
		$invalidTicketData = array();
		$dupTicketData = array();
		$dupTxt = "";
		foreach ($ticketArray as $ticket) {
			if ($isMobile == OT_YES) {
				$ticket = (object) $ticket;
			}

			$ticket->tpNumber = preg_replace("/\r|\n/", "", $ticket->tpNumber);
			$tickets[] = $ticket->tpNumber;
		}
		$tickets = (DB::table('ticket_print')->whereIn('tp_actual_number', $tickets)->pluck('tp_actual_number'));
		foreach ($tickets as $data) {
			$ticketActual[] = $data;
		}
		$usersIds = DB::table('users')->whereNull('deleted_at')->select('*')->get();
		foreach ($usersIds as $data) {
			$users[$data->usr_id] = $data->usr_login_seq_no;
		}
		foreach ($ticketArray as $ticket) {
			$ticket = (object) $ticket;
			$userId = $ticket->usrid;
			$ticket->usrid = preg_replace("/\r|\n/", "", $ticket->usrid);
			$ticket->tpNumber = preg_replace("/\r|\n/", "", $ticket->tpNumber);
			$ticket->deviceToken = preg_replace("/\r|\n/", "", $ticket->deviceToken);

			$token = $users[$ticket->usrid];
			if ($ticket->deviceToken != $token) {
				$invData['it_user'] = $ticket->usrid;
				$invData['it_ticket'] = $ticket->tpNumber;
				$invData['it_data'] = json_encode($ticket);
				$invalidTicketData[] = $invData;
			} else if (!in_array($ticket->tpNumber, $ticketActual)) {
				$ticketActual[] = $ticket->tpNumber;
				$ticket->attrId = preg_replace("/\r|\n/", "", $ticket->attrId);
				$ticket->prefix = preg_replace("/\r|\n/", "", $ticket->prefix);

				$tpData['tp_attr_id'] = $ticket->attrId;
				$tpData['tp_rate'] = $ticket->rate;
				$start = strlen($ticket->content) - 5;
				$str1 = substr($ticket->content, $start);
				if ($str1 == '<br/>') {
					$tpData['tp_content'] = substr($ticket->content, 0, strlen($ticket->content) - 5);
				} else {
					$tpData['tp_content'] = $ticket->content;
				}

				$tpData['tp_time'] = $ticket->time;
				$tpData['tp_number'] = $ticket->tno;
				$tpData['tp_counter_id'] = $ticket->counter;
				$tpData['tp_usr_id'] = $ticket->usrid;
				$tpData['tp_actual_number'] = $ticket->tpNumber;
				$tpData['tp_prefix'] = $ticket->prefix;
				$tpData['tp_date'] = $ticket->dbdate;
				$tpData['tp_gno'] = $ticket->gno;
				$tpData['tp_pay_mode'] = $ticket->paymode;
				$tpData['tp_is_offline'] = $isoffline;
				$tpData['ip_created'] = $ip;
				$tpData['u_createdby'] = $ticket->usrid;
				$tpData['tp_data'] = json_encode($ticket);
				$tpData['tp_dest_id'] = $ticket->destId;
				$tpData['tp_classdata'] = json_encode($ticket->classdata);
				$tpData['ua_created'] = $agent;
				if (isset($ticket->cgstData)) {
					$tpData['tp_cgst_data'] = $ticket->cgstData;
				}
				if (isset($ticket->sgstData)) {
					$tpData['tp_sgst_data'] = $ticket->sgstData;
				}
				$tpDa = Ticketprint::create($tpData);
				// DB::table('ticket_print')->insert($tpData);
				// $tpDa = DB::table('ticket_print')->latest('tp_id')->first();
				$tpId = $tpDa->tp_id;
				$ticketNumberArray[] = $tpId;
				$ticket->classIds = preg_replace("/\r|\n/", "", $ticket->classIds);
				$ticket->className = preg_replace("/\r|\n/", "", $ticket->className);
				$classIds = explode("|", $ticket->classIds);
				$classNames = explode("|", $ticket->className);
				$classRates = explode("|", $ticket->classRate);
				$classQuantities = explode("|", $ticket->classQuantity);
				if (isset($ticket->cgst)) {
					$cgst = explode("|", $ticket->cgst);
					$cgstRate = explode("|", $ticket->cgstRate);
					$sgst = explode("|", $ticket->sgst);
					$sgstRate = explode("|", $ticket->sgstRate);
					$gst = explode("|", $ticket->gst);
				}
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
						if (isset($ticket->cgst)) {
							$classDet['tc_cgst'] = $cgst[$i];
							$classDet['tc_sgst'] = $sgst[$i];
							$classDet['tc_cgst_rate'] = $cgstRate[$i];
							$classDet['tc_sgst_rate'] = $sgstRate[$i];
							$classDet['tc_gst'] = $gst[$i];
						}
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
			} else {
				$dupData['dup_user'] = $ticket->usrid;
				$dupData['dup_ticket'] = $ticket->tpNumber;
				$dupData['dup_data'] = json_encode($ticket);
				$dupTicketData[] = $dupData;
				$dupTxt .= json_encode($ticket) . "/\n";
			}
		}
		if (count($dupTicketData)) {
			$emailData['email'] = 'arya.origami@gmail.com';
			$emailData['cc'] = 'nishadnp@gmail.com';
			$emailData['subject'] = 'Ticket Duplication ' . $_SERVER['SERVER_NAME'];
			try {
				$userName = $ticketClass['user'];
			} catch (\Exception $e) {
				$userName = "POS User : " . $userId;
				$emailData['cc'] = 'pratheeshtravansoft@gmail.com';
			}
			$emailData['body'] = " The ticket has been duplicated User - " . $userName . ' : ' . $dupTxt;
			//   sendMail($emailData);
		}
		if (count($classData)) {
			DB::table('ticket_class')->insert($classData);
		}

		if (count($invalidTicketData)) {
			DB::table('invalid_tickets')->insert($invalidTicketData);
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Invalid Token', 'Ticket number' => $ticketNumberArray]);
		}
		if (count($dupTicketData)) {
			DB::table('dup_tickets')->insert($dupTicketData);
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Duplicate Tickets', 'Ticket number' => $ticketNumberArray]);
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Successfully Added', 'Ticket number' => $ticketNumberArray]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 17/06/2021
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <verification>
		    * Name: dataSync;
		    * Description: dataSync
		    * Action Type: Application;
		    * Category: Manage;
		    * </verification>
	*/
	public function verification(Request $request)
	{
		$error = OT_ZERO;
		$errorArray = array();
		$inputObject = $request->json()->all();
		$inputArray = (array) $inputObject;
		$dbArray = array();
		$dbFinalArray = array();
		$newDbArray = array();
		foreach ($inputArray as $input) {
			$dbArray['verification_tp_no'] = $input['ticket'];
			$dbArray['verification_isoffline'] = $input['isoffline'];
			$dbArray['verification_date'] = $input['date'];
			$dbArray['verification_time'] = $input['time'];
			$dbArray['verification_by'] = $input['user'];
			$dbFinalArray[] = $dbArray;
			$newDbArray['tp_ver_status'] = OT_YES;
			$newDbArray['tp_ver_by'] = $input['user'];
			$newDbArray['tp_ver_time'] = $input['time'];
			$newDbArray['tp_ver_date'] = $input['date'];
			$newDbArray['tp_ver_is_offline'] = $input['isoffline'];
			DB::table('ticket_print')->where('tp_actual_number', $input['ticket'])->update($newDbArray);
		}
		DB::table('verification')->insert($dbFinalArray);
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Successfully verified']);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 17/06/2021
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <offlineupdateverification>
		    * Name: dataSync;
		    * Description: dataSync
		    * Action Type: Application;
		    * Category: Manage;
		    * </offlineupdateverification>
	*/
	public function offlineupdateverification(Request $request)
	{
		$newDbArray = array();
		$baseSelectQuery = DB::table('verification');
		$baseSelectQuery->join('ticket_print', 'ticket_print.tp_actual_number', '=', 'verification.verification_tp_no');
		$baseSelectQuery->where('ticket_print.tp_ver_status', '=', OT_NO);
		$baseSelectQuery->whereNull('ticket_print.deleted_at');
		$baseSelectQuery->whereNull('verification.deleted_at');
		$data = $baseSelectQuery->select('ticket_print.*', 'verification.*')->get();
		foreach ($data as $input) {
			$newDbArray['tp_ver_status'] = OT_YES;
			$newDbArray['tp_ver_by'] = $input->verification_by;
			$newDbArray['tp_ver_time'] = $input->verification_time;
			$newDbArray['tp_ver_date'] = $input->verification_date;
			$newDbArray['tp_ver_is_offline'] = $input->verification_isoffline;
			DB::table('ticket_print')->where('tp_actual_number', $input->verification_tp_no)->update($newDbArray);
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Successfully synced']);
	}

	/*
		   -----input json----
		   [ {
		    "ticketnumber":252614926,
		    "userid":1,
		    "attrid":1,
		    "destid":1,
		    "totalnumber":100,
		    "datetime":"09-01-20:10:10:10"
		              },
		              {
		    "ticketnumber":252614926,
		    "userid":1,
		    "attrid":1,
		    "destid":1,
		    "totalnumber":10,
		    "datetime":"09-01-2020:10:10:10"
		              }

		]
	*/
	public function report(Request $request)
	{
		$this->validate($request, [
			'destid' => 'required|integer:exists:destination,dest_id',
			'date' => 'required|date_format:d-m-y',
		]);
		$parts = explode('-', $request->input('date'));
		$date = $parts[1] . '-' . $parts[0] . '-' . $parts[2];
		$destName = DB::table('destination')->where('dest_id', '=', $request->input('destid'))->pluck('dest_name');
		$baseSelectQuery = DB::table('ticket_class');
		$baseSelectQuery->leftjoin('tickets', 'tickets.ticket_id', '=', 'ticket_class.ticket_id');
		$baseSelectQuery->where('ticket_class.dest_name', '=', $destName[0]);
		$baseSelectQuery->where('tickets.date', '=', $date);
		$dataArray = $baseSelectQuery->select(
			'tickets.date AS DATE',
			'ticket_class.dest_name AS DESTINATION NAME',
			'ticket_class.attraction_name AS ATTRACTION NAME',
			'ticket_class.class_name AS CLASS NAME',
			'ticket_class.tc_rate_per_class AS RATE PER CLASS',
			'ticket_class.total_rate AS TOTAL RATE'
		)->get();
		if ($dataArray == '[]') {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'No record found']);
		} else {
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);
		}
	}

	public function searchTicket(Request $request)
	{
		$this->validate($request, ['data' => 'required']);
		if ($request->input($this->paginator) != '') {
			$dataArray = DB::table('tickets')->where('ticket_number', 'like', '%' . $request->input('data') . '%')
				->orwhere('ticket_number', 'like', $request->input('data') . '%')
				->orwhere('ticket_number', 'like', '%' . $request->input('data'))
				->where('deleted_at', NULL)
				->paginate($request->input($this->paginator));
		} else {
			$dataArray = DB::table('tickets')->where('ticket_number', 'like', '%' . $request->input('data') . '%')
				->orwhere('ticket_number', 'like', $request->input('data') . '%')
				->orwhere('ticket_number', 'like', '%' . $request->input('data'))
				->where('deleted_at', NULL)
				->get();
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 11/Nov/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <create order history>
		    * Name: create order history;
		    * Description: create order history
		    * Action Type: Application;
		    * Category: Manage;
		    * </create order history>
	*/
	public function createHistory(Request $request)
	{
		$this->validate($request, [
			'ticketid' => 'required|integer|exists:tickets,ticket_id',
			'userid' => 'integer|required',
			'type' => 'required|in:1,2',
			'status' => ' required|in:1,2,3,4,5,6',
		]);
		$checkData = DB::table('order_history')->where('history_ticket_id', '=', $request->input('ticketid'))
			->where('history_user_id', '=', $request->input('userid'))
			->pluck('history_id');
		if ($checkData != '[]') {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'order history already added']);
		}

		$dbArray['history_ticket_id'] = $request->input('ticketid');
		$dbArray['history_user_id'] = $request->input('userid');
		$dbArray['history_status'] = $request->input('status');
		$dbArray['history_user_type'] = $request->input('type');
		$curDate = Carbon::now();
		$dbArray['history_date'] = $curDate;
		if (DB::table('order_history')->insert($dbArray) != 1) {
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'error in insrtion']);
		}

		$id = DB::getPdo()->lastInsertId();
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'insertion successfull id:' . $id]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 11/Nov/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <list order history>
		    * Name: create order history;
		    * Description: list order history
		    * Action Type: Application;
		    * Category: Manage;
		    * </create order history>
	*/
	public function listHistory(Request $request)
	{
		$this->validate($request, ['userid' => 'integer|required']);
		$baseSelectQuery = DB::table('order_history');
		$baseSelectQuery->leftjoin('users', 'order_history.history_user_id', '=', 'users.usr_id');
		$baseSelectQuery->leftjoin('tickets', 'tickets.ticket_id', '=', 'order_history.history_ticket_id');
		$baseSelectQuery->leftjoin('ticket_class', 'ticket_class.ticket_id', '=', 'order_history.history_ticket_id');
		$baseSelectQuery->where('order_history.history_user_id', '=', $request->input('userid'));
		if ($request->input($this->paginator) != '') {
			$dataArray = $baseSelectQuery->select('users.*', 'tickets.*', 'ticket_class.*', 'order_history.*')->paginate($request->input($this->paginator));
		} else {
			$dataArray = $baseSelectQuery->select('users.*', 'tickets.*', 'ticket_class.*', 'order_history.*')->get();
		}

		return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 11/Nov/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <view order history>
		    * Name: view order history;
		    * Description: create order history
		    * Action Type: Application;
		    * Category: Manage;
		    * </view order history>
	*/
	public function viewHistory(Request $request)
	{
		$this->validate($request, ['historyid' => 'integer|required|exists:order_history,history_id']);
		$baseSelectQuery = DB::table('order_history');
		$baseSelectQuery->leftjoin('users', 'order_history.history_user_id', '=', 'users.usr_id');
		$baseSelectQuery->leftjoin('tickets', 'tickets.ticket_id', '=', 'order_history.history_ticket_id');
		$baseSelectQuery->leftjoin('ticket_class', 'ticket_class.ticket_id', '=', 'order_history.history_ticket_id');
		$baseSelectQuery->where('order_history.history_id', '=', $request->input('historyid'));
		if ($request->input($this->paginator) != '') {
			$dataArray = $baseSelectQuery->select('users.*', 'tickets.*', 'ticket_class.*', 'order_history.*')->paginate($request->input($this->paginator));
		} else {
			$dataArray = $baseSelectQuery->select('users.*', 'tickets.*', 'ticket_class.*', 'order_history.*')->get();
		}

		return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => $dataArray]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 11/Nov/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <edit order history>
		    * Name: edit order history;
		    * Description: edit order history
		    * Action Type: Application;
		    * Category: Manage;
		    * </edit order history>
	*/
	public function editHistory(Request $request)
	{
		$this->validate($request, [
			'historyid' => 'integer|required|exists:order_history,history_id',
			//'ticketid' => 'integer|exists:tickets,ticket_id',
			//'type'=> 'required|in:1,2',
			'status' => ' required|in:1,2,3,4,5,6',
		]);

		$dbArray['history_status'] = $request->input('status');
		$curDate = Carbon::now();
		$dbArray['history_date'] = $curDate;
		if (DB::table('order_history')->where('history_id', '=', $request->input('historyid'))->update($dbArray) == 1) {
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Updated successfully..!']);
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error occured..!']);
		}
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 19/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <counterdata>
		    * Name: counterdata;
		    * Description: Get the counter data;
		    * Action Type: Application;
		    * Category: Manage;
		    * </counterdata>
	*/

	public function counterdata(Request $request)
	{
		if ($request->input('counterId')) {
			$baseSelectQuery = DB::table('counter');
			$baseSelectQuery->leftjoin('attraction', 'attraction.attr_dest_id', '=', 'counter.counter_dest_id');
			$baseSelectQuery->leftjoin("counter_attractions", function ($join) {
				$join->on("counter_attractions.ca_counter_id", "=", "counter.counter_id")
					->on("counter_attractions.ca_attr_id", "=", "attraction.attr_id")
					->whereNull('counter_attractions.deleted_at');
			});
			$baseSelectQuery->whereNull('attraction.deleted_at');
			$baseSelectQuery->where('counter.counter_id', '=', $request->input('counterId'));
			$result = $baseSelectQuery->select('counter.*', 'attraction.*', 'counter_attractions.*')->orderBy('attr_name')->get();
			$dataArray = array();
			$destId = '';
			$counter = '';
			foreach ($result as $data) {
				$status = false;
				if ($data->ca_id) {
					$status = true;
				}

				$dataArray[] = array('attrId' => $data->attr_id, 'attrname' => $data->attr_name, 'status' => $status);
				if (!$destId) {
					$destId = $data->counter_dest_id;
				}

				if (!$counter) {
					$counter = $data->counter_name;
				}
			}

			$baseSelectQuery = DB::table('counter');
			$baseSelectQuery->leftjoin('users', 'users.dest_id', '=', 'counter.counter_dest_id');
			$baseSelectQuery->leftjoin("user_counters", function ($join) {
				$join->on("user_counters.uc_counter_id", "=", "counter.counter_id")
					->on("user_counters.uc_usr_id", "=", "users.usr_id")
					->whereNull('user_counters.deleted_at');
			});
			$baseSelectQuery->whereNull('users.deleted_at');
			$baseSelectQuery->where('counter.counter_id', '=', $request->input('counterId'));
			$baseSelectQuery->where('users.ugrp_id', '=', 18); //use COUNTER_STAFF
			$result = $baseSelectQuery->select('counter.*', 'users.*', 'user_counters.*')->orderBy('usr_name')->get();
			$userArray = array();
			foreach ($result as $data) {
				$status = false;
				if ($data->uc_id) {
					$status = true;
				}

				$userArray[] = array('userId' => $data->usr_id, 'user' => $data->usr_name . '(' . $data->usr_user_name . ')', 'status' => $status);
				if (!$destId) {
					$destId = $data->counter_dest_id;
				}

				if (!$counter) {
					$counter = $data->counter_name;
				}
			}
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Counter Id not found']);
		}

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Success', 'Data' => array('attrData' => $dataArray, 'userData' => $userArray, 'destId' => $destId, 'counter' => $counter)]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 11/Nov/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <changecounterstatus>
		    * Name: changecounterstatus;
		    * Description: counter opening and closing
		    * Action Type: Application;
		    * Category: Manage;
		    * </changecounterstatus>
	*/
	public function changecounterstatus(Request $request)
	{
		$this->validate($request, [
			'counter' => 'integer|required|exists:counter,counter_id',
			'id' => 'integer|required|exists:users,usr_id',
			'status' => 'required|in:1,2',
		]);
		//if counter closing then retreive the total cash collected and no of tickets sold
		$baseSelectQuery = DB::table('counter');
		$baseSelectQuery->leftjoin('counter_history', 'counter_history.ch_counter_id', '=', 'counter.counter_id');
		$baseSelectQuery->where('counter.counter_id', '=', $request->input('counter'));
		$baseSelectQuery->whereNull('counter.deleted_at');
		$counterData = $baseSelectQuery->select('counter.*', 'counter_history.*')->latest('ch_id')->first();
		$returnData = array();
		$status = OT_NO;
		if ($request->input('status') == OT_NO) {
			// for counter closing
			if ($counterData->ch_id) {
				// check counter open or not
				if ($counterData->counter_active_usr_id == $request->input('id')) {
					//check the counter opened user is same as the requested user
					if ($counterData->counter_active_status == OT_YES) {
						// check currently counter in open mode
						$returnData['cash'] = $counterData->ch_total_cash;
						$returnData['tickets'] = $counterData->ch_no_ticket;
						$returnData['opentime'] = $counterData->ch_opening_time;
						$returnData['date'] = $counterData->ch_date;
						$dbArray['counter_active_status'] = $request->input('status');
						$dbArray['counter_latest_close_time'] = Carbon::now();
						if (DB::table('counter')->where('counter_id', '=', $request->input('counter'))->update($dbArray)) {
							$hisData['ch_closing_time'] = date('H:i:s');
							if (DB::table('counter_history')->where('ch_id', '=', $counterData->ch_id)->update($hisData)) {
								$feedback = 'Successfully closed.';
								$status = OT_YES;
							} else {
								$feedback = 'Sorry! Some error has occured. Please try again.';
							}
						} else {
							$feedback = 'Sorry! Some error has occured. Please try again.';
						}
					} else {
						$feedback = 'Currently counter is closed';
					}
				} else {
					$feedback = 'User is not an counter opened user';
				}
			} else {
				$feedback = 'Counter currently not open';
			}
		} else {
			$data['counter_active_status'] = $request->input('status');
			$data['counter_active_usr_id'] = $request->input('id');
			$data['counter_latest_open_time'] = Carbon::now();
			if (DB::table('counter')->where('counter_id', '=', $request->input('counter'))->update($data)) {
				$dbArray['ch_counter_id'] = $request->input('counter');
				$dbArray['ch_usr_id'] = $request->input('id');
				$dbArray['ch_date'] = date('Y/m/d');
				$dbArray['ch_opening_time'] = date('H:i:s');
				if (DB::table('counter_history')->insert($dbArray)) {
					$feedback = 'Successfully opened';
					$status = OT_YES;
				} else {
					$feedback = 'Sorry! Some errors found. Please try again.';
				}
			} else {
				$feedback = 'Sorry! Some errors found. Please try again.';
			}

			// } else {
			//     $feedback = 'Counter currenlty opened';
			// }
		}
		return response()->json(['Status' => $status, 'Feedback' => $feedback, 'Data' => $returnData]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 11/Nov/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <getcounterbyuser>
		    * Name: getcounterbyuser;
		    * Description: Get counter by user
		    * Action Type: Application;
		    * Category: Manage;
		    * </getcounterbyuser>
	*/
	public function getcounterbyuser(Request $request)
	{
		$this->validate($request, [
			'user' => 'integer|required|exists:users,usr_id',
		]);
		//DB::enableQueryLog();
		//if counter closing then retreive the total cash collected and no of tickets sold
		$baseSelectQuery = DB::table('ticket_print');
		$baseSelectQuery->leftjoin('counter', 'counter.counter_id', '=', 'ticket_print.tp_counter_id');
		$baseSelectQuery->leftjoin('destheirarchy', 'destheirarchy.destid', '=', 'counter.counter_dest_id');
		$baseSelectQuery->leftjoin('users', 'users.dest_id', '=', 'destheirarchy.mainparent');
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'destheirarchy.destid');
		$baseSelectQuery->leftjoin('users AS counteruser', 'counteruser.usr_id', '=', 'counter.counter_active_usr_id');
		$baseSelectQuery->leftjoin('users AS counterstaff', 'counterstaff.usr_id', '=', 'ticket_print.tp_usr_id');
		$baseSelectQuery->where('users.usr_id', '=', $request->input('user'));
		$baseSelectQuery->where('ticket_print.tp_number', '>', 0);
		if ($request->input('bookeddate')) {
			$baseSelectQuery->where(DB::raw('ticket_print.tp_date::text'), 'ilike', $request->input('bookeddate') . '%');
		}

		if ($request->input('counter')) {
			$baseSelectQuery->where('counter.counter_name', 'ilike', $request->input('counter') . '%');
		}

		if ($request->input('username')) {
			$baseSelectQuery->where('counterstaff.usr_name', 'ilike', $request->input('username') . '%');
		}

		if ($request->input('staff')) {
			$baseSelectQuery->where('counterstaff.usr_id', '=', $request->input('staff'));
		}

		if ($request->input('counterid')) {
			$baseSelectQuery->where('counter.counter_id', '=', $request->input('counterid'));
		}

		$baseSelectQuery->groupBy('counter.counter_id');
		$baseSelectQuery->groupBy('counterstaff.usr_id');
		$baseSelectQuery->groupBy('ticket_print.tp_date');
		$baseSelectQuery->groupBy('destination.dest_name');
		$baseSelectQuery->groupBy('destination.dest_id');
		$baseSelectQuery->groupBy('ticket_print.tp_date');
		$baseSelectQuery->groupBy('counter.counter_name');
		$baseSelectQuery->groupBy('counter.counter_active_status');
		$baseSelectQuery->groupBy('counteruser.usr_name');
		$baseSelectQuery->groupBy('counter.counter_latest_open_time');
		$baseSelectQuery->groupBy('counterstaff.usr_name');

		$baseSelectQuery->orderBy('ticket_print.tp_date', 'desc');
		$baseSelectQuery->orderBy('counter.counter_name');
		$baseSelectQuery->orderBy('destination.dest_name');

		$baseSelectQuery->select(DB::raw("(CASE WHEN counter.counter_active_status=1 THEN 'Closed' ELSE 'Opened' END) AS counterstatus"), 'ticket_print.tp_date', 'destination.dest_id', 'destination.dest_name', 'counter.counter_id', 'counter.counter_name', 'counter.counter_active_status', 'counteruser.usr_name', DB::raw('SUM(ticket_print.tp_rate) as rate'), DB::raw('COUNT(ticket_print.tp_id) as tickets'), 'counter.counter_latest_open_time', 'counterstaff.usr_name as counterstaffname', 'counterstaff.usr_id AS staffid');
		if ($request->input($this->paginator)) {
			$counterData = $baseSelectQuery->paginate($request->input($this->paginator));
		} else {
			$counterData = $baseSelectQuery->get();
		}

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Successfully Completed', 'Data' => $counterData]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 11/Nov/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <getallcounterbyuser>
		    * Name: getallcounterbyuser;
		    * Description: Get counter by user
		    * Action Type: Application;
		    * Category: Manage;
		    * </getallcounterbyuser>
	*/
	public function getallcounterbyuser(Request $request)
	{
		$this->validate($request, [
			'user' => 'integer|required|exists:users,usr_id',
		]);
		//DB::enableQueryLog();
		//if counter closing then retreive the total cash collected and no of tickets sold
		$baseSelectQuery = DB::table('ticket_print');
		$baseSelectQuery->leftjoin('counter', 'counter.counter_id', '=', 'ticket_print.tp_counter_id');
		$baseSelectQuery->leftjoin('destheirarchy', 'destheirarchy.destid', '=', 'counter.counter_dest_id');
		$baseSelectQuery->leftjoin('users', 'users.dest_id', '=', 'destheirarchy.mainparent');
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'destheirarchy.destid');
		$baseSelectQuery->leftjoin('users AS counteruser', 'counteruser.usr_id', '=', 'counter.counter_active_usr_id');
		$baseSelectQuery->leftjoin('users AS counterstaff', 'counterstaff.usr_id', '=', 'ticket_print.tp_usr_id');
		$baseSelectQuery->where('users.usr_id', '=', $request->input('user'));
		$baseSelectQuery->where('ticket_print.tp_number', '>', 0);
		if ($request->input('bookeddate')) {
			$baseSelectQuery->where(DB::raw('ticket_print.tp_date::text'), 'ilike', $request->input('bookeddate') . '%');
		}

		if ($request->input('counter')) {
			$baseSelectQuery->where('counter.counter_name', 'ilike', $request->input('counter') . '%');
		}

		if ($request->input('username')) {
			$baseSelectQuery->where('counterstaff.usr_name', 'ilike', $request->input('username') . '%');
		}

		$baseSelectQuery->groupBy('counter.counter_id');
		$baseSelectQuery->groupBy('counterstaff.usr_id');
		$baseSelectQuery->groupBy('destination.dest_name');
		$baseSelectQuery->groupBy('destination.dest_id');
		//$baseSelectQuery->groupBy('ticket_print.tp_date');
		$baseSelectQuery->groupBy('counter.counter_name');
		$baseSelectQuery->groupBy('counter.counter_active_status');
		$baseSelectQuery->groupBy('counteruser.usr_name');
		$baseSelectQuery->groupBy('counter.counter_latest_open_time');
		$baseSelectQuery->groupBy('counterstaff.usr_name');

		//$baseSelectQuery->orderBy('ticket_print.tp_date','desc');
		$baseSelectQuery->orderBy('counter.counter_name');
		$baseSelectQuery->orderBy('destination.dest_name');

		$baseSelectQuery->select(DB::raw("(CASE WHEN counter.counter_active_status=1 THEN 'Closed' ELSE 'Opened' END) AS counterstatus"), 'destination.dest_id', 'destination.dest_name', 'counter.counter_id', 'counter.counter_name', 'counter.counter_active_status', 'counteruser.usr_name', DB::raw('SUM(ticket_print.tp_rate) as rate'), DB::raw('COUNT(ticket_print.tp_id) as tickets'), 'counter.counter_latest_open_time', 'counterstaff.usr_name as staff', 'counterstaff.usr_id AS staffid');
		if ($request->input($this->paginator)) {
			$counterData = $baseSelectQuery->paginate($request->input($this->paginator));
		} else {
			$counterData = $baseSelectQuery->get();
		}

		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Successfully Completed', 'Data' => $counterData]);
	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 11/Nov/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <getCounters>
		    * Name: getCounters;
		    * Description: Get counter by user
		    * Action Type: Application;
		    * Category: Manage;
		    * </getCounters>
	*/
	public function getCounters(Request $request)
	{
		$this->validate($request, [
			'dest' => 'integer|required|exists:destination,dest_id',
		]);
		//DB::enableQueryLog();
		//if counter closing then retreive the total cash collected and no of tickets sold
		$baseSelectQuery = DB::table('counter');
		$baseSelectQuery->leftjoin('destheirarchy', 'destheirarchy.destid', '=', 'counter.counter_dest_id');
		$baseSelectQuery->where('destheirarchy.mainparent', '=', $request->input('dest'));
		$baseSelectQuery->whereNull('counter.deleted_at');
		$baseSelectQuery->orderBy('destheirarchy.dest_name');
		$baseSelectQuery->orderBy('counter.counter_name');

		$baseSelectQuery->select('counter.*', 'dest_name');
		$counterData = $baseSelectQuery->get();
		$counters = array();
		$dest = array();
		$destintions = array();
		foreach ($counterData as $data) {
			$counters[] = array('counterId' => $data->counter_id, 'counterName' => $data->counter_name . '-' . $data->dest_name);
			if (!in_array($data->counter_dest_id, $destintions)) {
				$destintions[] = $data->counter_dest_id;
				$dest[] = array('destId' => $data->counter_dest_id, 'destName' => $data->dest_name);
			}
		}
		// dd($baseSelectQuery);
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Successfully Completed', 'Data' => $counters, 'Dest' => $dest]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 11/Nov/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <exportincome>
		    * Name: exportincome;
		    * Description: Get counter by user
		    * Action Type: Application;
		    * Category: Manage;
		    * </exportincome>
	*/
	public function exportincome(Request $request)
	{
		$this->validate($request, [
			'key' => 'required',
		]);
		$key = '4578op98a9';
		if ($request->input('key') != $key) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Invalid key']);
		}

		$date = $request->input('date');
		if ($date == "") {
			$date = Carbon::yesterday()->format('Y-m-d');
		}

		$result = DB::select("SELECT ticket_print.tp_date AS tpdate,destination.dest_name AS branch,attraction.attr_name AS attractionname,attraction.attr_id AS attractionid,class.class_name AS ticketclass,class.class_id AS ticketclassid,SUM(ticket_class.tc_number::bigint) AS ticket,SUM(ticket_class.total_rate::numeric(13,2)) AS amount

        FROM ticket_print
            LEFT JOIN ticket_class ON ticket_class.tc_tp_id=ticket_print.tp_id
            LEFT JOIN class ON class.class_id=ticket_class.tc_class_id
            LEFT JOIN attraction ON attraction.attr_id=class.class_attr_id
            LEFT JOIN destination ON destination.dest_id=attraction.attr_dest_id
            WHERE ticket_print.deleted_at IS NULL AND tp_actual_number IS NOT NULL AND ticket_print.tp_is_cancelled=1 AND ticket_print.tp_date='" . $date . "'
            GROUP BY ticket_print.tp_date,class.class_name,class.class_id,attraction.attr_name,attraction.attr_id,destination.dest_name ORDER BY ticket_print.tp_date");
		$data = array();
		$data['date'] = $date;
		$data['key'] = '12asr78sa45hy67';
		$count = 0;
		foreach ($result as $re) {
			$data['branch'][$re->branch] = $re->branch;
			$count++;
			$data['result'][] = array('collectiondate' => $re->tpdate, 'branch' => $re->branch, 'amount' => $re->amount, 'attraction' => $re->attractionname, 'class' => $re->ticketclass, 'ticket' => $re->ticket, 'ticketclassid' => $re->ticketclassid, 'attrid' => $re->attractionid);
		}
		if ($count == 0) {
			$data['result'][] = array();
		}

		$ch = curl_init(FMIS_URL . '/cronforbranch/collection');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		$resultData = json_decode($response, true);

		if ($resultData['Status'] == 2) {
			foreach ($resultData['data'] as $key => $value) {
				$dbArray = array('class_fmis_id' => $value);
				DB::table('class')->where('class_id', $key)->update($dbArray);
			}
		}
		return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Successfully Completed', 'Data' => $data, 'Response' => $response]);
	}
	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 11/Nov/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <exportchangeincome>
		    * Name: exportchangeincome;
		    * Description: Get counter by user
		    * Action Type: Application;
		    * Category: Manage;
		    * </exportchangeincome>
	*/
	public function exportchangeincome(Request $request)
	{
		ob_start();
		$this->validate($request, [
			'key' => 'required',
		]);
		$key = '4578op98a9';
		if ($request->input('key') != $key) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Invalid key']);
		}

		$data = array();

		$data['key'] = '12asr78sa45hy67';

		$baseSelectQuery = DB::table('change_history');
		$baseSelectQuery->whereNull('deleted_at');
		$baseSelectQuery->where('ch_is_imported', '=', OT_NO);
		$dataArray = $baseSelectQuery->select('change_history.*')->get()->first();
		if (isset($dataArray)) {
			if ($dataArray->ch_id) {
				$data['date'] = $dataArray->ch_date;
				$data['branch'] = $dataArray->ch_dest_name;
				$ch = curl_init(FMIS_URL . '/cronforbranch/remcollection');
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$resultData = json_decode($response, true);

				if ($resultData['Status'] == 2) {
					$servr = $_SERVER['SERVER_NAME'];
					$fileName = "http://" . $servr . "/v1/ticketing/exportincome?date=" . $dataArray->ch_date . "&&key=" . $key;
					$resData = readfile($fileName);
					$dbArray = array('ch_is_imported' => OT_YES);
					DB::table('change_history')->where('ch_id', $dataArray->ch_id)->update($dbArray);
				}
			}
		}
		// return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Successfully Completed', 'Data' => $data]);

	}

	/*
		    * @author SABIN P V
		    * @copyright Origami Technologies
		    * @created 01/July/2022
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <paytmofflinecheck>
		    * Name: paytmofflinecheck;
		    * Description: Get paytm offline status
		    * Action Type: Application;
		    * Category: Manage;
		    * </paytmofflinecheck>
	*/
	public function paytmofflinecheck(Request $request)
	{
		ob_start();
		$this->validate($request, [
			'key' => 'required',
		]);
		$key = '4578op98a9';
		if ($request->input('key') != $key) {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Invalid key']);
		}

		$data = array();

		$data['key'] = '12asr78sa45hy67';

		$baseSelectQuery = DB::table('payment');
		$baseSelectQuery->where('payment_trans_status', '=', OT_NO);
		$baseSelectQuery->orderBy('payment_id', 'ASC');
		$baseSelectQuery->limit(1);
		$dataArray = $baseSelectQuery->select('payment.*')->get()->all();

		foreach ($dataArray as $value) {
			$status_response = checktransactionstatus($value->payment_id);
			$responseString = json_encode($status_response, true);
			extract($responseString);

			$gatewayStatus = $body['resultInfo']['resultStatus'];
			$transactionId = $body['orderId'];

			if ($gatewayStatus == 'TXN_SUCCESS') {
				$transactionStatus = OT_YES;
				$responseKey = '1313ef866Q885456a4sdXC54AWD';
			} else {
				$transactionStatus = OT_THREE;
				$responseKey = '876488AD5464dfsg454sdfs7fd8';
			}

			$orderId = $transactionId;
			$dbArray['payment_response'] = $responseString;
			$dbArray['payment_trans_status'] = $transactionStatus;

			DB::table('payment')->where('payment_id', $orderId)->update($dbArray);
			//$dataArray = DB::table('payment')->where('payment_id', $orderId)->first();

			if ($transactionStatus == OT_YES) {
				/*update payment status in ticket table */
				$ticketNumberArray = DB::table('tickets')->where('ticket_id', '=', $value->payment_ticket_id)->first();

				$ticketId = $ticketNumberArray->ticket_id;
				$dbArray = NULL;
				$dbArray['ticket_payment_status'] = $transactionStatus;
				DB::table('tickets')->where('ticket_id', $ticketId)->update($dbArray);
			}
			//}
			//}

			// return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Successfully Completed', 'Data' => $data]);

		}
	}
}
