<?php
namespace App;
//namespace Amir\Permission\Models;

//use Illuminate\Database\Eloquent\Model;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Ticketprint extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

        /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ticket_print';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tp_rate','tp_attr_id','tp_content','tp_time','tp_number','u_createdby','ip_created','tp_usr_id','tp_actual_number','tp_prefix','tp_date','tp_gno','tp_data','tp_is_offline','tp_dest_id','tp_counter_id','tp_pay_mode','tp_classdata','ua_created','tp_cgst_data','tp_sgst_data'
    ];
    protected $primaryKey = 'tp_id';
   // protected $password = 'usr_pass';
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        
    ];

 /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
     /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * //@return array
     */
   


}
