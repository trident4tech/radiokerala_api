<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laminas\Config\Reader\Ini;
use Illuminate\Http\Request;
use Carbon\Carbon;
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

class ConstantController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $paginator = 'p';
    public function __construct(Request $request)
    {
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 09/02/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <create constants>
    * Name: createconstants;
    * Description: create new constants
    * Action Type: Application;
    * Category: Manage;
    * </create constants>
    */

    public function create(Request $request)
    {
        $this->validate($request, [
            'constantname' =>   'string',
            'constantvalue' =>  'string'
        ]);
        $dbArray['const_name'] = $request->input('constantname');
        if ( $request->input('date') != NULL ){
            $dbArray['const_affective_date'] = $request->input('date');
            $whereDate = $request->input('date');
        }
        else {
            $dbArray['const_affective_date'] = Carbon::now();
            $whereDate = Carbon::now();
        }
        $dbArray['const_description'] = $request->input('description');
        $dbArray['const_value'] = $request->input('constantvalue');
        $dbArray['u_createdby'] = $request->input('ucreated');
        $dbArray['created_at'] = Carbon::now();
        $dataArray = [];
        $dataArray = DB::table('core_constants')
                    ->where('const_name', $request->input('constantname'))
                    ->where('const_affective_date', $whereDate)
                    ->where('deleted_at', NULL)
                    ->select('*')->get();
        if ($dataArray != '[]') {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Constant name with this date field is already exist', 'Data' => $dataArray]);
        }
        try {
            if (DB::table('core_constants')->insert($dbArray) == 1)
                return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'successfully inserted']);
            else
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Error in insrtion']);
        } catch (\Exception $e) {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Date formate must be mm-dd-yy']);
        }
    }

    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 09/02/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <edit constants>
    * Name: editconstants;
    * Description: edit constants
    * Action Type: Application;
    * Category: Manage;
    * </edit constants>
    */

    public function edit(Request $request)
    {
        $this->validate($request, [
            'constantname' => 'required|exists:core_constants,const_name',
            'constantvalue' =>   'string',
            'date' =>   'date'
        ]);
        if ($request->input('date') != '') {
            $dbArray['const_affective_date'] = $request->input('date');
            $dbArray['const_name'] = $request->input('constantname');
            $dbArray['const_description'] = $request->input('description');
            $dbArray['const_value'] = $request->input('constantvalue');
            try {
                if (DB::table('core_constants')->insert($dbArray) == 1)
                    return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'successfully updated']);
                else
                    return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'updation failed']);
            } catch (\Exception $e) {
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Date formate must be mm-dd-yy']);
            }
        }
    }

    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 09/02/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <delete constants>
    * Name: deleteconstants;
    * Description: delete new constants
    * Action Type: Application;
    * Category: Manage;
    * </delete constants>
    */

    public function delete(Request $request)
    {
        $this->validate($request, ['constantid' => 'required|integer|exists:core_constants,const_id']);
        try {
            $dbArray['deleted_at'] = Carbon::now();
            if (DB::table('core_constants')->where('const_id', '=', $request->input('constantid'))->update($dbArray)/*->delete()*/)
                return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Action Success']);
            else
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'class_id not exist']);
        } catch (\Exception $e) {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'ForeignKey violation']);
        }
    }

    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 31/10/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <list constants>
    * Name: Listconstants;
    * Description: List Constants
    * Action Type: Application;
    * Category: Manage;
    * </List constants>
    */

    public function list(Request $request)
    {
        //$this->validate($request, [ 'constantid' =>'required|integer|exists:core_constants,const_id']);
        try {

            $curDate = Carbon::now();
            if ($request->input($this->paginator) != '') {
                $dataArray = DB::table('core_constants')->select('*')->distinct('const_name')->whereDate('const_affective_date', '<=', $curDate)->where('deleted_at', NULL)->paginate($request->input($this->paginator));
            } else
                $dataArray = DB::table('core_constants')->select('*')->where('deleted_at', NULL)->get();
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success..!', 'Data' => $dataArray]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'ForeignKey violation']);
        }
    }


        /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 31/10/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <list constants>
    * Name: Listconstants;
    * Description: List Constants
    * Action Type: Application;
    * Category: Manage;
    * </List constants>
    */

    public function viewscheduled(Request $request)
    {
        if ( $request->input('viewlog') == 2 ){
            $dataArray = DB::table('core_constants')
            ->leftjoin('users', 'users.usr_id', '=', 'core_constants.u_createdby')
            ->select('users.*','core_constants.*')->where('core_constants.deleted_at', NULL)->where('core_constants.const_name' , $request->input('constantname'))->get();

        } else{
            $curDate = Carbon::now();
            $dataArray = DB::table('core_constants')
            ->leftjoin('users', 'users.usr_id', '=', 'core_constants.u_createdby')
            ->select('users.*','core_constants.*')->whereDate('core_constants.const_affective_date', '>=', $curDate)->where('core_constants.deleted_at', NULL)->where('core_constants.const_name' , $request->input('constantname'))->get();
        }
        return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success..!', 'Data' => $dataArray]);

    }
    
}
