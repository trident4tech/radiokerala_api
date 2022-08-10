<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Hotels;
use App\Weatheralert;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HotelController extends Controller {
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
		$this->validate($request, ['destid' => 'integer|exists:destination,dest_id',
		'name' => 'required',
		'des' => 'required',
		'shortdes' => 'required',
		'contact' => 'required|regex:/[0-9]{9}/',
		'lat' => 'required',
		'lng' => 'required',
		'roleid' => 'required|integer|exists:roles,id',
		'userid' => 'required|exists:users,usr_id']);	
		$fileId = array();
		if ($request->hasFile('file')) {
			$fileId = fileUpload($request);
		}
		$data = array();
		$data['hotel_name'] = $request->input('name');
		$data['hotel_dest_id'] = $request->input('destid');
		$data['hotel_img'] = $fileId[0];
		$data['hotel_desc'] = $request->input('des');
		$data['hotel_phoneno'] = $request->input('contact');
		$data['hotel_latitude'] = $request->input('lat');
		$data['hotel_longitude'] = $request->input('lng');
		$data['hotel_short_desc'] = $request->input('shortdes');
		if (DB::table('hotels')->insert($data) == 1) {
			$hId = DB::getPdo()->lastInsertId();
			if ($request->hasFile('file')) {
				$fileData = array();
				if (is_array($fileId)) {
					foreach ($fileId as $id) {					
						$files['hf_hotel_id'] = $hId;
						$files['hf_file_id'] = $id;
						$fileData[] = $files;
					}
				}
				else {
					$files['hf_hotel_id'] = $hId;
					$files['hf_file_id'] = $fileId;
					$fileData[] = $files;
				}
				DB::table('hotel_files')->insert($fileData);
			}
			return response(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Hotel has been created successfully']);
		}else {
			return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error while Creating Hotel']);
		}
		
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
	public function addaudio(Request $request) {
		// $this->validate($request, ['destid' => 'integer|exists:destination,dest_id',
		// 'name' => 'required',
		// 'des' => 'required',
		// 'shortdes' => 'required',
		// 'contact' => 'required|regex:/[0-9]{9}/',
		// 'lat' => 'required',
		// 'lng' => 'required',
		// 'roleid' => 'required|integer|exists:roles,id',
		// 'userid' => 'required|exists:users,usr_id']);	
		$fileId = array();
		if ($request->hasFile('file')) {
			$fileId = fileUpload($request);
		}
		return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => $fileId]);
		$data = array();
		$data['hotel_name'] = $request->input('name');
		$data['hotel_dest_id'] = $request->input('destid');
		$data['hotel_img'] = $fileId[0];
		$data['hotel_desc'] = $request->input('des');
		$data['hotel_phoneno'] = $request->input('contact');
		$data['hotel_latitude'] = $request->input('lat');
		$data['hotel_longitude'] = $request->input('lng');
		$data['hotel_short_desc'] = $request->input('shortdes');
		if (DB::table('hotels')->insert($data) == 1) {
			$hId = DB::getPdo()->lastInsertId();
			if ($request->hasFile('file')) {
				$fileData = array();
				if (is_array($fileId)) {
					foreach ($fileId as $id) {					
						$files['hf_hotel_id'] = $hId;
						$files['hf_file_id'] = $id;
						$fileData[] = $files;
					}
				}
				else {
					$files['hf_hotel_id'] = $hId;
					$files['hf_file_id'] = $fileId;
					$fileData[] = $files;
				}
				DB::table('hotel_files')->insert($fileData);
			}
			return response(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Hotel has been created successfully']);
		}else {
			return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error while Creating Hotel']);
		}
		
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
	public function update(Request $request) {
		$this->validate($request, ['destid' => 'integer|exists:destination,dest_id',
			'hotelid' => 'integer|exists:hotels,hotel_id',
		'name' => 'required',
		'des' => 'required',
		'shortdes' => 'required',
		'contact' => 'required|regex:/[0-9]{9}/',
		'lat' => 'required',
		'lng' => 'required',
		'roleid' => 'required|integer|exists:roles,id',
		'userid' => 'required|exists:users,usr_id']);	
		$fileId = array();
		if ($request->hasFile('file')) {
			$fileId = fileUpload($request);
		}
		$baseSelectQuery = DB::table('hotels');
		$baseSelectQuery->leftjoin('hotel_files', 'hotel_files.hf_hotel_id', '=', 'hotels.hotel_id');
		$baseSelectQuery->where('hotels.hotel_id', '=', $request->input('hotelid'));		
		$user = $baseSelectQuery->select('hotel_files.*','hotels.hotel_img')->whereNull('hotels.deleted_at')->whereNull('hotel_files.deleted_at')->get();
		

		$data = array();
		$data['hotel_name'] = $request->input('name');
		$data['hotel_dest_id'] = $request->input('destid');
		if (count($user)==0)
			$data['hotel_img'] = $fileId[0];
		$data['hotel_desc'] = $request->input('des');
		$data['hotel_phoneno'] = $request->input('contact');
		$data['hotel_latitude'] = $request->input('lat');
		$data['hotel_longitude'] = $request->input('lng');
		$data['hotel_short_desc'] = $request->input('shortdes');
		if (DB::table('hotels')->where('hotel_id', '=', $request->input('hotelid'))->update($data)) {
			$hId = $request->input('hotelid');
			if ($request->hasFile('file')) {
				$fileData = array();
				if (is_array($fileId)) {
					foreach ($fileId as $id) {					
						$files['hf_hotel_id'] = $hId;
						$files['hf_file_id'] = $id;
						$fileData[] = $files;
					}
				}
				else {
					$files['hf_hotel_id'] = $hId;
					$files['hf_file_id'] = $fileId;
					$fileData[] = $files;
				}
				DB::table('hotel_files')->insert($fileData);
			}
			return response(['Status' => OT_YES, 'version' => VERSION, 'Feedback' => 'Hotel has been updated successfully']);
		}else {
			return response(['Status' => OT_NO, 'version' => VERSION, 'Feedback' => 'Error while Updating Hotel']);
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
		$baseSelectQuery = DB::table('hotels');
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'hotels.hotel_dest_id');		
		$baseSelectQuery->leftjoin('core_files', 'core_files.file_id', '=', 'hotels.hotel_img');
		if ($request->input('destId') != '') {
			$baseSelectQuery->leftjoin('destheirarchy', 'destheirarchy.destid', '=', 'destination.dest_id');
			$baseSelectQuery->where('destheirarchy.mainparent', '=', $request->input('destId'));
		}
		if ($request->input($this->paginator) != '') {
			$user = $baseSelectQuery->select('hotels.*', 'destination.dest_name')->whereNull('hotels.deleted_at')->orderBy('hotels.hotel_id')->paginate($request->input($this->paginator));
		} else {
			$user = $baseSelectQuery->select('hotels.*', 'destination.dest_name')->whereNull('hotels.deleted_at')->orderBy('hotels.hotel_id')->get();
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
	public function listall(Request $request) {

		$baseSelectQuery = DB::table('hotels');
		$baseSelectQuery->leftjoin('destination', 'destination.dest_id', '=', 'hotels.hotel_dest_id');		
		$baseSelectQuery->leftjoin('core_files', 'core_files.file_id', '=', 'hotels.hotel_img');
		$baseSelectQuery->leftjoin('core_files as destfiles', 'destfiles.file_id', '=', 'destination.dest_img_file');
		$baseSelectQuery->whereNull('hotels.deleted_at');
		$baseSelectQuery->where('hotels.status','=','2');
		$user = $baseSelectQuery->select('hotels.*', 'core_files.*','destination.*','destfiles.file_name as destfile')->whereNull('hotels.deleted_at')->orderBy('hotels.hotel_id')->get();

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
		$this->validate($request, ['hotelid' => 'required|integer|exists:hotels,hotel_id']);
		$delArray['deleted_at'] = Carbon::now();
		if (DB::table('hotels')->where('hotel_id', '=', $request->input('hotelid'))
			->update($delArray)) {
			return response()->json(['Status' => OT_YES, 'Feedback' => 'Hotel has been removed']);
			
		}

		return response()->json(['Status' => OT_NO, 'Feedback' => 'Hotel deletion failed']);
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
