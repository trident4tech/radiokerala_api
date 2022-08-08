<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\User;
use App\Usergroup;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
class GroupUserController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $paginator='p';
    public function __construct(Request $request) {
        $this->validate($request, [$this->paginator => 'integer' ]);
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 25/07/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <register>
    * Name: API Group Register;
    * Description: Group registration Function;
    * Action Type: Application;
    * Category: Manage;
    * </register>

    */
    public function register(Request $request) {
        $this->validate($request, [ 'ugrpname' => 'required|String|unique:usergroups,ugrp_name',
                                    'destination_allowed'=> 'required|in:1,2']);
        // Register Usergroup...
        try {
            $userGroup = new Usergroup;
            $userGroup->ugrp_name = $request->input('ugrpname');
            $userGroup->ugrp_destination_allowed=$request->input('destination_allowed');
            if ($userGroup->save()){
                 $insertedId = $userGroup->ugrp_id;
                $output = ['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'User Group Registers Successfully'];
            }
            else{
                $output = ['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Error while Creating User Group'];
            }
        }
        catch(Exception $e) {
           // dd($e->getMessage());
            $output = ['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'RunTime Exception occured'];
        }
        return response()->json($output);
    }

    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 21/07/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <List all Use Groupr>
    * Name: List;
    * Description: List all UserGroup executed here;
    * Action Type: Application;
    * Category: Manage;
    * </List>

    */
    public function list(Request $request) {
        if($request->input($this->paginator) != '')
            $userGrops = DB::table('usergroups')->where('deleted_at',NULL)->paginate($request->input($this->paginator));
        else
            $userGrops = Usergroup::all();
        return response()->json(['Status' => OT_YES,'version'=>VERSION,'Feedback' => 'Success' ,'Data' => $userGrops,'allows'=>array(OT_NO=>'Allowed',OT_YES=>'Not Allowed')]);
    }



    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 21/07/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <Edit all groupt>
    * Name: Edit;
    * Description: Edit all groups executed here;
    * Action Type: Application;
    * Category: Manage;
    * </List>

    */

    public function edit(Request $request) {
        $this->validate($request, ['id' => 'required|integer|exists:usergroups,ugrp_id',
                                    'ugrp_name' =>'required|string|unique:usergroups,ugrp_name',
                                    'ugrp_destination_allowed' =>'required|in:1,2' ]); //basic validation
        $userGroup = Usergroup::find($request->input('id'));
        $userGroup->ugrp_name = $request->input('ugrp_name');
        $userGroup->ugrp_destination_allowed = $request->input('ugrp_destination_allowed');
        $userGroup->save();
        return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Successfully Updated']);
    }

    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 21/07/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <List Single User>
    * Name: list Single group;
    * Description: Listing of single group execute here
    * Action Type: Application;
    * Category: Manage;
    * </listsingle>
    */
    public function view(Request $request) {
        $this->validate($request, ['id' => 'required|integer|exists:usergroups,ugrp_id' ]); //basic validation
        $userGrops = Usergroup::whereugrp_id($request->input('id'))->first();
        return response()->json(['Status' => OT_YES,'version'=>VERSION,'Feedback' => 'Success' ,'Data' => $userGrops]);
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
    public function delete(Request $request) {
        $this->validate($request, ['id' => 'required|integer|exists:usergroups,ugrp_id' ]); //basic validation
        try{
            $dataArray['deleted_at'] = Carbon::now();
            $user = Usergroup::whereugrp_id($request->input('id'))->update($dataArray)/*->delete()*/;
        }
        catch(\Exception $e){
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'violates foreign key constraint']);
        }
        return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Deleted']);
    }

/*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 27/07/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <Change Status>
    * Name: Status;
    * Description: Change Status of Group
    * Action Type: Application;
    * Category: Manage;
    * </listsingle>

    */

    public function status(Request $request) {
        $this->validate($request, ['id' => 'required','status' => 'required' ]); //basic validation
        try{
           // $id = Crypt::decrypt($request->input('id'));
             $id = $request->input('id');
            $user = Usergroup::find($id);
            if($user != NULL){
                $user->status = $request->input('status');
                $user->save();
                return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'id: '.$id.'Status in changed']);
            }
            else{
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'id: '.$id.'Not  Valid']);
            }
        }
        catch (DecryptException $e) {
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Id is not  valid']);
        }
    }

}
