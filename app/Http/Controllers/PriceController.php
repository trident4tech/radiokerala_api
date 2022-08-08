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
class PriceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request) {
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 09/03/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <create price>
    * Name: create;
    * Description: create base price for a class
    * Action Type: Application;
    * Category: Manage;
    * </create price>
    */

    public function create(Request $request){
        $this->validate($request, [ 'userid'    =>  'required|integer|exists:users,usr_id',
                                    'classid'   =>  'required|integer|exists:class,class_id',
                                    'fromdate'  =>  'date_format:m-d-y|required',
                                    'todate'    =>  'date_format:m-d-y',
                                    'rate'      =>  'integer|required']);
        $destinatioId=(DB::table('users')->where('usr_id', '=',$request->input('userid'))->pluck('dest_id'));
        if($destinatioId == '[]' || $destinatioId == '[null]')
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'User have not a valid destination']);
        $array1 = DB::table('price')->select('*')->where('price_class_id', '=', $request->input('classid'))->pluck('price_class_id')->toArray();
        $array2 = DB::table('price')->select('*')->where('price_to_date', '>=', $request->input('fromdate'))->pluck('price_class_id')->toArray();
        $array3 = DB::table('price')->select('*')->where('price_from_date', '>=', $request->input('fromdate'))->pluck('price_class_id')->toArray();
        if(in_array($request->input('classid'),$array1)){
            if(in_array($request->input('classid'),$array2)){
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'classId between this dates are already defined']);
            }
            if(in_array($request->input('classid'),$array3)){
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'classId between this dates are already defined']);
            }

        }
        $classIdFromTable=(DB::table('class')->where('class_dest_id', '=',$destinatioId[0])->pluck('class_id'))->toArray();
        if(!(in_array($request->input('classid'),$classIdFromTable)))
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'User-destination and Class-destination mismatch']);
        $dbArray['price_class_id']=$request->input('classid');
        $dbArray['price_base_rate']=$request->input('rate');
        $dbArray['price_from_date']=$request->input('fromdate');
        $dbArray['price_to_date']=$request->input('todate');
        if(DB::table('price')->insert($dbArray) != 1)
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'error in db insertion']);
        else
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Successfully inserted']);
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 09/03/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <edit price>
    * Name: edit;
    * Description: edit price of a class
    * Action Type: Application;
    * Category: Manage;
    * </edit price>
    */
    public function edit(Request $request){
        $this->validate($request, [ 'id'    =>  'required|integer|exists:price,price_id',
                                    'fromdate'  =>  'required|date_format:m-d-y',
                                    'todate'    =>  'date_format:m-d-y',
                                    'rate'      =>  'integer|required']);
        $classId=(DB::table('price')->where('price_id', '=',$request->input('id'))->pluck('price_class_id'));
        $dbArray['price_class_id']=$classId[0];
        $dbArray['price_base_rate']=$request->input('rate');
        $dbArray['price_from_date']=$request->input('fromdate');
        $dbArray['price_to_date']=$request->input('todate');
        if(DB::table('price')->insert($dbArray) != 1)
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'error in db insertion']);
        else
            return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Price updated successfully']);
    }
    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 09/03/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <delete price>
    * Name: delete;
    * Description: delete price of a class
    * Action Type: Application;
    * Category: Manage;
    * </delete price>
    */
    public function delete(Request $request){
        $this->validate($request, [ 'priceid' =>'required|integer|exists:price,price_id']);
        try{
            if(DB::table('price')->where('price_id', '=', $request->input('priceid'))->delete())
                return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Deletion success']);
            else
                return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'price id not exist']);
        }
        catch(\Exception $e){
            return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'ForeignKey violation']);
        }
    }
}

