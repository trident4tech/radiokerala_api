<?php
namespace App\Http\Middleware;
use Illuminate\Http\Request;
use App\User;
use App\Permission;
use App\Role;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
class Authenticate {
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
    public function __construct(Auth $auth) {
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
    public function handle($request, Closure $next, $guard = null) {

        try{
            $this->auth->guard($guard)->authenticate();
        }
        catch(\Exception $e){
            try{
                $newToken = auth()->refresh(1);
                return response()->json(['Status' => OT_YES, 'Feedback' => 'Token refreshed','Data' => $newToken]);
                //return response()->json(['Token:'=>$newToken]);
            }
            catch(\Exception $e){
                return response()->json(['Status' => OT_NO, 'Feedback' => 'Given token is blacklisted one','Error' => $e]);
                //return response()->json(['given token is black listed']);
            }
        }
        if ($this->auth->guard($guard)->guest()) {
              return response()->json(['Status' => OT_NO, 'Feedback' => 'No valid JWT is found']);

        }






        //return $newToken = auth()->refresh();
        //die;

        return $next($request);
    }
}
