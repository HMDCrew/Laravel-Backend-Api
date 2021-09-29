# laravel

    composer create-project — prefer-dist laravel/*<name-of-app>*
    cd <name-of-app>

    composer require laravel/passport



    ## Edit and update: 
    /database/migrations/timestamp_create_users_table.php


*/timestamp_create_users_table.php*
```php
public function up() {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
}
```
    


    php artisan migrate
    php artisan passport:install



    ## Edit and update:
    /app/Models/User.php


*User.php*
```php
use Laravel\Passport\HasApiTokens;
class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;
    …
}
```

    ## Edit and update:
    /app/Providers/AuthServiceProvider.php


*AuthServiceProvider.php*
```php
namespace App\Providers;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
class AuthServiceProvider extends ServiceProvider {
    ...
    public function boot() {
        $this->registerPolicies();
        if (! $this->app->routesAreCached()) {
            Passport::routes();
            Passport::tokensExpireIn(now()->addDays(1));
        }
    }
}
```
    
    
    ## Edit and update:
    /config/auth.php
    
    
*auth.php*
```php
return [
    …
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],
    …
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
            'hash' => false
        ],
    ],
    …
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ]
    …
];
```

    php artisan make:middleware CORSMiddleware


    ## Edit and update:
    /app/Http/Middleware/CORSMiddleware.php


*CORSMiddleware.php*
```php
*CORSMiddleware.php*
…
class CORSMiddleware {
    public function handle( Request $request, Closure $next ) {

        //Intercepts OPTIONS requests
        if($request->isMethod('OPTIONS')) {
            $response = \response('', 200);

        } else {
            //Pass the request to the next middleware
            $response = $next( $request );
        }

        $response->header('Access-Control-Allow-Origin',"*");
        $response->header('Access-Control-Allow-Methods','PUT, GET, POST, DELETE, OPTIONS, PATCH');
        $response->header('Access-Control-Allow-Headers',$request->header('Access-Control-Request-Headers'));
        $response->header('Access-Control-Allow-Credentials','true');
        $response->header('Accept','application/json');
        $response->header('Access-Control-Expose-Headers','location');

        return $response;
    }
}
```
   
   
    ## Edit and update:
    /app/Http/Kernel.php


*Kernel.php*
```php
namespace App\Http;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
class Kernel extends HttpKernel {
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        ...
        \App\Http\Middleware\CORSMiddleware::class,
    ];
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];
    protected $routeMiddleware = [
        ...
        'CORS' => \App\Http\Middleware\CORSMiddleware::class,
    ];
}
```

    
    ## Edit and update:
    /routes/api.php


*api.php*
```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'users', 'middleware' => 'CORS'], function ($router) {
    Route::post('/register', [UserController::class, 'register'])->name('register.user');
    Route::post('/login', [UserController::class, 'login'])->name('login.user');
    Route::get('/view-profile', [UserController::class, 'viewProfile'])->name('profile.user');
    Route::get('/logout', [UserController::class, 'logout'])->name('logout.user');
}); 
```
    
    
    ## Edit and update:
    /app/Http/Controllers/Controller.php


*Controller.php*
```php
namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function respondWithToken($token, $responseMessage, $data){
        return \response()->json([
            "success" => true,
            "message" => $responseMessage,
            "data" => $data,
            "token" => $token,
            "token_type" => "bearer",
        ],200);
    }
}
```    
    
    
    php artisan make:controller UserController


    ## Edit and update:
    /app/Http/Controllers/UserController.php


*UserController.php*
```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller {

    protected $user;

    public function __construct() {
        $this->middleware("auth:api", ["except" => ["login","register"]]);
        $this->user = new User;
    }

    public function register(Request $request) {

        $validator = Validator::make( $request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        if( $validator->fails() ) {

            return response()->json([
                'success' => false,
                'message' => $validator->messages()->toArray()
            ], 500);
        }

        $data = [
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make( $request->password )
        ];


        $this->user->create( $data );
        $responseMessage = "Registration Successful";

        // $user_id = $this->user::orderBy('id', 'desc')->first()->id;

        return response()->json([
            'success' => true,
            'message' => $responseMessage
        ], 200);
    }

    public function login( Request $request ) {

        $validator = Validator::make( $request->all(), [
            'email' => 'required|string',
            'password' => 'required|min:6',
        ]);

        if( $validator->fails() ) {

            return response()->json([
                'success' => false,
                'message' => $validator->messages()->toArray()
            ], 500);
        }

        $credentials = $request->only( ["email","password"] );
        $user = User::where( 'email', $credentials['email'] )->first();

        if( $user ) {
            if( !auth()->attempt( $credentials ) ) {
                $responseMessage = "Invalid username or password";
                return response()->json([
                    "success" => false,
                    "message" => $responseMessage,
                    "error" => $responseMessage
                ], 422);
            }

            $accessToken = auth()->user()->createToken('authToken')->accessToken;
            $responseMessage = "Login Successful";

            return $this->respondWithToken( $accessToken, $responseMessage, auth()->user() );
        } else {
            $responseMessage = "Sorry, this user does not exist";
            return response()->json([
                "success" => false,
                "message" => $responseMessage,
                "error" => $responseMessage
            ], 422);
        }
    }

    public function viewProfile() {

        $responseMessage = "user profile";
        $data = Auth::guard("api")->user();

        return response()->json([
            "success" => true,
            "message" => $responseMessage,
            "data" => $data
        ], 200);
    }


    public function logout() {

        $user = Auth::guard("api")->user()->token();
        $user->revoke();
        $responseMessage = "successfully logged out";

        return response()->json([
            'success' => true,
            'message' => $responseMessage
        ], 200);
    }
}
```


    
Postman Registration Api
![alt text](https://cloud.nis.md/s/4PiyLn7jGSTWizP/preview)

Postman Login Api
![alt text](https://cloud.nis.md/s/4PiyLn7jGSTWizP/preview)

Postman View Profile Api
![alt text](https://cloud.nis.md/s/mFoRg8qD8WxjMPo/preview)
