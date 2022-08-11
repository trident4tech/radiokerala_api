<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Survey;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller {
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
	public function add(Request $request) {
		$agent = $request->userAgent();
		$ip = $request->ip();
		$name = $request->input('name');
		$mob = $request->input('mob');
		$feedback = $request->input('feedback');
		$lat = $request->input('lat');
		$lng = $request->input('lng');
		$acc = $request->input('acc');
		$quality = $request->input('quality');
		$type = $request->input('type');
		$usr = $request->input('usr');
		$offline = $request->input('offline');
		$time = $request->input('time');
		$dbdate = $request->input('dbdate');
		
		$fileId = array();
		if ($request->hasFile('file')) {
			$fileId = fileUpload($request);
		}

		$i = 0;
		for($i = 0;$i<count($name);$i++){
			$data = array();
			$data['sr_name'] = $name[$i];
			$data['sr_mob'] = $mob[$i];
			if (count($fileId))
				$data['sr_file_id'] = $fileId[$i];
			$data['sr_feedback'] = $feedback[$i];
			$data['sr_lat'] = $lat[$i];
			$data['sr_lng'] = $lng[$i];
			$data['sr_accuracy'] = $acc[$i];
			$data['sr_quality'] = $quality[$i];
			$data['sr_type'] = $type[$i];
			$data['sr_usr_id'] = $usr[$i];
			$data['ip_created'] = $ip;
			$data['sr_agent_data'] = $agent;
			$data['sr_is_offline'] = $offline[$i];
			$data['sr_date'] = $dbdate[$i];
			$data['sr_time'] = $time[$i];
			$insData[] = $data;
		}
		if (DB::table('survey')->insert($insData)) {
			return response(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Survey has been created successfully']);
		}else {
			return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error while Creating Survey']);
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
		$this->validate($request, ['userid' => 'required|integer|exists:users,usr_id']);
		$baseSelectQuery = DB::table('survey');		
		$baseSelectQuery->leftjoin('core_files', 'core_files.file_id', '=', 'survey.sr_file_id');
		if ($request->input('date') != '') {
			$baseSelectQuery->where('survey.sr_date','>=',$request->input('date'));
		}
		if ($request->input('tdate') != '') {
			$baseSelectQuery->where('survey.sr_date','<=',$request->input('tdate'));
		}
		if ($request->input($this->paginator) != '') {
			$user = $baseSelectQuery->select('survey.*', 'core_files.*')->whereNull('survey.deleted_at')->orderBy('survey.sr_id','desc')->paginate($request->input($this->paginator));
		} else {
			$user = $baseSelectQuery->select('survey.*', 'core_files.*')->whereNull('survey.deleted_at')->orderBy('survey.sr_id','desc')->get();
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
	public function delete(Request $request) {
		$this->validate($request, ['id' => 'required|integer|exists:survey,sr_id']);
		$delArray['deleted_at'] = Carbon::now();
		if (DB::table('survey')->where('sr_id', '=', $request->input('id'))
			->update($delArray)) {
			return response()->json(['Status' => OT_YES, 'Feedback' => 'Survey has been removed']);
			
		}

		return response()->json(['Status' => OT_NO, 'Feedback' => 'Survey deletion failed']);
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
	public function changestatus(Request $request) {
		$this->validate($request, ['primary' => 'required|integer|exists:hotels,hotel_id','status' => 'required|in:1,2']); //basic validation
		$id = $request->input('primary');
		$user = Hotels::find($id);
		if ($user != NULL) {
			$user->status = $request->input('status');
			$user->save();
			return response()->json(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'id: ' . $id . 'Status in changed']);
		} else {
			return response()->json(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'id: ' . $id . 'Not  Valid']);
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
	public function view(Request $request) {
		$this->validate($request, ['hotelid' => 'required|integer|exists:hotels,hotel_id']);
		$baseSelectQuery = DB::table('hotels');
		$baseSelectQuery->leftjoin('hotel_files', 'hotel_files.hf_hotel_id', '=', 'hotels.hotel_id');
		$baseSelectQuery->leftjoin('core_files', 'core_files.file_id', '=', 'hotel_files.hf_file_id');

		if ($request->input('hotelid') != '') {
			$baseSelectQuery->where('hotels.hotel_id', '=', $request->input('hotelid'));
		}
		$user = $baseSelectQuery->select('hotel_files.*','core_files.*')->whereNull('hotels.deleted_at')->whereNull('hotel_files.deleted_at')->
		orderBy('core_files.file_id')->get();
		$files = array();
		foreach ($user as $value) {
			$files[] = array('hf_id'=>$value->hf_id,'fileName'=>$value->file_name);
		}

		return response()->json(['Status' => OT_YES, 'Feedback' => 'Success', 'Data' => $files]);
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
		$this->validate($request, ['fileid' => 'required|integer|exists:hotel_files,hf_id']);
		$delArray['deleted_at'] = Carbon::now();
		if (DB::table('hotel_files')->where('hf_id', '=', $request->input('fileid'))
			->update($delArray)) {
			return response()->json(['Status' => OT_YES, 'Feedback' => 'File has been removed']);
			
		}

		return response()->json(['Status' => OT_NO, 'Feedback' => 'File deletion failed']);
	}
}
