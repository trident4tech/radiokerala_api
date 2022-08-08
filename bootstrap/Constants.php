<?php
use Carbon\Carbon;
define('OT_YES',2);
define('OT_NO',1);
define('ZOHO_KEY','UPjBZmJPf3QOeNfYw9NhJysivIydIV8vFGO311nPLedVym4dM4ExQcw3F2ZoyI3o');
define('OT_ZERO',0);
define('OT_ONE',1);
define('OT_THREE',3);
define('OT_FROM','+918156887144');
define('OT_MAIL_FROM','pratheeshktmz@gmail.com');
define('OT_aMAIL_FROM','pratheeshktmz@gmail.com');
define('TXN_SUCCESS','TXN_SUCCESS');

$distinctName = DB::table('core_constants')->distinct()->where('const_affective_date', '<=', Carbon::now())->get(['const_name']);

foreach($distinctName as $outerLoopData){
    $allData = DB::table('core_constants')->select('*')->where('const_affective_date', '<=', Carbon::now())->where('const_name','=',$outerLoopData->const_name)->get();
    foreach($allData as $innerLoopData){
        $curDate=strtotime(Carbon::now()->toDateString());
        $myDate=strtotime($innerLoopData->const_affective_date);
        if($outerLoopData->const_name == $innerLoopData->const_name &&  $curDate >= $myDate){
            $datetime1 = new DateTime($innerLoopData->const_affective_date);
            $datetime2 = new DateTime(Carbon::now());
            $interval = $datetime1->diff($datetime2);
            $days = $interval->format('%a');//now do whatever you like with $days
            $daysArray[]=$days;
            $constantNameArray[$days]=$innerLoopData->const_name;
            $constantValueArray[$days]=$innerLoopData->const_value;
        }
    }
    if (!defined($constantNameArray[min($daysArray)]))
        define($constantNameArray[min($daysArray)], $constantValueArray[min($daysArray)]);
    $constantNameArray=array();
    $constantValueArray=array();
    $daysArray=array();
}


