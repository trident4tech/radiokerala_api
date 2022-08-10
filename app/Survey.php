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

class Survey extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $table = 'survey';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['sr_date','sr_name','sr_mob','sr_file_id','ip_created','u_createdby','sr_quality','sr_lat','sr_lng','sr_type','sr_accuracy','sr_usr_id'       
    ];
    protected $primaryKey = 'sr_id';
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
