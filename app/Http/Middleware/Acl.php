<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use App\User;
use App\Permission;
use App\Role;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use DB;

class Acl
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;
    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $user = new User;
        $permission = new Permission;
        $role = new Role;
        /*
        if (!$request->session()->has('roleid')) {
           return response()->json(['Status' => OT_NO, 'Feedback' => 'You have No ACL Privilage']);
        }
        */
        if ($request->input('roleid') == null) {
            return response()->json(['Status' => OT_NO, 'Feedback' => 'You have No ACL Privilage']);
        } else {
            //$session = $request->session(); //create session variable
            $role_id = $request->input('roleid'); //$session->get('roleid');
            $url = substr(strchr($request->Url(), '.com'), 5);

            if ($request->input('userid') != NULL) {
                $results = DB::select(
                    "
                                        SELECT * from permission_user
                                        join permissions on permissions.id = permission_user.pu_permission_id AND permission_user.deleted_at IS NULL
                                        WHERE permissions.Url ILIKE '" . $url . "' AND permission_user.deleted_at IS NULL AND
                                        permission_user.pu_usr_id=" . $request->input('userid')
                );
                
                $isAccess = OT_ZERO;
                foreach ($results as $result) {
                    if ($result->pu_status == OT_YES) {
                        $isAccess = OT_YES;
                        break;
                    }

                    if ($result->pu_status == OT_NO) {
                        $isAccess = OT_NO;
                        break;
                    }
                }

                if ($isAccess == OT_YES) {
                    return $next($request);
                }
                if ($isAccess == OT_NO) {
                    return response()->json(['Status' => 404, 'Feedback' => 'You have No ACL Privilage..!']);
                }
            }

            $results = DB::select(
                "
                                    SELECT * from permission_role
                                    join permissions on permissions.id = permission_role.permission_id 
                                    AND permission_role.deleted_at IS NULL
                                    WHERE permissions.Url ILIKE '" . $url . "'
                                    AND permission_role.deleted_at IS NULL AND  permission_role.role_id=" . $request->input('roleid')
            );
            foreach ($results as $user) {
                /*
                $incomeUrl = preg_replace('/[^A-Za-z0-9\-]/', '', $request->Url());
                $storedUrl = preg_replace('/[^A-Za-z0-9\-]/', '', url($user->Url));
                if ($incomeUrl == $storedUrl) return $next($request);
                */
                return $next($request);
            }
            return response()->json(['Status' => 404, 'Feedback' => 'You have No ACL Privilage..!']);
        }
    }
}
