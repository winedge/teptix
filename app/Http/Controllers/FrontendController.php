<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Exception;
use App\Models\Event;
use App\Models\User;
use App\Models\Review;
use App\Models\Ticket;
use App\Models\Coupon;
use App\Models\Tax;
use App\Models\OrderTax;
use App\Models\AppUser;
use App\Models\Category;
use App\Models\Blog;
use App\Models\Faq;
use Twilio\Rest\Client;
use App\Models\Order;
use App\Models\Setting;
use App\Models\PaymentSetting;
use App\Models\NotificationTemplate;
use App\Models\EventReport;
use App\Models\OrderChild;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use OneSignal;
use Twilio\Rest\Client as Clients;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Rave;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use App\Mail\ResetPassword;
use App\Mail\TicketBook;
use App\Mail\TicketBookOrg;
use App\Models\Language;
use App\Http\Controllers\FaqController;
use App\Models\Banner;
use App\Models\ContactUs;
use App\Models\Country;
use App\Models\CouponUsageHistory;
use App\Models\Module;
use App\Models\OrganizerPaymentKeys;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Spatie\Permission\Traits\HasRoles;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\SEOTools;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\JsonLd;
use FontLib\Table\Type\name;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Guard;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Modules\Seatmap\Entities\Rows;
use Modules\Seatmap\Entities\SeatMaps;
use Modules\Seatmap\Entities\Seats;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Throwable;
use Vonage\Client as VonageClient;
use Vonage\SMS\Message\SMS;
use Vonage\SMS\Message\SMSCollection;
use Illuminate\Support\Facades\Cache; // Import the Cache facade
use App\Models\GuestUser;
use App\Models\GuestOtp;
use App\Models\StripeTransaction;
use App\Models\SeatTable;
use App\Models\EventVenueRow;
use App\Models\EventVenueSection;
use App\Models\EventVenueSeat;
use Laravel\Socialite\Facades\Socialite;
use App\Services\CheckoutServices;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class FrontendController extends Controller
{
    protected $checkoutServices;
    public function __construct(CheckoutServices $checkoutServices)
    {
        $this->checkoutServices = $checkoutServices;
        if (env('DB_DATABASE') != null) {
            (new AppHelper)->mailConfig();
            (new AppHelper)->eventStatusChange();
        }
    }
     public function scannerPrivacy(Request $request){
        return view('frontend.scanner-privacy');
    }
    public function home()
    {
        if (env('DB_DATABASE') == null) {
            return view('admin.frontpage');
        } else {
            $setting = Setting::first(['app_name', 'logo']);

            SEOMeta::setTitle($setting->app_name . ' - Home' ?? env('APP_NAME'))
                ->setDescription('Discover and buy tickets to unforgettable events or unleash your event organizer potential with The Event Palette. Whether youre a fan seeking experiences or a creator aiming to showcase, join our vibrant community and transform how events are discovered and enjoyed.')
                ->setCanonical(url()->current())
                ->addKeyword(['home page', $setting->app_name, $setting->app_name . ' Home']);

            OpenGraph::setTitle($setting->app_name . ' - Home' ?? env('APP_NAME'))
                ->setDescription('Discover and buy tickets to unforgettable events or unleash your event organizer potential with The Event Palette. Whether youre a fan seeking experiences or a creator aiming to showcase, join our vibrant community and transform how events are discovered and enjoyed.')
                ->setUrl(url()->current());

            JsonLdMulti::setTitle($setting->app_name . ' - Home' ?? env('APP_NAME'));
            JsonLdMulti::setDescription('Discover and buy tickets to unforgettable events or unleash your event organizer potential with The Event Palette. Whether youre a fan seeking experiences or a creator aiming to showcase, join our vibrant community and transform how events are discovered and enjoyed.');
            JsonLdMulti::addImage($setting->imagePath . $setting->logo);

            SEOTools::setTitle($setting->app_name . ' - Home' ?? env('APP_NAME'));
            SEOTools::setDescription('Discover and buy tickets to unforgettable events or unleash your event organizer potential with The Event Palette. Whether youre a fan seeking experiences or a creator aiming to showcase, join our vibrant community and transform how events are discovered and enjoyed.');
            SEOTools::opengraph()->setUrl(url()->current());
            SEOTools::setCanonical(url()->current());
            SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);


            $timezone = Setting::find(1)->timezone;
            $date = Carbon::now($timezone);
            $events  = Event::with(['category:id,name'])
                ->where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d H:i:s')]])
                ->orderBy('start_time', 'desc')->get();
            $now = Carbon::now();

            $pastevents  = Event::with(['category:id,name'])
                ->where('status', 1)
                ->where('is_deleted', 0)
                ->where('event_status', 'Pending')
                ->where('end_time', '<=', $now)
                ->orderBy('start_time', 'desc')
                ->get();
            $organizer = User::role('Organizer')->orderBy('id', 'DESC')->get();
            $category = Category::where('status', 1)->orderBy('id', 'DESC')->get();
            $categoryEventCounts = Event::where([['status', 1], ['is_deleted', 0]])
                ->selectRaw('category_id, count(*) as total')
                ->groupBy('category_id')
                ->pluck('total', 'category_id');
            $blog = Blog::with(['category:id,name'])->where('status', 1)->orderBy('id', 'DESC')->get();
            foreach ($events as $value) {
                $value->total_ticket = Ticket::where([['event_id', $value->id], ['is_deleted', 0], ['status', 1]])->sum('quantity');
                $value->sold_ticket = Order::where('event_id', $value->id)->sum('quantity');
                $value->available_ticket = $value->total_ticket - $value->sold_ticket;
            }
            // Prefer an admin-marked featured event; fall back to the soonest upcoming event.
            $featuredEvent = $events->where('is_featured', 1)->sortBy('start_time')->first()
                ?? $events->sortBy('start_time')->first();
            $banner = Banner::with('event')
                ->where('status', 1)
                ->orderByRaw('display_order IS NULL, display_order ASC')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();
            $user = Auth::guard('appuser')->user();
            $showLinkBanner = Setting::find(1,['show_link_banner','googleplay_link','appstore_link']);
            return view('frontend.home', compact('events','pastevents', 'organizer', 'category', 'categoryEventCounts', 'blog', 'banner', 'user','showLinkBanner', 'featuredEvent'));
        }
    }
    public function login()
    {
        if (Auth::guard('appuser')->check() || Auth::check()) {
            return redirect()->back();
        }
        $setting = Setting::first(['app_name', 'logo']);
        SEOMeta::setTitle($setting->app_name . ' - Login' ?? env('APP_NAME'))
            ->setDescription('This is login page')
            ->setCanonical(url()->current())
            ->addKeyword(['login page', $setting->app_name, $setting->app_name . ' Login', 'sign-in page', $setting->app_name . ' sign-in']);

        OpenGraph::setTitle($setting->app_name . ' - Login' ?? env('APP_NAME'))
            ->setDescription('This is login page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - Login' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is login page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - Login' ?? env('APP_NAME'));
        SEOTools::setDescription('This is login page');
        SEOTools::opengraph()->addProperty(
            'keywords',
            [
                'login page', $setting->app_name, $setting->app_name . ' Login',
                'sign-in page', $setting->app_name . ' sign-in'
            ]
        );
        SEOTools::opengraph()->addProperty('image', $setting->imagePath . $setting->logo);
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        return view('frontend.auth.login');
    }
    public function redirectToGoogle(Request $request)
    {
        session(['login_type' => $request->type??0]);
        return Socialite::driver('google')->redirect();
    }


    public function handleGoogleCallback(Request $request)
    {
        $googleUser = Socialite::driver('google')->stateless()->user();
        $type = session('login_type');
        if ($type == 'user') {
            $user = AppUser::where('email', $googleUser->email)->first();
        }else{
            $user = User::where('email', $googleUser->email)->first();
        }
        if(!isset($user->id)){
            $data['password'] = Hash::make('123456');
            $data['email'] = $googleUser->email;
            $data['image'] = "defaultuser.png";
            $data['status'] = 1;
            $data['provider'] = "LOCAL";
            $data['language'] = Setting::first()->language;
            $data['is_verify'] = 1;
            if ($type == 'user') {
                $data['name'] = $googleUser->user['given_name']??"";
                $user = AppUser::create($data);
            }else{
                $data['first_name'] = $googleUser->user['given_name']??"";
                $user = User::create($data);
                $user->assignRole('Organizer');
                OrganizerPaymentKeys::create([
                    'organizer_id' => $user->id,
                ]);
            }
        }
        if ($type == 'user') {
            $previousSessionId = $request->session()->getId();

            if (Auth::guard('appuser')->loginUsingId($user->id)) {
                $user =  Auth::guard('appuser')->user();
                $setting = Setting::first(['app_name', 'logo']);
                if ($user->status == 0) {
                    return redirect('user/login')->with('error_msg', 'Blocked By Admin.');
                }
                $this->syncCheckoutVenueSeatHoldsAfterLogin($previousSessionId);
                if (!$setting->user_verify) {
                    if (Session::has('multiticketssession')) {
                        return redirect('checkout');
                    }
                    return redirect()->intended('/');
                } else {
                    if (!$user->is_verify) {
                        $details = [
                            'id' => $user->id,
                        ];
                        Mail::to($user->email)->send(new \App\Mail\VerifyMail($details));
                        return redirect('user/login')->with(['success' => "Verification link has been sent to your email. Please visit that link to complete the verification"]);
                    }
                }
                $this->setLanguage($user);
            } else {
                return Redirect::back()->with('error_msg', 'Invalid Username or Password.');
            }
        }else{
            if (Auth::loginUsingId($user->id)) {
                // Check if account is deleted
                if (Auth::user()->deleted_softaccount !== null || Auth::user()->trashed()) {
                    Auth::logout();
                    return Redirect::back()->with('error_msg', 'This account has been deleted and cannot be accessed.');
                }
                if (Auth::user()->hasRole('Organizer')) {
                    if (Auth::user()->status == 1) {
                        $this->setLanguage(Auth::user());
                        return redirect()->intended('organization-home');
                    } else {
                        return "hello";
                        return Redirect::back();
                    }
                } else {
                    Auth::logout();
                    return Redirect::back()->with('error_msg', 'Only authorized person can login.');
                }
            } else {
                return Redirect::back()->with('error_msg', 'Invalid Username or Password.');
            }
        }
    }
    public function userLogin(Request $request)
    {
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

        // on server hide above code and unhide below code

        // $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
        //     'secret' => $secretKey,
        //     'response' => $recaptchaResponse,
        // ]);

        $responseBody = json_decode($response->getBody());
        if (!$responseBody->success) {
            return Redirect::back()->withErrors(['g-recaptcha-response' => 'ReCAPTCHA verification failed.']);
        }

        $userdata = array(
            'email' => $request->email,
            'password' => $request->password,
        );
        $remember = $request->get('remember');
        if ($request->type == 'user') {
            $previousSessionId = $request->session()->getId();

            if (Auth::guard('appuser')->attempt($userdata, $remember)) {
                $user =  Auth::guard('appuser')->user();
                $setting = Setting::first();
                if ($user->status == 0) {
                    Auth::guard('appuser')->logout();
                    return redirect('user/login')->with('error_msg', 'Blocked By Admin.');
                }
                if ($setting && $setting->user_verify == 1 && !$user->is_verify) {
                    Auth::guard('appuser')->logout();
                    return redirect('user/login')->with('error_msg', 'Please verify your email address first. Check your inbox and click the confirmation link before logging in.');
                }
                if ($user->is_verify && $user->email_verified_at === null) {
                    \App\Models\AppUser::where('id', $user->id)->update(['email_verified_at' => \Carbon\Carbon::now()]);
                }
                $this->syncCheckoutVenueSeatHoldsAfterLogin($previousSessionId);
                if (Session::has('multiticketssession')) {
                    return redirect('checkout');
                }
                $this->setLanguage($user);
                return redirect()->intended('/');
            } else {
                return Redirect::back()->with('error_msg', 'Invalid Username or Password.');
            }
        }
        if ($request->type == 'org') {
            if (Auth::attempt($userdata, $remember)) {
                // Check if account is deleted
                if (Auth::user()->deleted_softaccount !== null || Auth::user()->trashed()) {
                    Auth::logout();
                    return Redirect::back()->with('error_msg', 'This account has been deleted and cannot be accessed.');
                }
                if (Auth::user()->hasRole('Organizer')) {
                    if (Auth::user()->status == 1) {
                        if (Auth::user()->email_verified_at === null) {
                            Auth::logout();
                            return redirect('user/login')->with('error_msg', 'Please verify your email address first. Check your inbox and click the confirmation link before logging in.');
                        }
                        $this->setLanguage(Auth::user());
                        return redirect()->intended('organization-home');
                    } else {
                        Auth::logout();
                        return Redirect::back()->with('error_msg', 'Blocked By Admin.');
                    }
                } else {
                    Auth::logout();
                    return Redirect::back()->with('error_msg', 'Only authorized person can login.');
                }
            } else {
                return Redirect::back()->with('error_msg', 'Invalid Username or Password.');
            }
        }
    }

    public function userLogout(Request $request)
    {
        if (Auth::guard('appuser')->check()) {
            Auth::guard('appuser')->logout();
            return redirect('/user/login');
        }
    }
    public function register()
    {
        if (Auth::guard('appuser')->check() || Auth::check()) {
            return redirect()->back();
        }
        $setting = Setting::first(['app_name', 'logo']);

        SEOMeta::setTitle($setting->app_name . ' - Register' ?? env('APP_NAME'))
            ->setDescription('This is register page')
            ->setCanonical(url()->current())
            ->addKeyword([
                'register page', $setting->app_name, $setting->app_name . ' Register',
                'sign-up page', $setting->app_name . ' sign-up'
            ]);

        OpenGraph::setTitle($setting->app_name . ' - Register' ?? env('APP_NAME'))
            ->setDescription('This is register page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - Register' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is register page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - Register' ?? env('APP_NAME'));
        SEOTools::setDescription('This is register page');
        SEOTools::opengraph()->addProperty(
            'keywords',
            [
                'register page', $setting->app_name,
                $setting->app_name . ' Register',
                'sign-up page', $setting->app_name . ' sign-up'
            ]
        );
        SEOTools::opengraph()->addProperty('image', $setting->imagePath . $setting->logo);
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        $logo = Setting::find(1)->logo;
        $phone = Country::get();
        return view('frontend.auth.register', compact('logo', 'phone'));
    }

    public function userRegister(Request $request)
    {
        $existingDeniedUser = \App\Models\User::where('email', $request->email)->where('is_verify', 2)->first();
        if ($existingDeniedUser) {
            return redirect()->back()->withInput()->withErrors(['email' => __('your mail is rejected so you can create new mail id')]);
        }

        $request->validate([
            'first_name' => 'bail|required',
            'last_name' => 'bail|required',
            'email' => 'bail|required|email|unique:app_user|unique:users',
            'phone' => 'bail|required|numeric',
            'password' => 'bail|required|min:6',
            'Countrycode' => 'bail|required',
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
        $verify = Setting::first()->user_verify == 1 ? 0 : 1;
        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        $data['image'] = "defaultuser.png";
        $data['status'] = 1;
        $data['provider'] = "LOCAL";
        $data['language'] = Setting::first()->language;
        $data['phone'] = "+" . $request->Countrycode . $request->phone;
        if ($data['user_type'] == 'organizer') {
            $data['first_name'] = $request->first_name;
            $data['is_verify'] = 0;
            $data['onboarding_completed_at'] = null;
            $user = User::create($data);
            $user->assignRole('Organizer');
            OrganizerPaymentKeys::create([
                'organizer_id' => $user->id,
            ]);

            Log::info('Organizer registration completed (user/register)', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            try {
                Log::info('Sending organizer welcome email (user/register)', ['email' => $user->email]);
                Mail::to($user->email)->send(new \App\Mail\OrganizerWelcome($user));
                Log::info('Organizer welcome email sent successfully (user/register)', ['email' => $user->email]);
            } catch (\Exception $e) {
                Log::error('Organizer welcome email failed (user/register)', [
                    'email'  => $user->email,
                    'error'  => $e->getMessage(),
                    'trace'  => $e->getTraceAsString(),
                ]);
            }
        } else {
            $data['name'] = $request->first_name;
            $data['is_verify'] = $verify;
            $user = AppUser::create($data);
        }
        if ($user->is_verify == 0) {

            if (Setting::first()->verify_by == 'email' && Setting::first()->mail_host != NULL) {
                if ($data['user_type'] == 'organizer') {
                    $details = [
                        'url' => url('organizer/VerificationConfirm/' .  $user->id)
                    ];
                } else {
                    $details = [
                        'url' => url('user/VerificationConfirm/' .  $user->id)
                    ];
                }
                Mail::to($user->email)->send(new \App\Mail\VerifyMail($details));
                return redirect('user/login')->with(['success' => "Verification link has been sent to your email. Please visit that link to complete the verification"]);
            }
            if (Setting::first()->verify_by == 'phone') {

                $setting = Setting::first();
                $otp = rand(100000, 999999);
                $to = $user->phone;
                $message = "Your phone verification code is $otp for $setting->app_name.";
                if ($setting->enable_twillio == 1) {
                    $twilio_sid = $setting->twilio_account_id;
                    $twilio_token = $setting->twilio_auth_token;
                    $twilio_phone_number = $setting->twilio_phone_number;
                    try {
                        $twilio = new Clients($twilio_sid, $twilio_token);
                        $twilio->messages->create(
                            $to,
                            [
                                'from' => $twilio_phone_number,
                                'body' => $message,
                            ]
                        );
                    } catch (\Throwable $th) {
                        return redirect()->back()->with('error', 'Somthing Went Wrong');
                    }
                }
                if ($setting->enable_vonage == 1) {
                    $apiKey = $setting->vonege_api_key;
                    $apiSecret = $setting->vonage_account_secret;
                    $virtualNumber = $setting->vonage_sender_number;
                    $response = Http::post('https://rest.nexmo.com/sms/json', [
                        'api_key' => $apiKey,
                        'api_secret' => $apiSecret,
                        'to' => $to,
                        'from' => $virtualNumber,
                        'text' => $message,
                    ]);
                }
                if ($data['user_type'] == 'organizer') {
                    $user = User::find($user->id);
                    $user->otp = $otp;
                    $user->update();
                    return redirect('organizer/otp-verify/' . $user->id)->with(['success' => "Phone verification code sent via SMS."]);
                } else {
                    $user = AppUser::find($user->id);
                    $user->otp = $otp;
                    $user->update();
                    return redirect('user/otp-verify/' . $user->id)->with(['success' => "Phone verification code sent via SMS."]);
                }
            }
        }
        return redirect('user/login')->with(['success' => "Congratulations! Your account registration was successful. "]);
    }

    public function LoginByMail($id)
    {
        $user = AppUser::find($id);
        $previousSessionId = request()->session()->getId();

        if ($user && Auth::guard('appuser')->loginUsingId($id)) {
            $user = Auth::guard('appuser')->user();
            $verify = AppUser::find($user->id);
            $verify->email_verified_at = Carbon::now();
            $verify->is_verify = 1;
            $verify->update();
            $this->syncCheckoutVenueSeatHoldsAfterLogin($previousSessionId);
            $this->setLanguage($user);
            return redirect()->route('users.onboarding');
        }
        return redirect('user/login')->with('error_msg', 'Invalid or expired verification link.');
    }

    public function LoginByMailOrganizer($id)
    {
        $user = User::find($id);
        if ($user && Auth::loginUsingId($id)) {
            $user = Auth::user();
            $verify = User::find($user->id);
            $verify->email_verified_at = Carbon::now();
            // NOTE: is_verify is intentionally NOT set here.
            // Organizer account approval (is_verify = 1) must be done manually by Admin.
            $verify->update();
            $this->setLanguage($user);
            return redirect()->route('users.onboarding');
        }
        return redirect('user/login')->with('error_msg', 'Invalid or expired verification link.');
    }

    public function resetPassword()
    {
        $setting = Setting::first(['app_name', 'logo']);
        SEOMeta::setTitle($setting->app_name . ' - reset password' ?? env('APP_NAME'))
            ->setDescription('This is reset password page')
            ->setCanonical(url()->current())
            ->addKeyword([
                'reset password page', $setting->app_name, $setting->app_name . ' reset password',
                'forgot password page', $setting->app_name . ' forgot password'
            ]);

        OpenGraph::setTitle($setting->app_name . ' - reset password' ?? env('APP_NAME'))
            ->setDescription('This is reset password page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - reset password' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is reset password page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - reset password' ?? env('APP_NAME'));
        SEOTools::setDescription('This is reset password page');
        SEOTools::opengraph()->addProperty(
            'keywords',
            [
                'reset password page', $setting->app_name,
                $setting->app_name . ' reset password',
                'forgot password page', $setting->app_name . ' forgot password'
            ]
        );
        SEOTools::opengraph()->addProperty('image', $setting->imagePath . $setting->logo);
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        return view('frontend.auth.resetPassword');
    }

    public function userResetPassword(Request $request)
    {
        $request->validate([
            'email' => 'bail|required|email',
            'g-recaptcha-response' => 'required',
        ]);

        $recaptchaResponse = $request->input('g-recaptcha-response');
        $secretKey = env('RECAPTCHA_SECRET_KEY');
        $response = Http::withOptions(['verify' => false])->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
        ]);

        // on server hide above code and unhide below code

        // $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
        //     'secret' => $secretKey,
        //     'response' => $recaptchaResponse,
        // ]);

        $responseBody = json_decode($response->getBody());
        if (!$responseBody->success) {
            return Redirect::back()->withErrors(['g-recaptcha-response' => 'ReCAPTCHA verification failed.']);
        }

        if ($request->type == 'user') {
            $user = AppUser::where('email', $request->email)->first();
        } else {
            $user = User::where('email', $request->email)->first();
        }
        $password = rand(100000, 999999);
        if ($user) {
            $content = NotificationTemplate::where('title', 'Reset Password')->first()->mail_content;
            $detail['user_name'] = $user->name;
            $detail['password'] = $password;
            $detail['app_name'] = Setting::find(1)->app_name;
            if ($request->type == 'user') {
                AppUser::find($user->id)->update(['password' => Hash::make($password)]);
            } else {
                User::find($user->id)->update(['password' => Hash::make($password)]);
            }
            try {

                Mail::to($user->email)->send(new ResetPassword($content, $detail));
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', $th->getMessage());
            }
            return redirect()->route('user.login')->with('success', 'New password will send in your mail, please check it.');
        } else {
            return Redirect::back()->with('error', 'Invalid Email Id, Please try another.');
        }
    }

    public function orgRegister()
    {
        $setting = Setting::first(['app_name', 'logo']);

        SEOMeta::setTitle($setting->app_name . ' - Organizer Register' ?? env('APP_NAME'))
            ->setDescription('This is organizer register page')
            ->setCanonical(url()->current())
            ->addKeyword([
                'organizer register page', $setting->app_name, $setting->app_name . ' Organizer Register',
                'organizer sign-up page', $setting->app_name . ' organizer sign-up'
            ]);

        OpenGraph::setTitle($setting->app_name . ' - Organizer Register' ?? env('APP_NAME'))
            ->setDescription('This is organizer register page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - Organizer Register' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is organizer register page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - Organizer Register' ?? env('APP_NAME'));
        SEOTools::setDescription('This is register page');
        SEOTools::opengraph()->addProperty(
            'keywords',
            [
                'register page', $setting->app_name,
                $setting->app_name . ' Organizer Register',
                'organizer sign-up page', $setting->app_name . ' organizer sign-up'
            ]
        );
        SEOTools::opengraph()->addProperty('image', $setting->imagePath . $setting->logo);
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        return view('frontend.auth.orgRegister');
    }

    public function organizerRegister(Request $request)
    {
        $existingDeniedUser = \App\Models\User::where('email', $request->email)->where('is_verify', 2)->first();
        if ($existingDeniedUser) {
            return redirect()->back()->withInput()->withErrors(['email' => __('your mail is rejected so you can create new mail id')]);
        }

        $request->validate([
            'name' => 'bail|required',
            'first_name' => 'bail|required',
            'last_name' => 'bail|required',
            'email' => 'bail|required|email|unique:users',
            'phone' => 'bail|required|numeric',
            'password' => 'bail|required|min:6',
            'confirm_password' => 'bail|required|min:6|same:password',
            'country' => 'bail|required',
        ]);
        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        $data['image'] = 'defaultuser.png';
        $data['language'] = Setting::first()->language;
        $data['status'] = 1;
        $data['is_verify'] = 0;
        $data['onboarding_completed_at'] = null;
        $user = User::create($data);
        $user->assignRole('Organizer');
        OrganizerPaymentKeys::create([
            'organizer_id' => $user->id,
        ]);

        Log::info('Organizer registration completed', [
            'user_id'  => $user->id,
            'email'    => $user->email,
            'name'     => $user->name,
        ]);

        try {
            Log::info('Sending organizer welcome email', ['email' => $user->email]);
            Mail::to($user->email)->send(new \App\Mail\OrganizerWelcome($user));
            Log::info('Organizer welcome email sent successfully', ['email' => $user->email]);
        } catch (\Exception $e) {
            Log::error('Organizer welcome email failed', [
                'email'   => $user->email,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        $details = [
            'url' => url('organizer/VerificationConfirm/' . $user->id)
        ];
        try {
            Mail::to($user->email)->send(new \App\Mail\VerifyMail($details));
        } catch (\Exception $e) {
            Log::error('Verify mail send failed (organizer)', ['error' => $e->getMessage()]);
        }

        return redirect('user/login')->with('success', 'Verification link has been sent to your email. Please visit that link to complete the verification.');
    }

    public function allEvents(Request $request)
    {
        (new AppHelper)->eventStatusChange();
        $setting = Setting::first(['app_name', 'logo']);

        SEOMeta::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'))
            ->setDescription('This is all events page')
            ->setCanonical(url()->current())
            ->addKeyword([
                'all event page',
                $setting->app_name,
                $setting->app_name . ' All-Events',
                'events page',
                $setting->app_name . ' Events',
            ]);

        OpenGraph::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'))
            ->setDescription('This is all events page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is all events page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'));
        SEOTools::setDescription('This is all events page');
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            'all event page',
            $setting->app_name,
            $setting->app_name . ' All-Events',
            'events page',
            $setting->app_name . ' Events',
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);

        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);

        // Check if showing past events
        if ($request->has('event_type') && $request->event_type == 'past') {
            $events  = Event::with(['category:id,name'])->where([['status', 1], ['is_deleted', 0], ['end_time', '<', $date->format('Y-m-d H:i:s')]]);
        } else {
            $events  = Event::with(['category:id,name'])->where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d H:i:s')]]);
        }

        $chip = array();
        if ($request->has('event_type') && $request->event_type == 'past') {
            $chip['event_type'] = 'Past Events';
        }
        if ($request->has('type') && $request->type != null) {
            $chip['type'] = $request->type;
            $events = $events->where('type', $request->type);
        }
        if ($request->has('category') && $request->category != null) {
            $chip['category'] = Category::find($request->category)->name;
            $events = $events->where('category_id', $request->category);
        }
        if ($request->has('duration') && $request->duration != null) {
            $chip['date'] = $request->duration;
            if ($request->duration == 'Today') {
                $startOfDay = Carbon::now($timezone)->startOfDay();
                $endOfDay = Carbon::now($timezone)->endOfDay();
                $events = $events->where('start_time', '<=', $endOfDay)
                               ->where('end_time', '>=', $startOfDay);
            } else if ($request->duration == 'Tomorrow') {
                $startOfTomorrow = Carbon::tomorrow($timezone)->startOfDay();
                $endOfTomorrow = Carbon::tomorrow($timezone)->endOfDay();
                $events = $events->where('start_time', '<=', $endOfTomorrow)
                               ->where('end_time', '>=', $startOfTomorrow);
            } else if ($request->duration == 'ThisWeek') {
                $startOfWeek = Carbon::now($timezone)->startOfWeek();
                $endOfWeek = Carbon::now($timezone)->endOfWeek();
                $events = $events->where('start_time', '<=', $endOfWeek)
                               ->where('end_time', '>=', $startOfWeek);
            } else if ($request->duration == 'date') {
                if (isset($request->date)) {
                    $selectedDate = Carbon::parse($request->date, $timezone);
                    $startOfSelectedDate = $selectedDate->copy()->startOfDay();
                    $endOfSelectedDate = $selectedDate->copy()->endOfDay();
                    $events = $events->where('start_time', '<=', $endOfSelectedDate)
                                   ->where('end_time', '>=', $startOfSelectedDate);
                }
            }
        }
        $events = $events->orderBy('start_time', 'ASC')->get();
        foreach ($events as $value) {
            $value->total_ticket = Ticket::where([['event_id', $value->id], ['is_deleted', 0], ['status', 1]])->sum('quantity');
            $value->sold_ticket = Order::where('event_id', $value->id)->sum('quantity');
            $value->available_ticket = $value->total_ticket - $value->sold_ticket;
        }
        $user = Auth::guard('appuser')->user();
        $offlinecount = 0;
        $onlinecount = 0;
        foreach ($events as $key => $value) {
            if ($value->type == 'online') {
                $onlinecount += 1;
            }
            if ($value->type == 'offline') {
                $offlinecount += 1;
            }
        }
        return view('frontend.events', compact('user', 'events', 'chip', 'onlinecount', 'offlinecount'));
    }

    public function eventDetail($id, $name = null)
    {
        Session::forget('multiticketssession');
        $setting = Setting::first(['app_name', 'logo']);
        $currency = Setting::first(['currency_sybmol']);
        $data = Event::with(['category:id,name,image', 'organization:id,first_name,organization_name,bio,last_name,image'])->find($id);

        // Check if event exists
        if (!$data) {
            abort(404, 'Event not found');
        }

        SEOMeta::setTitle($data->name)
            ->setDescription($data->description);

        // Only add category meta if category exists
        if ($data->category) {
            SEOMeta::addMeta('event:category', $data->category->name, 'property');
        }

        SEOMeta::addKeyword([
                $setting->app_name,
                $data->name,
                $setting->app_name . ' - ' . $data->name,
                $data->category?->name,
                $data->tags
            ]);

        OpenGraph::setTitle($data->name)
            ->setDescription($data->description)
            ->setUrl(url()->current())
            ->addImage($data->imagePath . $data->image)
            ->setArticle([
                'start_time' => $data->start_time,
                'end_time' => $data->end_time,
                'organization' => $data->organization?->name,
                'catrgory' => $data->category?->name,
                'type' => $data->type,
                'address' => $data->address,
                'tag' => $data->tags,
            ]);

        JsonLd::setTitle($data->name)
            ->setDescription($data->description)
            ->setType('Article')
            ->addImage($data->imagePath . $data->image);

        SEOTools::setTitle($data->name);
        SEOTools::setDescription($data->description);
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            $setting->app_name,
            $data->name,
            $setting->app_name . ' - ' . $data->name,
            $data->category?->name,
            $data->tags
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        SEOTools::jsonLd()->addImage($data->imagePath . $data->image);
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        // Include tickets even if sales ended so the view can render a "Sales End" label
        $isOrganizerOrAdmin = Auth::check() && (
            (method_exists(Auth::user(), 'hasRole') && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('organizer'))) ||
            (int) Auth::id() === (int) $data->user_id
        );

        $data->all_ticket = Ticket::with('allowUser')
            ->where([['event_id', $data->id], ['is_deleted', 0], ['status', 1], ['is_add_on', 0]])
            ->orderBy('id', 'ASC')
            ->get();

        if (!$isOrganizerOrAdmin) {
            $data->all_ticket = $data->all_ticket->filter(function ($t) {
                return (int) $t->allow_to_user === 1;
            })->values();
        }

        if ((int) $data->id === 46) {
            $data->all_ticket = $data->all_ticket->filter(function ($ticket) {
                return (int) $ticket->id !== 150;
            });
        }

        // $data->paid_ticket = Ticket::where([['event_id', $data->id], ['is_deleted', 0], ['type', 'paid'], ['status', 1], ['is_add_on', 0], ['end_time', '>=', $date->format('Y-m-d H:i:s')], ['start_time', '<=', $date->format('Y-m-d H:i:s')]])->orderBy('id', 'DESC')->get();
        // $data->review = Review::where('event_id', $data->id)->orderBy('id', 'DESC')->get();
        foreach ($data->all_ticket as $value) {
            // Calculate used tickets correctly (handle comma-separated ticket_ids)
            $used = 0;
            $orders = Order::where('event_id', $data->id)->get();

            foreach ($orders as $order) {
                $ticketIds = explode(',', $order->ticket_id);
                $quantities = explode(',', $order->quantity);

                foreach ($ticketIds as $index => $tid) {
                    if ((int)trim($tid) === $value->id) {
                        if (isset($quantities[$index])) {
                            $used += (int)trim($quantities[$index], '"');
                        }
                    }
                }
            }

            $value->available_qty = $value->quantity - $used;

            if ($data->id == 46) {
                $value->available_qty = 0;
            }
        }

        // foreach ($data->free_ticket as $value) {
        //     $used = Order::where('ticket_id', $value->id)->sum('quantity');
        //     $value->available_qty = $value->quantity - $used;
        // }
        $images = explode(",", $data->gallery);
        $tags =  explode(",", $data->tags);
        $appUser = Auth::guard('appuser')->user();
        $rate = round(Review::where('event_id', $data->id)->avg('rate'));

        // Load all organizers (user_id may be comma-separated for multiple organizers)
        $organizerIds = array_filter(array_map('trim', explode(',', (string)($data->user_id ?? ''))));
        $organizations = \App\Models\User::whereIn('id', $organizerIds)
            ->select('id', 'first_name', 'last_name', 'organization_name', 'image')
            ->get();

        $liveVenueMap = $data->liveVenueMap()
            ->with('template')
            ->first();
        $venueSeatMapSeats = collect();

        if ($liveVenueMap) {
            EventVenueSeat::expiredHolds()
                ->where('event_id', $data->id)
                ->update([
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                    'hold_token' => null,
                    'held_by_session_id' => null,
                    'held_by_app_user_id' => null,
                    'held_by_guest_user_id' => null,
                    'held_at' => null,
                    'hold_expires_at' => null,
                ]);

            $this->syncLiveVenueMapSeatCoordinates($liveVenueMap);

            $venueSeatMapSeats = $liveVenueMap->seats()
                ->orderBy('section_name')
                ->orderBy('row_name')
                ->orderBy('seat_number')
                ->get();
        }

        return view('frontend.eventDetail', compact('currency', 'data', 'images', 'tags', 'appUser', 'rate', 'organizations', 'liveVenueMap', 'venueSeatMapSeats'));
    }

    public function venueSeatSelection(Request $request, $id)
    {
        $result = $this->prepareVenueSeatSelectionData($request, $id);

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        return view('frontend.venueSeatSelection', $result);
    }

    /**
     * Returns just the seat-map picker markup (no site header/nav), used to inject
     * the seat-selection UI inline into the event-detail page's ticket-flow modal.
     */
    public function venueSeatMapPartial(Request $request, $id)
    {
        $result = $this->prepareVenueSeatSelectionData($request, $id);

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            $errorMessage = $result->getSession() ? $result->getSession()->get('error') : null;

            return response()->json([
                'success' => false,
                'message' => $errorMessage ?: __('Unable to load seat selection for this event.'),
                'redirect' => $result->getTargetUrl(),
            ], 422);
        }

        return response(view('frontend.partials.venue-seat-map-content', $result)->render());
    }

    private function prepareVenueSeatSelectionData(Request $request, $id)
    {
        $currency = Setting::first(['currency_sybmol']);
        $data = Event::with(['category:id,name,image', 'organization:id,first_name,organization_name,bio,last_name,image'])->findOrFail($id);

        $liveVenueMap = $data->liveVenueMap()
            ->with('template')
            ->first();

        if (!$liveVenueMap) {
            return redirect()->route('eventDetail', ['id' => $data->id, 'name' => Str::slug($data->name)])
                ->with('error', __('Venue seat map is not available for this event.'));
        }

        EventVenueSeat::expiredHolds()
            ->where('event_id', $data->id)
            ->update([
                'status' => EventVenueSeat::STATUS_AVAILABLE,
                'hold_token' => null,
                'held_by_session_id' => null,
                'held_by_app_user_id' => null,
                'held_by_guest_user_id' => null,
                'held_at' => null,
                'hold_expires_at' => null,
            ]);

        $this->syncLiveVenueMapSeatCoordinates($liveVenueMap);

        $venueSeatMapSeats = $liveVenueMap->seats()
            ->with(['ticket' => function ($query) {
                $query->select('id', 'name', 'type', 'price')->with('allowUser');
            }])
            ->orderBy('section_name')
            ->orderBy('row_name')
            ->orderBy('seat_number')
            ->get();

        $selectedTicketIds = $request->input('tickets', []);
        if (is_string($selectedTicketIds)) {
            $selectedTicketIds = explode(',', $selectedTicketIds);
        }
        $selectedTicketIds = collect((array) $selectedTicketIds)
            ->filter(fn ($ticketId) => is_numeric($ticketId))
            ->map(fn ($ticketId) => (int) $ticketId)
            ->values()
            ->all();

        $selectedTickets = Ticket::where('event_id', $data->id)
            ->whereIn('id', $selectedTicketIds)
            ->where('is_deleted', 0)
            ->where('status', 1)
            ->get();

        if (empty($selectedTicketIds) || $selectedTickets->isEmpty()) {
            return redirect()->route('eventDetail', ['id' => $data->id, 'name' => Str::slug($data->name)])
                ->with('error', __('Please select a ticket before choosing seats.'));
        }

        $venueMapUsesTicketRows = $venueSeatMapSeats->contains(function ($seat) {
            return !empty($seat->ticket_id);
        });

        return compact('currency', 'data', 'liveVenueMap', 'venueSeatMapSeats', 'selectedTickets', 'selectedTicketIds', 'venueMapUsesTicketRows');
    }

    public function venueSeatStatuses(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $liveVenueMap = $event->liveVenueMap()->first();

        if (!$liveVenueMap) {
            return response()->json([
                'success' => false,
                'message' => __('Venue seat map is not available for this event.'),
            ], 404);
        }

        $this->releaseExpiredVenueSeatHolds($event->id);

        $selectedTicketIds = $request->input('tickets', []);
        if (is_string($selectedTicketIds)) {
            $selectedTicketIds = explode(',', $selectedTicketIds);
        }

        $selectedTicketIds = collect((array) $selectedTicketIds)
            ->filter(fn ($ticketId) => is_numeric($ticketId))
            ->map(fn ($ticketId) => (int) $ticketId)
            ->values()
            ->all();

        $seats = $liveVenueMap->seats()
            ->with(['ticket' => function ($query) {
                $query->select('id', 'name', 'type', 'price')->with('allowUser');
            }])
            ->select([
                'id',
                'status',
                'ticket_id',
                'held_by_session_id',
                'hold_expires_at',
                'seat_label',
                'section_name',
                'row_name',
                'seat_number',
            ])
            ->orderBy('id')
            ->get();

        $venueMapUsesTicketRows = $seats->contains(function ($seat) {
            return !empty($seat->ticket_id);
        });

        $sessionId = $request->session()->getId();
        $isTheatreCategory = (int) $event->category_id === 8;

        return response()->json([
            'success' => true,
            'server_time' => now()->toIso8601String(),
            'seats' => $seats->map(function ($seat) use ($sessionId, $venueMapUsesTicketRows, $selectedTicketIds, $event, $isTheatreCategory) {
                $seatTicketId = (int) ($seat->ticket_id ?? 0);

                // Theatre category (category_id=8):
                // - Seats with no ticket_id → red (booked class) - unconnected to any ticket row
                // - Held seats by others → grey (held class, overridden by CSS to grey on frontend)
                // Other categories: no change from original behaviour
                $isUnconnectedSeat = $isTheatreCategory && $seatTicketId === 0;

                $status = $seat->status ?: EventVenueSeat::STATUS_AVAILABLE;
                $ticket = $seat->ticket;
                if ($ticket && (int) $ticket->allow_to_user === 0) {
                    $status = EventVenueSeat::STATUS_BOOKED;
                }
                $isHeldByCurrentSession = $status === EventVenueSeat::STATUS_HELD
                    && $seat->held_by_session_id === $sessionId
                    && $seat->hold_expires_at
                    && $seat->hold_expires_at->isFuture();
                $isAvailable = ($status === EventVenueSeat::STATUS_AVAILABLE) && !$isUnconnectedSeat;
                $matchesSelectedTicket = !$venueMapUsesTicketRows || ($seatTicketId > 0 && in_array($seatTicketId, $selectedTicketIds, true));
                $isSelectable = ($isAvailable || $isHeldByCurrentSession) && $matchesSelectedTicket && !$isUnconnectedSeat;

                if ($isTheatreCategory) {
                    // Theatre: unconnected → booked (red), held by current session → selected
                    //          held by others → 'held' class which CSS overrides to grey
                    $classStatus = $isUnconnectedSeat
                        ? EventVenueSeat::STATUS_BOOKED   // red
                        : ($isHeldByCurrentSession
                            ? 'selected'
                            : ($isAvailable && !$isSelectable ? EventVenueSeat::STATUS_BLOCKED : $status));
                } else {
                    // Original logic for all other categories
                    $classStatus = $isHeldByCurrentSession
                        ? 'selected'
                        : ($isAvailable && !$isSelectable ? EventVenueSeat::STATUS_BLOCKED : $status);
                }

                $seatLabel = $seat->seat_label ?: trim($seat->section_name . ' ' . $seat->row_name . ' Seat ' . $seat->seat_number);

                return [
                    'id' => (int) $seat->id,
                    'status' => $status,
                    'class_status' => $classStatus,
                    'selectable' => $isSelectable,
                    'disabled' => !$isSelectable,
                    'held_by_current_session' => $isHeldByCurrentSession,
                    'hold_expires_at' => $seat->hold_expires_at ? $seat->hold_expires_at->toIso8601String() : null,
                    'title' => trim($seatLabel . ' - ' . ucfirst($status)),
                ];
            })->values(),
        ]);
    }

    private function syncLiveVenueMapSeatCoordinates($liveVenueMap)
    {
        if (!$liveVenueMap) {
            return;
        }

        $liveVenueMap->loadMissing(['template.sections', 'template.rows', 'template.seats.section', 'template.seats.row']);

        if (!$liveVenueMap->template) {
            return;
        }

        DB::transaction(function () use ($liveVenueMap) {
            $template = $liveVenueMap->template;
            $sectionIds = [];

            foreach ($template->sections as $section) {
                $eventSection = EventVenueSection::updateOrCreate(
                    [
                        'event_venue_map_id' => $liveVenueMap->id,
                        'venue_map_section_id' => $section->id,
                    ],
                    [
                        'name' => $section->name,
                        'code' => $section->code,
                        'color' => $section->color,
                        'sort_order' => $section->sort_order,
                        'status' => EventVenueSeat::STATUS_AVAILABLE,
                    ]
                );

                $sectionIds[$section->id] = $eventSection->id;
            }

            $rowIds = [];

            foreach ($template->rows as $row) {
                if (!isset($sectionIds[$row->venue_map_section_id])) {
                    continue;
                }

                $eventRow = EventVenueRow::updateOrCreate(
                    [
                        'event_venue_map_id' => $liveVenueMap->id,
                        'venue_map_row_id' => $row->id,
                    ],
                    [
                        'event_venue_section_id' => $sectionIds[$row->venue_map_section_id],
                        'name' => $row->name,
                        'seat_count' => $row->seat_count,
                        'sort_order' => $row->sort_order,
                        'status' => EventVenueSeat::STATUS_AVAILABLE,
                    ]
                );

                $rowIds[$row->id] = $eventRow->id;
            }

            $masterSeatIds = $template->seats->pluck('id')->all();

            // Pass 1: Assign temporary unique seat_number to prevent key collision when seat numbers shift
            foreach ($template->seats as $seat) {
                if (!isset($sectionIds[$seat->venue_map_section_id], $rowIds[$seat->venue_map_row_id])) {
                    continue;
                }
                $existingEventSeat = EventVenueSeat::where('event_venue_map_id', $liveVenueMap->id)
                    ->where('venue_map_seat_id', $seat->id)
                    ->first();
                if ($existingEventSeat && (int) $existingEventSeat->seat_number !== (int) $seat->seat_number) {
                    $existingEventSeat->seat_number = 90000 + (int) $seat->id;
                    $existingEventSeat->save();
                }
            }

            // Pass 2: Save updated seat attributes and final seat_number
            foreach ($template->seats as $seat) {
                if (!isset($sectionIds[$seat->venue_map_section_id], $rowIds[$seat->venue_map_row_id])) {
                    continue;
                }

                $eventRow = EventVenueRow::find($rowIds[$seat->venue_map_row_id]);
                $eventSeat = EventVenueSeat::firstOrNew([
                    'event_venue_map_id' => $liveVenueMap->id,
                    'venue_map_seat_id' => $seat->id,
                ]);

                $eventSeat->fill([
                    'event_venue_section_id' => $sectionIds[$seat->venue_map_section_id],
                    'event_venue_row_id' => $rowIds[$seat->venue_map_row_id],
                    'event_id' => $liveVenueMap->event_id,
                    'section_name' => optional($seat->section)->name ?: 'Section',
                    'row_name' => optional($seat->row)->name ?: 'Row',
                    'seat_number' => $seat->seat_number,
                    'seat_label' => $seat->seat_label ?: trim((optional($seat->row)->name ?: 'Row') . ' Seat ' . $seat->seat_number),
                    'x_percent' => $seat->x_percent,
                    'y_percent' => $seat->y_percent,
                    'radius_percent' => $seat->radius_percent,
                    'is_accessible' => (bool) $seat->is_accessible,
                    'ticket_id' => $eventRow?->ticket_id,
                    'pricing_tier' => $eventRow?->pricing_tier,
                    'price' => $eventRow?->price,
                ]);

                if (!$eventSeat->exists) {
                    $eventSeat->status = EventVenueSeat::STATUS_AVAILABLE;
                }

                $eventSeat->save();
            }

            EventVenueSeat::where('event_venue_map_id', $liveVenueMap->id)
                ->where(function ($query) use ($masterSeatIds) {
                    $query->whereNull('venue_map_seat_id')
                        ->orWhereNotIn('venue_map_seat_id', $masterSeatIds);
                })
                ->where('status', '!=', EventVenueSeat::STATUS_BOOKED)
                ->delete();

            EventVenueRow::where('event_venue_map_id', $liveVenueMap->id)
                ->whereDoesntHave('seats')
                ->delete();

            EventVenueSection::where('event_venue_map_id', $liveVenueMap->id)
                ->whereDoesntHave('rows')
                ->delete();
        });
    }

    public function orgDetail($id)
    {
        $setting = Setting::first(['app_name', 'logo']);
        $data = User::find($id);

        SEOMeta::setTitle(($data->first_name ?? '') . ' ' . ($data->last_name ?? ''))
            ->setDescription($data->bio)
            ->addKeyword([
                $setting->app_name,
                $data->name,
                ($data->first_name ?? '') . ' ' . ($data->last_name ?? ''),
            ]);

            OpenGraph::setTitle(($data->first_name ?? '') . ' ' . $data->last_name ?? '')
            ->setDescription($data->bio)
            ->setType('profile')
            ->setUrl(url()->current())
            ->addImage($data->imagePath . $data->image)
            ->setProfile([
                'first_name' => ($data->first_name ?? ''),
                'last_name' => ($data->last_name ?? ''),
                'username' => $data->name,
                'email' => $data->email,
                'bio' => $data->bio,
                'country' => $data->country,
            ]);

            JsonLd::setTitle(($data->first_name ?? '') . ' ' . ($data->last_name ?? ''))
            ->setDescription($data->bio)
            ->setType('Profile')
            ->addImage($data->imagePath . $data->image);

        SEOTools::setTitle(($data->first_name ?? '') . ' ' . ($data->last_name ?? ''));
        SEOTools::setDescription($data->bio);
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            $setting->app_name,
            $data->name,
            ($data->first_name ?? '') . ' ' . ($data->last_name ?? ''),
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        SEOTools::jsonLd()->addImage($data->imagePath . $data->image);

        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $data->total_event = Event::where([['status', 1], ['is_deleted', 0], ['user_id', $id], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d H:i:s')]])->count();
        $data->events = Event::where([['status', 1], ['is_deleted', 0], ['user_id', $id], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d H:i:s')]])->orderBy('start_time', 'ASC')->get();
        return view('frontend.orgDetail', compact('data'));
    }

    public function reportEvent(Request $request)
    {
        $data = $request->all();
        if (Auth::guard('appuser')->check()) {
            $data['user_id'] = Auth::guard('appuser')->user()->id;
        }
        EventReport::create($data);
        return redirect()->back()->withStatus(__('Report is submitted successfully.'));
    }

    // public function checkout(Request $request, $id)
    // {

    //     $data = Ticket::find($id);
    //     $data->event = Event::find($data->event_id);

    //     $setting = Setting::first();

    //     SEOMeta::setTitle($data->name)
    //         ->setDescription($data->description)
    //         ->addKeyword([
    //             $setting->app_name,
    //             $data->name,
    //             $data->event->name,
    //             $data->event->tags
    //         ]);

    //     OpenGraph::setTitle($data->name)
    //         ->setDescription($data->description)
    //         ->setUrl(url()->current());

    //     JsonLd::setTitle($data->name)
    //         ->setDescription($data->description);

    //     SEOTools::setTitle($data->name);
    //     SEOTools::setDescription($data->description);
    //     SEOTools::opengraph()->setUrl(url()->current());
    //     SEOTools::setCanonical(url()->current());
    //     SEOTools::opengraph()->addProperty('keywords', [
    //         $setting->app_name,
    //         $data->name,
    //         $data->event->name,
    //         $data->event->tags
    //     ]);
    //     SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);

    //     $arr = [];
    //     $used = Order::where('ticket_id', $id)->sum('quantity');
    //     $data->available_qty = $data->quantity - $used;
    //     $data->tax = Tax::where([['allow_all_bill', 1], ['status', 1]])->orderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at']);
    //     foreach ($data->tax as $key => $item) {
    //         if ($item->amount_type == 'percentage') {

    //             $amount = ($item->price * $data->price) / 100;
    //             array_push($arr, $amount);
    //         }
    //         if ($item->amount_type == 'price') {
    //             $amount = $item->price;
    //             array_push($arr, $amount);
    //         }
    //     }
    //     $data->tax_total = array_sum($arr);
    //     $data->tax_total = round($data->tax_total, 2);
    //     $data->currency_code = $setting->currency;
    //     $data->currency = $setting->currency_sybmol;
    //     $data->module = Module::where('module', 'Seatmap')->first();
    //     if ($data->seatmap_id != null && $data->module->is_install == 1 && $data->module->is_enable == 1) {
    //         $seat_map = SeatMaps::findOrFail($data->seatmap_id);
    //         $rows = Rows::where('seat_map_id', $data->seatmap_id)->get();
    //         foreach ($rows as $row) {
    //             $seats = Seats::where('row_id', $row->id)->get();
    //             $seatsByRow[$row->id] = $seats;
    //         }
    //         $data->seat_map = $seat_map;
    //         $data->rows = $rows;
    //         $data->seatsByRow = $seatsByRow;
    //     }
    //     $data->totalPersTax = Tax::where([['allow_all_bill', 1], ['status', 1], ['amount_type', 'percentage']])->sum('price');
    //     $data->totalAmountTax = Tax::where([['allow_all_bill', 1], ['status', 1], ['amount_type', 'price']])->sum('price');
    //     $data->phone = Country::get();
    //     Session::put('guest_user', false);
    //     return view('frontend.checkout', compact('data'));
    // }

    private function validateSeatSelections($seatSelections, $quantities = [])
    {
        $errors = [];

        foreach ($seatSelections as $ticketId => $seatData) {
            // Handle both old format (direct ID) and new format (array with seat_table_id and sponser_id)
            if (is_array($seatData)) {
                $seatId = $seatData['selectionSeat_id'];
                $sponserId = $seatData['sponser_id'];
            } else {
                // Fallback for old format
                $seatId = $seatData;
                // For old format, we need to get the sponsership_id from the seat table
                $seatTable = \App\Models\SeatTable::find($seatId);
                $sponserId = $seatTable ? $seatTable->sponsership_id : null;
            }

            // Get seat details using SeatTable model
            $seat = \App\Models\SeatTable::find($seatId);

            if (!$seat) {
                continue;
            }

            // Count existing orders where SeatDetails_id matches the seat table's sponsership_id
            $existingOrders = \App\Models\OrderChild::where('SeatDetails_id', $seat->sponsership_id)
                                                   ->where('seat_id', $seat->id)
                                                   ->count();

            // Get quantity for this seat from request
            $requestedQuantity = isset($quantities[$seatId]) ? $quantities[$seatId] : 1;

            // Check if seat is completely sold out
            if ($seat->number_seat <= $existingOrders) {
                $errors[] = $seat->name_of_table . " is sold out";
                continue;
            }

            // Check if requested quantity exceeds available seats
            $availableSeats = $seat->number_seat - $existingOrders;
            if ($requestedQuantity > $availableSeats) {
                $errors[] = "Your number of quantity is not flexible with this " . $seat->name_of_table . " so you can select other seat or decrease quantity";
            }
        }

        return $errors;
    }

    public function validateSeatsAjax(Request $request)
    {
        $seatSelections = $request->input('seat_selections', []);
        $quantities = $request->input('seat_quantities', []);

        $errors = $this->validateSeatSelections($seatSelections, $quantities);

        return response()->json([
            'errors' => $errors,
            'valid' => empty($errors)
        ]);
    }

     public function checkout(Request $request)
    {
        $result = $this->resolveCheckoutData($request);

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        return view('frontend.checkout', ['data' => $result]);
    }

    /**
     * Returns just the checkout markup (no site header/nav), used to inject
     * the checkout UI inline into the event-detail page's ticket-flow modal.
     */
    public function checkoutContentPartial(Request $request)
    {
        $result = $this->resolveCheckoutData($request);

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            $errorMessage = $result->getSession() ? $result->getSession()->get('error') : null;

            return response()->json([
                'success' => false,
                'message' => $errorMessage ?: __('Unable to load checkout for this order.'),
                'redirect' => $result->getTargetUrl(),
            ], 422);
        }

        return response(view('frontend.partials.checkout-content', ['data' => $result])->render());
    }

    private function resolveCheckoutData(Request $request)
    {
        // This flag only guards against replaying a stale back-button navigation to
        // checkout after an order already completed. A fresh checkout attempt always
        // arrives with its own newly-stored ticket selection, so just clear the flag
        // and continue - redirecting away here would incorrectly block legitimate
        // back-to-back purchases in the same session (e.g. buying a second ticket).
        if (Session::has('order_completed')) {
            Session::forget('order_completed');
        }

        // EMERGENCY DEBUG - Check what's in session and request
        // \Log::emergency('CHECKOUT DEBUG - Session data:', [
        //     'multiticketssession' => Session::get('multiticketssession'),
        //     'seat_selections_session' => Session::get('seat_selections'),
        //     'all_session_data' => Session::all()
        // ]);
        $seatSelections = Session::get('seat_selections');

        // \Log::emergency('CHECKOUT DEBUG - Request data:', [
        //     'all_request' => $request->all(),
        //     'multitickets' => $request->input('multitickets'),
        //     'seat_selections' => $request->input('seat_selections'),
        //     'seat_selections_json' => $request->input('seat_selections_json')
        // ]);

        // $seatTable = \App\Models\SeatTable::find($seatId);
        // $existingOrders = \App\Models\OrderChild::where('SeatDetails_id', $seatTable->sponsership_id)->where('seat_id', $seatTable->id)->count();
        // $availableSeats = $seatTable->number_seat - $existingOrders;
        if (Session::has('multiticketssession')) {
            $selectedOptions = Session::get('multiticketssession');
        } else {
            $selectedOptions = $request->input('multitickets', []);
        }
        // Ensure we have an array of selected options and compute per-ticket counts for validation
        $selectedOptions = collect(is_array($selectedOptions) ? $selectedOptions : (array) $selectedOptions)
            ->filter(function ($ticketId) {
                return is_numeric($ticketId) && (int) $ticketId > 0;
            })
            ->map(function ($ticketId) {
                return (int) $ticketId;
            })
            ->values()
            ->all();
        $ticketCounts = array_count_values($selectedOptions);

        // Handle seat selections - prioritize request data first, then session data
        $seatSelections = [];

        // PRIORITY 1: Check if seat selections are in the request (from direct form submission)
        $requestSeatSelections = $request->input('seat_selections', []);
        if (!empty($requestSeatSelections)) {
            // \Log::emergency('Checkout - Found seat selections in request input:', $requestSeatSelections);
            $seatSelections = $requestSeatSelections;
            Session::put('seat_selections', $seatSelections);
            // \Log::emergency('Checkout - Seat selections from request stored in session:', $seatSelections);
        }

        // PRIORITY 2: Check if JSON seat selections are provided (from fallback form submission)
        if (empty($seatSelections)) {
            $jsonSeatSelections = $request->input('seat_selections_json');
            if (!empty($jsonSeatSelections)) {
                // \Log::emergency('Checkout - Found JSON seat selections in request:', $jsonSeatSelections);
                $decodedSelections = json_decode($jsonSeatSelections, true);
                if (is_array($decodedSelections)) {
                    $seatSelections = $decodedSelections;
                    Session::put('seat_selections', $seatSelections);
                    // \Log::emergency('Checkout - JSON seat selections from request stored in session:', $seatSelections);
                }
            }
        }

        // PRIORITY 3: Check if seat selections are already in session (from event details page via AJAX)
        if (empty($seatSelections) && Session::has('seat_selections')) {
            $sessionSeatSelections = Session::get('seat_selections');
            // \Log::emergency('Checkout - Retrieved seat selections from session:', ['data' => $sessionSeatSelections]);

            // Check if session data is in the correct format (associative array with ticket IDs as keys)
            if (is_array($sessionSeatSelections) && !empty($sessionSeatSelections)) {
                // Check if this is a numeric indexed array containing seat objects
                $firstKey = array_key_first($sessionSeatSelections);
                $firstValue = $sessionSeatSelections[$firstKey];

                // If first key is numeric AND the value is an array with seat_table_id, it's the wrong format
                if (is_numeric($firstKey) && is_array($firstValue) && isset($firstValue['seat_table_id'])) {
                    // This is the wrong format - numeric array containing seat objects
                    // \Log::emergency('Checkout - Session data in wrong format (numeric array with seat objects), attempting to fix using request data');

                    // Get the selected tickets to map the seat data properly
                    $selectedTickets = $request->input('multitickets', []);
                    if (!empty($selectedTickets) && count($selectedTickets) === count($sessionSeatSelections)) {
                        $correctedSeatSelections = [];
                        foreach ($selectedTickets as $index => $ticketId) {
                            if (isset($sessionSeatSelections[$index])) {
                                $correctedSeatSelections[$ticketId] = $sessionSeatSelections[$index];
                            }
                        }
                        $seatSelections = $correctedSeatSelections;
                        // \Log::emergency('Checkout - Corrected seat selections format:', $seatSelections);

                        // Update session with corrected format
                        Session::put('seat_selections', $seatSelections);
                    } else {
                        $seatSelections = $sessionSeatSelections;
                        // \Log::emergency('Checkout - Could not correct format, using session data as-is:', $seatSelections);
                    }
                } else {
                    // Proper associative array format (ticket_id => seat_data)
                    $seatSelections = $sessionSeatSelections;
                    // \Log::emergency('Checkout - Using session data (associative array format):', $seatSelections);
                }
            } else {
                \Log::emergency('Checkout - Session seat selections empty or invalid format, checking request data');
            }
        } else {
            \Log::emergency('Checkout - No seat selections found in session, checking request data');
        }

        // PRIORITY 4: Finally, check for the seat_selection key (legacy format) only if no other data exists
        if (empty($seatSelections)) {
            $altRequestSeatSelections = $request->input('seat_selection', []);
            if (!empty($altRequestSeatSelections)) {
                // \Log::emergency('Checkout - Found legacy seat_selection in request:', $altRequestSeatSelections);
                // Convert legacy format to new format
                $convertedSelections = [];
                foreach ($altRequestSeatSelections as $ticketId => $seatTableId) {
                    // Get the seat table to find the sponser_id
                    $seatTable = \App\Models\SeatTable::find($seatTableId);
                    if ($seatTable) {
                        $convertedSelections[$ticketId] = [
                            'seat_table_id' => $seatTableId,
                            'sponser_id' => $seatTable->sponsership_id
                        ];
                    } else {
                        // Fallback if seat table not found
                        $convertedSelections[$ticketId] = [
                            'seat_table_id' => $seatTableId,
                            'sponser_id' => $seatTableId
                        ];
                    }
                }
                $seatSelections = $convertedSelections;
                Session::put('seat_selections', $seatSelections);
                // \Log::emergency('Checkout - Converted seat selections:', [
                //     'original' => $altRequestSeatSelections,
                //     'converted' => $convertedSelections
                // ]);
            }
        }

        // Log all request data for debugging
        // \Log::info('Checkout - Full request data:', $request->all());

        // Get existing seat selections from session if available
        $existingSeatSelections = Session::get('seat_selections', []);
        // \Log::info('Checkout - Final seat selections for validation:', $existingSeatSelections);

        // Validate seat selections if they exist
        if (!empty($existingSeatSelections)) {
            $quantities = $request->input('seat_quantities', []); // Assuming quantities come from request
            $seatValidationErrors = $this->validateSeatSelections($existingSeatSelections, $quantities);

            if (!empty($seatValidationErrors)) {
                return redirect()->back()->withErrors($seatValidationErrors)->withInput();
            }
        }

        Session::forget('multiticketssession');
        // If no tickets are selected, return an error or redirect
        if (empty($selectedOptions)) {
            return redirect()->back()->with('error', 'No tickets selected.');
        }

        // $tickets = Ticket::whereIn('id', $selectedOptions)->get();
        $setting = Setting::first();
        $metaTags = [];
        $openGraphProperties = [];

        $datamain=[];
        $dataevent=[];
        $dataavailable_qty=[];
        $datatax=[];
        $datataxtotal=[];
        $datamodule=[];
        $dataseat_map=[];
        $datarows=[];
        $dataseatsByRow=[];
        $datatotalPersTax=[];
        $datatotalAmountTax=[];
        $percentamountax=0;
        $priceamountax=0;
        $taxpercname='';
        $taxpriname='';
        foreach(array_keys($ticketCounts) as $ticket){

            $datamain[]=$datas = Ticket::with('seatTable')->find($ticket);

            // Enforce per-ticket per-order limit (applies to guest and authenticated users)
            $requested_for_this_ticket = $ticketCounts[$ticket] ?? 0;
            $datas->selected_quantity = $requested_for_this_ticket;
            if (isset($datas->ticket_per_order) && $datas->ticket_per_order > 0 && $requested_for_this_ticket > $datas->ticket_per_order) {
                \Log::warning("Checkout - Ticket per order limit exceeded", [
                    'ticket_id' => $ticket,
                    'ticket_name' => $datas->name ?? '',
                    'requested' => $requested_for_this_ticket,
                    'limit' => $datas->ticket_per_order
                ]);
                return redirect()->back()->with('error', "You can only purchase up to {$datas->ticket_per_order} tickets for '{$datas->name}' in a single order.");
            }

            $eventid=$datas->event_id;

            $dataevent[]=$datas->event = Event::find($datas->event_id);

            // Calculate total orders for this event
            $orders = Order::where('event_id', $eventid)->get();
            $total_orders = 0;
            foreach ($orders as $order) {
                $total_orders += (int)trim($order->quantity, '"');
            }

            // Validate if user can add requested quantity based on event people limit
            // Example: If event.people = 500 and total_orders = 459
            // Then max_allowed = 500 - 459 = 41
            // If user tries to add 45 tickets, only 41 should be allowed
            $people_limit = $datas->event->people ?? 0;
            $requested_quantity = count($selectedOptions); // Number of tickets in cart
            $max_allowed = $people_limit - $total_orders;

            \Log::info('Checkout - People limit validation:', [
                'event_id' => $eventid,
                'event_name' => $datas->event->name,
                'people_limit' => $people_limit,
                'total_orders' => $total_orders,
                'requested_quantity' => $requested_quantity,
                'max_allowed' => $max_allowed
            ]);

            // Only validate if people limit is set
            if ($people_limit > 0 && $requested_quantity > $max_allowed) {
                \Log::warning('Checkout - Quantity exceeds people limit:', [
                    'event_id' => $eventid,
                    'requested' => $requested_quantity,
                    'max_allowed' => $max_allowed,
                    'would_exceed_by' => $requested_quantity - $max_allowed
                ]);

                return redirect()->back()->with('error', "Only {$max_allowed} tickets are available for this event. You are trying to add {$requested_quantity} tickets.");
            }

            SEOMeta::setTitle($datas->event->name)
            ->setDescription($datas->event->description)
            ->addKeyword([
                $setting->app_name,
                $datas->event->name,
                $datas->event->tags
            ]);

            OpenGraph::setTitle($datas->event->name)
                ->setDescription($datas->event->description)
                ->setUrl(url()->current());

            JsonLd::setTitle($datas->event->name)
                ->setDescription($datas->event->description);

            SEOTools::setTitle($datas->event->name);
            SEOTools::setDescription($datas->event->description);
            SEOTools::opengraph()->setUrl(url()->current());
            SEOTools::setCanonical(url()->current());
            SEOTools::opengraph()->addProperty('keywords', [
                $setting->app_name,
                $datas->event->name,
                $datas->event->tags
            ]);
            SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);

            $metaTags[] = [
                'name' => 'description',
                'content' => $datas->event->description
            ];
            $metaTags[] = [
                'name' => 'keywords',
                'content' => implode(',', [
                    $setting->app_name,
                    $datas->event->name,
                    $datas->event->tags
                ])
            ];

            // Set OpenGraph properties for each ticket
            $openGraphProperties[] = [
                'property' => 'og:title',
                'content' => $datas->name
            ];
            $openGraphProperties[] = [
                'property' => 'og:description',
                'content' => $datas->description
            ];
            $openGraphProperties[] = [
                'property' => ':keywords',
                'content' => $setting->app_name
            ];
            $openGraphProperties[] = [
                'property' => ':keywords',
                'content' => $datas->name
            ];
            $openGraphProperties[] = [
                'property' => ':keywords',
                'content' => $datas->event->name
            ];
            $openGraphProperties[] = [
                'property' => ':keywords',
                'content' => $datas->event->tags
            ];

            $arr = [];

            // Calculate used tickets correctly (handle comma-separated ticket_ids)
            $used = 0;
            $orders = Order::where('event_id', $datas->event_id)->get();

            foreach ($orders as $order) {
                $ticketIds = explode(',', $order->ticket_id);
                $quantities = explode(',', $order->quantity);

                foreach ($ticketIds as $index => $tid) {
                    if ((int)trim($tid) === (int)$ticket) {
                        if (isset($quantities[$index])) {
                            $used += (int)trim($quantities[$index], '"');
                        }
                    }
                }
            }

            $dataavailable_qty[]=$datas->available_qty = $datas->quantity - $used;

            if(!empty($datas->tax_id)){
                $taxarray=explode(',',$datas->tax_id);
                // $taxcalmul=[];
                foreach($taxarray as $tid){
                    $datatax[$ticket]['multi'][]=$datas->tax = Tax::where([['allow_all_bill', 1], ['status', 1],['id',$tid]])->orderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at'])->first();
                    if ($datas->tax && $datas->tax->amount_type == 'percentage') {
                        $amount = round((($datas->tax->price * $datas->price) / 100) * $requested_for_this_ticket, 2);
                        array_push($arr, $amount);
                        $percentamountax +=$amount;
                        $taxpercname=$datas->tax->name.'('.$datas->tax->price.'%)';
                        $taxcalmul[$ticket][$tid][]['name']=$taxpercname;
                        $taxcalmul[$ticket][$tid][]['type']=$datas->tax->amount_type;
                        $taxcalmul[$ticket][$tid][]['price']=$datas->tax->price;
                        $taxcalmul[$ticket][$tid][]['amount']=$amount;
                        $taxcalmul[$ticket][$tid][]['createdby']=$datas->tax->created_by;

                    }
                    if ($datas->tax && $datas->tax->amount_type == 'price') {
                        $amount = round($datas->tax->price * $requested_for_this_ticket, 2);
                        array_push($arr, $amount);
                        $priceamountax+=$amount;
                        $taxpriname=$datas->tax->name;
                        $taxcalmul[$ticket][$tid][]['name']=$taxpriname;
                        $taxcalmul[$ticket][$tid][]['type']=$datas->tax->amount_type;
                        $taxcalmul[$ticket][$tid][]['price']=$datas->tax->price;
                        $taxcalmul[$ticket][$tid][]['amount']=$amount;
                        $taxcalmul[$ticket][$tid][]['createdby']=$datas->tax->created_by;
                    }
                }
            } else {
                // Only apply default taxes if no specific tax_id is set for this ticket
                $datatax['defualt'][]=$datas->tax = Tax::where([['allow_all_bill', 1], ['status', 1],['is_default',1]])->orderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at'])->first();
                if ($datas->tax && $datas->tax->amount_type == 'percentage') {
                    // Check if seat selection exists for this ticket (seat-wise booking)
                    if (isset($seatSelections[$ticket]) && !empty($seatSelections[$ticket])) {
                        $datas->tax->price = 4; // Set special rate for seat-wise booking
                    }
                    $amount = round((($datas->tax->price * $datas->price) / 100) * $requested_for_this_ticket, 2);
                    array_push($arr, $amount);
                    $percentamountax +=$amount;
                    $taxpercname=$datas->tax->name.'('.$datas->tax->price.'%)';
                    $taxcalmul[$ticket][$datas->tax->id][]['name']=$taxpercname;
                    $taxcalmul[$ticket][$datas->tax->id][]['type']=$datas->tax->amount_type;
                    $taxcalmul[$ticket][$datas->tax->id][]['price']=$datas->tax->price;
                    $taxcalmul[$ticket][$datas->tax->id][]['amount']=$amount;
                    $taxcalmul[$ticket][$datas->tax->id][]['createdby']=$datas->tax->created_by;
                }
                if ($datas->tax && $datas->tax->amount_type == 'price') {
                    $amount = round($datas->tax->price * $requested_for_this_ticket, 2);
                    array_push($arr, $amount);
                    $priceamountax+=$amount;
                    $taxpriname=$datas->tax->name;
                    $taxcalmul[$ticket][$datas->tax->id][]['name']=$taxpriname;
                    $taxcalmul[$ticket][$datas->tax->id][]['type']=$datas->tax->amount_type;
                    $taxcalmul[$ticket][$datas->tax->id][]['price']=$datas->tax->price;
                    $taxcalmul[$ticket][$datas->tax->id][]['amount']=$amount;
                    $taxcalmul[$ticket][$datas->tax->id][]['createdby']=$datas->tax->created_by;
                }
            }

            $datas->tax_total = array_sum($arr);
            $datataxtotal[]=$datas->tax_total = round($datas->tax_total, 2);


            $datamodule[]=$datas->module = Module::where('module', 'Seatmap')->first();
            if ($datas->seatmap_id != null && $datas->module->is_install == 1 && $datas->module->is_enable == 1) {
                $seat_map = SeatMaps::findOrFail($datas->seatmap_id);
                $rows = Rows::where('seat_map_id', $datas->seatmap_id)->get();
                foreach ($rows as $row) {
                    $seats = Seats::where('row_id', $row->id)->get();
                    $seatsByRow[$row->id] = $seats;
                }
                $dataseat_map[]=$datas->seat_map = $seat_map;
                $datarows[]=$datas->rows = $rows;
                $dataseatsByRow[]=$datas->seatsByRow = $seatsByRow;
            }
            $datatotalPersTax[]=$datas->totalPersTax = Tax::where([['allow_all_bill', 1], ['status', 1], ['amount_type', 'percentage']])->sum('price');
            $datatotalAmountTax[]=$datas->totalAmountTax = Tax::where([['allow_all_bill', 1], ['status', 1], ['amount_type', 'price']])->sum('price');

        }

        $data['main']['ticket']=$datamain;
        $data['main']['event']=$dataevent;
        $data['main']['available_qty']=$dataavailable_qty;
        $data['main']['tax']=$datatax;
        $data['main']['taxmulti']=$taxcalmul;
        $data['tax_total']= round(array_sum($datataxtotal),2);
        $data['currency_code']= $setting->currency;
        $data['currency']= $setting->currency_sybmol;
        $data['main']['module']= $datamodule;
        $data['main']['seat_map']=$dataseat_map;
        $data['main']['rows']=$datarows;
        $data['main']['seatsByRow']=$dataseatsByRow;
        $data['total_orders']= $total_orders ?? 0;
        $data['max_allowed']= $max_allowed ?? 999;

        // Only pass seat selections if user has actually selected tables
        $seatSelections = Session::get('seat_selections', []);
        if (!empty($seatSelections)) {
            $convertedSeatSelections = [];
            $selectedSeatsIds = [];
            $selectedSeatsDetails = [];

            foreach ($seatSelections as $ticketId => $seatValue) {
                // Handle both formats: direct array with seat_table_id or simple values
                if (is_array($seatValue) && (isset($seatValue['seat_table_id']) || isset($seatValue['selectionSeat_id']))) {
                    // Normalize keys
                    $seatTableId = $seatValue['seat_table_id'] ?? $seatValue['selectionSeat_id'];
                    $sponserId = $seatValue['sponser_id'] ?? null;

                    $convertedSeatSelections[$ticketId] = [
                        'seat_table_id' => $seatTableId,
                        'sponser_id' => $sponserId
                    ];
                    $selectedSeatsIds[] = $seatTableId;
                    $selectedSeatsDetails[] = json_encode([
                        'seat_table_id' => $seatTableId,
                        'sponser_id' => $sponserId
                    ]);
                }
                 else {
                    // Convert legacy format
                    if (is_numeric($seatValue)) {
                        $seatTable = \App\Models\SeatTable::find($seatValue);
                    } else {
                        $seatTable = \App\Models\SeatTable::where('name_of_table', $seatValue)->first();
                    }

                    if ($seatTable) {
                        $seatData = [
                            'seat_table_id' => $seatTable->id,
                            'sponser_id' => $seatTable->sponsership_id

                        ];
                        $convertedSeatSelections[$ticketId] = $seatData;
                        $selectedSeatsIds[] = $seatTable->id;
                        $selectedSeatsDetails[] = json_encode($seatData);
                    }
                }
            }

            // Store converted seat selections back to session for createOrder function
            Session::put('seat_selections', $convertedSeatSelections);
            $data['seat_selections'] = $convertedSeatSelections;

            // Prepare data for the checkout form (used by createOrder method)
            $data['selectedSeatsIds'] = implode(',', $selectedSeatsIds);
            $data['selectedSeatsDetails'] = implode(',', $selectedSeatsDetails);

            \Log::info('Checkout - Converted seat selections:', [
                'original' => $seatSelections,
                'converted' => $convertedSeatSelections,
                'selectedSeatsIds' => $data['selectedSeatsIds'],
                'selectedSeatsDetails' => $data['selectedSeatsDetails']
            ]);
        } else {
            // No seat selections
            $data['seat_selections'] = [];
            $data['selectedSeatsIds'] = '';
            $data['selectedSeatsDetails'] = '';
        }

        $requestVenueSeatIds = $this->normalizeVenueSeatIds($request->input('venue_seat_ids', []));
        if (!empty($requestVenueSeatIds)) {
            $requestVenueSeatDetails = $request->input('venue_seat_details', []);
            if (is_string($requestVenueSeatDetails)) {
                $decodedVenueSeatDetails = json_decode($requestVenueSeatDetails, true);
                $requestVenueSeatDetails = json_last_error() === JSON_ERROR_NONE ? $decodedVenueSeatDetails : [];
            }

            Session::put('venue_seat_ids', $requestVenueSeatIds);
            Session::put('venue_seat_details', is_array($requestVenueSeatDetails) ? $requestVenueSeatDetails : []);
        }

        $checkoutEvent = $dataevent[0] ?? null;
        $venueSeatIds = $this->normalizeVenueSeatIds(Session::get('venue_seat_ids', []));

        if (empty($venueSeatIds) && $checkoutEvent) {
            $venueSeatIds = EventVenueSeat::where('event_id', $checkoutEvent->id)
                ->where('status', EventVenueSeat::STATUS_HELD)
                ->where('held_by_session_id', $request->session()->getId())
                ->where('hold_expires_at', '>', now())
                ->orderBy('id')
                ->pluck('id')
                ->map(function ($seatId) {
                    return (int) $seatId;
                })
                ->values()
                ->all();

            if (!empty($venueSeatIds)) {
                Session::put('venue_seat_ids', $venueSeatIds);
            }
        }

        $venueSeatDetails = Session::get('venue_seat_details', []);
        if (is_string($venueSeatDetails)) {
            $decodedVenueSeatDetails = json_decode($venueSeatDetails, true);
            $venueSeatDetails = json_last_error() === JSON_ERROR_NONE ? $decodedVenueSeatDetails : [];
        }
        $venueSeatDetails = is_array($venueSeatDetails) ? $venueSeatDetails : [];

        if (!empty($venueSeatIds) && (empty($venueSeatDetails) || count($venueSeatDetails) !== count($venueSeatIds))) {
            $venueSeatDetails = $this->venueSeatDetailsFromIds($venueSeatIds, $checkoutEvent->id ?? null);

            if (!empty($venueSeatDetails)) {
                Session::put('venue_seat_details', $venueSeatDetails);
            }
        }

        $data['venueSeatIds'] = $venueSeatIds;
        $data['venueSeatDetails'] = $venueSeatDetails;
        $data['checkoutExpireRedirectUrl'] = $checkoutEvent
            ? (!empty($venueSeatIds)
                ? $this->venueSeatSelectionRedirectUrl($checkoutEvent->id, Session::get('request', []))
                : route('eventDetail', ['id' => $checkoutEvent->id, 'name' => Str::slug($checkoutEvent->name)]))
            : route('home');
        $data['venueSeatHoldExpiresAt'] = null;

        if (!empty($venueSeatIds) && $checkoutEvent) {
            $this->releaseExpiredVenueSeatHolds($checkoutEvent->id);

            $heldSeats = EventVenueSeat::where('event_id', $checkoutEvent->id)
                ->whereIn('id', $venueSeatIds)
                ->where('status', EventVenueSeat::STATUS_HELD)
                ->where('held_by_session_id', $request->session()->getId())
                ->where('hold_expires_at', '>', now())
                ->get(['id', 'hold_expires_at']);

            if ($heldSeats->count() !== count($venueSeatIds)) {
                Session::forget(['request', 'multiticketssession', 'seat_selections', 'venue_seat_ids', 'venue_seat_details']);

                return redirect($data['checkoutExpireRedirectUrl'])
                    ->with('error', __('Your seat hold has expired. Please select seats again.'));
            }

            $holdExpiresAt = $heldSeats
                ->pluck('hold_expires_at')
                ->filter()
                ->sortBy(function ($expiresAt) {
                    return $expiresAt instanceof Carbon
                        ? $expiresAt->timestamp
                        : Carbon::parse($expiresAt)->timestamp;
                })
                ->first();

            $data['venueSeatHoldExpiresAt'] = $holdExpiresAt
                ? ($holdExpiresAt instanceof Carbon ? $holdExpiresAt : Carbon::parse($holdExpiresAt))->toIso8601String()
                : null;
        }


        $data['totalPersTax']=round(array_sum($datatotalPersTax),2);
        $data['totalAmountTax']= round(array_sum($datatotalAmountTax),2);
        $data['taxpart'][$taxpriname]=array('name'=>$taxpriname,'amount_type'=>'price','val'=>round($priceamountax,2));
        $data['taxpart'][$taxpercname]= array('name'=>$taxpercname,'amount_type'=>'percentage','val'=>round($percentamountax,2));

        // Set meta tags
        // SEOMeta::addMeta($metaTags);

        // Set OpenGraph properties
        foreach ($openGraphProperties as $property) {
            // Add each property to OpenGraph
            OpenGraph::addProperty($property['property'], $property['content']);
        }


        $data['phone'] = Country::get();

        $timezone = $setting->timezone;
        $date = Carbon::now($timezone);
        $data['add_ons'] = Ticket::where([['event_id', $eventid], ['is_deleted', 0], ['status', 1], ['is_add_on', 1], ['end_time', '>=', $date->format('Y-m-d H:i:s')], ['start_time', '<=', $date->format('Y-m-d H:i:s')]])->orderBy('id', 'DESC')->get();

        // Available coupons for checkout (active + event not ended)
        $now = Carbon::now();
        $data['coupons'] = Coupon::where('status', 1)
            ->whereHas('event', function ($q) use ($now) {
                $q->where('end_time', '>=', $now);
            })
            ->orderBy('id', 'DESC')
            ->get();
        foreach ($data['add_ons'] as $value) {
            $taxcalmul=[];
            $priceamountax=0;
            // $value->setAttribute('taxinfo', []);
            $used = Order::where('ticket_id', $value->id)->sum('quantity');
            $value->quantity = $value->quantity - $used;
            if(!empty($value->tax_id)){
                $taxarray=explode(',',$value->tax_id);
                foreach($taxarray as $tid){
                    $datas->tax_addon = Tax::where([['allow_all_bill', 1], ['status', 1],['id',$tid]])->orderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at'])->first();
                    if ($datas->tax_addon->amount_type == 'percentage') {
                        $amount = ($datas->tax_addon->price * $value->price) / 100;
                        $percentamountax +=$amount;
                        $taxpercname=$datas->tax_addon->name.'('.$datas->tax_addon->price.'%)';
                        $taxcalmul[$value->id][$tid][]['name']=$taxpercname;
                        $taxcalmul[$value->id][$tid][]['type']=$datas->tax_addon->amount_type;
                        $taxcalmul[$value->id][$tid][]['price']=$datas->tax_addon->price;
                        $taxcalmul[$value->id][$tid][]['amount']=$amount;
                        $taxcalmul[$value->id][$tid][]['createdby']=$datas->tax_addon->created_by;

                    }
                    if ($datas->tax_addon->amount_type == 'price') {
                        $amount = $datas->tax_addon->price;
                        $priceamountax+=$amount;
                        $taxpriname=$datas->tax_addon->name;
                        $taxcalmul[$value->id][$tid][]['name']=$taxpriname;
                        $taxcalmul[$value->id][$tid][]['type']=$datas->tax_addon->amount_type;
                        $taxcalmul[$value->id][$tid][]['price']=$datas->tax_addon->price;
                        $taxcalmul[$value->id][$tid][]['amount']=$amount;
                        $taxcalmul[$value->id][$tid][]['createdby']=$datas->tax_addon->created_by;
                    }
                }
            }
            $datas->tax_addon = Tax::where([['allow_all_bill', 1], ['status', 1],['is_default',1]])->orderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at'])->first();
            if ($datas->tax_addon->amount_type == 'percentage') {
                // Check if seat selection exists for the main ticket (assuming same event)
                $hasSeatSelection = false;
                foreach ($seatSelections as $ticketId => $selection) {
                    $ticket = Ticket::find($ticketId);
                    if ($ticket && $ticket->event_id == $value->event_id && !empty($selection)) {
                        $hasSeatSelection = true;
                        break;
                    }
                }

                if ($hasSeatSelection) {
                    $datas->tax_addon->price = 4; // Set special rate for seat-wise booking
                }
                $amount = ($datas->tax_addon->price * $value->price) / 100;
                $percentamountax +=$amount;
                $taxpercname=$datas->tax_addon->name.'('.$datas->tax_addon->price.'%)';
                $taxcalmul[$value->id][$datas->tax_addon->id][]['name']=$taxpercname;
                $taxcalmul[$value->id][$datas->tax_addon->id][]['type']=$datas->tax_addon->amount_type;
                $taxcalmul[$value->id][$datas->tax_addon->id][]['price']=$datas->tax_addon->price;
                $taxcalmul[$value->id][$datas->tax_addon->id][]['amount']=$amount;
                $taxcalmul[$value->id][$datas->tax_addon->id][]['createdby']=$datas->tax_addon->created_by;

            }
            if ($datas->tax_addon->amount_type == 'price') {
                $amount = $datas->tax_addon->price;
                $priceamountax+=$amount;
                $taxpriname=$datas->tax_addon->name;
                $taxcalmul[$value->id][$datas->tax_addon->id][]['name']=$taxpriname;
                $taxcalmul[$value->id][$datas->tax_addon->id][]['type']=$datas->tax_addon->amount_type;
                $taxcalmul[$value->id][$datas->tax_addon->id][]['price']=$datas->tax_addon->price;
                $taxcalmul[$value->id][$datas->tax_addon->id][]['amount']=$amount;
                $taxcalmul[$value->id][$datas->tax_addon->id][]['createdby']=$datas->tax_addon->created_by;

            }

            $value->setAttribute('taxinfo', $taxcalmul);
        }
        Session::put('guest_user', false);

        return $data;
    }

    public function applyCoupon(Request $request)
{
    $total = (float) $request->total;
    $today = Carbon::today();

    // Parse ticket ids safely: "79, 12 ,80" -> [79,12,80]
    $ticketIds = $request->ticket_id
        ? array_map('intval', array_filter(array_map('trim', explode(',', $request->ticket_id)), 'strlen'))
        : [];

    // Rule: if ticket 80 is present, no coupons allowed
    if (in_array(80, $ticketIds, true)) {
        return response([
            'success' => false,
            'message' => 'Coupons cannot be applied to this ticket type.'
        ]);
    }

    // Fetch coupon by code + event
    $coupon = Coupon::where([
        ['coupon_code', $request->coupon_code],
        ['status', 1],
        ['event_id', $request->event_id],
    ])->first();

    if (!$coupon) {
        return response([
            'success' => false,
            'message' => 'Invalid coupon code for this event!'
        ]);
    }

    // Special rule: coupon id 21 requires ticket 79 in cart
    if ((int) $coupon->id === 21 && !in_array(79, $ticketIds, true)) {
        return response([
            'success' => false,
            'message' => 'This coupon is not valid for this ticket.'
        ]);
    }

    // User + usage limits
    $user = Auth::guard('appuser')->user();
    if (!$user) {
        return response(['success' => false, 'message' => 'Login required to use coupons.']);
    }

    $usageCount = CouponUsageHistory::where([
        ['coupon_id', $coupon->id],
        ['appuser_id', $user->id],
    ])->count();

    if (!is_null($coupon->max_use_per_user) && $usageCount >= (int) $coupon->max_use_per_user) {
        return response([
            'success' => false,
            'message' => 'You have reached the maximum usage for this coupon.'
        ]);
    }

    if (!$today->between(
        Carbon::parse($coupon->start_date)->startOfDay(),
        Carbon::parse($coupon->end_date)->endOfDay()
    )) {
        return response(['success' => false, 'message' => 'This coupon is expired or not yet active.']);
    }

    if (!is_null($coupon->max_use) && (int) $coupon->use_count >= (int) $coupon->max_use) {
        return response(['success' => false, 'message' => 'This coupon has reached maximum total uses.']);
    }

    /**
     * Determine the base amount on which to compute the discount.
     * For coupon 21 → ONLY the subtotal of items with ticket_id = 79 should be discounted.
     * Pass items as: items: [{ticket_id, qty, unit_price}, ...]
     */
    $eligibleBase = $total;

    if ((int) $coupon->id === 21) {
        $items = collect($request->items ?? []); // expected shape: [{ticket_id, qty, unit_price}]
        if ($items->isNotEmpty()) {
            $eligibleBase = $items
                ->filter(fn($i) => (int)($i['ticket_id'] ?? 0) === 79)
                ->sum(fn($i) => (float)($i['unit_price'] ?? 0) * (int)($i['qty'] ?? 1));
        }
    }

    if ((int) $coupon->id === 21 && $eligibleBase <= 0) {
        return response(['success' => false, 'message' => 'No eligible amount found for this coupon.']);
    }

    // Minimum amount check: for item-specific coupon use eligibleBase, otherwise use grand total
    $minCheckBase = ((int) $coupon->id === 21) ? $eligibleBase : $total;
    if ($minCheckBase < (float) $coupon->minimum_amount) {
        return response([
            'success' => false,
            'message' => 'Minimum amount not met for this coupon.'
        ]);
    }

    // Compute discount on eligible base
    $discount = ($coupon->discount_type == 0)
        ? $eligibleBase * ((float) $coupon->discount / 100)
        : (float) $coupon->discount;

    // Cap at maximum discount if defined
    if (!is_null($coupon->maximum_discount) && $discount > (float) $coupon->maximum_discount) {
        $discount = (float) $coupon->maximum_discount;
    }

    // Apply discount to the GRAND total (but calculated from eligible portion)
    $payable = max(0, $total - $discount);

    return response([
        'success'        => true,
        'payableamount'  => $discount,     // NOTE: you were returning discount here; kept same key for compatibility
        'total_price'    => $payable,
        'total'          => $total,
        'discount'       => $coupon->discount,
        'coupon_id'      => $coupon->id,
        'coupon_type'    => $coupon->discount_type,
        'eligible_base'  => $eligibleBase, // for debugging/verification
    ]);
}



    public function createOrder(Request $request)
    {
        if (!Auth::guard('appuser')->check()) {
            $verifiedEmail = Session::get('guest_verified_email');
            if (Session::get('guest_user') !== true || $verifiedEmail !== $request->guest_email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your email before checkout.'
                ], 422);
            }
        }

        $data = $request->all();
        $ticketIds = explode(',', $request->ticket_id);
        $tickets = Ticket::whereIn('id', $ticketIds)->get();
        $event_id = trim($request->event_id);
        $event = Event::find($event_id);
        $org = User::find($event->user_id);

        // Validate ticket quantities before creating order
        $ticketqty = json_decode($request->ticketqty, true);

        foreach ($tickets as $ticket) {
            $requestedQuantity = $ticketqty['ticket_qty' . $ticket->id];

            // Calculate booked quantity (handle comma-separated ticket_ids)
            $bookedQuantity = 0;
            $orders = Order::where('event_id', $event_id)->get();

            foreach ($orders as $order) {
                $orderTicketIds = explode(',', $order->ticket_id);
                $orderQuantities = explode(',', $order->quantity);

                foreach ($orderTicketIds as $index => $tid) {
                    if ((int)trim($tid) === $ticket->id) {
                        if (isset($orderQuantities[$index])) {
                            $bookedQuantity += (int)trim($orderQuantities[$index], '"');
                        }
                    }
                }
            }

            $availableQuantity = $ticket->quantity - $bookedQuantity;

            // Check if requested quantity exceeds available
            if ($requestedQuantity > $availableQuantity) {
                return redirect()->back()->withErrors([
                    'quantity' => "Ticket '{$ticket->name}' only has {$availableQuantity} tickets available. You requested {$requestedQuantity} tickets."
                ])->withInput();
            }
        }

        if(!empty(Auth::guard('appuser')->user()->id)){
            $user = AppUser::find(Auth::guard('appuser')->user()->id);
            $data['customer_id'] = $user->id;
            $child['customer_id'] = Auth::guard('appuser')->user()->id;
            $coupenData['appuser_id']=$user->id;
        }else{
            $guestData['name']=$request['guest_first_name'];
            $guestData['last_name']=$request['guest_last_name'];
            $guestData['email']=$request['guest_email'];
            $guestData['phone']=$request['guest_phone'];

            $user = GuestUser::create($guestData);

            $data['guestuser_id'] = $user->id;
            $child['guestuser_id'] = $user->id;
            $coupenData['guestuser_id']=$user->id;
        }
        $data['order_id'] = '#' . rand(9999, 100000);
        $data['event_id'] = $event->id;
        $data['organization_id'] = $org->id;
        $data['order_status'] = 'Pending';

        if ($request->payment_type == 'LOCAL') {
            $data['payment_status'] = 0;
            $data['order_status'] = 'Pending';
        } else {
            $data['payment_status'] = 1;
            $data['order_status'] = 'Complete';
        }


$com = Setting::find(1, ['org_commission_type', 'org_commission']);
$p = (float) $request->payment - (float) $request->tax;

if ($request->payment_type == 'FREE') {
    $data['org_commission']  = 0;
    $data['payment_status'] = 1;
    $data['order_status'] = 'Complete';
} else {
    if ($com->org_commission_type == "percentage") {
        $data['org_commission'] = $p * $com->org_commission / 100;
    } else if ($com->org_commission_type == "amount") {
        $data['org_commission'] = $com->org_commission;
    }
}


        if ($request->coupon_code != null) {
            $coupon = Coupon::find($request->coupon_code);
            $count = $coupon->use_count + 1;
            $coupon->update(['use_count' => $count]);
            $coupenData['coupon_id']=$request->coupon_code;
            CouponUsageHistory::create($coupenData);
            $data['coupon_discount'] = $coupon->discount ?? 0;
            $data['coupon_id'] = $coupon->id;
        }

        $venueSeatIds = $this->resolveCheckoutVenueSeatIds($request, $event->id);
        $venueSeatDetails = $request->selectedVenueSeats ?: json_encode(Session::get('venue_seat_details', []));

        if (!empty($venueSeatIds)) {
            $this->releaseExpiredVenueSeatHolds($event->id);

            $activeHeldSeats = EventVenueSeat::where('event_id', $event->id)
                ->whereIn('id', $venueSeatIds)
                ->where('status', EventVenueSeat::STATUS_HELD)
                ->where('held_by_session_id', $request->session()->getId())
                ->where('hold_expires_at', '>', now())
                ->count();

            if ($activeHeldSeats !== count($venueSeatIds)) {
                Session::forget(['venue_seat_ids', 'venue_seat_details']);

                return redirect()->back()->with('error', __('Your seat hold has expired. Please select seats again.'));
            }

            $data['book_seats'] = implode(',', $venueSeatIds);
            $data['seat_details'] = $venueSeatDetails;

            if ($request->payment_type === 'STRIPE') {
                $stripeCheck = $this->validateSeatMapStripePayment($request, $data);

                if ($stripeCheck !== true) {
                    return response()->json([
                        'success' => false,
                        'message' => $stripeCheck
                    ], 422);
                }
            }
        } else {
            $data['book_seats'] = isset($request->selectedSeatsId) ? $request->selectedSeatsId : null;
            $data['seat_details'] = isset($request->selectedSeats) ? $request->selectedSeats : null;
        }

        // Debug logging for seat data
        \Log::info('CreateOrder - Seat data received:', [
            'selectedSeatsId' => $request->selectedSeatsId,
            'selectedSeats' => $request->selectedSeats,
            'book_seats' => $data['book_seats'],
            'seat_details' => $data['seat_details']
        ]);

        $order = Order::create($data);

        // Store Stripe Transaction if payment_type is STRIPE
        if ($request->payment_type == 'STRIPE' && $order->payment_token) {
            $this->storeStripeTransaction($order);
        }

        $module = Module::where('module', 'Seatmap')->first();
        if (empty($venueSeatIds) && $module->is_enable == 1 && $module->is_install == 1) {
            $seats = explode(',', $data['book_seats']);
            foreach ($seats as $key => $value) {
                $seat = Seats::find($value);
                if ($seat) {
                    $seat->update(['type' => 'occupied']);
                }
            }
        }

        $ticketqty=json_decode($request->ticketqty,true);
        $totalQuantity = 0;

        // Get seat selections from session (converted from table names to IDs in checkout method)
        $seatSelections = Session::get('seat_selections', []);
        $hasSeatSelection = !empty($seatSelections);

        // Debug logging to check seat selections
        \Log::info('CreateOrder Debug - Seat Selections:', [
            'seatSelections' => $seatSelections,
            'hasSeatSelection' => $hasSeatSelection,
            'ticketIds' => $ticketIds
        ]);

        // Determine user field based on user type
        $userField = !empty(Auth::guard('appuser')->user()->id) ? 'customer_id' : 'guestuser_id';

        foreach($tickets as $tc){
            $quantity = $ticketqty['ticket_qty'.$tc->id];
            $totalQuantity += $quantity;

            // Check if this ticket has a seat selection
            if ($hasSeatSelection && isset($seatSelections[$tc->id])) {
                $seatData = $seatSelections[$tc->id];

                // Handle both old format (direct ID) and new format (array with seat_table_id and sponser_id)
                if (is_array($seatData)) {
                    $seatId = $seatData['seat_table_id'];
                    $sponsershipId = $seatData['sponser_id'];
                } else {
                    // Fallback for old format
                    $seatId = $seatData;
                    $sponsershipId = null;
                }

                $seat = \App\Models\SeatTable::find($seatId);

                if ($seat) {
                    $prefixname = $seat->prefixname;

                    // Get the last seat number used for this prefix
                    $lastNumber = OrderChild::where('seat_id', $seat->id)
                        ->whereNotNull('Book_Seat_Id')
                        ->get()
                        ->map(function ($orderChild) use ($seat) {
                            return (int) str_replace($seat->prefixname.'_', '', $orderChild->Book_Seat_Id);
                        })
                        ->max();

                    $currentNumber = ($lastNumber !== null) ? $lastNumber + 1 : 1;

                    for ($i = 1; $i <= $quantity; $i++) {
                        $ticketNumber = uniqid();

                        $childData = [
                            'ticket_number' => $ticketNumber,
                            'ticket_id' => $tc->id,
                            'order_id' => $order->id,
                            'checkin' => $tc->maximum_checkins ?? null,
                            'paid' => 1,
                            $userField => $user->id,
                            'seat_id' => $seat->id, // Store the seat reference
                            'Book_Seat_Id' => $seat->prefixname.'_'.$currentNumber, // e.g. "sachin_1"
                            'SeatDetails_id' => $sponsershipId ?? $seat->sponsership_id // From seat table
                        ];

                        OrderChild::create($childData);
                        $currentNumber++;
                    }
                } else {
                    // Handle case where seat table not found - create without seat info
                    for ($i = 1; $i <= $quantity; $i++) {
                        $childData = [
                            'ticket_number' => uniqid(),
                            'ticket_id' => $tc->id,
                            'order_id' => $order->id,
                            'checkin' => $tc->maximum_checkins ?? null,
                            'paid' => 1,
                            $userField => $user->id,
                            'seat_id' => null,
                            'Book_Seat_Id' => null,
                            'SeatDetails_id' => null
                        ];
                        OrderChild::create($childData);
                    }
                }
            } else {
                // Handle normal booking without seats - explicitly set seat fields to null
                for ($i = 1; $i <= $quantity; $i++) {
                    $childData = [
                        'ticket_number' => uniqid(),
                        'ticket_id' => $tc->id,
                        'order_id' => $order->id,
                        'checkin' => $tc->maximum_checkins ?? null,
                        'paid' => 1,
                        $userField => $user->id,
                        'seat_id' => null,
                        'Book_Seat_Id' => null,
                        'SeatDetails_id' => null
                    ];
                    OrderChild::create($childData);
                }
            }
        }
        $order->update(['quantity' => (string) $totalQuantity]);
        $this->attachVenueSeatsToOrder($order, $event, $venueSeatIds);

        if (isset($request->tax_data)) {
            foreach (json_decode($data['tax_data']) as $value) {
                $tax['order_id'] = $order->id;
                $tax['tax_id'] = $value->id;
                $tax['price'] = $value->price;
                OrderTax::create($tax);
            }
        }
        $this->createOrderFees($order);

        $setting = Setting::find(1);

        // Get the correct user object (already defined earlier in the method)
        // For authenticated users, refresh from database; for guests, use existing GuestUser
        if(!empty(Auth::guard('appuser')->user()->id)){
            $authUser = AppUser::find($order->customer_id);
        // for user notification
            $message = NotificationTemplate::where('title', 'Book Ticket')->first()->message_content;
            $detail['user_name'] = $authUser->organization_name;
            $detail['quantity'] = $request->quantity;
            $detail['event_name'] = Event::find($order->event_id)->name;
            $detail['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
            $detail['app_name'] = $setting->app_name;
            $noti_data = ["{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
            $message1 = str_replace($noti_data, $detail, $message);
            $notification = array();
            $notification['organizer_id'] = null;
            $notification['user_id'] = $authUser->id;
            $notification['order_id'] = $order->id;
            $notification['title'] = 'Ticket Booked';
            $notification['message'] = $message1;
            Notification::create($notification);
            if ($setting->push_notification == 1) {
                if ($authUser->device_token != null) {
                    (new AppHelper)->sendOneSignal('user', $authUser->device_token, $message1);
                }
            }
        }

        Log::info('About to send ticket email', [
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'user_email' => $user->email ?? 'no email',
            'order_id' => $order->id
        ]);

        // for user mail
        $ticket_book = NotificationTemplate::where('title', 'Book Ticket')->first();

        // Handle both AppUser and GuestUser
        $userName = ($user instanceof \App\Models\AppUser && isset($user->organization_name))
            ? $user->organization_name
            : ($user->name . ' ' . ($user->last_name ?? ''));

        $details['user_name'] = $userName;
        $details['quantity'] = $request->quantity;
        $details['event_name'] = Event::find($order->event_id)->name;
        $details['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
        $details['app_name'] = $setting->app_name;

        Log::info('Preparing to send ticket email', [
            'user_type' => get_class($user),
            'user_email' => $user->email,
            'order_id' => $order->id,
            'mail_notification' => $setting->mail_notification
        ]);

        if ($setting->mail_notification == 1) {

            try {
                // Send invoice PDF and ticket PDFs together in one email
                Log::info('Calling sendUserMailWithAttachments for order: ' . $order->id);
                $event = Event::find($order->event_id);
                $this->sendUserMailWithAttachments($order, $user, $request, $event, $setting, $ticket_book);
                Log::info("Invoice PDF and Ticket PDFs sent successfully for order: " . $order->id);
            } catch (\Throwable $th) {
                Log::error('Error in ticket email sending process: ' . $th->getMessage() . ' | Stack: ' . $th->getTraceAsString());
            }

        }

        // for Organizer notification
        $org =  User::find($order->organization_id);
        $or_message = NotificationTemplate::where('title', 'Organizer Book Ticket')->first()->message_content;
        $or_detail['organizer_name'] = $org->organization_name;
        $or_detail['user_name'] = $user->name . ' ' . $user->last_name;
        $or_detail['quantity'] = $request->quantity;
        $or_detail['event_name'] = Event::find($order->event_id)->name;
        $or_detail['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
        $or_detail['app_name'] = $setting->app_name;
        $or_noti_data = ["{{organizer_name}}", "{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
        $or_message1 = str_replace($or_noti_data, $or_detail, $or_message);
        $or_notification = array();
        $or_notification['organizer_id'] =  $org->id;
        $or_notification['user_id'] = null;
        $or_notification['order_id'] = $order->id;
        $or_notification['title'] = 'New Ticket Booked';
        $or_notification['message'] = $or_message1;
        Notification::create($or_notification);
        if ($setting->push_notification == 1) {
            if ($org->device_token != null) {
                (new AppHelper)->sendOneSignal('organizer', $org->device_token, $or_message1);
            }
        }
        // for Organizer mail
        $new_ticket = NotificationTemplate::where('title', 'Organizer Book Ticket')->first();
        $details1['organizer_name'] = $org->first_name . ' ' . $org->last_name;
        $details1['user_name'] = $user->name . ' ' . $user->last_name;
        $details1['quantity'] = $request->quantity;
        $details1['event_name'] = Event::find($order->event_id)->name;
        $details1['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
        $details1['app_name'] = $setting->app_name;
        if ($setting->mail_notification == 1) {
            try {
                Mail::to($org->email)->send(new TicketBookOrg($new_ticket->mail_content, $details1, $new_ticket->subject));
            } catch (\Throwable $th) {
                Log::info($th->getMessage());
            }
        }
        $url="";
        Session::put('guestorderid', $order->id);

        // Clear seat selections from session after successful order creation
        Session::forget('seat_selections');
        Session::forget('venue_seat_ids');
        Session::forget('venue_seat_details');

        // Mark order as completed to prevent back button access to checkout
        Session::put('order_completed', true);

        if (Auth::guard('appuser')->check()) {
            $url='ordersuccessuser';
        }else{
            $url='ordersuccess';
        }
        return response()->json(['success' => true, 'message' => 'Payment successful',"id"=>$order->id,'url'=>$url]);
    }

    public function sendMail($id)
    {
        try {
            Log::info('sendMail started for order ID: ' . $id);
            $order = Order::with(['event', 'organization', 'ticket', 'appUser', 'guestUser'])->find($id);

            if (!$order) {
                Log::error('sendMail: Order not found for ID: ' . $id);
                return;
            }

            $order->tax_data = OrderTax::where('order_id', $order->id)->get();
            $order->ticket_data = OrderChild::where('order_id', $order->id)->get();
            $customPaper = array(0, 0, 720, 1440);
            $setting = Setting::select('*')->first();

            Log::info('sendMail: Generating PDF for order: ' . $order->id);
            $pdf = FacadePdf::loadView('ticketmail', compact('order'))->save(public_path("ticket.pdf"))->setPaper($customPaper, $orientation = 'portrait');

            // $customqrPaper = array(0, 0, 603.75,900);

            // $ticketpdf = FacadePdf::loadView('qrticketpdf', compact('order', 'setting'))
            //     ->setPaper($customqrPaper, 'landscape') // Correctly pass 'landscape' here
            //     ->save(public_path("ticketPdf.pdf"));
            // $tickettempp = $ticketpdf->output();
            $tempDirectory = storage_path('temp_qrcodes');
            if (!file_exists($tempDirectory)) {
                mkdir($tempDirectory);
            }

            // Initialize the $qrCodeFiles array
            $qrCodeFiles = [];

            // Set the desired size of the QR codes
            $qrCodeSize = 400;

            // Generate and save QR codes for each ticket
            // foreach ($order->tickets() as $item) {
            //     // Generate the QR code with the ticket number and specified size
            //     $qrCodeText = $item->ticket_number;
            //     $qrCode = QrCode::format('png')->size($qrCodeSize)->generate($qrCodeText);

            //     // Save the QR code image with the ticket number
            //     $qrCodeFileName = "qr_code_{$item->ticket_number}.png";
            //     $qrCodeFilePath = "{$tempDirectory}/{$qrCodeFileName}";
            //     file_put_contents($qrCodeFilePath, $qrCode);
            //     $qrCodeFiles[] = $qrCodeFilePath; // Add the file path to the array
            // }

            $customer = $order->customer;
            if (!$customer || !$customer->email) {
                Log::error('sendMail: Customer or email not found for order: ' . $order->id . ', guestuser_id: ' . $order->guestuser_id . ', customer_id: ' . $order->customer_id);
                return;
            }

            $data["email"] = $customer->email;
            $data["title"] = "Event Tickets | The Event Palette";
            $data["body"] = "";
            $tempp = $pdf->output();

            Log::info('sendMail: Sending email with PDF attachment to: ' . $data["email"]);

            try {
                Mail::send('mail', $data, function ($message) use ($data, $tempp, $setting) {
                    $message->from($setting->sender_email, $setting->app_name)
                        ->to($data["email"])
                        ->subject($data["title"])
                        ->attachData($tempp, "ticket.pdf");
                        // ->attachData($tickettempp, "ticketPdf.pdf");
                        // Attach QR codes to the email
                        // foreach ($qrCodeFiles as $qrCodeFile) {
                        //     $message->attach($qrCodeFile);
                        // }
                });

                // Check if there were any mail failures
                if (count(Mail::failures()) > 0) {
                    Log::error('sendMail: Mail sending failed for: ' . implode(', ', Mail::failures()));
                } else {
                    Log::info('sendMail: Email sent successfully to: ' . $data["email"]);
                }
            } catch (\Throwable $mailException) {
                Log::error("Error sending mail in sendMail: " . $mailException->getMessage());
            }

        } catch (\Throwable $e) {
            Log::error("Error in sendMail: " . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
        }
    }

    public function categoryEvents($id, $name = null)
    {
        $setting = Setting::first(['app_name', 'logo']);
        $category = Category::findOrFail($id);
        $expectedSlug = \Illuminate\Support\Str::slug($category->name);

        if ($name !== $expectedSlug) {
            return redirect()->to(url('/events-category/' . $id . '/' . $expectedSlug), 301);
        }

        SEOMeta::setTitle($setting->app_name . '- Events' ?? env('APP_NAME'))
            ->setDescription('This is category events page')
            ->setCanonical(url()->current())
            ->addKeyword([
                'category event page',
                $category->name . ' - Events',
                $setting->app_name,
                $setting->app_name . ' Events',
                'events page',
            ]);

        OpenGraph::setTitle($setting->app_name . ' - Events' ?? env('APP_NAME'))
            ->setDescription('This is category events page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - Events' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is category events page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - Events' ?? env('APP_NAME'));
        SEOTools::setDescription('This is category events page');
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            'category event page',
            $category->name . ' - Events',
            $setting->app_name,
            $setting->app_name . ' Events',
            'events page',
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);

        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $events  = Event::with(['category:id,name'])
            ->where([['status', 1], ['is_deleted', 0], ['category_id', $id], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d H:i:s')]])
            ->orderBy('start_time', 'ASC')->get();
        $offlinecount = 0;
        $onlinecount = 0;
        foreach ($events as $key => $value) {
            if ($value->type == 'online') {
                $onlinecount += 1;
            }
            if ($value->type == 'offline') {
                $offlinecount += 1;
            }
        }
        $user = Auth::guard('appuser')->user();
        $catactive = $name;
        return view('frontend.events', compact('events', 'category', 'onlinecount', 'offlinecount', 'user', 'catactive'));
    }

    public function eventType($type)
    {
        $setting = Setting::first(['app_name', 'logo']);

        SEOMeta::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'))
            ->setDescription('This is all events page')
            ->setCanonical(url()->current())
            ->addKeyword([
                'all event page',
                $setting->app_name,
                $setting->app_name . ' All-Events',
                'events page',
                $setting->app_name . ' Events',
            ]);

        OpenGraph::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'))
            ->setDescription('This is all events page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is all events page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'));
        SEOTools::setDescription('This is all events page');
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            'all event page',
            $setting->app_name,
            $setting->app_name . ' All-Events',
            'events page',
            $setting->app_name . ' Events',
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);


        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        if ($type == "all") {
            $events  = Event::with(['category:id,name'])
                ->where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d H:i:s')]])
                ->orderBy('start_time', 'ASC')->get();

            return view('frontend.events', compact('events'));
        } else {
            $events  = Event::with(['category:id,name'])
                ->where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['type', $type], ['end_time', '>', $date->format('Y-m-d H:i:s')]])
                ->orderBy('start_time', 'ASC')->get();
            return view('frontend.events', compact('events', 'type'));
        }
    }

    public function allCategory()
    {
        $setting = Setting::first(['app_name', 'logo']);

        SEOMeta::setTitle($setting->app_name . ' - Category' ?? env('APP_NAME'))
            ->setDescription('This is all category page')
            ->setCanonical(url()->current())
            ->addKeyword([
                'all event page',
                $setting->app_name,
                $setting->app_name . ' Category',
                'category page',
                $setting->app_name . ' category',
            ]);

        OpenGraph::setTitle($setting->app_name . ' - Category' ?? env('APP_NAME'))
            ->setDescription('This is all category page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - Category' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is all category page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - Category' ?? env('APP_NAME'));
        SEOTools::setDescription('This is all category page');
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            'all event page',
            $setting->app_name,
            $setting->app_name . ' Category',
            'category page',
            $setting->app_name . ' category',
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        $data = Category::where('status', 1)->orderBy('id', 'DESC')->get();
        $catactive = 'all';

        return view('frontend.allCategory', compact('data', 'catactive'));
    }

    public function blogs()
    {
        $blogs = Blog::where('status', 1)->orderBy('id', 'DESC')->get();
        $category = Category::where('status', 1)->orderBy('id', 'DESC')->get();
        $setting = Setting::first(['app_name', 'logo']);
        SEOMeta::setTitle($setting->app_name . ' - Blogs' ?? env('APP_NAME'))
            ->setDescription('This is blogs page')
            ->setCanonical(url()->current())
            ->addKeyword([
                'blogs page',
                $setting->app_name,
                $setting->app_name . ' Blogs',
                'blog page',
            ]);
        OpenGraph::setDescription('This is blogs page');
        OpenGraph::setTitle($setting->app_name . ' - Blogs' ?? env('APP_NAME'));
        OpenGraph::setUrl(url()->current());
        OpenGraph::addProperty('type', 'blogs');
        JsonLd::setTitle($setting->app_name . ' - Blogs' ?? env('APP_NAME'));
        JsonLd::setDescription('This is blogs page');
        JsonLd::addImage($setting->imagePath . $setting->logo);
        SEOTools::setTitle($setting->app_name . ' - Blogs' ?? env('APP_NAME'));
        SEOTools::setDescription('This is blogs page');
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('type', 'blogs');
        $user = Auth::guard('appuser')->user();
        return view('frontend.blog', compact('blogs', 'category', 'user'));
    }

    public function blogDetail($id, $name)
    {
        $setting = Setting::first(['app_name', 'logo']);

        $data = Blog::find($id);
        $data->category = Category::find($data->category_id);
        $tags = explode(',', $data->tags);
        SEOMeta::setTitle($data->title);
        SEOMeta::setDescription($data->description);
        SEOMeta::addMeta('blog:published_time', $data->created_at->toW3CString(), 'property');
        SEOMeta::addMeta('blog:category', $data->category->name, 'property');
        SEOMeta::addKeyword($data->tags);

        OpenGraph::setTitle($data->title)
            ->setDescription($data->description)
            ->setType('blog')
            ->addImage($data->imagePath . $data->image)
            ->setArticle([
                'published_time' => $data->created_at,
                'modified_time' => $data->updated_at,
                'section' => $data->category->name,
                'tag' => $data->tags
            ]);

        JsonLd::setTitle($data->title);
        JsonLd::setDescription($data->description);
        JsonLd::setType('Blog');
        JsonLd::addImage($data->imagePath . $data->image);
        $user = Auth::guard('appuser')->user();

        return view('frontend.blogDetail', compact('data', 'tags', 'user'));
    }

    public function profile()
    {
        $user = Auth::guard('appuser')->user();
        $setting = Setting::first(['app_name', 'logo']);

        SEOMeta::setTitle('User Profile')
            ->setDescription('This is user profile page')
            ->addKeyword([
                $setting->app_name,
                $user->name,
                $user->name . ' ' . $user->last_name,
            ]);

        OpenGraph::setTitle('User Profile')
            ->setDescription('This is user profile page')
            ->setType('profile')
            ->setUrl(url()->current())
            ->addImage($user->imagePath . $user->image)
            ->setProfile([
                'first_name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'bio' => $user->bio,
                'country' => $user->country,
            ]);

        JsonLd::setTitle('User Profile' ?? env('APP_NAME'))
            ->setDescription('This is user profile page')
            ->setType('Profile')
            ->addImage($user->imagePath . $user->image);

        SEOTools::setTitle('User Profile' ?? env('APP_NAME'));
        SEOTools::setDescription('This is user profile page');
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            $setting->app_name,
            $user->name,
            $user->name . ' ' . $user->last_name,
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        SEOTools::jsonLd()->addImage($user->imagePath . $user->image);

        $user->saved_event = Event::whereIn('id', array_filter(explode(',', $user->favorite)))->where([['status', 1], ['is_deleted', 0]])->get();
        $user->saved_blog = Blog::whereIn('id', array_filter(explode(',', $user->favorite_blog)))->where('status', 1)->get();
        $user->following = User::whereIn('id', array_filter(explode(',', $user->following)))->get();
        foreach ($user->saved_event as $value) {
            $value->total_ticket = Ticket::where([['event_id', $value->id], ['is_deleted', 0], ['status', 1]])->sum('quantity');
            $value->sold_ticket = Order::where('event_id', $value->id)->sum('quantity');
            $value->available_ticket = $value->total_ticket - $value->sold_ticket;
        }
        return view('frontend.profile', compact('user'));
    }

    public function update_profile()
    {
        $user =  Auth::guard('appuser')->user();
        $phone = Country::get();
        $languages = Language::where('status', 1)->get();
        return view('frontend.user_profile', compact('user', 'languages', 'phone'));
    }

    public function update_user_profile(Request $request)
    {
        $data = $request->all();
        $user =  Auth::guard('appuser')->user();
        $user->update($data);
        $this->setLanguage($user);
        return redirect('/user/profile');
    }

    /**
     * Update phone number submitted from header modal (AJAX JSON).
     * Accepts { country_code, phone } and returns JSON { success, message }.
     */
    public function updatePhoneNumber(Request $request)
    {
        try {
            $request->validate([
                'country_code' => ['required', 'string', 'max:6'],
                'phone' => ['required', 'regex:/^[0-9]{7,12}$/'],
            ]);

            // If authenticated app user, update their phone
            if (Auth::guard('appuser')->check()) {
                $user = Auth::guard('appuser')->user();
                $user->phone = $request->country_code . $request->phone;
                $user->save();

                if (session()->has('show_phone_popup')) {
                    session()->forget('show_phone_popup');
                }

                return response()->json(['success' => true, 'message' => __('Phone number updated successfully')]);
            }

            // If guest (modal shown via session flag), persist to session for later use
            if (session('show_phone_popup')) {
                session(['guest_country_code' => $request->country_code, 'guest_phone' => $request->phone]);
                session()->forget('show_phone_popup');
                return response()->json(['success' => true, 'message' => __('Phone number saved')]);
            }

            // Otherwise unauthorized
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 401);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            \Log::error('updatePhoneNumber Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('An error occurred. Please try again.')], 500);
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

    public function addFavorite($id, $type)
    {
        $users = AppUser::find(Auth::guard('appuser')->user()->id);
        if ($type == "event") {
            $likes = array_filter(explode(',', $users->favorite));
            if (count(array_keys($likes, $id)) > 0) {
                if (($key = array_search($id, $likes)) !== false) {
                    unset($likes[$key]);
                }
                $msg = "Remove event from Favorite!";
            } else {
                array_push($likes, $id);
                $msg = "Add event in Favorite!";
            }
            $client = AppUser::find(Auth::guard('appuser')->user()->id);
            $client->favorite = implode(',', $likes);
        } else if ($type == "blog") {
            $likes = array_filter(explode(',', $users->favorite_blog));
            if (count(array_keys($likes, $id)) > 0) {
                if (($key = array_search($id, $likes)) !== false) {
                    unset($likes[$key]);
                }
                $msg = "Remove blog from Favorite!";
            } else {
                array_push($likes, $id);
                $msg = "Add blog in Favorite!";
            }
            $client = AppUser::find(Auth::guard('appuser')->user()->id);
            $client->favorite_blog = implode(',', $likes);
        }
        $client->update();
        return response()->json(['msg' => $msg, 'success' => true, 'type' => $type], 200);
    }

    public function addFollow($id)
    {
        $users = AppUser::find(Auth::guard('appuser')->user()->id);
        $likes = array_filter(explode(',', $users->following));
        if (count(array_keys($likes, $id)) > 0) {
            if (($key = array_search($id, $likes)) !== false) {
                unset($likes[$key]);
            }
            $msg = "Remove from following list!";
        } else {
            array_push($likes, $id);
            $msg = "Add in following!";
        }
        $client = AppUser::find(Auth::guard('appuser')->user()->id);
        $client->following = implode(',', $likes);
        $client->update();
        return response()->json(['msg' => $msg, 'success' => true], 200);
    }

    public function addBio(Request $request)
    {
        $success = AppUser::find(Auth::guard('appuser')->user()->id)->update(['bio' => $request->bio]);
        return response()->json(['data' => $request->bio, 'success' => $success], 200);
    }

    public function changePassword()
    {
        $setting = Setting::first(['app_name', 'logo']);

        SEOMeta::setTitle($setting->app_name . ' - Change Password' ?? env('APP_NAME'))
            ->setDescription('This is change password page')
            ->setCanonical(url()->current())
            ->addKeyword([
                'change password page',
                $setting->app_name,
                $setting->app_name . ' Change Password'
            ]);

        OpenGraph::setTitle($setting->app_name . ' - Change Password' ?? env('APP_NAME'))
            ->setDescription('This is change password page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - Change Password' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is change password page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - Change Password' ?? env('APP_NAME'));
        SEOTools::setDescription('This is change password page');
        SEOTools::opengraph()->addProperty('keywords', [
            'change password page',
            $setting->app_name,
            $setting->app_name . ' Change Password'
        ]);
        SEOTools::opengraph()->addProperty('image', $setting->imagePath . $setting->logo);
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);

        return view('frontend.auth.changePassword');
    }

    public function changeUserPassword(Request $request)
    {
        $request->validate([
            'old_password' => 'bail|required',
            'password' => 'bail|required|min:6',
            'password_confirmation' => 'bail|required|same:password|min:6'
        ]);
        if (Hash::check($request->old_password, Auth::guard('appuser')->user()->password)) {
            AppUser::find(Auth::guard('appuser')->user()->id)->update(['password' => Hash::make($request->password)]);
            return redirect('change-password')->with('status', __('Password is changed successfully.'));
        } else {
            return Redirect::back()->with('error_msg', 'Current Password is wrong!');
        }
    }

    public function uploadProfileImage(Request $request)
    {
        $appuser = AppUser::find(Auth::guard('appuser')->user());
        if ($request->hasFile('image') != 'defaultuser.png') {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:3048',
            ]);
            (new AppHelper)->deleteFile($appuser->image);
            $imageName = (new AppHelper)->saveImage($request);
            AppUser::find(Auth::guard('appuser')->user()->id)->update(['image' => $imageName]);
        } else {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:3048',
            ]);
            $imageName = (new AppHelper)->saveImage($request);
            AppUser::find(Auth::guard('appuser')->user()->id)->update(['image' => $imageName]);
        }
        return response()->json(['data' => $imageName, 'success' => true], 200);
    }

    public function contact()
    {
        $setting = Setting::first(['app_name', 'logo']);
        $data = ContactUs::find(1);
        SEOMeta::setTitle($setting->app_name . ' - Contact Us' ?? env('APP_NAME'))
            ->setDescription('This is contact us page')
            ->setCanonical(url()->current())
            ->addKeyword([
                $setting->app_name,
                $setting->app_name . ' Contact Us',
                'contact us page',
            ]);

        OpenGraph::setTitle($setting->app_name . ' - Contact Us' ?? env('APP_NAME'))
            ->setDescription('This is contact us page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - Contact Us' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is contact us page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - Contact Us' ?? env('APP_NAME'));
        SEOTools::setDescription('This is contact us page');
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            $setting->app_name,
            $setting->app_name . ' Contact Us',
            'contact us page',
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        if ($data) {
            return view('frontend.contact', compact('data'));
        }
        return view('frontend.contact');
    }

    public function becomeOrganizer()
    {
        $setting = Setting::first(['app_name', 'logo', 'appstore_link', 'googleplay_link']);
        SEOMeta::setTitle(($setting->app_name ?? env('APP_NAME')) . ' - Become an Organizer')
            ->setDescription('Host your event on ' . ($setting->app_name ?? env('APP_NAME')) . ' with 0% commission. Manage everything from our organizer app and check in guests instantly with our scanner app.')
            ->setCanonical(url()->current());

        return view('frontend.becomeOrganizer', compact('setting'));
    }

    public function userTickets()
    {

        $user = Auth::guard('appuser')->user();
        $setting = Setting::first(['app_name', 'logo', 'currency']);
        SEOMeta::setTitle('User Tickets')
            ->setDescription('This is user tickets page')
            ->addKeyword([
                $setting->app_name,
                $user->name,
                $user->name . ' ' . $user->last_name,
                $user->name . ' ' . $user->last_name . ' tickets',
            ]);

        OpenGraph::setTitle('User Tickets')
            ->setDescription('This is user tickets page')
            ->setUrl(url()->current())
            ->addImage($user->imagePath . $user->image);


        JsonLd::setTitle('User Tickets' ?? env('APP_NAME'))
            ->setDescription('This is user tickets page')
            ->addImage($user->imagePath . $user->image);

        SEOTools::setTitle('User Tickets' ?? env('APP_NAME'));
        SEOTools::setDescription('This is user tickets page');
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            $setting->app_name,
            $user->name,
            $user->name . ' ' . $user->last_name,
            $user->name . ' ' . $user->last_name . ' tickets',
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        SEOTools::jsonLd()->addImage($user->imagePath . $user->image);
        (new AppHelper)->eventStatusChange();

        // Get upcoming tickets (events that haven't ended yet)
        $ticket['upcoming'] = Order::with(['event:id,name,image,image_2,start_time,type,end_time,address', 'organization:id,first_name,last_name,image'])
            ->whereHas('event', function($query) {
                $query->where('end_time', '>=', Carbon::now());
            })
            ->where('customer_id', Auth::guard('appuser')->user()->id)
            ->whereIn('order_status', ['Pending', 'Complete'])
            ->orderBy('id', 'DESC')->paginate(10);

        // Collect tax data for upcoming tickets
        $upcomingTaxes = [];
        foreach ($ticket['upcoming'] as $item) {
            $orderTaxes = OrderTax::where('order_id', $item->id)->get();
            foreach ($orderTaxes as $orderTax) {
                $tax = Tax::find($orderTax->tax_id);
                if ($tax) {
                    $upcomingTaxes[] = $tax;
                }
            }
        }
        $ticket['upcoming']->maintax = $upcomingTaxes;


        // Get past tickets (events that have ended)
        $ticket['past'] = Order::with(['event:id,name,image,image_2,start_time,type,end_time,address', 'organization:id,first_name,last_name,image'])
            ->whereHas('event', function($query) {
                $query->where('end_time', '<', Carbon::now());
            })
            ->where('customer_id', Auth::guard('appuser')->user()->id)
            ->whereIn('order_status', ['Pending', 'Complete', 'Cancel'])
            ->orderBy('id', 'DESC')->paginate(10);

        // Collect tax data for past tickets
        $pastTaxes = [];
        foreach ($ticket['past'] as $item) {
            $orderTaxes = OrderTax::where('order_id', $item->id)->get();
            foreach ($orderTaxes as $orderTax) {
                $tax = Tax::find($orderTax->tax_id);
                if ($tax) {
                    $pastTaxes[] = $tax;
                }
            }
        }
        $ticket['past']->maintax = $pastTaxes;

        $likedEvents = Event::whereIn('id', array_filter(explode(',', $user->favorite)))->where([['status', 1], ['is_deleted', 0]])->orderBy('id', 'DESC')->get();
        foreach ($likedEvents as $value) {
            $value->description =  str_replace("&nbsp;", " ", strip_tags($value->description));
            $value->time = $value->start_time->format('d F Y h:i a');
        }
        $likedBlogs = Blog::whereIn('id', array_filter(explode(',', $user->favorite_blog)))->where([['status', 1]])->orderBy('id', 'DESC')->get();
        $userFollowing = User::whereIn('id', array_filter(explode(',', $user->following)))->where([['status', 1]])->orderBy('id', 'DESC')->get();
        $wallet = PaymentSetting::first()->wallet;
        return view('frontend.userTickets', compact('likedEvents', 'ticket', 'likedBlogs', 'userFollowing', 'wallet'));
    }
    public function userOrderTicket($id)
    {
        $order = Order::with(['event', 'ticket', 'organization'])->find($id);
        $taxes_id = OrderTax::where('order_id', $order->id)->get();
        // $coupon = Coupon::find($order->coupon_id);
        $taxes = [];
        foreach ($taxes_id as $key => $value) {
            $temp_tax[] = Tax::find($value->tax_id);
            $taxes = $temp_tax;
        }
        // $orderchild = OrderChild::where('order_id', $order->id)->get();
        $review = Review::where('order_id', $order->id)->first();
        return view('frontend.userOrderTicket', compact('order', 'taxes', 'review'));
    }
    public function  getOrder($id)
    {
        $data = Order::with(['event:id,name,image,start_time,type,end_time,address', 'organization:id,first_name,last_name,image'])->find($id);
        $data->review = Review::where('order_id', $id)->first();
        $data->time = $data->created_at->format('D') . ', ' . $data->created_at->format('d M Y') . ' at ' . $data->created_at->format('h:i a');
        $data->start_time = $data->event->start_time->format('d M Y') . ', ' . $data->event->start_time->format('h:i a');
        $data->end_time = $data->event->end_time->format('d M Y') . ', ' . $data->event->end_time->format('h:i a');
        $taxs = array();
        $ordertax = OrderTax::where('order_id', $id)->get();
        foreach ($ordertax as $item) {
            $taxs = Tax::find($item->tax_id)->get();
            $taxs = $taxs;
        }
        $data->maintax = $taxs;

        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function addReview(Request $request)
    {
        $data = $request->all();
        $data['organization_id'] = Order::find($request->order_id)->organization_id;
        $data['event_id'] = Order::find($request->order_id)->event_id;
        $data['user_id'] = Auth::guard('appuser')->user()->id;
        $data['status'] = 0;
        Review::create($data);
        return redirect()->back();
    }

    // public function sentMessageToAdmin(Request $request)
    // {
    //     $data = $request->all();
    //     try {
    //         Mail::send('emails.message', ['data' => $data], function ($message) use ($data) {
    //             $setting = Setting::first();
    //             $message->from($setting->sender_email);
    //             $message->to(User::find(1)->email);
    //             $message->subject($data['subject']);
    //         });
    //         return redirect()->back()->with('success', 'Your form has been submitted successfully!');
    //     } catch (Throwable $th) {
    //         Log::info($th->getMessage());
    //     }
    //     // return redirect('/contact');
    // }

    public function sentMessageToAdmin(Request $request)
    {
        $data = $request->all();

        try {
            $setting = Setting::firstOrFail(); // Ensure the setting exists
            $adminEmail = User::findOrFail(1)->email; // Ensure the admin user exists

            Mail::send('emails.message', ['data' => $data], function ($message) use ($data, $setting, $adminEmail) {
                $message->from($setting->sender_email, $setting->sender_name ?? 'No-Reply');
                $message->to($adminEmail);
                $message->subject($data['subject']);
            });
            return redirect()->back()->with('success', 'Your form has been successfully submitted!');
        } catch (Throwable $th) {
            // Log the error with context for debugging
            Log::error("Error in sendMessageToAdmin: " . $th->getMessage(), [
                'data' => $data,
                'trace' => $th->getTraceAsString()
            ]);

            // Return with an error message
            return redirect()->back()->with('error', 'Failed to send the message. Please try again later.');
        }
    }


    public function privacypolicy()
    {
        $policy = Setting::find(1)->privacy_policy_organizer;
        return view('frontend.privacy-policy', compact('policy'));
    }

    public function appuserPrivacyPolicyShow(Request $request)
    {
        $policy = Setting::find(1)->appuser_privacy_policy;
        return view('frontend.privacy-policy', compact('policy'));
    }
    public function searchEvent(Request $request)
    {
        $search = $request->search ?? '';
        if ($search == '') {
            return redirect()->back();
        }
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $events  = Event::with(['category:id,name'])
            ->where([['address', 'LIKE', "%$search%"], ['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d')]])
            ->orWhere([['name', 'LIKE', "%$search%"], ['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d')]])
            ->orWhere([['description', 'LIKE', "%$search%"], ['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d')]]);
        $chip = array();
        if ($request->has('type') && $request->type != null) {
            $chip['type'] = $request->type;
            $events = $events->where('type', $request->type);
        }
        if ($request->has('category') && $request->category != null) {
            $chip['category'] = Category::find($request->category)->name;
            $events = $events->where('category_id', $request->category);
        }
        if ($request->has('duration') && $request->duration != null) {
            $chip['date'] = $request->duration;
            if ($request->duration == 'Today') {
                $temp = Carbon::now($timezone)->format('Y-m-d');
                $events = $events->whereBetween('start_time', [$temp . ' 00:00:00', $temp . ' 23:59:59']);
            } else if ($request->duration == 'Tomorrow') {
                $temp = Carbon::tomorrow($timezone)->format('Y-m-d');
                $events = $events->whereBetween('start_time', [$temp . ' 00:00:00', $temp . ' 23:59:59']);
            } else if ($request->duration == 'ThisWeek') {
                $now = Carbon::now($timezone);
                $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i:s');
                $weekEndDate = $now->endOfWeek()->format('Y-m-d H:i:s');
                $events = $events->whereBetween('start_time', [$weekStartDate, $weekEndDate]);
            } else if ($request->duration == 'date') {
                if (isset($request->date)) {
                    $temp = Carbon::parse($request->date)->format('Y-m-d H:i:s');
                    $events = $events->whereBetween('start_time', [$request->date . ' 00:00:00', $request->date . ' 23:59:59']);
                }
            }
        }
        $events = $events->orderBy('start_time', 'ASC')->get();
        foreach ($events as $value) {
            $value->total_ticket = Ticket::where([['event_id', $value->id], ['is_deleted', 0], ['status', 1]])->sum('quantity');
            $value->sold_ticket = Order::where('event_id', $value->id)->sum('quantity');
            $value->available_ticket = $value->total_ticket - $value->sold_ticket;
        }
        $user = Auth::guard('appuser')->user();
        $offlinecount = 0;
        $onlinecount = 0;
        foreach ($events as $key => $value) {
            if ($value->type == 'online') {
                $onlinecount += 1;
            }
            if ($value->type == 'offline') {
                $offlinecount += 1;
            }
        }

        return view('frontend.events', compact('user', 'events', 'chip', 'onlinecount', 'offlinecount'));
    }
    public function eventsByTag($tag)
    {
        $events = Event::where([['tags', 'LIKE', "%$tag%"], ['is_deleted', 0]])->get();
        $onlinecount = 0;
        $offlinecount = 0;
        foreach ($events as $key => $value) {
            if ($value->type == 'online') {
                $onlinecount += 1;
            } else {
                $offlinecount += 1;
            }
        }
        if (Auth::guard('appuser')->check()) {
            $user = Auth::guard('appuser')->user();
            return view('frontend.events', compact('events', 'onlinecount', 'offlinecount', 'user'));
        }
        return view('frontend.events', compact('events', 'onlinecount', 'offlinecount'));
    }
    public function blogByTag($tag)
    {
        $blogs = Blog::where('tags', 'LIKE', "%$tag%")->where('status', 1)->orderBy('id', 'DESC')->get();
        $category = Category::where('status', 1)->orderBy('id', 'DESC')->get();
        if (Auth::guard('appuser')->user()) {
            $user = Auth::guard('appuser')->user();
            return view('frontend.blog', compact('blogs', 'category', 'user'));
        }
        return view('frontend.blog', compact('blogs', 'category'));
    }
    public function Faqs()
    {
        $data = Faq::where('status', 1)->get();
        return view('frontend.show_faq', compact('data'));
    }
    public function otpView($id)
    {
        $user = AppUser::find($id);
        return view('frontend.auth.otp', compact('user'));
    }
    public function otpViewOrganizer($id)
    {
        $user = User::find($id);
        return view('frontend.auth.otporganizer', compact('user'));
    }
    public function otpVerify(Request $request)
    {
        $request->validate([
            'otp' => 'required',
        ]);
        $user = AppUser::find($request->id);
        if ($user->otp == $request->otp) {
            $user->otp = null;
            $user->is_verify = 1;
            $user->update();
            $previousSessionId = $request->session()->getId();
            Auth::guard('appuser')->login($user);
            $this->syncCheckoutVenueSeatHoldsAfterLogin($previousSessionId);
            return redirect('/');
        } else {
            return redirect()->back()->with('error', 'Wrong OTP. Please try again.');
        }
    }
    public function otpVerifyOrganizer(Request $request)
    {
        $request->validate([
            'otp' => 'required',
        ]);
        $user = User::find($request->id);
        if ($user->otp == $request->otp) {
            $user->otp = null;
            $user->email_verified_at = Carbon::now();
            $user->update();
            Auth::login($user);
            return redirect()->route('users.onboarding');
        } else {
            return redirect()->back()->with('error', 'Wrong OTP. Please try again.');
        }
    }
    public function checkoutSession(Request $request)
    {
        //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
        //pk_live_51OGQw0KzSXP4o2z96BBuLfIjOUklksNUdwUp5yL7MPbUP6fp6LLRQM73RUFBrUPyZuy2mWQXVNtgJrYm7faFwsJc00rRw0YkTj
        $allData=$request->all();
        $checkData=$this->checkoutServices->getTicketPrice($allData);
        $allData['payment']=$checkData;
        $request->payment=$checkData;
        if(empty(Auth::guard('appuser')->user()->id)){
            $verifiedEmail = Session::get('guest_verified_email');
            if (Session::get('guest_user') !== true || $verifiedEmail !== ($request->guest_email ?? null)) {
                return response()->json(['message' => 'Verify email first.'], 422);
            }
        }

        $request->session()->put('request', $allData);
        $key = PaymentSetting::first()->stripeSecretKey;
        Stripe::setApiKey($key);
        $supportedCurrency = [
            "EUR",   # Euro
            "GBP",   # British Pound Sterling
            "CAD",   # Canadian Dollar
            "AUD",   # Australian Dollar
            "JPY",   # Japanese Yen
            "CHF",   # Swiss Franc
            "NZD",   # New Zealand Dollar
            "HKD",   # Hong Kong Dollar
            "SGD",   # Singapore Dollar
            "SEK",   # Swedish Krona
            "DKK",   # Danish Krone
            "PLN",   # Polish Złoty
            "NOK",   # Norwegian Krone
            "CZK",   # Czech Koruna
            "HUF",   # Hungarian Forint
            "ILS",   # Israeli New Shekel
            "MXN",   # Mexican Peso
            "BRL",   # Brazilian Real
            "MYR",   # Malaysian Ringgit
            "PHP",   # Philippine Peso
            "TWD",   # New Taiwan Dollar
            "THB",   # Thai Baht
            "TRY",   # Turkish Lira
            "RUB",   # Russian Ruble
            "INR",   # Indian Rupee
            "ZAR",   # South African Rand
            "AED",   # United Arab Emirates Dirham
            "SAR",   # Saudi Riyal
            "KRW",   # South Korean Won
            "CNY"    # Chinese Yuan
        ];
        $amount = $request->payment;
        if (!in_array($request->currency, $supportedCurrency)) {
            $amount = round($amount * 100);
        }
        $currencyCode = Setting::first()->currency;
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currencyCode,
                        'product_data' => [
                            'name' => "Payment"
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => route('stripe.success'),
            'cancel_url' => route('stripe.cancel'),
        ]);
        $request->session()->put('payment_token', $session->id);
        return response()->json(['id' => $session->id, 'url' => $session->url, 'status' => 200]);
    }

    public function createSeatMapPaymentIntent(Request $request)
    {
        if (!Auth::guard('appuser')->check()) {
            $verifiedEmail = Session::get('guest_verified_email');

            if (Session::get('guest_user') !== true || $verifiedEmail !== ($request->guest_email ?? null)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Please verify your email before checkout.')
                ], 422);
            }
        }

        $event = Event::find($request->event_id);
        if (!$event && $request->ticket_id) {
            $ticketId = collect(explode(',', (string) $request->ticket_id))
                ->map(fn ($ticketId) => trim($ticketId))
                ->first(fn ($ticketId) => is_numeric($ticketId) && (int) $ticketId > 0);
            $ticket = $ticketId ? Ticket::find((int) $ticketId) : null;
            $event = $ticket ? Event::find($ticket->event_id) : null;
        }
        $venueSeatIds = $event ? $this->resolveCheckoutVenueSeatIds($request, $event->id) : [];

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => __('Event is required for this checkout.')
            ], 422);
        }

        if (empty($venueSeatIds)) {
            return response()->json([
                'success' => false,
                'message' => __('Seat selection is required for this checkout.')
            ], 422);
        }

        $this->releaseExpiredVenueSeatHolds($event->id);

        if (!$this->hasActiveVenueSeatHold($event->id, $venueSeatIds, $request->session()->getId())) {
            Session::forget(['venue_seat_ids', 'venue_seat_details']);

            return response()->json([
                'success' => false,
                'expired' => true,
                'redirect_url' => $this->venueSeatSelectionRedirectUrl($event->id, $request->all()),
                'message' => __('Your seat hold has expired. Please select seats again.')
            ], 409);
        }

        $paymentSetting = PaymentSetting::first();
        $setting = Setting::first();

        if (!$paymentSetting || !$paymentSetting->stripeSecretKey || !$setting || !$setting->currency) {
            return response()->json([
                'success' => false,
                'message' => __('Stripe payment is not configured.')
            ], 422);
        }

        $allData = $request->all();
        $amount = $this->checkoutServices->getTicketPrice($allData);
        $currency = strtolower($setting->currency);

        Stripe::setApiKey($paymentSetting->stripeSecretKey);

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $this->stripeAmountToMinorUnit($amount, $currency),
                'currency' => $currency,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'event_id' => (string) $event->id,
                    'type' => 'event_ticket',
                    'appuser_id' => (string) (Auth::guard('appuser')->id() ?: ''),
                    'guest_email' => (string) ($request->guest_email ?: ''),
                    'venue_seat_ids' => implode(',', $venueSeatIds),
                ],
            ]);

            return response()->json([
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
            ]);
        } catch (Throwable $e) {
            Log::error('Seat map Stripe PaymentIntent creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Unable to start Stripe payment. Please try again.')
            ], 422);
        }
    }

    private function stripeAmountToMinorUnit($amount, string $currency): int
    {
        $zeroDecimalCurrencies = [
            'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga',
            'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
        ];

        $amount = (float) $amount;

        if (in_array(strtolower($currency), $zeroDecimalCurrencies, true)) {
            return max(0, (int) round($amount));
        }

        return max(0, (int) round($amount * 100));
    }

    private function validateSeatMapStripePayment(Request $request, array $orderData)
    {
        $paymentToken = (string) $request->payment_token;

        if (!Str::startsWith($paymentToken, 'pi_')) {
            return __('Stripe payment was not completed.');
        }

        $paymentSetting = PaymentSetting::first();
        $setting = Setting::first();

        if (!$paymentSetting || !$paymentSetting->stripeSecretKey || !$setting || !$setting->currency) {
            return __('Stripe payment is not configured.');
        }

        try {
            $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
            $paymentIntent = $stripe->paymentIntents->retrieve($paymentToken, []);
            $expectedAmount = $this->stripeAmountToMinorUnit(
                $this->checkoutServices->getTicketPrice($orderData),
                strtolower($setting->currency)
            );

            if ($paymentIntent->status !== 'succeeded') {
                return __('Stripe payment was not completed.');
            }

            if ((int) $paymentIntent->amount !== $expectedAmount || strtolower($paymentIntent->currency) !== strtolower($setting->currency)) {
                return __('Stripe payment amount does not match this checkout.');
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Seat map Stripe payment validation failed: ' . $e->getMessage());

            return __('Unable to verify Stripe payment. Please try again.');
        }
    }
    // public function stripeSuccess()
    // {
    //     $request = Session::get('request');
    //     $ticketIds = explode(',', $request['ticket_id']);
    //     // $ticket = Ticket::findOrFail($request['ticket_id']);
    //     $tickets = Ticket::whereIn('id', $ticketIds)->get();

    //     $event_id = trim($request['event_id']);
    //     $event = Event::find($event_id);

    //     $org = User::find($event->user_id);

    //     $isusercount=0;
    //     if(!empty(Auth::guard('appuser')->user()->id)){
    //         $user = AppUser::find(Auth::guard('appuser')->user()->id);
    //         $data['customer_id'] = $user->id;
    //         $child['customer_id'] = Auth::guard('appuser')->user()->id;
    //         $coupenData['appuser_id']=$user->id;
    //         $isusercount=1;
    //     }else{
    //         $guestData['name']=$request['guest_first_name'];
    //         $guestData['last_name']=$request['guest_last_name'];
    //         $guestData['email']=$request['guest_email'];
    //         $guestData['phone']=$request['guest_phone'];

    //         $user = GuestUser::create($guestData);

    //         $data['guestuser_id'] = $user->id;
    //         $child['guestuser_id'] = $user->id;
    //         $coupenData['guestuser_id']=$user->id;
    //     }
    //     $data['order_id'] = '#' . rand(9999, 100000);
    //     $data['event_id'] = $event->id;
    //     $data['organization_id'] = $org->id;
    //     $data['order_status'] = 'Pending';
    //     $data['ticket_id'] = $request['ticket_id'];
    //     $data['quantity'] = $request['quantity'];
    //     $data['payment_type'] = 'STRIPE';
    //     $data['payment_token'] = Session::get('payment_token');
    //     $data['payment'] = $request['payment'];
    //     $data['tax'] = $request['tax'];
    //     $data['coupon_id'] = $request['coupon_id'] ?? null;
    //     $data['payment_status'] = 1;
    //     $data['order_status'] = 'Complete';
    //     $data['admin_revenue'] = $request['admin_revenue'];
    //     $data['org_revenue'] = $request['org_revenue'];

    //     $com = Setting::find(1, ['org_commission_type', 'org_commission']);
    //     $p =   $request['payment'] - $request['tax'];
    //     if ($request['payment_type'] == "FREE") {
    //         $data['org_commission']  = 0;
    //     } else {
    //         if ($com->org_commission_type == "percentage") {
    //             $data['org_commission'] =  $p * $com->org_commission / 100;
    //         } else if ($com->org_commission_type == "amount") {
    //             $data['org_commission']  = $com->org_commission;
    //         }
    //     }

    //     if (isset($request['coupon_code'])) {
    //         $coupon = Coupon::find($request->coupon_code);
    //         $count = $coupon->use_count + 1;
    //         $coupon->update(['use_count' => $count]);
    //         $coupenData['coupon_id']=$request->coupon_code;
    //         CouponUsageHistory::create($coupenData);
    //         $data['coupon_discount'] = $coupon->discount;
    //         $data['coupon_id'] = $coupon->id;
    //     }

    //     $data['book_seats'] = isset($request['selectedSeatsId']) ? $request['selectedSeatsId'] : null;
    //     $data['seat_details'] = isset($request['selectedSeats']) ? $request['selectedSeats'] : null;
    //     $order = Order::create($data);
    //     $module = Module::where('module', 'Seatmap')->first();
    //     if ($module->is_enable == 1 && $module->is_install == 1) {
    //         $seats = explode(',', $data['selectedSeatsId']);
    //         foreach ($seats as $key => $value) {
    //             $seat = Seats::find($value);
    //             if ($seat) {
    //                 $seat->update(['type' => 'occupied']);
    //             }
    //         }
    //     }
    //     $ticketqty=json_decode($request['ticketqty'],true);
    //     foreach($tickets as $tc){
    //         for ($i = 1; $i <= $ticketqty['ticket_qty'.$tc->id]; $i++) {
    //             $child['ticket_number'] = uniqid();
    //             $child['ticket_id'] = $tc->id;
    //             $child['order_id'] = $order->id;
    //             $child['checkin'] = $tc->maximum_checkins ?? null;
    //             $child['paid'] =  1 ;
    //             OrderChild::create($child);
    //         }

    //     }

    //     if (isset($request['tax_data'])) {
    //         foreach (json_decode($data['tax_data']) as $value) {
    //             $tax['order_id'] = $order->id;
    //             $tax['tax_id'] = $value->id;
    //             $tax['price'] = $value->price;
    //             OrderTax::create($tax);
    //         }
    //     }

    //     $setting = Setting::find(1);

    //     // for user notification
    //     if(!empty(Auth::guard('appuser')->user()->id)){
    //         $user = AppUser::find($order->customer_id);

    //         $message = NotificationTemplate::where('title', 'Book Ticket')->first()->message_content;
    //         $detail['user_name'] = $user->name . ' ' . $user->last_name;
    //         $detail['quantity'] = $request['quantity'];
    //         $detail['event_name'] = Event::find($order->event_id)->name;
    //         $detail['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
    //         $detail['app_name'] = $setting->app_name;
    //         $noti_data = ["{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
    //         $message1 = str_replace($noti_data, $detail, $message);
    //         $notification = array();
    //         $notification['organizer_id'] = null;
    //         $notification['user_id'] = $user->id;
    //         $notification['order_id'] = $order->id;
    //         $notification['title'] = 'Ticket Booked';
    //         $notification['message'] = $message1;
    //         Notification::create($notification);
    //         if ($setting->push_notification == 1) {
    //             if ($user->device_token != null) {
    //                 (new AppHelper)->sendOneSignal('user', $user->device_token, $message1);
    //             }
    //         }
    //     }
    //     // for user mail
    //     $ticket_book = NotificationTemplate::where('title', 'Book Ticket')->first();
    //     $details['user_name'] = $user->name . ' ' . $user->last_name;
    //     $details['quantity'] = $request['quantity'];
    //     $details['event_name'] = Event::find($order->event_id)->name;
    //     $details['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
    //     $details['app_name'] = $setting->app_name;
    //     if ($setting->mail_notification == 1) {

    //         try {
    //             $qrcode = $order->order_id;
    //             Mail::to($user->email)->send(new TicketBook($ticket_book->mail_content, $details, $ticket_book->subject, $qrcode));
    //         } catch (\Throwable $th) {
    //             Log::info($th->getMessage());
    //         }
    //         $this->sendMail($order->id);
    //     }

    //     // for Organizer notification
    //     $org =  User::find($order->organization_id);
    //     $or_message = NotificationTemplate::where('title', 'Organizer Book Ticket')->first()->message_content;
    //     $or_detail['organizer_name'] = $org->first_name . ' ' . $org->last_name;
    //     $or_detail['user_name'] = $user->name . ' ' . $user->last_name;
    //     $or_detail['quantity'] = $request['quantity'];
    //     $or_detail['event_name'] = Event::find($order->event_id)->name;
    //     $or_detail['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
    //     $or_detail['app_name'] = $setting->app_name;
    //     $or_noti_data = ["{{organizer_name}}", "{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
    //     $or_message1 = str_replace($or_noti_data, $or_detail, $or_message);
    //     $or_notification = array();
    //     $or_notification['organizer_id'] =  $org->id;
    //     $or_notification['user_id'] = null;
    //     $or_notification['order_id'] = $order->id;
    //     $or_notification['title'] = 'New Ticket Booked';
    //     $or_notification['message'] = $or_message1;
    //     Notification::create($or_notification);
    //     if ($setting->push_notification == 1) {
    //         if ($org->device_token != null) {
    //             (new AppHelper)->sendOneSignal('organizer', $org->device_token, $or_message1);
    //         }
    //     }
    //     // for Organizer mail
    //     $new_ticket = NotificationTemplate::where('title', 'Organizer Book Ticket')->first();
    //     $details1['organizer_name'] = $org->first_name . ' ' . $org->last_name;
    //     $details1['user_name'] = $user->name . ' ' . $user->last_name;
    //     $details1['quantity'] = $request['quantity'];
    //     $details1['event_name'] = Event::find($order->event_id)->name;
    //     $details1['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
    //     $details1['app_name'] = $setting->app_name;
    //     if ($setting->mail_notification == 1) {
    //         try {
    //             $setting = Setting::first();
    //             Mail::to($org->email)->send(new TicketBookOrg($new_ticket->mail_content, $details1, $new_ticket->subject));
    //         } catch (\Throwable $th) {
    //             Log::info($th->getMessage());
    //         }
    //     }
    //     Session::forget('request');
    //     if (Session::has('multiticketssession')) {
    //         Session::forget('multiticketssession');
    //     }
    //     if($isusercount < 1){

    //         Session::put('guestorderid',$order->id);
    //         return redirect()->route('ordersuccess');
    //     }else{
    //         return redirect()->route('myTickets');
    //     }

    // }
    public function striepCancel()
    {
        $requestData = Session::get('request', []);
        $eventId = (int) ($requestData['event_id'] ?? 0);

        if ($eventId > 0 && (!empty($requestData['selectedVenueSeatIds']) || !empty(Session::get('venue_seat_ids', [])))) {
            return redirect($this->venueSeatSelectionRedirectUrl($eventId, $requestData))
                ->with('error', __('Payment was cancelled. Please select seats again.'));
        }

        return redirect()->back();
    }

    public function verifyGuestemail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'bail|required|numeric',
        ]);


        $data["email"] = $request->email;
        $data["title"] = "Guest Email Verification";
        $data["otp"] = rand(1000, 9999);
        $sender = Setting::select('sender_email', 'app_name')->first();
        $data['app_name']=$sender->app_name;
        try {
            Mail::send('guestemailverify', ['data' => $data], function ($message) use ($data, $sender) {
                $message->from($sender->sender_email, $sender->app_name)
                    ->to($data["email"])
                    ->subject($data["title"]);
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        // Store OTP in database (delete any existing one for this email first)
        GuestOtp::where('email', $request->email)->delete();
        GuestOtp::create([
            'email'      => $request->email,
            'otp'        => $data["otp"],
            'expires_at' => now()->addMinutes(10),
        ]);

        return response([
            'success' => true
        ]);
    }

    function resendOtp(Request $request){

        $data["email"] = $request->email;
        $data["title"] = "Guest Email Verification";
        $data["otp"] = rand(1000, 9999);
        $sender = Setting::select('sender_email', 'app_name')->first();
        $data['app_name']=$sender->app_name;
        try {
            Mail::send('guestemailverify', ['data' => $data], function ($message) use ($data, $sender) {
                $message->from($sender->sender_email, $sender->app_name)
                    ->to($data["email"])
                    ->subject($data["title"]);
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        // Store OTP in database (delete any existing one for this email first)
        GuestOtp::where('email', $request->email)->delete();
        GuestOtp::create([
            'email'      => $request->email,
            'otp'        => $data["otp"],
            'expires_at' => now()->addMinutes(10),
        ]);

        return response([
            'success' => true
        ]);
    }

    function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric',
            'email' => 'required|email',
        ]);

        $email = $request->email;

        $record = GuestOtp::where('email', $email)->latest()->first();

        if ($record && !$record->isExpired() && (string) $record->otp === (string) $request->otp) {
            // OTP is valid, delete it
            $record->delete();

            Session::put('guest_user', true);
            Session::put('guest_verified_email', $email);

            return response()->json(['message' => 'OTP verified successfully.']);
        }

        // OTP is invalid or expired
        return response()->json(['message' => 'Invalid or expired OTP.'], 422);
    }

    /**
     * Lightweight guest-email confirmation (no OTP mail round-trip): marks the guest's
     * session verified directly from the details they entered, so checkout is one click.
     */
    function confirmGuestEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'bail|required|numeric',
        ]);

        Session::put('guest_user', true);
        Session::put('guest_verified_email', $request->email);

        return response()->json(['success' => true]);
    }

    function storeSessionTickets(Request $request){
        \Log::emergency('storeSessionTickets CALLED - Request data:', $request->all());

        $selectedOptions = $request->input('multitickets');
        Session::put('multiticketssession', $selectedOptions);

        \Log::emergency('After storeSessionTickets - Session data:', [
            'seat_selections' => Session::get('seat_selections'),
            'multiticketssession' => Session::get('multiticketssession')
        ]);
        // Also handle seat selections if they are provided
        $seatSelections = $request->input('seat_selections', []);
        if (!empty($seatSelections)) {
            Session::put('seat_selections', $seatSelections);
            \Log::emergency('storeSessionTickets - Seat selections stored in session:', $seatSelections);
            \Log::info('storeSessionTickets - Seat selections format type:', [
                'is_array' => is_array($seatSelections),
                'keys' => array_keys($seatSelections),
                'first_value_type' => !empty($seatSelections) ? gettype(reset($seatSelections)) : 'empty'
            ]);
        } else {
            \Log::emergency('storeSessionTickets - NO seat selections provided in request');
        }

        $venueSeatIds = $this->normalizeVenueSeatIds($request->input('venue_seat_ids', []));
        $sessionId = $request->session()->getId();
        $preserveExistingHold = $request->boolean('preserve_existing_hold');
        $holdResult = ['success' => true, 'hold_expires_at' => null];

        if (!empty($venueSeatIds)) {
            $selectedTicketIds = collect((array) $selectedOptions)
                ->filter(function ($ticketId) {
                    return is_numeric($ticketId) && (int) $ticketId > 0;
                })
                ->map(function ($ticketId) {
                    return (int) $ticketId;
                })
                ->unique()
                ->values()
                ->all();

            $mappedSeatTicketIds = EventVenueSeat::whereIn('id', $venueSeatIds)
                ->whereNotNull('ticket_id')
                ->pluck('ticket_id')
                ->map(function ($ticketId) {
                    return (int) $ticketId;
                })
                ->unique()
                ->values()
                ->all();

            if (!empty($mappedSeatTicketIds) && !empty(array_diff($mappedSeatTicketIds, $selectedTicketIds))) {
                return response()->json([
                    'success' => false,
                    'message' => __('Selected seat does not match the selected ticket.'),
                ], 422);
            }

            $holdResult = DB::transaction(function () use ($venueSeatIds, $sessionId, $preserveExistingHold) {
                EventVenueSeat::expiredHolds()->update([
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                    'hold_token' => null,
                    'held_by_session_id' => null,
                    'held_by_app_user_id' => null,
                    'held_by_guest_user_id' => null,
                    'held_at' => null,
                    'hold_expires_at' => null,
                ]);

                $releaseQuery = EventVenueSeat::where('held_by_session_id', $sessionId)
                    ->where('status', EventVenueSeat::STATUS_HELD);

                if ($preserveExistingHold) {
                    $releaseQuery->whereNotIn('id', $venueSeatIds);
                }

                $releaseQuery->update([
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                    'hold_token' => null,
                    'held_by_session_id' => null,
                    'held_by_app_user_id' => null,
                    'held_by_guest_user_id' => null,
                    'held_at' => null,
                    'hold_expires_at' => null,
                ]);

                $lockedSeats = EventVenueSeat::whereIn('id', $venueSeatIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($lockedSeats->count() !== count($venueSeatIds)) {
                    return ['success' => false];
                }

                $unavailableSeat = $lockedSeats->first(function ($seat) use ($sessionId) {
                    return !(
                        $seat->status === EventVenueSeat::STATUS_AVAILABLE
                        || (
                            $seat->status === EventVenueSeat::STATUS_HELD
                            && $seat->held_by_session_id === $sessionId
                        )
                    );
                });

                if ($unavailableSeat) {
                    return ['success' => false];
                }

                $expiresAt = now()->addMinutes(10);
                if ($preserveExistingHold) {
                    $existingHoldExpiresAt = $lockedSeats
                        ->filter(function ($seat) use ($sessionId) {
                            return $seat->status === EventVenueSeat::STATUS_HELD
                                && $seat->held_by_session_id === $sessionId
                                && $seat->hold_expires_at
                                && $seat->hold_expires_at->isFuture();
                        })
                        ->pluck('hold_expires_at')
                        ->sortBy(function ($expiresAt) {
                            return $expiresAt instanceof Carbon
                                ? $expiresAt->timestamp
                                : Carbon::parse($expiresAt)->timestamp;
                        })
                        ->first();

                    if ($existingHoldExpiresAt) {
                        $expiresAt = $existingHoldExpiresAt instanceof Carbon
                            ? $existingHoldExpiresAt
                            : Carbon::parse($existingHoldExpiresAt);
                    }
                }

                EventVenueSeat::whereIn('id', $venueSeatIds)->update([
                    'status' => EventVenueSeat::STATUS_HELD,
                    'hold_token' => Str::random(40),
                    'held_by_session_id' => $sessionId,
                    'held_by_app_user_id' => Auth::guard('appuser')->id(),
                    'held_at' => now(),
                    'hold_expires_at' => $expiresAt,
                ]);

                return ['success' => true, 'hold_expires_at' => $expiresAt->toIso8601String()];
            });

            if (!$holdResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => __('One or more selected seats are no longer available.'),
                ], 409);
            }

            $venueSeatDetails = $request->input('venue_seat_details', []);
            if (is_string($venueSeatDetails)) {
                $decodedVenueSeatDetails = json_decode($venueSeatDetails, true);
                $venueSeatDetails = json_last_error() === JSON_ERROR_NONE ? $decodedVenueSeatDetails : [];
            }

            if (empty($venueSeatDetails) || count($venueSeatDetails) !== count($venueSeatIds)) {
                $venueSeatDetails = $this->venueSeatDetailsFromIds($venueSeatIds);
            }

            Session::put('venue_seat_ids', $venueSeatIds);
            Session::put('venue_seat_details', is_array($venueSeatDetails) ? $venueSeatDetails : []);
        } elseif ($request->has('venue_seat_ids')) {
            DB::transaction(function () use ($sessionId) {
                EventVenueSeat::expiredHolds()->update([
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                    'hold_token' => null,
                    'held_by_session_id' => null,
                    'held_by_app_user_id' => null,
                    'held_by_guest_user_id' => null,
                    'held_at' => null,
                    'hold_expires_at' => null,
                ]);

                EventVenueSeat::where('held_by_session_id', $sessionId)
                    ->where('status', EventVenueSeat::STATUS_HELD)
                    ->update([
                        'status' => EventVenueSeat::STATUS_AVAILABLE,
                        'hold_token' => null,
                        'held_by_session_id' => null,
                        'held_by_app_user_id' => null,
                        'held_by_guest_user_id' => null,
                        'held_at' => null,
                        'hold_expires_at' => null,
                    ]);
            });

            Session::forget('venue_seat_ids');
            Session::forget('venue_seat_details');
        }

        // Log the request data for debugging
        \Log::info('storeSessionTickets - Full request data:', $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tickets and seat selections stored successfully',
            'hold_expires_at' => $holdResult['hold_expires_at'] ?? null,
        ]);
    }

    public function releaseCheckoutSeatHold(Request $request)
    {
        $eventId = (int) $request->input('event_id');
        $venueSeatIds = $this->normalizeVenueSeatIds($request->input('venue_seat_ids', Session::get('venue_seat_ids', [])));
        $sessionId = $request->session()->getId();
        $requestData = Session::get('request', []);

        $releaseQuery = EventVenueSeat::where('status', EventVenueSeat::STATUS_HELD)
            ->where('held_by_session_id', $sessionId);

        if ($eventId > 0) {
            $releaseQuery->where('event_id', $eventId);
        }

        if (!empty($venueSeatIds)) {
            $releaseQuery->whereIn('id', $venueSeatIds);
        }

        $releaseQuery->update([
            'status' => EventVenueSeat::STATUS_AVAILABLE,
            'hold_token' => null,
            'held_by_session_id' => null,
            'held_by_app_user_id' => null,
            'held_by_guest_user_id' => null,
            'held_at' => null,
            'hold_expires_at' => null,
        ]);

        $event = $eventId > 0 ? Event::find($eventId) : null;
        $hasVenueSeatCheckout = !empty($venueSeatIds)
            || !empty($requestData['selectedVenueSeatIds'] ?? null)
            || !empty(Session::get('venue_seat_ids', []));

        Session::forget(['request', 'multiticketssession', 'seat_selections', 'venue_seat_ids', 'venue_seat_details']);

        return response()->json([
            'success' => true,
            'redirect_url' => $event && $hasVenueSeatCheckout
                ? $this->venueSeatSelectionRedirectUrl($event->id, $requestData)
                : ($event
                    ? route('eventDetail', ['id' => $event->id, 'name' => Str::slug($event->name)])
                    : route('home')),
        ]);
    }

    public function storeSeatSelections(Request $request)
    {
        $seatSelections = $request->input('seat_selections', []);

        if (!empty($seatSelections)) {
            Session::put('seat_selections', $seatSelections);
            \Log::info('storeSeatSelections - Seat selections stored in session:', $seatSelections);

            return response()->json([
                'success' => true,
                'message' => 'Seat selections stored successfully',
                'data' => $seatSelections
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No seat selections provided'
        ]);
    }


    function orderSuccess(){
        $isguest=1;

        // Clear the order completed flag when viewing order success page
        if (Session::has('order_completed')) {
            Session::forget('order_completed');
        }

        if (Session::has('guestorderid')) {
            $orderid=Session::get('guestorderid');
        }else{
            if(Auth::check()){
                return redirect()->route('home');
            }else{
                return redirect()->route('user.login');
            }
        }
        Session::forget('guestorderid');

        // Clear multiticketssession to prevent checkout access
        Session::forget('multiticketssession');

        $order = Order::with(['event', 'ticket', 'organization'])->find($orderid);

        return view('frontend.guestordersuccess', $this->buildOrderSuccessViewData($order, $isguest));
    }

    function ordersuccessuser(Request $request){
        $isguest=0;

        // Clear the order completed flag when viewing order success page
        if (Session::has('order_completed')) {
            Session::forget('order_completed');
        }

        if(isset($request->id)){
            $orderid=$request->id;
        }else{
            if (Session::has('guestorderid')) {
                $orderid=Session::get('guestorderid');
            }else{
                if (Auth::guard('appuser')->check()) {
                    return redirect()->route('home');
                }else{
                    return redirect()->route('user.login');
                }
            }
            Session::forget('guestorderid');
        }

        // Clear multiticketssession to prevent checkout access
        Session::forget('multiticketssession');

        $order = Order::with(['event', 'ticket', 'organization'])->find($orderid);

        return view('frontend.guestordersuccess', $this->buildOrderSuccessViewData($order, $isguest));
    }

    /**
     * Returns just the order-success markup (no site header/nav), used to show the
     * "thank you" celebration inline in the event-detail page's ticket-flow modal.
     */
    public function orderSuccessContentPartial(Request $request, $id)
    {
        $order = Order::with(['event', 'ticket', 'organization'])->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => __('Order not found.')], 404);
        }

        $ownsAsGuest = (string) Session::get('guestorderid') === (string) $id;
        $ownsAsUser = Auth::guard('appuser')->check()
            && $order->customer_id
            && (int) $order->customer_id === (int) Auth::guard('appuser')->id();

        if (!$ownsAsGuest && !$ownsAsUser) {
            return response()->json(['success' => false, 'message' => __('Unable to load this order.')], 403);
        }

        if (Session::has('order_completed')) {
            Session::forget('order_completed');
        }
        if ($ownsAsGuest) {
            Session::forget('guestorderid');
        }
        Session::forget('multiticketssession');

        $isguest = $ownsAsUser ? 0 : 1;

        return response(view('frontend.partials.order-success-content', $this->buildOrderSuccessViewData($order, $isguest))->render());
    }

    private function buildOrderSuccessViewData($order, $isguest)
    {
        $userDetail = null;

        if ($order->customer_id) {
            $userDetail = AppUser::select('email')->find($order->customer_id);
        } elseif ($order->guestuser_id) {
            $userDetail = GuestUser::select('email')->find($order->guestuser_id);
        }
        $taxes_id = OrderTax::where('order_id', $order->id)->get();
        $taxes = [];
        foreach ($taxes_id as $key => $value) {
            $temp_tax[] = Tax::find($value->tax_id);
            $taxes = $temp_tax;
        }
        $review = Review::where('order_id', $order->id)->first();
        $setting = Setting::find(1);

        return compact('order', 'taxes', 'review', 'setting', 'userDetail', 'isguest');
    }

    public function uploadTicketImage(Request $request)
    {
        try {
            $images = $request->input('images'); // Retrieve images array
            $email = $request->input('email');
            $orderId = $request->input('order_id'); // Get order ID for invoice generation
            $storedImages = [];
            $invoicePdfPath = null;

            // Check if this is an organizer-created order (LOCAL/Paid) - skip sending PNG email
            if ($orderId) {
                $order = Order::find($orderId);
                if ($order && in_array($order->payment_type, ['LOCAL', 'Paid'])) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Ticket email already sent for organizer-created order.',
                    ]);
                }
            }

            // Validate email is provided
            if (empty($email)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipient email address is missing. Cannot send ticket.',
                ], 400);
            }

            if ($images && is_array($images)) {
                foreach ($images as $index => $imageData) {
                    // Decode the base64 image
                    $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageData));

                    // Generate a unique file name
                    $fileName = 'ticket_' . time() . "_{$index}.png";

                    // Store the image in the public disk
                    $filePath = "tickets/{$fileName}";
                    Storage::disk('public')->put($filePath, $image);

                    $storedImages[] = Storage::disk('public')->path($filePath); // Store full path for email attachment
                }

                // Generate invoice PDF if order ID provided and payment amount > 0
                if ($orderId) {
                    try {
                        $order = Order::with(['event', 'organization', 'ticket', 'appUser', 'guestUser'])->find($orderId);
                        if ($order && $order->payment > 0) {
                            $order->tax_data = OrderTax::where('order_id', $order->id)->get();
                            $order->ticket_data = OrderChild::where('order_id', $order->id)->get();
                            $customPaper = array(0, 0, 720, 1440);
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ticketmail', compact('order'))
                                ->setPaper($customPaper, 'portrait');

                            $invoiceFileName = 'invoice_' . time() . '.pdf';
                            $invoicePdfPath = storage_path('app/public/tickets/' . $invoiceFileName);

                            // Ensure directory exists
                            if (!file_exists(dirname($invoicePdfPath))) {
                                mkdir(dirname($invoicePdfPath), 0777, true);
                            }

                            file_put_contents($invoicePdfPath, $pdf->output());
                            $storedImages[] = $invoicePdfPath; // Add invoice to attachments
                            Log::info('Invoice PDF generated for paid order: ' . $orderId);
                        } elseif ($order && $order->payment == 0) {
                            Log::info('Skipped invoice PDF generation for free order: ' . $orderId);
                        }
                    } catch (\Exception $pdfException) {
                        Log::error('Error generating invoice PDF: ' . $pdfException->getMessage());
                    }
                }

                // Send email with all attachments (ticket PNGs + invoice PDF)
                try {
                    $this->sendEmailWithAttachments($storedImages, $email);
                    $emailSent = true;
                    $emailError = null;
                } catch (\Exception $mailException) {
                    $emailSent = false;
                    $emailError = $mailException->getMessage();
                    Log::error('Error sending email with ticket attachments: ' . $mailException->getMessage(), [
                        'trace' => $mailException->getTraceAsString()
                    ]);
                }

                // Delete images after attempting to send email
                foreach ($storedImages as $path) {
                    if (file_exists($path)) {
                        unlink($path); // Delete the file
                    }
                }

                if ($emailSent) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Ticket sent via email successfully!',
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to send email. Please check your email settings. Error: ' . $emailError,
                    ], 500);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'No images provided!',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error uploading ticket images: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing images: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send an email with attachments.
     *
     * @param array $attachments
     * @param string $email
     * @return void
     * @throws \Exception
     */
    protected function sendEmailWithAttachments(array $attachments, $email = "")
    {
        try {
            // Validate that a valid email address is provided
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid or missing recipient email address.');
            }

            $data["email"] = $email;
            $data["title"] = "Event Tickets | The Event Palette";
            $data["body"] = "";
            $sender = Setting::select('sender_email', 'app_name')->first();

            if (!$sender || !$sender->sender_email) {
                throw new \Exception('Email sender configuration is missing. Please configure email settings in admin panel.');
            }

            Mail::send('qrTicketmail', $data, function ($message) use ($data, $attachments, $sender) {
                $message->from($sender->sender_email, $sender->app_name)
                    ->to($data["email"])
                    ->subject($data["title"]);

                // Attach each file
                foreach ($attachments as $file) {
                    if (file_exists($file)) {
                        $message->attach($file);
                    }
                }
            });

            // Check if mail failed
            if (count(Mail::failures()) > 0) {
                throw new \Exception('Failed to send email to: ' . implode(', ', Mail::failures()));
            }
        } catch (\Exception $e) {
            Log::error('Email sending failed in sendEmailWithAttachments: ' . $e->getMessage(), [
                'email' => $email ?? 'not provided',
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handles the Stripe payment success process.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function stripeSuccess()
    {
        try {
            $request = Session::get('request');
            $ticketIds = explode(',', $request['ticket_id']);
            $setting = Setting::first();
            $tickets = Ticket::whereIn('id', $ticketIds)->get();
            $event = Event::findOrFail(trim($request['event_id']));
            $organization = User::findOrFail($event->user_id);
            $venueSeatIds = $this->normalizeVenueSeatIds($request['selectedVenueSeatIds'] ?? Session::get('venue_seat_ids', []));

            if (!empty($venueSeatIds)) {
                $this->releaseExpiredVenueSeatHolds($event->id);

                if (!$this->hasActiveVenueSeatHold($event->id, $venueSeatIds, session()->getId())) {
                    Session::forget(['request', 'venue_seat_ids', 'venue_seat_details']);

                    return redirect($this->venueSeatSelectionRedirectUrl($event->id, $request))
                        ->with('error', __('Your seat hold has expired. Please select seats again.'));
                }
            }

            $user = $this->handleUser($request);
            $couponData=$this->updateCouponUsage($request, $user);
            $orderData = $this->prepareOrderData($request, $event, $organization, $user,$couponData,$setting);
            $order = Order::create($orderData);

            // Store Stripe transaction from checkout session
            if ($order->payment_type == 'STRIPE' && $order->payment_token) {
                $this->storeStripeTransactionFromSession($order);
            }

            $this->processSeatMap($request);
            $this->createOrderChildren($request, $order,$tickets);
            $this->attachVenueSeatsToOrder($order, $event, $venueSeatIds);
            $this->createOrderTaxes($request, $order);
            $this->createOrderFees($order);
            $this->sendNotifications($order, $user,$event,$setting);
            $this->handleTicketBookingNotifications($order, $user, $request,$event,$setting);

            // Clear all session data including seat selections after successful order creation
            Session::forget(['request', 'multiticketssession', 'seat_selections', 'venue_seat_ids', 'venue_seat_details']);
            return $this->handleGuestOrder($order, $event);
            // return $user instanceof AppUser
            //     ? redirect()->route('myTickets')
            //     : $this->handleGuestOrder($order);
        } catch (\Throwable $e) {
            Log::error("Error in stripeSuccess: " . $e->getMessage());
        }
    }


    /**
     * Handle user (Guest or Authenticated) processing.
     *
     * @param array $request
     * @return mixed
     */
    private function handleUser($request)
    {
        try {
            if (Auth::guard('appuser')->check()) {
                return AppUser::find(Auth::guard('appuser')->user()->id);
            }

            return GuestUser::create([
                'name' => $request['guest_first_name'],
                'last_name' => $request['guest_last_name'],
                'email' => $request['guest_email'],
                'phone' => $request['guest_phone'],
            ]);
        } catch (\Exception $e) {
            Log::error("Error in handleUser: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare order data for insertion.
     *
     * @param array $request
     * @param Event $event
     * @param User $organization
     * @param mixed $user
     * @return array
     */
    private function prepareOrderData($request, $event, $organization, $user,$couponData,$commissionSetting)
    {
        try {
            $venueSeatIds = $this->normalizeVenueSeatIds($request['selectedVenueSeatIds'] ?? Session::get('venue_seat_ids', []));
            $venueSeatDetails = $request['selectedVenueSeats'] ?? json_encode(Session::get('venue_seat_details', []));
            $paymentWithoutTax = $request['payment'] - $request['tax'];
            $commission = $request['payment_type'] === 'FREE'
                ? 0
                : ($commissionSetting->org_commission_type === 'percentage'
                    ? $paymentWithoutTax * $commissionSetting->org_commission / 100
                    : $commissionSetting->org_commission);

            return [
                'order_id' => '#' . rand(9999, 100000),
                'event_id' => $event->id,
                'organization_id' => $organization->id,
                'customer_id' => $user instanceof AppUser ? $user->id : null,
                'guestuser_id' => $user instanceof GuestUser ? $user->id : null,
                'order_status' => 'Complete',
                'payment_type' => 'STRIPE',
                'payment_token' => Session::get('payment_token'),
                'payment' => $request['payment'],
                'tax' => $request['tax'],
                'coupon_id' => $couponData->id ?? null,
                'coupon_discount' => $couponData->discount ?? 0,
                'admin_revenue' => $request['admin_revenue'],
                'org_revenue' => $request['org_revenue'],
                'org_commission' => $commission,
                'quantity' => $request['quantity'],
                'ticket_id' => $request['ticket_id'],
                'payment_status' => 1,
                'book_seats' => !empty($venueSeatIds) ? implode(',', $venueSeatIds) : ($request['selectedSeatsId'] ?? null),
                'seat_details' => !empty($venueSeatIds) ? $venueSeatDetails : ($request['selectedSeats'] ?? null),
            ];
        } catch (\Exception $e) {
            Log::error("Error in prepareOrderData: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates coupon usage if a coupon was applied.
     */
    private function updateCouponUsage($request, $user)
    {
        if (isset($request['coupon_code'])) {
            $coupon = Coupon::findOrFail($request['coupon_code']);
            $coupon->increment('use_count');
            CouponUsageHistory::create([
                'coupon_id' => $request['coupon_code'],
                'appuser_id' => $user instanceof AppUser ? $user->id : null,
                'guestuser_id' => $user instanceof GuestUser ? $user->id : null,
            ]);
            return $coupon;
        }
    }

    /**
     * Processes the seat map if the seatmap module is enabled.
     */
    private function processSeatMap($request)
    {
        $module = Module::where('module', 'Seatmap')->first();
        if ($module->is_enable && $module->is_install && !empty($request['book_seats'])) {
            foreach (explode(',', $request['book_seats']) as $seatId) {
                Seats::find($seatId)?->update(['type' => 'occupied']);
            }
        }
    }

    /**
     * Stores Stripe transaction details for an order.
     */
    private function storeStripeTransaction($order)
    {
        try {
            $paymentSetting = PaymentSetting::first();

            if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
                \Log::error('Stripe secret key not configured');
                return;
            }

            if (Str::startsWith((string) $order->payment_token, 'cs_')) {
                $this->storeStripeTransactionFromSession($order);
                return;
            }

            $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
            $paymentIntent = $this->retrievePaymentIntentWithBalanceDetails($stripe, $order->payment_token);
            $this->upsertStripeTransactionFromPaymentIntent($order, $paymentIntent, $stripe);

            \Log::info("Stripe transaction stored for order #{$order->order_id}");
        } catch (\Exception $e) {
            \Log::error('Failed to store Stripe transaction: ' . $e->getMessage());
        }
    }

    /**
     * Stores Stripe transaction directly from PaymentIntent object.
     */
    private function storeStripeTransactionDirect($order, $paymentIntent)
    {
        try {
            $paymentSetting = PaymentSetting::first();

            if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
                \Log::error('Stripe secret key not configured');
                return;
            }

            $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
            if (!empty($paymentIntent->id)) {
                $paymentIntent = $this->retrievePaymentIntentWithBalanceDetails($stripe, $paymentIntent->id);
            }
            $this->upsertStripeTransactionFromPaymentIntent($order, $paymentIntent, $stripe);

            \Log::info("Stripe transaction stored directly for order #{$order->order_id}");
        } catch (\Exception $e) {
            \Log::error('Failed to store Stripe transaction directly: ' . $e->getMessage());
        }
    }

    /**
     * Stores Stripe transaction from Checkout Session.
     */
    private function storeStripeTransactionFromSession($order)
    {
        try {
            $paymentSetting = PaymentSetting::first();

            if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
                \Log::error('Stripe secret key not configured');
                return;
            }

            $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);

            // Retrieve the checkout session
            $session = $stripe->checkout->sessions->retrieve($order->payment_token);

            // Get the payment intent ID from the session
            if (!$session->payment_intent) {
                \Log::error("No payment intent found in checkout session for order #{$order->order_id}");
                return;
            }

            // Retrieve the payment intent
            $paymentIntentId = is_object($session->payment_intent)
                ? ($session->payment_intent->id ?? null)
                : $session->payment_intent;
            $paymentIntent = $this->retrievePaymentIntentWithBalanceDetails($stripe, $paymentIntentId);
            $this->upsertStripeTransactionFromPaymentIntent($order, $paymentIntent, $stripe);

            \Log::info("Stripe transaction stored from session for order #{$order->order_id}");
        } catch (\Exception $e) {
            \Log::error('Failed to store Stripe transaction from session: ' . $e->getMessage());
        }
    }

    private function upsertStripeTransactionFromPaymentIntent($order, $paymentIntent, \Stripe\StripeClient $stripe): StripeTransaction
    {
        $balanceDetails = $this->resolveStripeBalanceTransactionDetails($stripe, $paymentIntent, $order);
        $existing = StripeTransaction::where('payment_id', $paymentIntent->id)->first();
        $fullResponse = method_exists($paymentIntent, 'toArray')
            ? $paymentIntent->toArray()
            : json_decode(json_encode($paymentIntent), true);

        if (!empty($balanceDetails['balance_transaction_response'])) {
            $fullResponse['resolved_balance_transaction'] = $balanceDetails['balance_transaction_response'];
        }

        return StripeTransaction::updateOrCreate(
            ['payment_id' => $paymentIntent->id],
            [
                'order_id' => $order->id,
                'donation_id' => null,
                'amount' => $paymentIntent->amount / 100,
                'client_secret' => $paymentIntent->client_secret,
                'currency' => $paymentIntent->currency,
                'latest_charge' => $balanceDetails['latest_charge'] ?? $existing?->latest_charge,
                'txn_id' => $balanceDetails['txn_id'] ?? $existing?->txn_id,
                'tax_amount' => $balanceDetails['tax_amount'] ?? $existing?->tax_amount,
                'payment_method_types' => $paymentIntent->payment_method_types ?? [],
                'status' => $paymentIntent->status,
                'full_response' => $fullResponse,
            ]
        );
    }

    private function retrievePaymentIntentWithBalanceDetails(\Stripe\StripeClient $stripe, ?string $paymentIntentId)
    {
        $paymentIntent = null;

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId, [
                'expand' => ['latest_charge.balance_transaction'],
            ]);

            $latestCharge = $paymentIntent->latest_charge ?? null;
            $balanceTransaction = is_object($latestCharge)
                ? ($latestCharge->balance_transaction ?? null)
                : null;

            if ($latestCharge && $balanceTransaction) {
                return $paymentIntent;
            }

            if ($attempt < 4) {
                sleep(2);
            }
        }

        return $paymentIntent;
    }

    private function resolveStripeBalanceTransactionDetails(\Stripe\StripeClient $stripe, $paymentIntent, $order): array
    {
        $latestCharge = $paymentIntent->latest_charge ?? null;
        $latestChargeId = is_object($latestCharge) ? ($latestCharge->id ?? null) : $latestCharge;
        $txnId = null;
        $taxAmount = null;
        $balanceTransactionResponse = null;

        if (!$latestChargeId) {
            return [
                'latest_charge' => null,
                'txn_id' => null,
                'tax_amount' => null,
                'balance_transaction_response' => null,
            ];
        }

        try {
            $charge = is_object($latestCharge) && isset($latestCharge->balance_transaction)
                ? $latestCharge
                : $stripe->charges->retrieve($latestChargeId, [
                    'expand' => ['balance_transaction'],
                ]);

            $balanceTransaction = $charge->balance_transaction ?? null;
            $balanceTransactionId = is_object($balanceTransaction)
                ? ($balanceTransaction->id ?? null)
                : $balanceTransaction;

            if ($balanceTransactionId) {
                $resolvedBalanceTransaction = is_object($balanceTransaction) && isset($balanceTransaction->fee)
                    ? $balanceTransaction
                    : $stripe->balanceTransactions->retrieve($balanceTransactionId, []);

                $txnId = $resolvedBalanceTransaction->id ?? $balanceTransactionId;
                $taxAmount = isset($resolvedBalanceTransaction->fee)
                    ? $resolvedBalanceTransaction->fee / 100
                    : null;
                $balanceTransactionResponse = method_exists($resolvedBalanceTransaction, 'toArray')
                    ? $resolvedBalanceTransaction->toArray()
                    : json_decode(json_encode($resolvedBalanceTransaction), true);
            }
        } catch (\Exception $e) {
            \Log::warning("Could not retrieve charge/balance transaction for order #{$order->id}: " . $e->getMessage());
        }

        return [
            'latest_charge' => $latestChargeId,
            'txn_id' => $txnId,
            'tax_amount' => $taxAmount,
            'balance_transaction_response' => $balanceTransactionResponse,
        ];
    }

    /**
     * Creates order child records for tickets.
     */
    private function createOrderChildren($request, $order,$tickets)
    {
        $ticketQty = json_decode($request['ticketqty'], true);
        $totalQuantity = 0;

        // Get seat selections from session (same logic as createOrder method)
        $seatSelections = Session::get('seat_selections', []);
        $hasSeatSelection = !empty($seatSelections);

        // Debug logging to check seat selections
        \Log::info('CreateOrderChildren Debug - Seat Selections:', [
            'seatSelections' => $seatSelections,
            'hasSeatSelection' => $hasSeatSelection,
            'order_id' => $order->id
        ]);

        // Determine user field based on user type
        $userField = !empty(Auth::guard('appuser')->user()->id) ? 'customer_id' : 'guestuser_id';
        $userId = !empty(Auth::guard('appuser')->user()->id) ? Auth::guard('appuser')->user()->id : $order->guestuser_id;

        foreach ($tickets as $tc) {
            $quantity = $ticketQty['ticket_qty' . $tc->id] ?? 0;
            $totalQuantity += $quantity;

            // Check if this ticket has a seat selection
            if ($hasSeatSelection && isset($seatSelections[$tc->id])) {
                $seatData = $seatSelections[$tc->id];

                // Handle both old format (direct ID) and new format (array with seat_table_id and sponser_id)
                if (is_array($seatData)) {
                    $seatId = $seatData['seat_table_id'];
                    $sponsershipId = $seatData['sponser_id'];
                } else {
                    // Fallback for old format
                    $seatId = $seatData;
                    $sponsershipId = null;
                }

                $seat = \App\Models\SeatTable::find($seatId);

                if ($seat) {
                    $prefixname = $seat->prefixname;

                    // Get the last seat number used for this prefix
                    $lastNumber = OrderChild::where('Book_Seat_Id', 'like', $seat->prefixname.'_%')
                        ->get()
                        ->map(function ($orderChild) use ($seat) {
                            return (int) str_replace($seat->prefixname.'_', '', $orderChild->Book_Seat_Id);
                        })
                        ->max();

                    $currentNumber = ($lastNumber !== null) ? $lastNumber + 1 : 1;

                    for ($i = 1; $i <= $quantity; $i++) {
                        $ticketNumber = uniqid();

                        $childData = [
                            'ticket_number' => $ticketNumber,
                            'ticket_id' => $tc->id,
                            'order_id' => $order->id,
                            'checkin' => $tc->maximum_checkins ?? null,
                            'paid' => 1,
                            $userField => $userId,
                            'seat_id' => $seat->id, // Store the seat reference
                            'Book_Seat_Id' => $seat->prefixname.'_'.$currentNumber, // e.g. "sachin_1"
                            'SeatDetails_id' => $sponsershipId ?? $seat->sponsership_id // From seat table
                        ];

                        OrderChild::create($childData);
                        $currentNumber++;
                    }
                } else {
                    // Handle case where seat table not found - create without seat info
                    for ($i = 1; $i <= $quantity; $i++) {
                        $childData = [
                            'ticket_number' => uniqid(),
                            'ticket_id' => $tc->id,
                            'order_id' => $order->id,
                            'checkin' => $tc->maximum_checkins ?? null,
                            'paid' => 1,
                            $userField => $userId,
                            'seat_id' => null,
                            'Book_Seat_Id' => null,
                            'SeatDetails_id' => null
                        ];
                        OrderChild::create($childData);
                    }
                }
            } else {
                // Handle normal booking without seats - explicitly set seat fields to null
                for ($i = 1; $i <= $quantity; $i++) {
                    $childData = [
                        'ticket_number' => uniqid(),
                        'ticket_id' => $tc->id,
                        'order_id' => $order->id,
                        'checkin' => $tc->maximum_checkins ?? null,
                        'paid' => 1,
                        $userField => $userId,
                        'seat_id' => null,
                        'Book_Seat_Id' => null,
                        'SeatDetails_id' => null
                    ];
                    OrderChild::create($childData);
                }
            }
        }

        // Update order quantity
        $order->update(['quantity' => (string) $totalQuantity]);
    }

    private function normalizeVenueSeatIds($seatIds)
    {
        if (is_string($seatIds)) {
            $decoded = json_decode($seatIds, true);
            $seatIds = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $seatIds);
        }

        if (!is_array($seatIds)) {
            return [];
        }

        return collect($seatIds)
            ->map(function ($seatId) {
                if (is_array($seatId)) {
                    return $seatId['id'] ?? $seatId['seat_id'] ?? null;
                }

                return $seatId;
            })
            ->filter(function ($seatId) {
                return is_numeric($seatId) && (int) $seatId > 0;
            })
            ->map(function ($seatId) {
                return (int) $seatId;
            })
            ->unique()
            ->values()
            ->all();
    }

    private function resolveCheckoutVenueSeatIds(Request $request, $eventId): array
    {
        $venueSeatIds = $this->normalizeVenueSeatIds($request->selectedVenueSeatIds ?: Session::get('venue_seat_ids', []));

        if (!empty($venueSeatIds)) {
            return $venueSeatIds;
        }

        if (!$eventId) {
            return [];
        }

        $venueSeatIds = EventVenueSeat::where('event_id', $eventId)
            ->where('status', EventVenueSeat::STATUS_HELD)
            ->where('held_by_session_id', $request->session()->getId())
            ->where('hold_expires_at', '>', now())
            ->orderBy('id')
            ->pluck('id')
            ->map(function ($seatId) {
                return (int) $seatId;
            })
            ->values()
            ->all();

        if (!empty($venueSeatIds)) {
            Session::put('venue_seat_ids', $venueSeatIds);
        }

        return $venueSeatIds;
    }

    private function venueSeatDetailsFromIds(array $venueSeatIds, $eventId = null)
    {
        if (empty($venueSeatIds)) {
            return [];
        }

        $query = EventVenueSeat::whereIn('id', $venueSeatIds);

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $seats = $query->get()->keyBy('id');

        return collect($venueSeatIds)
            ->map(function ($venueSeatId) use ($seats) {
                $seat = $seats->get($venueSeatId);

                if (!$seat) {
                    return null;
                }

                $sectionName = $seat->section_name ?: 'Section';
                $rowName = $seat->row_name ?: 'Row';
                $seatLabel = $seat->seat_label ?: trim($sectionName . ' ' . $rowName . ' Seat ' . $seat->seat_number);

                return [
                    'id' => (int) $seat->id,
                    'label' => $seatLabel,
                    'section' => $sectionName,
                    'section_name' => $sectionName,
                    'row' => $rowName,
                    'row_name' => $rowName,
                    'seat_number' => $seat->seat_number,
                    'ticket_id' => $seat->ticket_id ? (int) $seat->ticket_id : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function attachVenueSeatsToOrder($order, $event, array $venueSeatIds)
    {
        if (empty($venueSeatIds)) {
            return;
        }

        $this->releaseExpiredVenueSeatHolds($event->id);

        $orderChildren = OrderChild::where('order_id', $order->id)
            ->orderBy('id')
            ->take(count($venueSeatIds))
            ->get();

        foreach ($venueSeatIds as $index => $venueSeatId) {
            $seat = EventVenueSeat::where('event_id', $event->id)->find($venueSeatId);

            if (!$seat || $seat->status !== EventVenueSeat::STATUS_HELD) {
                continue;
            }

            if ($seat->held_by_session_id !== session()->getId() || $seat->isHoldExpired()) {
                continue;
            }

            $orderChild = $orderChildren->get($index);
            $seatLabel = $seat->seat_label ?: trim($seat->section_name . ' ' . $seat->row_name . ' Seat ' . $seat->seat_number);

            $seat->update([
                'status' => EventVenueSeat::STATUS_BOOKED,
                'booked_order_id' => $order->id,
                'booked_order_child_id' => $orderChild ? $orderChild->id : null,
                'booked_at' => now(),
                'hold_token' => null,
                'held_by_session_id' => null,
                'held_by_app_user_id' => null,
                'held_by_guest_user_id' => null,
                'held_at' => null,
                'hold_expires_at' => null,
            ]);

            if ($orderChild && Schema::hasColumn('order_child', 'event_venue_seat_id')) {
                $orderChild->update([
                    'event_venue_seat_id' => $seat->id,
                    'Book_Seat_Id' => $seatLabel,
                ]);
            }
        }
    }

    private function syncCheckoutVenueSeatHoldsAfterLogin($previousSessionId)
    {
        $currentSessionId = session()->getId();

        if (!$previousSessionId || $previousSessionId === $currentSessionId) {
            return;
        }

        $venueSeatIds = $this->normalizeVenueSeatIds(Session::get('venue_seat_ids', []));

        if (empty($venueSeatIds)) {
            $venueSeatIds = EventVenueSeat::where('status', EventVenueSeat::STATUS_HELD)
                ->where('held_by_session_id', $previousSessionId)
                ->where('hold_expires_at', '>', now())
                ->orderBy('id')
                ->pluck('id')
                ->map(function ($seatId) {
                    return (int) $seatId;
                })
                ->values()
                ->all();

            if (empty($venueSeatIds)) {
                return;
            }
        }

        EventVenueSeat::whereIn('id', $venueSeatIds)
            ->where('status', EventVenueSeat::STATUS_HELD)
            ->where('held_by_session_id', $previousSessionId)
            ->where('hold_expires_at', '>', now())
            ->update([
                'held_by_session_id' => $currentSessionId,
                'held_by_app_user_id' => Auth::guard('appuser')->id(),
            ]);

        Session::put('venue_seat_ids', $venueSeatIds);

        $venueSeatDetails = $this->venueSeatDetailsFromIds($venueSeatIds);
        if (!empty($venueSeatDetails)) {
            Session::put('venue_seat_details', $venueSeatDetails);
        }
    }

    private function hasActiveVenueSeatHold($eventId, array $venueSeatIds, $sessionId)
    {
        if (empty($venueSeatIds)) {
            return false;
        }

        $activeHeldSeats = EventVenueSeat::where('event_id', $eventId)
            ->whereIn('id', $venueSeatIds)
            ->where('status', EventVenueSeat::STATUS_HELD)
            ->where('held_by_session_id', $sessionId)
            ->where('hold_expires_at', '>', now())
            ->count();

        return $activeHeldSeats === count($venueSeatIds);
    }

    private function releaseExpiredVenueSeatHolds($eventId = null)
    {
        $query = EventVenueSeat::expiredHolds();

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $query->update([
            'status' => EventVenueSeat::STATUS_AVAILABLE,
            'hold_token' => null,
            'held_by_session_id' => null,
            'held_by_app_user_id' => null,
            'held_by_guest_user_id' => null,
            'held_at' => null,
            'hold_expires_at' => null,
        ]);
    }

    /**
     * Creates order tax records.
     */
    private function createOrderTaxes($request, $order)
    {
        if (isset($request['tax_data'])) {
            foreach (json_decode($request['tax_data']) as $tax) {
                OrderTax::create([
                    'order_id' => $order->id,
                    'tax_id' => $tax->id,
                    'price' => $tax->price,
                ]);
            }
        }
    }


    /**
     * Sends notifications to the user and organizer.
     */
    private function sendNotifications($order, $appUser,$event,$setting)
    {
        if (!empty(Auth::guard('appuser')->user()->id)) {
            $notificationTemplate = NotificationTemplate::where('title', 'Book Ticket')->first();
            if (!$notificationTemplate) {
                Log::error("Notification template 'Book Ticket' not found.");
                return;
            }

            $messageContent = $notificationTemplate->message_content;
            $notificationDetails = [
                'user_name' => "{$appUser->name} {$appUser->last_name}",
                'quantity' => $order->quantity ?? 0,
                'event_name' => $event->name ?? 'N/A',
                'date' => optional($event->start_time)->format('d F Y h:i a') ?? 'N/A',
                'app_name' => $setting->app_name,
            ];

            $message = str_replace(
                ["{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"],
                $notificationDetails,
                $messageContent
            );

            $notificationData = [
                'organizer_id' => null,
                'user_id' => $appUser->id,
                'order_id' => $order->id,
                'title' => 'Ticket Booked',
                'message' => $message,
            ];
            Notification::create($notificationData);

            if ($setting->push_notification && $appUser->device_token) {
                (new AppHelper)->sendOneSignal('user', $appUser->device_token, $message);
            }
        }
    }

    /**
     * Handles sending emails and notifications for ticket booking.
     */
    public function handleTicketBookingNotifications($order, $user, $request,$event,$setting)
    {
        Log::info('handleTicketBookingNotifications CALLED', [
            'order_id' => $order->id,
            'user_email' => $user->email ?? 'no email',
            'event_id' => $event->id ?? 'no event',
        ]);

        $organizer = User::find($order->organization_id);

        if (!$event || !$setting || !$organizer) {
            Log::error('Missing necessary data for ticket booking notifications.', [
                'has_event' => $event ? true : false,
                'has_setting' => $setting ? true : false,
                'has_organizer' => $organizer ? true : false,
            ]);
            return;
        }

        $templates = NotificationTemplate::whereIn('title', ['Book Ticket', 'Organizer Book Ticket'])->get()->keyBy('title');

        Log::info('Templates fetched', [
            'book_ticket_exists' => $templates->has('Book Ticket'),
            'organizer_ticket_exists' => $templates->has('Organizer Book Ticket'),
        ]);

        // Send user email with invoice PDF and ticket PNGs immediately
        $this->sendUserMailWithAttachments($order, $user, $request, $event, $setting, $templates->get('Book Ticket'));
        $this->sendOrganizerNotification($order, $user, $request, $event, $setting, $organizer, $templates->get('Organizer Book Ticket'));
        $this->sendOrganizerMail($order, $user, $request, $event, $setting, $organizer, $templates->get('Organizer Book Ticket'));
    }

    /**
     * Sends a ticket booking email to the user with invoice PDF and ticket PNGs.
     */
    private function sendUserMailWithAttachments($order, $user, $request, $event, $setting, $template)
    {
        Log::emergency('>>>>> sendUserMailWithAttachments METHOD CALLED <<<<<', [
            'order_id' => $order->id,
            'user_email' => $user->email ?? 'no email'
        ]);

        Log::info('sendUserMailWithAttachments called', [
            'mail_notification' => $setting->mail_notification,
            'template_exists' => $template ? true : false,
            'user_email' => $user->email ?? 'no email',
            'order_id' => $order->id
        ]);

        if ($setting->mail_notification && $template) {
            $details = [
                'user_name' => "{$user->name} {$user->last_name}",
                'quantity' => $request['quantity'],
                'event_name' => $event->name,
                'date' => $event->start_time->format('d F Y h:i a'),
                'app_name' => $setting->app_name,
            ];

            try {
                Log::info('=== Starting PDF Generation for Order: ' . $order->id . ' ===');

                // Load order data
                Log::info('Step 1: Loading order data');
                $order->tax_data = OrderTax::where('order_id', $order->id)->get();
                $order->ticket_data = OrderChild::where('order_id', $order->id)->get();
                Log::info('Found ' . $order->ticket_data->count() . ' tickets in order');

                // Generate invoice PDF only if payment amount is greater than 0
                $invoicePdfPath = null;
                if ($order->payment > 0) {
                    Log::info('Step 2: Generating invoice PDF (Paid Order - Amount: ' . $order->payment . ')');
                    $customPaper = array(0, 0, 720, 1440);
                    $invoicePdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ticketmail', compact('order'))
                        ->setPaper($customPaper, 'portrait');
                    $invoiceFileName = 'invoice_' . time() . '.pdf';
                    $invoicePdfPath = storage_path('app/public/tickets/' . $invoiceFileName);
                    $invoicePdf->save($invoicePdfPath);

                    $invoiceExists = file_exists($invoicePdfPath);
                    $invoiceSize = $invoiceExists ? filesize($invoicePdfPath) : 0;
                    Log::info('Invoice PDF Generated', [
                        'path' => $invoicePdfPath,
                        'exists' => $invoiceExists,
                        'size_bytes' => $invoiceSize
                    ]);
                } else {
                    Log::info('Step 2: Skipping invoice PDF generation (Free Order - Amount: 0)');
                }

                // Generate ticket PDFs for each ticket
                Log::info('Step 3: Generating ticket PDFs...');
                $ticketPaths = [];
                foreach ($order->ticket_data as $index => $ticketChild) {
                    $ticketFileName = 'ticket_' . $ticketChild->ticket_number . '_' . time() . '_' . $index . '.pdf';
                    $ticketPath = storage_path('app/public/tickets/' . $ticketFileName);

                    Log::info('Generating ticket ' . ($index + 1) . ' for ticket_number: ' . $ticketChild->ticket_number);

                    // Generate ticket PDF with full design
                    $ticketData = [
                        'order' => $order,
                        'ticket' => $ticketChild,
                        'event' => $order->event,
                        'organization' => $order->organization,
                        'setting' => $setting
                    ];

                    try {
                        $ticketPaper = array(0, 0, 320, 450);
                        $ticketPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ticket_pdf', $ticketData)
                            ->setPaper($ticketPaper, 'portrait');
                        $ticketPdf->save($ticketPath);

                        $ticketExists = file_exists($ticketPath);
                        $ticketSize = $ticketExists ? filesize($ticketPath) : 0;

                        Log::info('Ticket PDF ' . ($index + 1) . ' Generated', [
                            'path' => $ticketPath,
                            'exists' => $ticketExists,
                            'size_bytes' => $ticketSize
                        ]);

                        if ($ticketExists) {
                            $ticketPaths[] = $ticketPath;
                        } else {
                            Log::error('Ticket PDF file not found after save: ' . $ticketPath);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error generating ticket PDF ' . ($index + 1) . ': ' . $e->getMessage());
                    }
                }

                Log::info('Step 4: Total ticket PDFs generated: ' . count($ticketPaths) . ' out of ' . $order->ticket_data->count());

                // Merge all attachments (include invoice only if it exists)
                $allAttachments = $ticketPaths;
                if ($invoicePdfPath && file_exists($invoicePdfPath)) {
                    array_unshift($allAttachments, $invoicePdfPath); // Add invoice at the beginning
                }
                
                Log::info('Step 5: Preparing to send email', [
                    'total_attachments' => count($allAttachments),
                    'invoice_pdf' => $invoicePdfPath ?? 'none (free order)',
                    'ticket_pdfs' => $ticketPaths,
                    'recipient' => $user->email
                ]);

                // Verify all files exist before sending
                $missingFiles = [];
                foreach ($allAttachments as $filePath) {
                    if (!file_exists($filePath)) {
                        $missingFiles[] = $filePath;
                    }
                }

                if (!empty($missingFiles)) {
                    Log::error('Missing attachment files: ' . implode(', ', $missingFiles));
                }

                // Send email with all attachments (correct parameter order)
                Log::info('Step 6: Sending email with attachments...');
                $this->sendEmailWithAttachments($allAttachments, $user->email);
                Log::info('Step 7: Email sent successfully!');

                // Clean up temporary files
                Log::info('Step 8: Cleaning up temporary files...');
                if ($invoicePdfPath && file_exists($invoicePdfPath)) {
                    @unlink($invoicePdfPath);
                }
                foreach ($ticketPaths as $path) {
                    @unlink($path);
                }

                Log::info('=== PDF Generation and Email Complete for Order: ' . $order->id . ' ===');
            } catch (\Throwable $th) {
                Log::error('sendUserMailWithAttachments FATAL ERROR: ' . $th->getMessage(), [
                    'trace' => $th->getTraceAsString()
                ]);
            }
        }
    }

    /**
     * Sends a ticket booking email to the user with PDF attachment.
     */
    private function sendUserMail($order, $user, $request, $event, $setting, $template)
    {
        Log::info('sendUserMail called', [
            'mail_notification' => $setting->mail_notification,
            'template_exists' => $template ? true : false,
            'user_email' => $user->email ?? 'no email',
            'order_id' => $order->id
        ]);

        if ($setting->mail_notification && $template) {
            $details = [
                'user_name' => "{$user->name} {$user->last_name}",
                'quantity' => $request['quantity'],
                'event_name' => $event->name,
                'date' => $event->start_time->format('d F Y h:i a'),
                'app_name' => $setting->app_name,
            ];

            try {
                $qrcode = $order->order_id;

                // Invoice PDF will be sent with ticket images after page load
                $pdfData = null;

                Log::info('Sending TicketBook email (without PDF) to: ' . $user->email);
                Mail::to($user->email)->send(new TicketBook($template->mail_content, $details, $template->subject, $qrcode, $pdfData));

                if (count(Mail::failures()) > 0) {
                    Log::error('TicketBook email failed for: ' . implode(', ', Mail::failures()));
                } else {
                    Log::info('TicketBook order confirmation email sent successfully to: ' . $user->email);
                }
            } catch (\Throwable $th) {
                Log::error('sendUserMail error: ' . $th->getMessage() . ' | Stack: ' . $th->getTraceAsString());
            }
        } else {
            Log::warning('sendUserMail skipped - mail_notification: ' . $setting->mail_notification . ', template: ' . ($template ? 'exists' : 'missing'));
        }
    }

    /**
     * Sends a notification to the organizer about the ticket booking.
     */
    private function sendOrganizerNotification($order, $user, $request, $event, $setting, $organizer, $template)
    {
        if ($template) {
            $notificationDetails = [
                'organizer_name' => "{$organizer->first_name} {$organizer->last_name}",
                'user_name' => "{$user->name} {$user->last_name}",
                'quantity' => $request['quantity'],
                'event_name' => $event->name,
                'date' => $event->start_time->format('d F Y h:i a'),
                'app_name' => $setting->app_name,
            ];

            $message = str_replace(
                ["{{organizer_name}}", "{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"],
                $notificationDetails,
                $template->message_content
            );

            Notification::create([
                'organizer_id' => $organizer->id,
                'user_id' => null,
                'order_id' => $order->id,
                'title' => 'New Ticket Booked',
                'message' => $message,
            ]);

            if ($setting->push_notification && $organizer->device_token) {
                (new AppHelper)->sendOneSignal('organizer', $organizer->device_token, $message);
            }
        }
    }

    /**
     * Sends an email to the organizer about the ticket booking.
     */
    private function sendOrganizerMail($order, $user, $request, $event, $setting, $organizer, $template)
    {
        if ($setting->mail_notification && $template) {
            $details = [
                'organizer_name' => "{$organizer->first_name} {$organizer->last_name}",
                'user_name' => "{$user->name} {$user->last_name}",
                'quantity' => $request['quantity'],
                'event_name' => $event->name,
                'date' => $event->start_time->format('d F Y h:i a'),
                'app_name' => $setting->app_name,
            ];

            try {
                Log::info('Sending organizer mail to: ' . $organizer->email);
                Mail::to($organizer->email)->send(new TicketBookOrg($template->mail_content, $details, $template->subject));
                Log::info('Organizer mail sent successfully');
            } catch (\Throwable $th) {
                Log::error('sendOrganizerMail error: ' . $th->getMessage());
            }
        }
    }

    private function venueSeatSelectionRedirectUrl($eventId, $requestData = []): string
    {
        $ticketIds = [];

        foreach ((array) ($requestData['multitickets'] ?? []) as $ticketId) {
            if (is_numeric($ticketId) && (int) $ticketId > 0) {
                $ticketIds[] = (int) $ticketId;
            }
        }

        if (empty($ticketIds) && !empty($requestData['ticket_id'])) {
            foreach (explode(',', (string) $requestData['ticket_id']) as $ticketId) {
                $ticketId = trim($ticketId);
                if (is_numeric($ticketId) && (int) $ticketId > 0) {
                    $ticketIds[] = (int) $ticketId;
                }
            }
        }

        if (empty($ticketIds)) {
            $ticketIds = EventVenueSeat::where('event_id', $eventId)
                ->whereNotNull('ticket_id')
                ->distinct()
                ->pluck('ticket_id')
                ->map(fn ($ticketId) => (int) $ticketId)
                ->all();
        }

        return route('event.venueSeatSelection', ['id' => $eventId])
            . (!empty($ticketIds) ? '?' . http_build_query(['tickets' => array_values(array_unique($ticketIds))]) : '');
    }

     /**
     * Handles guest order redirection. When the event is known (hosted Stripe Checkout
     * return trip), sends the browser back to the event page with the order id in the
     * query string so eventDetail.blade.php can reopen the ticket-flow modal and show
     * the success celebration inline, instead of landing on a separate page.
     */
    private function handleGuestOrder($order, $event = null)
    {
        Session::put('guestorderid', $order->id);

        if ($event) {
            $eventUrl = route('eventDetail', ['id' => $event->id, 'name' => Str::slug($event->name)]);

            return redirect($eventUrl . '?order_success=' . $order->id);
        }

        if (Auth::guard('appuser')->check()) {
            return redirect()->route('ordersuccessuser');
        }else{
            return redirect()->route('ordersuccess');
        }
    }
    public function deleteUserGoogle(){
        return view('frontend.zdelete_user');
    }

    private function createOrderFees($order)
    {
        $event = \App\Models\Event::find($order->event_id);
        if (!$event) {
            return;
        }

        // Only apply fee logic to new events (created at or after 2026-08-17 00:00:00)
        if ($event->created_at < '2026-08-17 00:00:00') {
            return;
        }

        // Fetch default active fee types
        $defaultFees = \App\Models\FeeType::where('status', 1)->where('is_default', 1)->get();

        // Fetch event-specific fee types configured by admin
        $eventFeeIds = \App\Models\EventFee::where('event_id', $event->id)->pluck('fee_type_id')->toArray();
        $configuredFees = \App\Models\FeeType::where('status', 1)->whereIn('id', $eventFeeIds)->get();

        // Merge them uniquely
        $feesToApply = $defaultFees->merge($configuredFees)->unique('id');

        // Sum up the subtotal of the ticket purchases via OrderChild
        $orderChildren = \App\Models\OrderChild::where('order_id', $order->id)->get();
        $subtotal = 0;
        foreach ($orderChildren as $child) {
            $ticket = \App\Models\Ticket::find($child->ticket_id);
            if ($ticket) {
                $subtotal += $ticket->price;
            }
        }

        $baseSubtotal = max(0, $subtotal - ($order->coupon_discount ?? 0));

        $totalOrderFees = 0;
        foreach ($feesToApply as $fee) {
            if ($fee->amount_type == 'percentage') {
                $amount = ($fee->price * $baseSubtotal) / 100;
            } else {
                $amount = $fee->price;
            }

            \App\Models\OrderFee::create([
                'order_id' => $order->id,
                'fee_type_id' => $fee->id,
                'name' => $fee->name,
                'amount' => $amount,
                'price' => $fee->price,
                'amount_type' => $fee->amount_type,
            ]);

            $totalOrderFees += $amount;
        }

        if ($totalOrderFees > 0) {
            $order->org_revenue = (float)$order->org_revenue - $totalOrderFees;
            $order->save();
        }
    }
}
