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

class Weatheralert extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $table = 'weather_alert';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['wa_date','wa_description','wa_is_general','wa_file_id','ip_created','u_createdby'       
    ];
    protected $primaryKey = 'wa_id';
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

}
