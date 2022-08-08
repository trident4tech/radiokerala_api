<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FileController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {

	}

	/*
		    * @author Pratheesh
		    * @copyright Origami Technologies
		    * @created 5/08/2020
		    * @license http://www.origamitechnologies.com
		    * @aclinfo <file uploadt>
		    * Name: API Add Master;
		    * Description: API add content into table;
		    * Action Type: Application;
		    * Category: Manage;
		    * </add>

	*/

	public function fileUpload(Request $request) {
		// $picName = $request->file('file')->getClientOriginalName();
		// $picName = uniqid() . '_' . $picName;
		// $path = 'uploads/user_files/';
		// $destinationPath = public_path($path); // upload path
		// File::makeDirectory($destinationPath, 0777, true, true);
		// $a = $request->file('file')->move($destinationPath, $picName);
		// return response()->json($a);

		// $this->validate($request, ['file' => 'required' ]);
		//  $a= fileUpload($request);
		// return response()->json($a);
	}
}