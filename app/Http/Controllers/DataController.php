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

class DataController extends Controller{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $reader;
    private $filename;
    private $ini;
    private $tableName;
    private $fields;
    private $str='';
    private $primary;
    private $schemaCheck='';
    private $status;
    private $object;
    private $foreign;
    private $filter;
    private $paginator='p';

  public function __construct(Request $request){
      //---commented for using angular
    $this->validate($request, ['schema' => 'required',$this->paginator => 'integer' ]);
    if ((Schema::hasTable($request->input('schema'))) > 0){
      $this->reader = new Ini();
      $this->filename=storage_path("Inii.ini");
      $this->ini   = $this->reader->fromFile($this->filename);
      $this->tableName=$request->input('schema');
      $this->object =$this->ini[$this->tableName];
      $this->fields=(object) $this->object;
      $this->primary=$this->fields->pkey;
      $this->status=$this->fields->status;
      $this->foreign=$this->fields->fkeys;
      $this->filter=$this->fields->filter;
        $this->referencelink = array();
        if (isset($this->fields->referencelink)) {
            foreach ($this->fields->referencelink as $link) {
                $this->referencelink[] = array('title'=>array_search($link,$this->fields->referencelink),'url'=>$link);
            }
        }
      $this->ordering = $this->fields->ordering;
    }
    else{
      $this->schemaCheck='Schema doesnt exist';
    }
  }

    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 12/08/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <list>
    * Name: List;
    * Description: List all records from table;
    * Action Type: Application;
    * Category: Manage;
    * </List>
    */

  public function list(Request $request){
    //----validation for angular
    //$data = $request->all();
    //$validator = Validator::make($data, ['schema' => 'required',$this->paginator => 'integer' ]);
    //if ($validator->fails()) {
      //  $error=$validator->errors();
       // return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $error]);
       //}
    $fields = array();
      $fkeys = array();
      $returnArray = array();
    //Check if input table exist or not
    if($this->schemaCheck != '')
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $this->schemaCheck]);
     $fields = $this->fields->fields;
      $QueryString[]=$this->tableName.'.*';
      foreach($this->ordering as $data => $key){
        $order=$data;
      }



    $baseSelectQuery = DB::table($this->tableName);
    //Retrive foreign-key,foreign-table,foreign-fields and primary-key of foreign table from .ini file;Run left join
        $i = 0;
    foreach($this->fields->fkeys as $key){
      if($key['label'] != OT_NO){
        $foregin=$key['name'];
        $foreignTable=$key['table'];
        $foreignColumn=$key['column'];
          
        $columnArray= explode (",", $foreignColumn);
        $primaryKeyOfTable=$this->ini[$foreignTable]['pkey'];
        $fTable = $foreignTable.$i;
        $baseSelectQuery->leftjoin($foreignTable.' as '.$fTable, $this->tableName.'.'.$foregin, '=', $fTable.'.'.$primaryKeyOfTable);
          //$baseSelectQuery ->leftjoin($foreignTable, $this->tableName.'.'.$foregin, '=', $foreignTable.'.'.$primaryKeyOfTable);
       // $baseSelectQuery->where($fTable.'.deleted_at',NULL);
          $fkeys[$foregin]=$foreignColumn;
          $j = 0;
        foreach($columnArray as $column){            
            //Assign alias name for foreign table columns
            $fkeyColumnName = explode("|",$column);
            $column = $fkeyColumnName[0];
            
            if (count($fkeyColumnName)>1) {
                if ($j==0)
                    $fkeys[$foregin]=$fkeyColumnName[1];
                $QueryString[]=$fTable.'.'.$column.' as '.$fkeyColumnName[1];
            }
            else
                $QueryString[]=$fTable.'.'.$column;
            $j++;
        }
      }
        $i++;
    }
    $filters = $this->fields->filter;
    //Check input filter if present,Run where query
    foreach($this->fields->filter as $fltr){
      if($fltr['label'] != OT_NO){
        if($request->input($fltr['column']) != ''){  
          $baseSelectQuery->where($this->tableName.'.'.$fltr['fieldname'], "=", $request->input($fltr['column']));
        }
      }
    }
    
       foreach ( $this->fields->fkeys as $key ) {
        if ( $key['label'] != OT_NO ) {
            $foregin = $key['name'];
            $foreignTable = $key['table'];
            $foreignColumn = $key['column'];
            $columnArray = explode (",", $foreignColumn);
            $primaryKeyOfTable = $this->ini[ $foreignTable ]['pkey'];
            $foreignColumnName = $columnArray[0];
            $responseDataArray = DB::table($foreignTable)->where('deleted_at',NULL)->where('status','=',OT_YES)->select('*')->get();
            foreach ( $responseDataArray as $tableData ){
                $fcolumnName = explode("|",$foreignColumnName);
                $colmn = $fcolumnName[0];
                $tempArray [ $tableData->$primaryKeyOfTable ] = $tableData->$colmn;
            }
            $returnArray[$key['name'] ] = $tempArray;
            $tempArray = [];
        }
        
    }
    //execute $baseSelectQuery
    try{
        //DB::enableQueryLog();
      if($request->input($this->paginator) != '')
        $dataArray=  $baseSelectQuery->select($QueryString)->whereNull($this->tableName.'.deleted_at')->orderBy($order)->paginate($request->input($this->paginator));
      else
      $dataArray=  $baseSelectQuery->select($QueryString)->whereNull($this->tableName.'.deleted_at')->orderBy($order)->get();
       // dd(DB::getQueryLog());
    }
    catch(\Illuminate\Database\QueryException $ex){
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $ex.'filter data type missmatch']);
    }
    return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Success' ,'Data' => $dataArray,'filters'=>$filters,'fields'=>$fields,'pkey'=>$this->primary,'fkeys'=>$fkeys,'fkeyvalue'=>$returnArray,'referencelink'=>$this->referencelink]);
  }

/*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 12/08/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <list single row>
    * Name: View;
    * Description: View single row by using input id;
    * Action Type: Application;
    * Category: Manage;
    * </View>
    */
  public function view(Request $request){
    if($this->schemaCheck != '')
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $this->schemaCheck]);
    $this->validate($request, [$this->primary => 'required']);
    $baseSelectQuery = DB::table($this->tableName);
    $queryString[]=$this->tableName.'.*';
    $primarKeyOfSchema=$request->input($this->primary);//*/Crypt::decrypt($request->input($this->primary));
    foreach($this->fields->fkeys as $key){
      if($key['label'] != OT_NO){
        $foregin=$key['name'];
        $foreignTable=$key['table'];
        $foreignColumn=$key['column'];
        $columnArray= explode (",", $foreignColumn);
        $primaryKeyOfTable=$this->ini[$foreignTable]['pkey'];
        $baseSelectQuery ->leftjoin($foreignTable, $this->tableName.'.'.$foregin, '=', $foreignTable.'.'.$primaryKeyOfTable);
        foreach($columnArray as $column){
          $queryString[]=$foreignTable.'.'.$column;
        }
      }
    }
    $baseSelectQuery ->where($this->tableName.'.'.$this->primary, '=', $primarKeyOfSchema);
    if( $baseSelectQuery->select($queryString)->get() != '[]'){
      $dataArray=  $baseSelectQuery->select($queryString)->get();
      return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' =>'Success', 'Data' => $dataArray]);
    }
   else
    return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'id not exist']);
  }

/*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 12/08/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <create>
    * Name: Create;
    * Description: insert single record into db;
    * Action Type: Application;
    * Category: Manage;
    * </Create>
    */
  public function create(Request $request){
    if($this->schemaCheck != '')
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $this->schemaCheck]);
    $ColumnNameArray = $this->fields->fields;
    $foreign = $this->fields->fkeys;
    $array = array();
    $returnArray = array();

    $inputArray = $request->all();
    foreach ( $this->fields->fkeys as $key ) {
        if ( $key['label'] != OT_NO ) {
            $foregin = $key['name'];
            $foreignTable = $key['table'];
            $foreignColumn = $key['column'];
            $columnArray = explode (",", $foreignColumn);
            $primaryKeyOfTable = $this->ini[ $foreignTable ]['pkey'];
            $foreignColumnName = $columnArray[0];
            $responseDataArray = DB::table($foreignTable)->where('deleted_at',NULL)->select('*')->get();
            foreach ( $responseDataArray as $tableData ){
                $column = explode ("|", $foreignColumnName);
                $foreignColumnName = $column[0];
                $tempArray [ $tableData->$primaryKeyOfTable ] = $tableData->$foreignColumnName;
            }
            $returnArray[ $key['label'] ] = $tempArray;
            $tempArray = [];

        }
    }
    if (count($inputArray) == OT_ONE){
        return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Foreign key data' , 'fdata' => $returnArray]);
    }



    foreach($ColumnNameArray as $columnName){
      if($request->input($columnName['name']) != '' ){
          if (isset($columnName['rule'])) {
            $rule = $columnName['rule'];
            $validator = Validator::make([$request->input($columnName['name'])],[$rule]);
            if ($validator->fails()) {
              return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' =>$columnName['name'].':'.$request->input($columnName['name']).' validation faild']);
            }
          }
        $array[''.$columnName['name']]=/*Crypt::decrypt($request->input($columnName['name']));//*/$request->input($columnName['name']);
      }
    }
    try{
      DB::table($this->tableName)->insert($array);
    }
    catch(\Illuminate\Database\QueryException $ex){
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' =>$ex.'error in insertion' , 'fdata' => $returnArray]);
    }
    return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Successfully inserted' , 'fdata' => $returnArray]);
  }

    /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 12/08/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <delete>
    * Name: Delete;
    * Description: Delete single row ;
    * Action Type: Application;
    * Category: Manage;
    * </Delete>
     */
  public function delete(Request $request){
    if($this->schemaCheck != '')
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $this->schemaCheck]);
    $this->validate($request, [$this->primary => 'required']);
    try{
   // $primaryKeyOfTable = Crypt::decrypt($request->input($this->primary));
    }
    catch(DecryptException $e){
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Faild in decryption']);
  }
    $primaryKeyOfTable = $request->input($this->primary);
    try{
        $dbArray['deleted_at'] = Carbon::now();

      if(DB::table($this->tableName)->where($this->primary, '=', $primaryKeyOfTable)->update($dbArray)/*->delete()*/)
        return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Action Succes']);
      else
        return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'id not exist']);
    }
    catch(\Illuminate\Database\QueryException $ex){
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Data type not matching of '.$this->primary]);
    }
  }

  /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 12/08/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <edit>
    * Name: Edit;
    * Description: Edit single record;
    * Action Type: Application;
    * Category: Manage;
    * </Edit>
    */
  public function edit(Request $request){
    if($this->schemaCheck != '')
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $this->schemaCheck]);
    $this->validate($request, [$this->primary => 'required']);
    $ColumnNameArray=$this->fields->fields;
    try{
    //$primaryKeyData=Crypt::decrypt($request->input($this->primary));
        $primaryKeyData=$request->input($this->primary);
    }
    catch(DecryptException $e){
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Faild in decryption','error'=>$e]);
    }
    //$primaryKeyData=$request->input($this->primary);
    //check if row exist in database
    if(DB::table($this->tableName)->where($this->primary, '=', $primaryKeyData)->get() == '[]')
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $this->primary.' not exist']);
    //validate each input;store it into array
    foreach($ColumnNameArray as $columnName){
      if($request->input($columnName['name']) != ''){
          $rule = '';
          if (isset($columnName['rule']))
                $rule = $columnName['rule'];
        $this->validate($request, [$columnName['name'] => $rule]);
        try{
        //$array[''.$columnName['name']]=Crypt::decrypt($request->input($columnName['name']));
            $array[''.$columnName['name']]=$request->input($columnName['name']);
        }
        catch(DecryptException $e){
          return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Faild in decryption','error'=>$e]);
        }
      }
    }
    try{
      DB::table($this->tableName)->where($this->primary, $primaryKeyData)->update($array);
    }
    catch(\Illuminate\Database\QueryException $ex){
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Runtime Exception'.$ex]);
    }
    return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Successfully updated']);
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
  public function statusChange(Request $request){
    if($this->schemaCheck != '')
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $this->schemaCheck]);
    $this->validate($request, [$this->primary => 'required',$this->status => 'required|in:1,2']);
    //$primaryKeyData=$request->input($this->primary);
    try{
    //$primaryKeyData=Crypt::decrypt($request->input($this->primary));
         $primaryKeyData=$request->input($this->primary);
    }
    catch(DecryptException $e){
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Faild in decryption']);
    }
    $statusValue=$request->input($this->status);
    $array[]=$statusValue;
    $array[]=$primaryKeyData;
    //check if the row is exist in database
    if( DB::table($this->tableName)->where($this->primary, '=', $primaryKeyData)->get() == '[]')
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $this->primary.' not exist']);
    //check the current value of status from databse,if it is same as input value return false
     $result=DB::table($this->tableName)->where($this->primary, '=', $primaryKeyData)->get($this->status);
    foreach($result as $re){
      $s=$this->status;
      if($re->$s == $statusValue){
       return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'Status is already '.$statusValue]);
      }
      else{
    //update status field
       DB::update('update '.$this->tableName.' set '.$this->status.'= ? where '.$this->primary.' = ?',$array);
       return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' => 'Status changed ']);
      }
    }
  }

  /*
    * @author Pratheesh
    * @copyright Origami Technologies
    * @created 12/08/2020
    * @license http://www.origamitechnologies.com
    * @aclinfo <insertexcel>
    * Name: insertExcel;
    * Description: insert into table from excel;
    * Action Type: Application;
    * Category: Manage;
    * </excel insert>
    */
  public function insertExcel(Request $request){
    $error=0;
    $errorArray=array();
    if($this->schemaCheck != '')
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => $this->schemaCheck]);
    $this->validate($request, ['file' => 'required']);
    include base_path().'/app/Library/SimpleXLSX.php';
    if ( $xlsx = SimpleXLSX::parse($request->file('file')) == FALSE )
    return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' => 'uploaded document is not valid']);
    $xlsx = SimpleXLSX::parse($request->file('file'));
    list($num_cols, $num_rows) = $xlsx->dimension(0);
    $column=OT_ZERO;
    $foreign=$this->fields->fkeys;
    //create foreign key array
    $array=array();
    $fieldArray=array();
    $fieldLabelArray=array();
    $fieldArray=$this->fields->fields;
    //create column name array
    foreach($fieldArray as $field){
      $fieldLabelArray[]=$field['label'];
      $fieldNameArray[]=$field['name'];
      $rule[]=$field['rule'];
    }
    //check column count
    if(count($fieldLabelArray) != $num_cols ){
       $errorArray[]='column count miss-match';
      $error=1;
    }
    $i = OT_ZERO;
    //fetch excel data
    foreach ($xlsx->rows() as $elt) {
      //stop the itration after fetch all fields
      if($column < $num_rows){
        $count1=OT_ZERO;
        //retrive column heading from excel
        if ($i == 0){
          $i++;
          $count=OT_ZERO;
            while($count < $num_cols){
              if(count($fieldLabelArray) != $num_cols)   return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' =>'column count missmatch']);
              if($fieldLabelArray[$count] != $elt[$count]){
                $errorArray[]='column name miss-match('.$fieldLabelArray[$count].'-'.$elt[$count].')';
                $error=1;
              }
              $count++;
            }
        }
        //start fetching contents from excel
        else{
          $count=OT_ZERO; //pointing cells in excel
            while($count < $num_cols){
              $array[''.$fieldNameArray[$count]]=$elt[$count];
              //validation of input data
              $messages = array('custom' => '',);
              $a=array($elt[$count]);
              $b=array($rule[$count]);
              $validator = Validator::make($a,$b,$messages);
              if ($validator->fails()){
                $errorArray[]='Validation failed at'.$column.'-'.($count+1);
                $error=1;
              }
              $count++;
            }
          $newArray[]=$array;
        }
        $column++;
      }
    }
    //return error if exist else execute insert query
    if($error == 1)
      return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' =>'insertion faild',$errorArray]);
    else{
      try{
        DB::table($this->tableName)->insert($newArray);
      }
      catch(\Illuminate\Database\QueryException $ex){
        return response()->json(['Status' => OT_NO,'version'=>VERSION, 'Feedback' =>$ex.'error in insertion']);
      }
      return response()->json(['Status' => OT_YES,'version'=>VERSION, 'Feedback' =>'Succefully inserted',$newArray]);
    }
  }
 }
