<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use LicenseBoxExternalAPI;
use Illuminate\Support\Facades\Redirect;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Models\Language;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Contracts\Session\Session;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Http;

use function GuzzleHttp\Promise\all;

class LicenseController extends Controller
{
    use AuthenticatesUsers;
    public function saveEnvData(Request $request)
    {
        $data['DB_HOST'] = $request->db_host;
        $data['DB_DATABASE'] = $request->db_name;
        $data['DB_USERNAME'] = $request->db_user;
        $data['DB_PASSWORD'] = $request->db_pass;

        $envFile = app()->environmentFilePath();
        if ($envFile) {
            $str = file_get_contents($envFile);
            if (count($data) > 0) {
                foreach ($data as $envKey => $envValue) {
                    $str .= "\n"; // In case the searched variable is in the last line without \n
                    $keyPosition = strpos($str, "{$envKey}=");
                    $endOfLinePosition = strpos($str, "\n", $keyPosition);
                    $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
                    // If key does not exist, add it
                    if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                        $str .= "{$envKey}=\"{$envValue}\"\n";
                    } else {
                        $str = str_replace($oldLine, "{$envKey}=\"{$envValue}\"", $str);
                    }
                }
            }
            $str = substr($str, 0, -1);

            try {
                if (file_put_contents($envFile, $str)) {
                    return response()->json(['data' => null, 'success' => true], 200);
                };
            } catch (Exception $e) {
                return response()->json(['data' => $e->getMessage(), 'success' => false], 200);
            }
        }
    }

    public function saveAdminData(Request $request)
    {
        $data = $request->all();
        try {
            $user = User::create($data);
            $user->password =  Hash::make($request->password);
            $user->assignRole('admin');
            $user->update();
            Setting::find(1)->update(['license_status' => 1, 'license_key' => $request->license_code, 'license_name' => $request->client_name]);
            return response()->json(['data' => url('login'), 'success' => true], 200);
        } catch (Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'success' => false], 200);
        }
    }

    public function installer()
    {
        return view("installer");
    }

    public function adminLogin(Request $request)
    {
        // check post request or get request
        if ($request->isMethod('get')) {
            return redirect('/login');
        }
        $request->validate([
            'email' => 'bail|required|email',
            'password' => 'bail|required',
            'g-recaptcha-response' => 'required',
        ]);
    
        $recaptchaResponse = $request->input('g-recaptcha-response');
        $secretKey = env('RECAPTCHA_SECRET_KEY');
        $response = Http::withOptions(['verify' => false])->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
        ]);
    
        $responseBody = json_decode($response->getBody());
        if (!$responseBody->success) {
            return Redirect::back()->withErrors(['g-recaptcha-response' => 'ReCAPTCHA verification failed.']);
        }
    
        $userdata = [
            'email' => $request->email,
            'password' => $request->password,
        ];
        $remember = $request->get('remember');
    
        if (Auth::attempt($userdata, $remember)) {
            $user = Auth::user();
    
            if ($user->hasRole('admin')) {
                return redirect()->intended('admin/home');
            } 
            elseif ($user->hasRole('Organizer')) {
                if ($user->status == 1) {
                    $this->setLanguage($user);
                    if (!$user->onboarding_completed_at) {
                        return redirect()->route('users.onboarding');
                    }
                    return redirect('organization-home');
                } else {
                    Auth::logout();
                    return Redirect::back()->with('error_msg', 'Only authorized person can login.');
                }
            } 
            elseif ($user->hasRole('scanner')) {
                if ($user->status == 1) {
                    return redirect('scanner-home');
                } else {
                    Auth::logout();
                    return Redirect::back()->with('error_msg', 'Only authorized person can login.');
                }
            }
            elseif ($user->hasRole('Manager')) {
                if ($user->status == 1) {
                    $this->setLanguage($user);
                    if ($user->hasManagerPermission('dashboard_access')) {
                        return redirect('manager-home');
                    } elseif ($user->hasManagerPermission('order_view')) {
                        return redirect('orders');
                    } elseif ($user->hasManagerPermission('order_create')) {
                        return redirect('orders-create-for-user');
                    } elseif ($user->hasManagerPermission('scanner_create')) {
                        return redirect('scanner');
                    } elseif ($user->hasManagerPermission('ticket_verify')) {
                        return redirect('organizer/ticket-verification');
                    } elseif ($user->hasManagerPermission('revenue_view')) {
                        return redirect('organization-income');
                    } else {
                        return redirect('manager-home');
                    }
                } else {
                    Auth::logout();
                    return Redirect::back()->with('error_msg', 'Only authorized person can login.');
                }
            }
            else {
                Auth::logout();
                return Redirect::back()->with('error_msg', 'Only authorized person can login.');
            }
        } else {
            return Redirect::back()->with('error_msg', 'Invalid Username or Password');
        }
    }

    public function adminLogout(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();
            return redirect('/login');
        }
    }
    public function licenseSetting()
    {
        $setting = Setting::find(1);
        return view('admin.license', compact('setting'));
    }
    public function saveLicenseSetting(Request $request)
    {

        $request->validate([
            'license_key' => 'bail|required',
            'license_name' => 'bail|required',
        ]);

        $api = new LicenseBoxExternalAPI();
        $result =  $api->activate_license($request->license_key, $request->license_name);
        if ($result['status'] === true) {
            Setting::find(1)->update(['license_status' => 1, 'license_key' => $request->license_key, 'license_name' => $request->license_name]);
            return redirect('admin/home');
        } else {
            Setting::find(1)->update(['license_status' => 0]);
            return redirect()->back()->withStatus(__($result['message']));
        }
    }
    public function setLanguage($user)
    {
        $name = $user->language;
        if (!$name) {
            $name = 'English';
        }
        App::setLocale($name);
        session()->put('locale', $name);
        $direction = Language::where('name', $name)->first()->direction;
        session()->put('direction', $direction);
        return true;
    }
    public function loginAsOrganizer($id)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect()->back();  
        }
        Auth::logout();
        Auth::login($user);
        return redirect('organization-home');
    }
    public function loginAsAppuser($id)
    {
        $user = AppUser::find($id);
        if (!$user) {
            return redirect()->back();  
        }
        
        Auth::guard('appuser')->login($user);
        return redirect('/');
    }
}
