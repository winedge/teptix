<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\AppUser;
use App\Models\GuestUser;
use App\Models\EventVenueSeat;
 use App\Models\User;
    use App\Models\Event;
    use App\Models\Banner;
    use App\Models\Video;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderTax;
use App\Models\OrderChild;
use App\Models\PaymentSetting;
use App\Models\Tax;
use App\Models\Feedback;
use App\Models\Currency;
use App\Models\Faq;
use App\Models\Ticket;
use App\Models\TicketAllowUser;
use App\Models\Setting;
use App\Mail\ResetPassword;
use App\Mail\TicketBook;
use App\Mail\TicketBookOrg;
use App\Http\Controllers\AppHelper;
use App\Models\NotificationTemplate;
use App\Models\Notification;
use App\Models\EventReport;
use App\Models\AdminActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Twilio\Rest\Client as Clients;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use App\Support\OrganizerVerificationStatus;
// use Tymon\JWTAuth\Facades\JWTAuth;


class OrganizationApiController extends Controller
{
    // public function organizationLogin(Request $request)
    // {
    // $credentials = $request->only('email', 'password');

    // $request->validate([
    //     'email' => 'required|email',
    //     'password' => 'required',
    // ]);

    // if (!$token = JWTAuth::attempt($credentials)) {
    //     return response()->json([
    //         'msg' => 'Invalid username or password',
    //         'success' => false
    //     ], 401);
    // }

    // $user = Auth::user();

    // // Optional: check role if needed
    // // if (!$user->is_organizer) {
    // //     return response()->json(['msg' => 'Only organizers can login', 'success' => false], 403);
    // // }

    // // Optional: Save device token
    // if ($request->filled('device_token')) {
    //     $user->update(['device_token' => $request->device_token]);
    // }

    // return response()->json([
    //     'msg' => 'Login successfully',
    //     'success' => true,
    //     'data' => [
    //         'user' => $user,
    //         'token' => $token,
    //         'token_type' => 'bearer',
    //         'expires_in' => auth('api')->factory()->getTTL() * 60
    //     ]
    // ], 200);
    // }
    public function organizationLogin(Request $request)
    {
        $request->validate([
            'email' => 'bail|required|email',
            'password' => 'bail|required',
            // 'device_token' => 'bail|required',
        ]);

        $user = array('email' => $request->email, 'password' => $request->password);
        if (Auth::attempt($user)) {
            $user = Auth::user();
            if (Auth::user()) {
                User::findOrFail($user->id)->update(['device_token' => $request->device_token]);
                $user['token'] = $user->createToken('eventRight')->accessToken;
                return response()->json(['msg' => 'Login successfully', 'data' => $user, 'success' => true], 200);
            } else {
                return response()->json(['msg' => 'Only Organizer can login.', 'data' => null, 'success' => false], 200);
            }
        } else {
            return response()->json(['msg' => 'Invalid Username or password', 'data' => null, 'success' => false], 400);
        }
    }
    public function organizationRegister(Request $request)
    {
        $request->validate([
            'email' => 'bail|required|email|unique:users',
            'confirm_email' => 'bail|required|email|same:email',
            'first_name' => 'bail|required',
            'last_name' => 'bail|required',
            'phone' => 'bail|required',
            'password' => 'bail|required|min:6',
            'Countrycode' => 'bail|required',
        ]);
        $data = $request->all();
        $data['image'] = "defaultuser.png";
        $data['phone'] = $request->Countrycode . $request->phone;
        $data['password'] =  Hash::make($request->password);
        $data['language'] = Setting::first()->language;
        $data['status'] = 1;
        $data['is_verify'] = 0;
        $data['onboarding_completed_at'] = null;
        $user = User::create($data);
        $user->assignRole('Organizer');
        if ($user->is_verify == 0) {
            if (Setting::first()->verify_by == 'email' && Setting::first()->mail_host != NULL) {
                $details = [
                    'url' => url('organizer/VerificationConfirm/' .  $user->id)
                ];
                $setting = Setting::first();
                $config = array(
                    'driver'     => $setting->mail_mailer,
                    'host'       => $setting->mail_host,
                    'port'       => $setting->mail_port,
                    'encryption' => $setting->mail_encryption,
                    'username'   => $setting->mail_username,
                    'password'   => $setting->mail_password
                );
                Config::set('mail', $config);
                Mail::to($user->email)->send(new \App\Mail\VerifyMail($details));
                return response()->json(['msg' => 'Verification link has been sent to your email. Please visit that link to complete the verification.', 'data' => $user, 'success' => true], 200);
            }
            if (Setting::first()->verify_by != 'email' && Setting::first()->twilio_auth_token != NULL) {
                $setting = Setting::first();
                $otp = rand(100000, 999999);
                $to = $user->phone;
                $message = "Your phone verification code is $otp for $setting->app_name.";
                $twilio_sid = $setting->twilio_account_id;
                $twilio_token = $setting->twilio_auth_token;
                $twilio_phone_number = $setting->twilio_phone_number;
                $twilio = new Clients($twilio_sid, $twilio_token);
                $twilio->messages->create(
                    $to,
                    [
                        'from' => $twilio_phone_number,
                        'body' => $message,
                    ]
                );
                $user = User::find($user->id);
                $user->otp = $otp;
                $user->update();
                return response()->json(['msg' => 'Phone verification code sent via SMS.', 'data' => $user, 'success' => true, 'otp' => $otp,], 200);
            }
        } else {
            $user['token'] = $user->createToken('eventRight')->accessToken;
        }
        return response()->json(['msg' => null, 'data' => $user, 'success' => true, 'Countrycode' => $request->Countrycode], 200);
    }
    public function otpVerify(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'otp' => 'required',
        ]);
        $user = User::find($request->id);
        if ($user->is_verify == 1) {
            return response()->json(['msg' => 'User is already verified', 'success' => false], 400);
        }
        if ($user->otp == $request->otp) {
            $user->otp = null;
            $user->device_token = $request->device_token ?? null;
            $user->email_verified_at = Carbon::now();
            $user->update();
            Auth::login($user);
            $user = Auth::user();
            $user['token'] = $user->createToken('eventRight')->accessToken;
            return response()->json(['msg' => 'OTP verify successfully', 'data' => $user, 'success' => true], 200);
        } else {
            return response()->json(['msg' => 'Wrong OTP. Please try again.', 'success' => false]);
        }
    }
    public function setProfile(Request $request)
    {

        $data = $request->all();
        if (isset($request->image)) {
            $data['image'] = (new AppHelper)->saveApiImage($request);
        }
        User::find(Auth::user()->id)->update($data);

        return response()->json(['msg' => 'profile set successfully.', 'success' => true], 200);
    }

    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'bail|required|email',
        ]);
        $user = User::where('email', $request->email)->first();

        $password = rand(100000, 999999);
        if ($user) {
            // Get notification template
            $template = NotificationTemplate::where('title', 'Reset Password')->first();
            if (!$template) {
                Log::error('Reset Password notification template not found');
                return response()->json(['success' => false, 'msg' => 'Email template not configured. Please contact support.', 'data' => null], 500);
            }

            $content = $template->mail_content;
            $detail['user_name'] = $user->name;
            $detail['password'] = $password;
            $detail['app_name'] = Setting::find(1)->app_name;

            try {
                $setting = Setting::first();

                // Validate mail settings exist
                if (!$setting || !$setting->mail_mailer || !$setting->mail_host) {
                    Log::error('Mail configuration incomplete in settings');
                    return response()->json(['success' => false, 'msg' => 'Email service not configured. Please contact support.', 'data' => null], 500);
                }

                $config = array(
                    'driver'     => $setting->mail_mailer,
                    'host'       => $setting->mail_host,
                    'port'       => $setting->mail_port,
                    'encryption' => $setting->mail_encryption,
                    'username'   => $setting->mail_username,
                    'password'   => $setting->mail_password,
                    'from'       => array(
                        'address' => $setting->mail_username,
                        'name'    => $setting->app_name
                    )
                );
                Config::set('mail', $config);
                Mail::to($user->email)->send(new ResetPassword($content, $detail));

                // Only update password after email is sent successfully
                User::find($user->id)->update(['password' => Hash::make($password)]);

            } catch (\Throwable $th) {
                Log::error('Forget Password Email Error: ' . $th->getMessage() . ' | Stack: ' . $th->getTraceAsString());
                return response()->json(['success' => false, 'msg' => 'Failed to send email. Please try again later.', 'data' => null], 500);
            }

            return response()->json(['success' => true, 'msg' => 'Please check your email new password will send on it.', 'data' => null], 200);
        } else {
            return response()->json(['success' => false, 'msg' => 'Invalid email ID', 'data' => null], 200);
        }
    }

    public function profile()
    {
        $data = Auth::user()->makeHidden(['created_at', 'updated_at']);
        return response()->json(['data' => $data, 'success' => true], 200);
    }
 private function mapEventAndroidImage($event, $bannerAndroidImages = null)
        {
            if (!$event) {
                return;
            }
    
            // 1. Check if image_for_android column exists on the event record
            $androidImage = $event->image_for_android ?? null;
    
            // 2. Check banner table for this same event
            if (empty($androidImage)) {
                if ($bannerAndroidImages !== null) {
                    $androidImage = $bannerAndroidImages[$event->id] ?? null;
                } else {
                    $androidImage = Banner::where('event_id', $event->id)
                        ->whereNotNull('image_for_android')
                        ->where('image_for_android', '!=', '')
                        ->value('image_for_android');
                }
            }
    
            // If an android image was resolved, assign it to the existing `image` field
            if (!empty($androidImage)) {
                $event->image = $androidImage;
            }
    
            // Strictly guarantee that image_for_android is never returned as a new field
            $event->makeHidden('image_for_android');
            unset($event->image_for_android);
        }
        
    // public function events()
    // {
    //     $timezone = Setting::find(1)->timezone;
    //     $date = Carbon::now($timezone);
    //     $userId = Auth::user()->id;
    //     $dateStr = $date->format('Y-m-d H:i:s');

    //     $userScope = function ($query) use ($userId) {
    //         $query->where('user_id', $userId)
    //               ->orWhereRaw('FIND_IN_SET(?, REPLACE(user_id, " ", ""))', [$userId]);
    //     };

    //     $data['past'] = Event::with(['ticket'])
    //         ->where('is_deleted', 0)
    //         ->where($userScope)
    //         ->where(function ($query) use ($dateStr) {
    //             $query->where(function ($q) use ($dateStr) {
    //                 $q->where('status', 1)
    //                   ->where('start_time', '<=', $dateStr)
    //                   ->where('end_time', '<=', $dateStr);
    //             })->orWhere(function ($q) use ($dateStr) {
    //                 $q->where('status', 1)
    //                   ->where('event_status', 'Cancel')
    //                   ->where('start_time', '<=', $dateStr)
    //                   ->where('end_time', '<=', $dateStr);
    //             });
    //         })
    //         ->orderBy('start_time', 'ASC')->get();

    //     foreach ($data['past'] as $value) {
    //         $value->description = str_replace("&nbsp;", " ", strip_tags($value->description));
    //     }

    //     $data['draft'] = Event::with(['ticket'])
    //         ->where('is_deleted', 0)
    //         ->where('status', 0)
    //         ->where('event_status', 'Pending')
    //         ->where($userScope)
    //         ->orderBy('start_time', 'ASC')->get();
    //     foreach ($data['draft'] as $value) {
    //         $value->description = str_replace("&nbsp;", " ", strip_tags($value->description));
    //     }

    //     $data['upcoming'] = Event::with(['ticket'])
    //         ->where('is_deleted', 0)
    //         ->where('status', 1)
    //         ->where('event_status', 'Pending')
    //         ->where('start_time', '>=', $dateStr)
    //         ->where($userScope)
    //         ->orderBy('start_time', 'ASC')->get();
    //     foreach ($data['upcoming'] as $value) {
    //         $value->description = str_replace("&nbsp;", " ", strip_tags($value->description));
    //     }

    //     $data['ongoing'] = Event::with(['ticket'])
    //         ->where('is_deleted', 0)
    //         ->where('status', 1)
    //         ->where('event_status', 'Pending')
    //         ->where('start_time', '<=', $dateStr)
    //         ->where('end_time', '>=', $dateStr)
    //         ->where($userScope)
    //         ->orderBy('start_time', 'ASC')->get();
    //     foreach ($data['ongoing'] as $value) {
    //         $value->description = str_replace("&nbsp;", " ", strip_tags($value->description));
    //     }

    //     return response()->json(['data' => $data, 'success' => true], 200);
    // }
      public function events()
        {
            $timezone = Setting::find(1)->timezone;
            $date = Carbon::now($timezone);
            $userId = Auth::user()->id;
            $dateStr = $date->format('Y-m-d H:i:s');
    
            $userScope = function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereRaw('FIND_IN_SET(?, REPLACE(user_id, " ", ""))', [$userId]);
            };
    
            $data['past'] = Event::with(['ticket'])
                ->where('is_deleted', 0)
                ->where($userScope)
                ->where(function ($query) use ($dateStr) {
                    $query->where(function ($q) use ($dateStr) {
                        $q->where('status', 1)
                          ->where('start_time', '<=', $dateStr)
                          ->where('end_time', '<=', $dateStr);
                    })->orWhere(function ($q) use ($dateStr) {
                        $q->where('status', 1)
                          ->where('event_status', 'Cancel')
                          ->where('start_time', '<=', $dateStr)
                          ->where('end_time', '<=', $dateStr);
                    });
                })
                ->orderBy('start_time', 'ASC')->get();
    
            $data['draft'] = Event::with(['ticket'])
                ->where('is_deleted', 0)
                ->where('status', 0)
                ->where('event_status', 'Pending')
                ->where($userScope)
                ->orderBy('start_time', 'ASC')->get();
    
            $data['upcoming'] = Event::with(['ticket'])
                ->where('is_deleted', 0)
                ->where('status', 1)
                ->where('event_status', 'Pending')
                ->where('start_time', '>=', $dateStr)
                ->where($userScope)
                ->orderBy('start_time', 'ASC')->get();
    
            $data['ongoing'] = Event::with(['ticket'])
                ->where('is_deleted', 0)
                ->where('status', 1)
                ->where('event_status', 'Pending')
                ->where('start_time', '<=', $dateStr)
                ->where('end_time', '>=', $dateStr)
                ->where($userScope)
                ->orderBy('start_time', 'ASC')->get();
    
            $allEventIds = collect()
                ->concat($data['past']->pluck('id'))
                ->concat($data['draft']->pluck('id'))
                ->concat($data['upcoming']->pluck('id'))
                ->concat($data['ongoing']->pluck('id'))
                ->unique()
                ->filter()
                ->values();
    
            $bannerAndroidImages = Banner::whereIn('event_id', $allEventIds)
                ->whereNotNull('image_for_android')
                ->where('image_for_android', '!=', '')
                ->pluck('image_for_android', 'event_id');
    
            foreach ($data['past'] as $value) {
                $value->description = str_replace("&nbsp;", " ", strip_tags($value->description));
                $this->mapEventAndroidImage($value, $bannerAndroidImages);
            }
    
            foreach ($data['draft'] as $value) {
                $value->description = str_replace("&nbsp;", " ", strip_tags($value->description));
                $this->mapEventAndroidImage($value, $bannerAndroidImages);
            }
    
            foreach ($data['upcoming'] as $value) {
                $value->description = str_replace("&nbsp;", " ", strip_tags($value->description));
                $this->mapEventAndroidImage($value, $bannerAndroidImages);
            }
    
            foreach ($data['ongoing'] as $value) {
                $value->description = str_replace("&nbsp;", " ", strip_tags($value->description));
                $this->mapEventAndroidImage($value, $bannerAndroidImages);
            }
    
            return response()->json(['data' => $data, 'success' => true], 200);
        }

    public function searchEvents()
    {
        $userId = Auth::user()->id;
        $data = Event::with(['ticket'])
            ->where('is_deleted', 0)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereRaw('FIND_IN_SET(?, REPLACE(user_id, " ", ""))', [$userId]);
            })
            ->orderBy('start_time', 'ASC')->get();
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function scanner()
    {
        $data = User::role('scanner')->where('org_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function addScanner(Request $request)
    {
        $request->validate([
            'first_name' => 'bail|required',
            'last_name' => 'bail|required',
            'email' => 'bail|required|email|unique:users',
            'phone' => 'bail|required',
            'password' => 'bail|required|min:6',
        ]);
        $data = $request->all();
        $data['org_id'] = Auth::user()->id;
        $data['password'] =  Hash::make($request->password);
        $data['language'] = Setting::first()->language;
        $data['image'] = 'defaultuser.png';
        $user = User::create($data);
        $user->assignRole('scanner');
        return response()->json(['data' => $user, 'success' => true], 200);
    }

    public function addEvent(Request $request)
    {
        if (!Auth::user()->onboarding_completed_at) {
            return response()->json([
                'msg' => 'Please complete onboarding before managing events.',
                'success' => false,
            ], 403);
        }

        if ((int) Auth::user()->is_verify !== 1) {
            return response()->json([
                'msg' => OrganizerVerificationStatus::blockingMessage(Auth::user()),
                'success' => false,
            ], 403);
        }

        $request->validate([
            'name' => 'bail|required',
            'image' => 'bail|required',
            'start_date' => 'bail|required|date_format:Y-m-d',
            'end_date' => 'bail|required|date_format:Y-m-d',
            'start_time' => 'bail|required',
            'end_time' => 'bail|required',
            'scanner_id' => 'bail|required_if:type,offline',
            'category_id' => 'bail|required|numeric',
            'type' => 'bail|required',  // offline & online
            'address' => 'bail|required_if:type,offline',
            'lat' => 'bail|required_if:type,offline',
            'lang' => 'bail|required_if:type,offline',
            'status' => 'bail|required|numeric',
            'description' => 'bail|required',
            'people' => 'bail|required|numeric',
        ]);

        // Custom dimension validation for event_logo
        if (isset($request->event_logo)) {
            $imageData2 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->event_logo));
            $imageInfo2 = getimagesizefromstring($imageData2);
            if ($imageInfo2 && ($imageInfo2[0] != 200 || $imageInfo2[1] != 200)) {
                return response()->json(['msg' => 'Event logo must be exactly 200x200 pixels.', 'success' => false], 400);
            }
        }

        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        // if ($request->tags != null) {
        //     $data['tags'] = array();
        //     foreach ($request->tags as $key) {
        //         array_push($data['tags'], $key['value']);
        //     }
        // }
        // if (isset($request->tags)) {
        //     if (count($data['tags']) > 0) {
        //         $data['tags'] = implode(',', $data['tags']);
        //     }
        // }
        if (isset($request->image)) {
            $data['image'] = (new AppHelper)->saveApiImage($request);
        }
        if (isset($request->event_logo)) {
            $data['event_logo'] = (new AppHelper)->saveApiImage($request, 'event_logo');
        }
        if (!isset($data['maximum_checkins']) || $data['maximum_checkins'] == '' || $data['maximum_checkins'] === null) {
            $data['maximum_checkins'] = 1;
        }
        $data['start_time'] = $request->start_date . ' ' . $request->start_time;
        $data['end_time']  = $request->end_date . ' ' . $request->end_time;

        // Check if event ID is provided for update
        if ($request->filled('id')) {
            $event = Event::findOrFail($request->id);
            $event->update($data);

            // Handle video for update
            if (isset($request->video)) {
                $videoData = [
                    'links' => $request->video['links'] ?? [],
                    'files' => $request->video['files'] ?? []
                ];
                Video::updateOrCreate(
                    ['event_id' => $request->id],
                    $videoData
                );
            }

            return response()->json(['data' => $event->fresh(), 'msg' => 'Update Event Successfully', 'success' => true], 200);
        } else {
            // Create new event
            $event = Event::create($data);

            // Record activity log with organizer's user ID (Auth::id())
            AdminActivityLog::record(
                AdminActivityLog::EVENT_CREATED,
                $event,
                'Event created via Organizer API',
                $event->name . ' was created.',
                [
                    'event_id'   => $event->id,
                    'event_name' => $event->name,
                    'event_type' => $event->type,
                    'start_time' => $event->start_time,
                    'end_time'   => $event->end_time,
                    'user_id'    => Auth::id(),
                ],
                $request
            );

            // Handle video for new event
            if (isset($request->video)) {
                $videoData = [
                    'event_id' => $event->id,
                    'links' => $request->video['links'] ?? [],
                    'files' => $request->video['files'] ?? []
                ];
                Video::create($videoData);
            }

            return response()->json(['data' => $event, 'msg' => 'Add Event Successfully', 'success' => true], 200);
        }
    }

    public function editEvent(Request $request)
    {
        if (!Auth::user()->onboarding_completed_at) {
            return response()->json([
                'msg' => 'Please complete onboarding before managing events.',
                'success' => false,
            ], 403);
        }

        if ((int) Auth::user()->is_verify !== 1) {
            return response()->json([
                'msg' => OrganizerVerificationStatus::blockingMessage(Auth::user()),
                'success' => false,
            ], 403);
        }

        $request->validate([
            'name' => 'bail|required',
            'id' => 'bail|required|numeric',
            'start_date' => 'bail|required|date_format:Y-m-d',
            'end_date' => 'bail|required|date_format:Y-m-d',
            'start_time' => 'bail|required',
            'end_time' => 'bail|required',
            'scanner_id' => 'bail|required_if:type,offline',
            'category_id' => 'bail|required|numeric',
            'type' => 'bail|required',  // offline & online
            'address' => 'bail|required_if:type,offline',
            'lat' => 'bail|required_if:type,offline',
            'lang' => 'bail|required_if:type,offline',
            'status' => 'bail|required|numeric',
            'description' => 'bail|required',
            'people' => 'bail|required|numeric',
        ]);

        $data = $request->all();
        if (isset($request->image)) {
            $data['image'] = (new AppHelper)->saveApiImage($request);
        }

        // Handle video
        if (isset($request->video)) {
            $videoData = [
                'links' => $request->video['links'] ?? [],
                'files' => $request->video['files'] ?? []
            ];
            Video::updateOrCreate(
                ['event_id' => $request->id],
                $videoData
            );
        }

        $data['start_time'] = $request->start_date . ' ' . $request->start_time;
        $data['end_time']  = $request->end_date . ' ' . $request->end_time;
        // if (isset($request->add_gallery)) {
        //     $oldphotos = Event::find($request->id)->gallery;
        //     $add_gallery = explode(', ', $request->add_gallery);
        //     $gallery = array_filter(explode(', ', $oldphotos));
        //     foreach ($add_gallery as $value) {
        //         $img = $value;
        //         $img = str_replace('data:image/png;base64,', '', $img);
        //         $img = str_replace(' ', '+', $img);
        //         $img_code = base64_decode($img);
        //         $Iname = uniqid();
        //         $file = public_path('/images/upload/') . $Iname . ".png";
        //         $success = file_put_contents($file, $img_code);
        //         $image_name = $Iname . ".png";
        //         array_push($gallery, $image_name);
        //     }
        //     $data['gallery'] = implode(',', $gallery);
        //     $event = Event::find($request->id);
        //     $event->gallery = $data['gallery'];
        //     $event->save();
        // }
        // if (isset($request->remove_gallery)) {
        //     $oldphotos = Event::find($request->id)->gallery;
        //     $remove_gallery = explode(',', $request->remove_gallery);
        //     $gallery = array_filter(explode(',', $oldphotos));
        //     foreach ($remove_gallery as $key2 => $value) {
        //         (new AppHelper)->deleteFile($value);
        //         // if (count(array_keys($gallery, $value)) > 0) {
        //         //     if (($key = array_search($value, $gallery)) !== false) {
        //         //         unset($gallery[$key]);
        //         //     }
        //         // }
        //         foreach ($gallery as $key => $values) {
        //             if ($values === $value) {
        //                 unset($gallery[$key]);
        //             }
        //         }
        //     }
        //     $data['gallery'] = implode(',', $gallery);
        // }
        if (!isset($data['maximum_checkins']) || $data['maximum_checkins'] == '' || $data['maximum_checkins'] === null) {
            $data['maximum_checkins'] = 1;
        }
        $event = Event::find($request->id)->update($data);
        return response()->json(['msg' => 'Update Event Successfully', 'success' => true], 200);
    }
    public function addImageGallery(Request $request)
    {
        $event = array_filter(explode(',', Event::find($request->id)->gallery));
        if ($request->image) {
            $name = (new AppHelper)->saveApiImage($request);
            array_push($event, $name);
            Event::find($request->id)->update(['gallery' => implode(',', $event)]);
        }
        return response()->json(['msg' => 'Added to gallery successfully', 'success' => true, 'data' => $name], 200);
    }
    public function removeImageGallery(Request $request)
    {
        $oldphotos = Event::find($request->id)->gallery;
        $oldphotos = array_filter(explode(',', $oldphotos));
        $remove_gallery =  $request->remove_gallery;
        foreach ($oldphotos as $key => $value) {
            if ($value == $remove_gallery) {
                unset($oldphotos[$key]);
            }
        }
        (new AppHelper)->deleteFile($remove_gallery);
        $data['gallery'] =  implode(',', $oldphotos);
        Event::find(Event::find($request->id)->update($data));
        return response()->json(['msg' => 'Removed from gallery successfully', 'success' => true, 'data'], 200);
    }

    public function cancelEvent($id)
    {
        Event::find($id)->update(['event_status' => 'Cancel']);
        return response()->json(['msg' => 'Event Canceled Successfully.', 'success' => true], 200);
    }

    public function deleteEvent($id)
    {
        Event::find($id)->update(['is_deleted' => 1, 'event_status' => 'Deleted']);
        $ticket = Ticket::where('event_id', $id)->update(['is_deleted' => 1]);
        return response()->json(['msg' => 'Event Deleted Successfully.', 'success' => true], 200);
    }

    public function eventDetail($id)
    {
        // $data = Event::with(['category:id,name', 'video'])->find($id);
        // $data->description =  str_replace("&nbsp;", " ", strip_tags($data->description));
         $data = Event::with(['category:id,name', 'video'])->find($id);
            if ($data) {
                $this->mapEventAndroidImage($data);
            }
            $data->description =  str_replace("&nbsp;", " ", strip_tags($data->description));
        $data->startTime = $data->start_time->format('h:i a');
        $data->endTime = $data->end_time->format('h:i a');
        $data->tags = array_filter(explode(',', $data->tags));
        $data->start_time = $data->start_time->format('Y-m-d\TH:i:s' . '.000000');
        $data->end_time =  $data->end_time->format('Y-m-d\TH:i:s' . '.000000');
        $gallery = array_filter(explode(',', $data->gallery));
        $g = array();
        if (count($gallery) > 0) {
            foreach ($gallery as  $value) {
                array_push($g, $value);
            }
            $data->gallery = $g;
        } else {
            $data->gallery = [];
        }

        // Format video data
        if ($data->video) {
            $data->video_links = $data->video->links ?? [];
            $data->video_files = $data->video->files ?? [];
        } else {
            $data->video_links = [];
            $data->video_files = [];
        }

        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function eventGuestList(Request $request, $id)
    {
        (new AppHelper)->eventStatusChange();
        $event = Event::find($id);
        $perPage = $request->input('per_page', $request->input('limit', 10));
        $data = Order::with([
            'customer:name,id,email,phone',
            'guestUser:name,id,email,phone',
            'ticket:id,name,ticket_number,type,price',
            'orderChild.ticket:id,name,ticket_number,type,price'
        ])
            ->where('event_id', $id)
            ->orderBy('id', 'DESC')
            ->paginate($perPage);

        $this->formatOrdersCollection($data->getCollection(), $event);

        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function orderDetail($id)
    {
        (new AppHelper)->eventStatusChange();
        $data = Order::with([
            'customer:name,id,email,phone',
            'guestUser:name,id,email,phone',
            'ticket:id,name,ticket_number,type,price',
            'event:id,name,start_time,end_time',
            'orderChild.ticket:id,name,ticket_number,type,price'
        ])
            ->where('id', $id)
            ->orWhere('order_id', $id)
            ->orWhere('order_id', '#' . $id)
            ->first();

        if (! $data) {
            return response()->json(['msg' => 'Order not found', 'data' => null, 'success' => false], 404);
        }

        $this->formatOrdersCollection(collect([$data]), $data->event);
        $data->setAppends([])->makeHidden(['updated_at', 'payment_token', 'coupon_discount', 'coupon_id', 'review']);
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    private function formatOrdersCollection($orders, $event = null)
    {
        if ($orders->isEmpty()) {
            return $orders;
        }

        $customerIds = [];
        $guestUserIds = [];
        $venueSeatIds = [];

        foreach ($orders as $order) {
            if ($order->customer_id) {
                $customerIds[] = $order->customer_id;
            }
            if ($order->guestuser_id) {
                $guestUserIds[] = $order->guestuser_id;
            }

            if ($order->relationLoaded('orderChild') && $order->orderChild) {
                foreach ($order->orderChild as $child) {
                    if ($child->customer_id) {
                        $customerIds[] = $child->customer_id;
                    }
                    if ($child->guestuser_id) {
                        $guestUserIds[] = $child->guestuser_id;
                    }
                    if ($child->event_venue_seat_id) {
                        $venueSeatIds[] = $child->event_venue_seat_id;
                    }
                }
            }
        }

        $customerIds = array_unique(array_filter($customerIds));
        $guestUserIds = array_unique(array_filter($guestUserIds));
        $venueSeatIds = array_unique(array_filter($venueSeatIds));

        $appUsers = !empty($customerIds) ? AppUser::whereIn('id', $customerIds)->get()->keyBy('id') : collect();
        $guestUsers = !empty($guestUserIds) ? GuestUser::whereIn('id', $guestUserIds)->get()->keyBy('id') : collect();

        $missingAppUserIds = array_diff($customerIds, $appUsers->keys()->toArray());
        $fallbackGuests = !empty($missingAppUserIds) ? GuestUser::whereIn('id', $missingAppUserIds)->get()->keyBy('id') : collect();

        $missingGuestUserIds = array_diff($guestUserIds, $guestUsers->keys()->toArray());
        $fallbackApps = !empty($missingGuestUserIds) ? AppUser::whereIn('id', $missingGuestUserIds)->get()->keyBy('id') : collect();

        $venueSeats = !empty($venueSeatIds) ? EventVenueSeat::whereIn('id', $venueSeatIds)->get()->keyBy('id') : collect();

        $knownEmails = array_filter(array_merge(
            $appUsers->pluck('email')->toArray(),
            $guestUsers->pluck('email')->toArray(),
            $fallbackGuests->pluck('email')->toArray(),
            $fallbackApps->pluck('email')->toArray()
        ));

        $crossGuests = !empty($knownEmails) ? GuestUser::whereIn('email', $knownEmails)->get()->keyBy('email') : collect();
        $crossApps = !empty($knownEmails) ? AppUser::whereIn('email', $knownEmails)->get()->keyBy('email') : collect();

        foreach ($orders as $order) {
            $customerId = $order->customer_id;
            $guestUserId = $order->guestuser_id;

            if ($order->relationLoaded('orderChild') && $order->orderChild) {
                if (empty($guestUserId)) {
                    $cg = $order->orderChild->first(fn($c) => !empty($c->guestuser_id));
                    if ($cg) {
                        $guestUserId = $cg->guestuser_id;
                    }
                }
                if (empty($customerId)) {
                    $cc = $order->orderChild->first(fn($c) => !empty($c->customer_id));
                    if ($cc) {
                        $customerId = $cc->customer_id;
                    }
                }
            }

            $appUser = $customerId ? ($appUsers->get($customerId) ?? $fallbackApps->get($customerId)) : null;
            $guestUser = $guestUserId ? ($guestUsers->get($guestUserId) ?? $fallbackGuests->get($guestUserId)) : null;

            if (!$appUser && $customerId) {
                $potentialGuest = $fallbackGuests->get($customerId) ?? GuestUser::find($customerId);
                if ($potentialGuest) {
                    if (!$guestUser) {
                        $guestUser = $potentialGuest;
                        $guestUserId = $potentialGuest->id;
                    }
                }
            }

            if (!$guestUser && $guestUserId) {
                $potentialApp = $fallbackApps->get($guestUserId) ?? AppUser::find($guestUserId);
                if ($potentialApp) {
                    if (!$appUser) {
                        $appUser = $potentialApp;
                        $customerId = $potentialApp->id;
                    }
                }
            }

            if ($appUser && !$guestUser && !empty($appUser->email)) {
                $matchedGuest = $crossGuests->get($appUser->email) ?? GuestUser::where('email', $appUser->email)->first();
                if ($matchedGuest) {
                    $guestUser = $matchedGuest;
                    $guestUserId = $matchedGuest->id;
                }
            } elseif ($guestUser && !$appUser && !empty($guestUser->email)) {
                $matchedApp = $crossApps->get($guestUser->email) ?? AppUser::where('email', $guestUser->email)->first();
                if ($matchedApp) {
                    $appUser = $matchedApp;
                    $customerId = $matchedApp->id;
                }
            }

            if (empty($customerId)) {
                $customerId = $guestUserId ?: ($order->customer_id ?: ($order->guestuser_id ?: 0));
            }
            if (empty($guestUserId)) {
                $guestUserId = $customerId ?: ($order->guestuser_id ?: ($order->customer_id ?: 0));
            }

            $order->customer_id = (int) $customerId;
            $order->guestuser_id = (int) $guestUserId;

            if ($appUser) {
                $appUserCopy = clone $appUser;
                $appUserCopy->makeHidden(['email_verified_at', 'otp', 'lat', 'lang', 'provider', 'provider_token', 'device_token', 'fcm_token', 'bio', 'language', 'is_verify', 'status', 'created_at', 'updated_at', 'deleted_at', 'following', 'favorite', 'favorite_blog', 'address']);
                $appUserCopy->last_name = $appUserCopy->last_name ?? '';
                $appUserCopy->image = $appUserCopy->image ?? 'defaultuser.png';
                $appUserCopy->phone = $appUserCopy->phone ?? ($guestUser ? ($guestUser->phone ?? '') : '');
                $order->setRelation('customer', $appUserCopy);
            } elseif ($guestUser) {
                $customerObj = (object) [
                    'id' => (int) $guestUser->id,
                    'name' => $guestUser->name ?? '',
                    'last_name' => $guestUser->last_name ?? '',
                    'email' => $guestUser->email ?? '',
                    'image' => 'defaultuser.png',
                    'phone' => $guestUser->phone ?? '',
                    'imagePath' => url('images/upload') . '/',
                ];
                $order->setRelation('customer', $customerObj);
            } else {
                $order->setRelation('customer', (object) [
                    'id' => (int) $order->customer_id,
                    'name' => '',
                    'last_name' => '',
                    'email' => '',
                    'image' => 'defaultuser.png',
                    'phone' => '',
                    'imagePath' => url('images/upload') . '/',
                ]);
            }

            if ($guestUser) {
                $guestUserCopy = clone $guestUser;
                $guestUserCopy->makeHidden(['created_at', 'updated_at', 'fcm_token', 'is_guest_user', 'deleted_at']);
                $guestUserCopy->last_name = $guestUserCopy->last_name ?? '';
                $guestUserCopy->phone = $guestUserCopy->phone ?? ($appUser ? ($appUser->phone ?? '') : '');
                $order->setRelation('guestUser', $guestUserCopy);
            } elseif ($appUser) {
                $guestObj = (object) [
                    'id' => (int) $appUser->id,
                    'name' => $appUser->name ?? '',
                    'last_name' => $appUser->last_name ?? '',
                    'email' => $appUser->email ?? '',
                    'phone' => $appUser->phone ?? '',
                ];
                $order->setRelation('guestUser', $guestObj);
            } else {
                $order->setRelation('guestUser', (object) [
                    'id' => (int) $order->guestuser_id,
                    'name' => '',
                    'last_name' => '',
                    'email' => '',
                    'phone' => '',
                ]);
            }

            // Seats resolution
            $rawBookSeats = $order->book_seats;
            $rawSeatDetails = $order->seat_details;
            $decodedSeatDetails = [];

            if (!empty($rawSeatDetails)) {
                if (is_string($rawSeatDetails)) {
                    $cleanJson = html_entity_decode($rawSeatDetails, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $decoded = json_decode($cleanJson, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $decodedSeatDetails = $decoded;
                    } else {
                        $decoded = json_decode($rawSeatDetails, true);
                        if (is_array($decoded)) {
                            $decodedSeatDetails = $decoded;
                        }
                    }
                } elseif (is_array($rawSeatDetails)) {
                    $decodedSeatDetails = $rawSeatDetails;
                }
            }

            // Normalize order-level seat details
            $normalizedOrderSeats = [];
            foreach ($decodedSeatDetails as $s) {
                if (is_array($s)) {
                    $label = $s['label'] ?? ($s['seat_label'] ?? null);
                    $section = $s['section'] ?? ($s['section_name'] ?? '');
                    $row = $s['row'] ?? ($s['row_name'] ?? '');
                    $seatNumber = (string) ($s['seat_number'] ?? '');
                    if (!$label && ($section || $row || $seatNumber)) {
                        $label = trim($section . ' ' . $row . '-' . $seatNumber);
                    }
                    $normalizedOrderSeats[] = [
                        'id' => (string) ($s['id'] ?? ($s['seat_id'] ?? '')),
                        'label' => (string) ($label ?? ''),
                        'section' => (string) $section,
                        'row' => (string) $row,
                        'seat_number' => $seatNumber,
                        'ticket_id' => (string) ($s['ticket_id'] ?? $order->ticket_id ?? ''),
                    ];
                }
            }

            $childSeatLabels = [];
            $childVenueSeatIds = [];
            if ($order->relationLoaded('orderChild') && $order->orderChild) {
                foreach ($order->orderChild as $child) {
                    if (!empty($child->Book_Seat_Id)) {
                        $childSeatLabels[] = $child->Book_Seat_Id;
                    }
                    if (!empty($child->event_venue_seat_id)) {
                        $childVenueSeatIds[] = $child->event_venue_seat_id;
                    }
                }
            }

            if (empty($normalizedOrderSeats) && !empty($childVenueSeatIds)) {
                $matchedVenueSeats = collect($childVenueSeatIds)->map(fn($sid) => $venueSeats->get($sid))->filter();
                foreach ($matchedVenueSeats as $s) {
                    $normalizedOrderSeats[] = [
                        'id' => (string) $s->id,
                        'label' => $s->seat_label ?: trim(($s->section_name ?? '') . ' ' . ($s->row_name ?? '') . '-' . ($s->seat_number ?? '')),
                        'section' => (string) ($s->section_name ?? ''),
                        'row' => (string) ($s->row_name ?? ''),
                        'seat_number' => (string) ($s->seat_number ?? ''),
                        'ticket_id' => (string) ($s->ticket_id ?? ''),
                    ];
                }
            }

            if (empty($normalizedOrderSeats) && !empty($childSeatLabels)) {
                foreach (array_values(array_unique($childSeatLabels)) as $label) {
                    $normalizedOrderSeats[] = [
                        'id' => (string) $label,
                        'label' => (string) $label,
                        'section' => 'Reserved',
                        'row' => '',
                        'seat_number' => (string) $label,
                        'ticket_id' => (string) $order->ticket_id,
                    ];
                }
            }

            // Associate seats strictly and accurately with each individual child ticket
            $usedSeatIndices = [];
            $assignedChildSeats = [];

            if ($order->relationLoaded('orderChild') && $order->orderChild) {
                foreach ($order->orderChild as $child) {
                    if (empty($child->customer_id)) {
                        $child->customer_id = (int) $order->customer_id;
                    }
                    if (empty($child->guestuser_id)) {
                        $child->guestuser_id = (int) $order->guestuser_id;
                    }
                    if ($child->checkin === null) {
                        $child->checkin = 0;
                    }

                    $matchedChildSeat = null;

                    // 1. Match by event_venue_seat_id
                    if (!empty($child->event_venue_seat_id)) {
                        foreach ($normalizedOrderSeats as $idx => $s) {
                            if (!in_array($idx, $usedSeatIndices) && (string) $s['id'] === (string) $child->event_venue_seat_id) {
                                $matchedChildSeat = $s;
                                $usedSeatIndices[] = $idx;
                                break;
                            }
                        }
                        if (!$matchedChildSeat && $venueSeats->has($child->event_venue_seat_id)) {
                            $vs = $venueSeats->get($child->event_venue_seat_id);
                            $matchedChildSeat = [
                                'id' => (string) $vs->id,
                                'label' => $vs->seat_label ?: trim(($vs->section_name ?? '') . ' ' . ($vs->row_name ?? '') . '-' . ($vs->seat_number ?? '')),
                                'section' => (string) ($vs->section_name ?? ''),
                                'row' => (string) ($vs->row_name ?? ''),
                                'seat_number' => (string) ($vs->seat_number ?? ''),
                                'ticket_id' => (string) ($vs->ticket_id ?? $child->ticket_id ?? ''),
                            ];
                        }
                    }

                    // 2. Match by Book_Seat_Id if not matched yet
                    if (!$matchedChildSeat && !empty($child->Book_Seat_Id)) {
                        foreach ($normalizedOrderSeats as $idx => $s) {
                            if (!in_array($idx, $usedSeatIndices) && ($s['label'] === $child->Book_Seat_Id || ($s['seat_label'] ?? '') === $child->Book_Seat_Id)) {
                                $matchedChildSeat = $s;
                                $usedSeatIndices[] = $idx;
                                break;
                            }
                        }
                        if (!$matchedChildSeat) {
                            $matchedChildSeat = [
                                'id' => (string) $child->Book_Seat_Id,
                                'label' => (string) $child->Book_Seat_Id,
                                'section' => 'Reserved',
                                'row' => '',
                                'seat_number' => (string) $child->Book_Seat_Id,
                                'ticket_id' => (string) $child->ticket_id,
                            ];
                        }
                    }

                    // 3. Match 1-to-1 only if entire order had matching seated count
                    if (!$matchedChildSeat && empty($child->Book_Seat_Id) && empty($child->event_venue_seat_id)) {
                        if (count($order->orderChild) === count($normalizedOrderSeats)) {
                            foreach ($normalizedOrderSeats as $idx => $s) {
                                if (!in_array($idx, $usedSeatIndices)) {
                                    $matchedChildSeat = $s;
                                    $usedSeatIndices[] = $idx;
                                    break;
                                }
                            }
                        }
                    }

                    if ($matchedChildSeat) {
                        $child->Book_Seat_Id = $matchedChildSeat['label'];
                        $child->seat_details = [$matchedChildSeat];
                        $assignedChildSeats[] = $matchedChildSeat;
                    } else {
                        $child->Book_Seat_Id = null;
                        $child->seat_details = [];
                    }

                    if (empty($child->event_venue_seat_id)) {
                        $child->event_venue_seat_id = 0;
                    }
                    $child->makeHidden(['created_at', 'updated_at', 'SeatDetails_id', 'seat_id']);
                }
            }

            // Final order-level seat fields
            $finalOrderSeats = !empty($assignedChildSeats) ? $assignedChildSeats : $normalizedOrderSeats;
            if (!empty($finalOrderSeats)) {
                $order->seat_details = array_values($finalOrderSeats);
                $labels = array_filter(array_column($finalOrderSeats, 'label'));
                $order->book_seats = !empty($labels) ? implode(', ', $labels) : null;
            } else {
                $order->seat_details = [];
                $order->book_seats = null;
            }

            // Fallback for order.ticket when order.ticket_id is comma-separated
            if ((!$order->relationLoaded('ticket') || !$order->ticket) && $order->relationLoaded('orderChild') && $order->orderChild && $order->orderChild->isNotEmpty()) {
                $firstTicket = $order->orderChild->first(fn($c) => !empty($c->ticket))?->ticket;
                if ($firstTicket) {
                    $order->setRelation('ticket', $firstTicket);
                }
            }

            if (empty($order->ticket_date)) {
                $childDate = ($order->relationLoaded('orderChild') && $order->orderChild)
                    ? $order->orderChild->pluck('ticket_date')->filter()->first()
                    : null;
                if ($childDate) {
                    $order->ticket_date = $childDate;
                } elseif ($event && !empty($event->start_time)) {
                    $order->ticket_date = $event->start_time;
                }
            }

            if (empty($order->checkins_count) && $order->relationLoaded('orderChild') && $order->orderChild) {
                $actualCheckins = $order->orderChild->filter(fn($c) => $c->status == 1 || !empty($c->checkin))->count();
                if ($actualCheckins > 0) {
                    $order->checkins_count = $actualCheckins;
                }
            }

            $order->setAppends([])->makeHidden(['payment_type', 'updated_at', 'payment_token', 'coupon_discount', 'coupon_id', 'review']);
        }

        return $orders;
    }

    public function eventTickets($id)
    {
        (new AppHelper)->eventStatusChange();
        $data = Ticket::with('allowUser')->where('event_id', $id)->orderBy('id', 'DESC')->get();
        foreach ($data as $value) {
            $value->use_ticket = Order::where('ticket_id', $value->id)->sum('quantity');
            $value->allow_to_user = $value->allow_to_user;
        }
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function editImage(Request $request)
    {
        $request->validate([
            'image' => 'bail|required',
            // 'image_2' => 'bail|required',
        ]);
        $updated = false;
        if (isset($request->image)) {
            $image_name = (new AppHelper)->saveApiImage($request, 'image');
            User::find(Auth::user()->id)->update(['image' => $image_name]);
            $updated = true;
        }

        if ($updated) {
            return response()->json(['msg' => null, 'data' => null, 'success' => true], 200);
        } else {
            return response()->json(['msg' => null, 'data' => null, 'success' => false], 200);
        }
    }
    public function editImage1(Request $request)
    {
        $request->validate([
            'image' => 'bail|required',
            'event_logo' => 'bail|required',
        ]);
        $updated = false;
        if (isset($request->event_logo)) {
            $image_name = (new AppHelper)->saveApiImage($request, 'event_logo');
            User::find(Auth::user()->id)->update(['event_logo' => $image_name]);
            $updated = true;
        }

        if ($updated) {
            return response()->json(['msg' => null, 'data' => null, 'success' => true], 200);
        } else {
            return response()->json(['msg' => null, 'data' => null, 'success' => false], 200);
        }
    }



    public function ticketDetail($id)
    {
        $data = Ticket::with('allowUser')->findOrFail($id);
        $data->allow_to_user = $data->allow_to_user;
    
        // Look for ticket_id with quotes or without quotes
        $quotedId = '"' . $data->id . '"'; 
    
        // Use REPLACE() to strip out any literal double quotes from the quantity field before summing
        $data->use_ticket = Order::where(function($query) use ($data, $quotedId) {
                                $query->where('ticket_id', $data->id)
                                      ->orWhere('ticket_id', $quotedId);
                            })
                            ->sum(\DB::raw('REPLACE(quantity, \'"\', \'\')'));
    
        $data->startTime = $data->start_time->format('Y-m-d\TH:i:s' . '.000000');
        $data->endTime =  $data->end_time->format('Y-m-d\TH:i:s' . '.000000');
    
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function addTicket(Request $request)
    {
        $request->validate([
            'name' => 'bail|required',
            'event_id' => 'bail|required|numeric',
            'quantity' => 'bail|required',
            'start_date' => 'bail|required|date_format:Y-m-d',
            'end_date' => 'bail|required|date_format:Y-m-d',
            'start_time' => 'bail|required',
            'end_time' => 'bail|required',
            'type' => 'bail|required',
            'ticket_per_order' => 'bail|required',
            'description' => 'bail|required',
            'price' =>  'bail|required_if:type,paid',
            'status' =>  'bail|required',
            // 'tax_ids' => 'array', // Optional: add validation if needed
        ]);
        $data = $request->all();
        if ($request->type == "free") {
            $data['price'] = 0;
        }
        if (!isset($data['maximum_checkins']) || $data['maximum_checkins'] == '' || $data['maximum_checkins'] === null) {
            $data['maximum_checkins'] = 1;
        }
        $data['start_time'] = $request->start_date . ' ' . $request->start_time;
        $data['end_time']  = $request->end_date . ' ' . $request->end_time;
        $data['ticket_number'] = chr(rand(65, 90)) . chr(rand(65, 90)) . '-' . rand(999, 10000);
        $event = Event::find($request->event_id);
        $data['user_id'] = $event->user_id;

        // Insert in tax_id also
        if (isset($data['tax_ids']) && is_array($data['tax_ids'])) {
            $data['tax_id'] = implode(',', $data['tax_ids']);
        } elseif (isset($data['tax_id'])) {
            // If tax_id is provided as a single value
            $data['tax_id'] = $data['tax_id'];
        } else {
            $data['tax_id'] = '';
        }

        $ticket = Ticket::create($data);
        $allowToUserVal = $request->has('allow_to_user') ? (int)$request->allow_to_user : ($request->has('is_allow_user') ? (int)$request->is_allow_user : 1);
        TicketAllowUser::updateOrCreate(
            ['ticket_id' => $ticket->id],
            ['allow_to_user' => $allowToUserVal]
        );
        $ticket->allow_to_user = $allowToUserVal;

        // Record activity log with organizer's user ID (Auth::id())
        AdminActivityLog::record(
            AdminActivityLog::TICKET_CREATED,
            $ticket,
            'Ticket created via Organizer API',
            $ticket->name . ' was created.',
            [
                'ticket_id'   => $ticket->id,
                'ticket_name' => $ticket->name,
                'ticket_type' => $ticket->type,
                'event_id'    => $ticket->event_id,
                'user_id'     => Auth::id(),
            ],
            $request
        );

        return response()->json(['data' => $ticket, 'msg' => 'Add Ticket Successfully', 'success' => true], 200);
    }



    public function editTicket(Request $request)
    {
        $request->validate([
            'name' => 'bail|required',
            'event_id' => 'bail|required|numeric',
            'id' => 'bail|required',
            'quantity' => 'bail|required',
            'start_date' => 'bail|required|date_format:Y-m-d',
            'end_date' => 'bail|required|date_format:Y-m-d',
            'start_time' => 'bail|required',
            'end_time' => 'bail|required',
            'type' => 'bail|required',
            'ticket_per_order' => 'bail|required',
            'description' => 'bail|required',
            'status' =>  'bail|required',
            'price' =>  'bail|required_if:type,paid',
        ]);
        $data = $request->all();
        if ($request->type == "free") {
            $data['price'] = 0;
        }
        $data['start_time'] = $request->start_date . ' ' . $request->start_time;
        $data['end_time']  = $request->end_date . ' ' . $request->end_time;
        Ticket::find($request->id)->update($data);
        if ($request->has('allow_to_user') || $request->has('is_allow_user')) {
            $allowToUserVal = $request->has('allow_to_user') ? (int)$request->allow_to_user : (int)$request->is_allow_user;
            TicketAllowUser::updateOrCreate(
                ['ticket_id' => $request->id],
                ['allow_to_user' => $allowToUserVal]
            );
        }
        $ticket = Ticket::find($request->id);
        return response()->json(['data' => $ticket, 'msg' => 'Update Ticket Successfully', 'success' => true], 200);
    }

    public function deleteTicket($id)
    {
        Ticket::find($id)->delete();
        return response()->json(['msg' => 'Ticket Deleted Successfully.', 'success' => true], 200);
    }

    public function allCategory()
    {
        $data = Category::where('status', 1)->orderBy('id', 'DESC')->get();
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function organizationSetting()
    {
        $data = Setting::find(1, ['currency', 'default_lat', 'default_long', 'privacy_policy_organizer', 'terms_use_organizer', 'app_version', 'or_onesignal_app_id', 'or_onesignal_project_number', 'footer_copyright', 'currency_sybmol']);
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function viewTax()
    {
        $data = Tax::where('id', '!=', 1)
            ->where('allow_all_bill', 1)
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->get();
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function taxDetail($id)
    {
        $data = Tax::find($id);
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function deleteOrder($id)
    {
        $data = Order::find($id);
        $data->delete();
        return response()->json(['msg' => 'Order deleted successfully.', 'success' => true], 200);
    }

    public function refundOrder(Request $request, $id = null)
    {
        $orderId = $id ?? $request->input('id') ?? $request->input('order_id');
        if (!$orderId) {
            return response()->json(['success' => false, 'msg' => 'Order ID is required.'], 400);
        }

        $order = Order::with(['orderChild', 'event'])->find($orderId);
        if (!$order) {
            $order = Order::with(['orderChild', 'event'])->where('order_id', $orderId)->first();
        }

        if (!$order) {
            return response()->json(['success' => false, 'msg' => 'Order not found.'], 404);
        }

        if (Auth::user() && Auth::user()->hasRole('Organizer') && $order->organization_id != Auth::user()->id) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized: You do not own this order.'], 403);
        }

        if ($order->payment_status == 2 || $order->order_status === 'Refunded') {
            return response()->json(['success' => false, 'msg' => 'Order #' . $order->order_id . ' is already refunded.'], 400);
        }

        try {
            DB::beginTransaction();

            // 1. Update Order status in database
            $order->order_status = 'Refunded';
            $order->payment_status = 2; // 2 = Refunded
            $order->save();

            // 2. Reset OrderChild status
            OrderChild::where('order_id', $order->id)->update(['status' => 0]);

            // 3. Release venue seats (EventVenueSeat)
            $childSeatIds = $order->orderChild ? $order->orderChild->pluck('event_venue_seat_id')->filter()->toArray() : [];
            $seatQuery = \App\Models\EventVenueSeat::where('booked_order_id', $order->id);
            if (!empty($childSeatIds)) {
                $seatQuery->orWhereIn('id', $childSeatIds);
            }
            $seatQuery->update([
                'status'                => \App\Models\EventVenueSeat::STATUS_AVAILABLE,
                'booked_order_id'       => null,
                'booked_order_child_id' => null,
                'booked_at'             => null,
                'hold_token'            => null,
                'held_by_session_id'    => null,
                'held_by_app_user_id'   => null,
                'held_by_guest_user_id' => null,
                'held_at'               => null,
                'hold_expires_at'       => null,
            ]);

            DB::commit();

            // Record Activity Log
            try {
                AdminActivityLog::record(
                    'order_refunded',
                    $order,
                    'Order Refunded',
                    'Order #' . $order->order_id . ' was refunded by ' . (Auth::user() ? Auth::user()->name : 'Organizer'),
                    [
                        'order_id' => $order->order_id,
                        'order_db_id' => $order->id,
                        'event_name' => $order->event?->name ?? 'N/A',
                        'refunded_amount' => $order->payment,
                    ]
                );
            } catch (\Exception $ae) {
                Log::warning("[Organizer API Refund] Could not record AdminActivityLog: " . $ae->getMessage());
            }

            // Send Refund Email to Customer
            try {
                $customerEmail = null;
                $customerName = 'Customer';

                if ($order->appUser && !empty($order->appUser->email)) {
                    $customerEmail = $order->appUser->email;
                    $customerName = trim(($order->appUser->name ?? '') . ' ' . ($order->appUser->last_name ?? ''));
                } elseif ($order->guestUser && !empty($order->guestUser->email)) {
                    $customerEmail = $order->guestUser->email;
                    $customerName = trim(($order->guestUser->name ?? '') . ' ' . ($order->guestUser->last_name ?? ''));
                }

                if ($customerEmail) {
                    $currency = Setting::first()->currency_sybmol ?? '$';
                    Mail::to($customerEmail)->send(new \App\Mail\OrderRefund($order, [
                        'customer_name' => $customerName ?: 'Customer',
                        'currency' => $currency
                    ]));
                    Log::info("[Organizer API Refund] Customer refund notification email sent to {$customerEmail}");
                }
            } catch (\Exception $me) {
                Log::error("[Organizer API Refund] Error sending customer refund email: " . $me->getMessage());
            }

            return response()->json([
                'success' => true,
                'msg' => 'Order refunded successfully and seat(s) released.',
                'data' => [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'payment_status' => $order->payment_status,
                    'order_status' => $order->order_status,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[Organizer API Refund] Error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => 'Error refunding order: ' . $e->getMessage()], 500);
        }
    }

    public function sendTicketEmail(Request $request)
    {
        $ticketNumber = $request->input('ticket_number') ?? $request->input('order_id');
        if (!$ticketNumber) {
            return response()->json(['success' => false, 'msg' => 'Ticket number or Order ID is required.'], 400);
        }

        // 1. Find OrderChild by ticket_number
        $childOrder = OrderChild::with(['order.customer', 'order.guestUser', 'order.event', 'order.ticket'])
            ->where('ticket_number', $ticketNumber)
            ->first();

        if ($childOrder && $childOrder->order) {
            $order = $childOrder->order;
            $qrValue = $childOrder->ticket_number;
        } else {
            // 2. Fallback: Find Order by order_id or id
            $order = Order::with(['customer', 'guestUser', 'event', 'ticket', 'orderChild'])
                ->where('order_id', $ticketNumber)
                ->orWhere('id', $ticketNumber)
                ->first();
            $qrValue = $ticketNumber;
        }

        if (!$order) {
            return response()->json(['success' => false, 'msg' => 'Order or ticket not found for: ' . $ticketNumber], 404);
        }

        // Verify organizer ownership of event/order
        if (Auth::user() && Auth::user()->hasRole('Organizer') && $order->organization_id != Auth::user()->id) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized: You do not own the event associated with this ticket.'], 403);
        }

        // 3. Determine target email address
        $targetEmail = $request->input('email');
        $customerName = 'Customer';

        if (!$targetEmail) {
            if ($order->customer && !empty($order->customer->email)) {
                $targetEmail = $order->customer->email;
                $customerName = trim(($order->customer->name ?? '') . ' ' . ($order->customer->last_name ?? ''));
            } elseif ($order->guestUser && !empty($order->guestUser->email)) {
                $targetEmail = $order->guestUser->email;
                $customerName = trim(($order->guestUser->name ?? '') . ' ' . ($order->guestUser->last_name ?? ''));
            }
        }

        if (!$targetEmail) {
            return response()->json(['success' => false, 'msg' => 'No email address found for this ticket.'], 400);
        }

        try {
            $setting = Setting::first();
            $template = NotificationTemplate::where('title', 'Ticket Booked')->first();

            $details = [
                'user_name'  => $customerName ?: 'Customer',
                'quantity'   => $order->quantity,
                'event_name' => $order->event?->name ?? 'Event',
                'date'       => $order->event && $order->event->start_time ? Carbon::parse($order->event->start_time)->format('d F Y h:i a') : 'N/A',
                'app_name'   => $setting->app_name ?? 'Teptix',
            ];

            $mailContent = $template->mail_content ?? 'Thank you for your order! Your ticket and QR code are attached below.';
            $subject = $template->subject ?? 'Your Event Ticket & QR Code';

            Mail::to($targetEmail)->send(new TicketBook($mailContent, $details, $subject, $qrValue));

            return response()->json([
                'success' => true,
                'msg'     => 'Ticket email with QR code sent successfully to ' . $targetEmail,
                'data'    => [
                    'ticket_number' => $qrValue,
                    'order_id'      => $order->order_id,
                    'sent_to'       => $targetEmail,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("[Send Ticket Email API] Exception: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => 'Failed to send ticket email: ' . $e->getMessage()], 500);
        }
    }

    public function organizationDashboard(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'msg' => 'Unauthenticated.'], 401);
        }

        $userId = $user->id;
        $setting = Setting::first();
        $currency = $setting->currency_sybmol ?? '$';
        $timezone = $setting->timezone ?? 'UTC';

        // Verification Status
        $verificationContactDate = null;
        $organizerVerificationStatus = null;
        if ($user->hasRole('Organizer') && (int) $user->is_verify !== 1) {
            $organizerVerificationStatus = OrganizerVerificationStatus::status($user);
            $verificationContactDate = $organizerVerificationStatus['contact_date'] ?? null;
        }

        // Metrics Summary
        $totalTickets = Ticket::where('user_id', $userId)->sum('quantity');
        $quantities = Order::where('organization_id', $userId)->pluck('quantity');
        $usedTickets = $quantities->sum(function ($q) {
            return (int) trim($q);
        });

        $totalEvents = Event::whereRaw('FIND_IN_SET(?, user_id)', [$userId])->where('is_deleted', 0)->count();
        $totalOrder = Order::where('organization_id', $userId)->count();
        $pendingOrder = Order::where([['order_status', 'Pending'], ['organization_id', $userId]])->count();
        $completeOrder = Order::where([['order_status', 'Complete'], ['organization_id', $userId]])->count();
        $cancelOrder = Order::where([['order_status', 'Cancel'], ['organization_id', $userId]])->count();
        $refundedOrder = Order::where([['payment_status', 2], ['organization_id', $userId]])->orWhere([['order_status', 'Refunded'], ['organization_id', $userId]])->count();
        $eventsPeople = Event::whereRaw('FIND_IN_SET(?, user_id)', [$userId])->where('is_deleted', 0)->sum('people');

        // Calculate earnings as SUM(payment) - SUM(tax) for completed orders
        $sumPayment = DB::table('orders')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->whereRaw('FIND_IN_SET(?, events.user_id)', [$userId])
            ->where('orders.payment_status', 1)
            ->sum('orders.payment');

        $sumTax = DB::table('orders')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->whereRaw('FIND_IN_SET(?, events.user_id)', [$userId])
            ->where('orders.payment_status', 1)
            ->sum('orders.tax');

        $earnings = (float) ($sumPayment - $sumTax);

        // Total Tickets Sold calculation
        $soldFromChildren = OrderChild::whereHas('order', function ($query) use ($userId) {
            $query->where('organization_id', $userId)
                  ->where('order_status', 'Complete');
        })->count();

        $soldFromOrders = Order::where([['order_status', 'Complete'], ['organization_id', $userId]])
            ->get()
            ->sum(function ($o) {
                return (int) trim($o->quantity);
            });

        $totalTicketsSold = max($soldFromChildren, $soldFromOrders);

        // Scanners
        $totalScanners = User::role('scanner')->where('org_id', $userId)->count();
        $scanners = User::role('scanner')->where('org_id', $userId)->select('id', 'first_name', 'last_name', 'email', 'phone', 'status', 'image')->orderBy('id', 'DESC')->get();

        // Current Month Events Summary
        $currentDate = Carbon::now($timezone);
        $startOfMonth = $currentDate->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $endOfMonth = $currentDate->copy()->endOfMonth()->format('Y-m-d H:i:s');

        $monthEvents = Event::whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->where([['status', 1], ['is_deleted', 0]])
            ->whereRaw('FIND_IN_SET(?, user_id)', [$userId])
            ->orderBy('start_time', 'ASC')
            ->get();

        foreach ($monthEvents as $value) {
            $value->tickets = Ticket::where('event_id', $value->id)->sum('quantity');
            $value->sold_ticket = OrderChild::whereHas('order', function ($query) use ($value) {
                $query->where('event_id', $value->id)
                      ->where('order_status', 'Complete');
            })->count();
            $value->average = $value->tickets == 0 ? 0 : round(($value->sold_ticket * 100 / $value->tickets), 2);
        }

        // Upcoming / Filtered Events List
        $date = Carbon::now($timezone);
        $eventsQuery = Event::with(['category:id,name'])
            ->where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d H:i:s')]])
            ->whereRaw('FIND_IN_SET(?, user_id)', [$userId]);

        if ($request->has('type') && $request->type != null) {
            $eventsQuery->where('type', $request->type);
        }
        if ($request->has('category') && $request->category != null) {
            $eventsQuery->where('category_id', $request->category);
        }
        if ($request->has('duration') && $request->duration != null) {
            if ($request->duration == 'Today') {
                $temp = Carbon::now($timezone)->format('Y-m-d');
                $eventsQuery->whereBetween('start_time', [$temp . ' 00:00:00', $temp . ' 23:59:59']);
            } else if ($request->duration == 'Tomorrow') {
                $temp = Carbon::tomorrow($timezone)->format('Y-m-d');
                $eventsQuery->whereBetween('start_time', [$temp . ' 00:00:00', $temp . ' 23:59:59']);
            } else if ($request->duration == 'ThisWeek') {
                $now = Carbon::now($timezone);
                $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i:s');
                $weekEndDate = $now->endOfWeek()->format('Y-m-d H:i:s');
                $eventsQuery->whereBetween('start_time', [$weekStartDate, $weekEndDate]);
            } else if ($request->duration == 'date' && isset($request->date)) {
                $eventsQuery->whereBetween('start_time', [$request->date . ' 00:00:00', $request->date . ' 23:59:59']);
            }
        }
        $upcomingEvents = $eventsQuery->orderBy('start_time', 'ASC')->get();

        foreach ($upcomingEvents as $value) {
            $ticketsCount = Event::find($value->id)->people ?? 0;
            $soldCount = OrderChild::whereHas('order', function ($query) use ($value) {
                $query->where('event_id', $value->id)
                      ->where('order_status', 'Complete');
            })->count();
            $value->available = $ticketsCount - $soldCount;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => $currency,
                'earnings' => $earnings,
                'events_revenue' => $earnings,
                'total_revenue' => $earnings,
                'total_tickets_sold' => (int) $totalTicketsSold,
                'total_tickets_sale' => (int) $totalTicketsSold,
                'total_scanners' => (int) $totalScanners,
                'scanners' => $scanners,
                'summary' => [
                    'total_tickets' => (int) $totalTickets,
                    'used_tickets' => (int) $usedTickets,
                    'total_tickets_sold' => (int) $totalTicketsSold,
                    'total_tickets_sale' => (int) $totalTicketsSold,
                    'tickets_sold' => (int) $totalTicketsSold,
                    'events_revenue' => (float) $earnings,
                    'total_revenue' => (float) $earnings,
                    'total_scanners' => (int) $totalScanners,
                    'total_events' => (int) $totalEvents,
                    'total_orders' => (int) $totalOrder,
                    'pending_orders' => (int) $pendingOrder,
                    'complete_orders' => (int) $completeOrder,
                    'cancel_orders' => (int) $cancelOrder,
                    'refunded_orders' => (int) $refundedOrder,
                    'total_capacity' => (int) $eventsPeople,
                ],
                'month_events' => $monthEvents,
                'upcoming_events' => $upcomingEvents,
                'verification_status' => $organizerVerificationStatus,
                'verification_contact_date' => $verificationContactDate,
            ]
        ], 200);
    }

    public function addTax(Request $request)
    {
        $request->validate([
            'name' => 'bail|required',
            'amount_type' => 'bail|required',
            'price' => 'bail|required|numeric',
            'allow_all_bill' => 'bail|required|numeric',
        ]);
        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        $tax = Tax::create($data);
        return response()->json(['data' => $tax, 'msg' => 'Add Tax Successfully', 'success' => true], 200);
    }

    public function editTax(Request $request)
    {
        $request->validate([
            'name' => 'bail|required',
            'id' => 'bail|required',
            'amount_type' => 'bail|required',



            'price' => 'bail|required|numeric',
            'allow_all_bill' => 'bail|required|numeric',
        ]);
        $data = $request->all();
        Tax::find($request->id)->update($data);
        $tax = Tax::find($request->id);
        return response()->json(['data' => $tax, 'msg' => 'Update Tax Successfully', 'success' => true], 200);
    }


    public function deleteTax($id)
    {
        Tax::find($id)->delete();
        return response()->json(['msg' => 'Tax Deleted Successfully.', 'success' => true], 200);
    }
    public function updateTaxApi(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:0,1',
        'allow_all_bill' => 'nullable|boolean',
    ]);

    $tax = Tax::findOrFail($id);

    if ($tax->id == 1) {
        return response()->json(['msg' => 'Tax with id=1 cannot be updated.', 'success' => false], 403);
    }

    // Default allow_all_bill to 0 if not sent
    $allowAllBill = $request->has('allow_all_bill') ? $request->input('allow_all_bill') : 0;

    $tax->update([
        'status' => $request->input('status'),
        'allow_all_bill' => $allowAllBill,
        'updated_by' => Auth::user()->hasRole('Organizer') ? 1 : 0,
    ]);

    return response()->json(['data' => $tax, 'msg' => 'Tax updated successfully.', 'success' => true], 200);
}


    public function changeStatusTax($id, $status)
    {
        if ($id == 1) {
            return response()->json(['msg' => 'This tax cannot be edited.', 'success' => false], 403);
        }
        Tax::find($id)->update(['status' => $status]);
        $data = Tax::find($id);
        return response()->json(['data' => $data, 'msg' => 'Status Change Successfully', 'success' => true], 200);
    }

    public function ticketTax(Request $request)
    {
        $ids = $request->input('id');

        // Support both array and comma-separated string
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $ids = is_array($ids) ? $ids : [$ids];
        $result = [];

        foreach ($ids as $id) {
            $ticket = Ticket::find($id);
            if (!$ticket) {
                $result[$id] = ['error' => 'Ticket not found'];
                continue;
            }

            // Get all tax IDs from the ticket
            $taxIds = array_filter(explode(',', $ticket->tax_id));

            // Calculate total tax for this ticket
            $totalTax = 0;
            $ticketPrice = $ticket->price ?? 0;
            $taxDetails = [];

            // Process each tax ID individually to match FrontendController logic
            foreach ($taxIds as $taxId) {
                $tax = Tax::where('status', 1)
                         ->where('allow_all_bill', 1)
                         ->where('id', trim($taxId))
                         ->first();

                if ($tax) {
                    $taxAmount = 0;
                    if ($tax->amount_type === 'percentage') {
                        // Calculate percentage of ticket price
                        $taxAmount = round(($ticketPrice * $tax->price) / 100, 2);
                    } else {
                        // Fixed price amount
                        $taxAmount = round($tax->price, 2);
                    }

                    $totalTax += $taxAmount;
                    $taxDetails[] = [
                        'tax_id' => $tax->id,
                        'name' => $tax->name,
                        'amount_type' => $tax->amount_type,
                        'rate' => $tax->price,
                        'calculated_amount' => $taxAmount
                    ];
                }
            }

            // Fetch all taxes for response (to maintain backward compatibility)
            $taxes = Tax::where('status', 1)
                        ->where('allow_all_bill', 1)
                        ->whereIn('id', $taxIds)
                        ->orderBy('id', 'DESC')
                        ->get();

            // Return taxes with calculated total
            $result[$id] = [
                'taxes' => $taxes,
                'tax_details' => $taxDetails,
                'ticket_price' => $ticketPrice,
                'total_tax' => round($totalTax, 2),
                'final_price' => round($ticketPrice + $totalTax, 2)
            ];
        }

        return response()->json(['success' => true, 'msg' => null, 'data' => $result], 200);
    }


    public function viewFaq()
    {
        $data = Faq::where('status', 1)->orderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at']);
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function addFeedback(Request $request)
    {
        $request->validate([
            'email' => 'bail|required|email',
            'message' => 'bail|required',
            'rate' => 'bail|required|numeric',
        ]);
        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        $feedback = Feedback::create($data);
        return response()->json(['data' => $feedback, 'msg' => 'Add FeedBack Successfully', 'success' => true], 200);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'bail|required',
            'password' => 'bail|required|min:6',
            'password_confirmation' => 'bail|required|same:password|min:6'
        ]);
        if (Hash::check($request->old_password, Auth::user()->password)) {
            User::find(Auth::user()->id)->update(['password' => Hash::make($request->password)]);
            return response()->json(['success' => true, 'msg' => 'Your password is change successfully', 'data' => null], 200);
        } else {
            return response()->json(['success' => false, 'msg' => 'Current Password is wrong!', 'data' => null], 200);
        }
    }

    public function editProfile(Request $request)
    {
        User::find(Auth::user()->id)->update($request->all());
        $user = User::find(Auth::user()->id);
        return response()->json(['success' => true, 'msg' => 'Update Profile Successfully', 'data' => $user], 200);
    }
    public function deleteProfileImage()
    {
        $user = User::find(Auth::user()->id);
        $oldImage = $user->image;

        // Only delete if it's not the default image
        if ($oldImage && $oldImage !== 'defaultuser.png') {
            // Delete the old image file
            (new AppHelper)->deleteFile($oldImage);
        }

        // Set image back to default
        $user->update(['image' => 'defaultuser.png']);

        return response()->json([
            'success' => true,
            'msg' => 'Profile image deleted successfully',
            'data' => null
        ], 200);
    }

    /**
     * Delete organizer account (authenticated)
     * DELETE /api/organization/delete-account
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccount(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'password' => 'required|string',
            ]);

            // Get the authenticated organizer
            $organizer = Auth::user();

            if (!$organizer) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Unauthorized. Please login first.'
                ], 401);
            }

            // Verify password
            if (!Hash::check($request->password, $organizer->password)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Invalid password. Account deletion cancelled.'
                ], 422);
            }

            // Get the user model and update it
            $user = User::find($organizer->id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'msg' => 'User not found.'
                ], 404);
            }

            // Mark the account as soft deleted with timestamp
            $user->deleted_softaccount = Carbon::now();
            $user->status = 0; // Disable account
            $user->image = 'defaultuser.png'; // Set default image instead of null
            $user->save();

            // Also soft delete the user
            $user->delete();

            return response()->json([
                'success' => true,
                'msg' => 'Your account has been successfully deleted.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Organizer account deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'An error occurred while deleting your account. Please try again.'
            ], 500);
        }
    }


    public function followers()
    {
        $user_id = Auth::user()->followers;
        $user  = AppUser::whereIn('id', $user_id)->get()->makeHidden(['created_at', 'updated_at', 'provider_token', 'provider', 'lang', 'lat', 'favorite', 'email_verified_at']);
        return response()->json(['success' => true, 'msg' => null, 'data' => $user], 200);
    }

    public function notifications()
    {
        $data = Notification::where('organizer_id', Auth::user()->id)->orderBy('id', 'DESC')->get()->each->setAppends(['event']);
        return response()->json(['success' => true, 'msg' => null, 'data' => $data], 200);
    }

    public function clearNotification()
    {
        $noti = Notification::where('organizer_id', Auth::user()->id)->get();
        foreach ($noti as $value) {
            $value->delete();
        }
        return response()->json(['success' => true, 'msg' => 'Notification Deleted Successfully.'], 200);
    }

    /**
     * Delete organizer notification (authenticated)
     * DELETE /api/organization/delete-notification/{id}
     */
    public function deleteNotification($id)
    {
        $notification = Notification::where('id', $id)
            ->where('organizer_id', Auth::user()->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'msg' => 'Notification not found.'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'msg' => 'Notification deleted successfully.'
        ], 200);
    }


    public function couponEvent()
    {
        $data = Event::where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['user_id', Auth::user()->id]])->orderBy('id', 'DESC')->get(['id', 'name'])->each->setAppends([]);
        return response()->json(['success' => true, 'msg' => null, 'data' => $data], 200);
    }

    public function coupons()
    {
        $coupon = Coupon::with(['event:id,name,image'])->where('user_id', Auth::user()->id)->OrderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at']);
        return response()->json(['success' => true, 'msg' => null, 'data' => $coupon], 200);
    }

    public function addCoupon(Request $request)
    {
        $request->validate([
            'name' => 'bail|required',
            'event_id' => 'bail|required',
            'discount' => 'bail|required',
            'start_date' => 'bail|required|date_format:Y-m-d',
            'end_date' => 'bail|required|date_format:Y-m-d',
            'max_use' => 'bail|required',
            'description' => 'bail|required',
            'minimum_amount' => 'bail|required',
            'maximum_discount' => 'bail|required',
            'discount_type' => 'bail|required',

        ]);
        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        $data['status'] = 1;
        $data['coupon_code'] =  chr(rand(65, 90)) . chr(rand(65, 90)) . '-' . rand(9999, 100000);
        $coupon = Coupon::create($data);

        // Record activity log with organizer's user ID (Auth::id())
        AdminActivityLog::record(
            AdminActivityLog::PROMOTION_CREATED,
            $coupon,
            'Coupon created via Organizer API',
            $coupon->name . ' was created.',
            [
                'coupon_id'     => $coupon->id,
                'coupon_name'   => $coupon->name,
                'coupon_code'   => $coupon->coupon_code,
                'discount'      => $coupon->discount,
                'discount_type' => $coupon->discount_type,
                'event_id'      => $coupon->event_id,
                'user_id'       => Auth::id(),
            ],
            $request
        );

        return response()->json(['success' => true, 'msg' => 'Add Coupon Successfully', 'data' => $coupon], 200);
    }

    public function editCoupon(Request $request)
    {
        $request->validate([
            'name' => 'bail|required',
            'id' => 'bail|required',
            'event_id' => 'bail|required',
            'discount' => 'bail|required',
            'start_date' => 'bail|required|date_format:Y-m-d',
            'end_date' => 'bail|required|date_format:Y-m-d',
            'max_use' => 'bail|required',
            'description' => 'bail|required',
            'minimum_amount' => 'bail|required',
            'maximum_discount' => 'bail|required',
            'discount_type' => 'bail|required',
        ]);
        $data = $request->all();
        $coupon =  Coupon::find($request->id)->update($data);
        return response()->json(['success' => true, 'msg' => null, 'data' => $coupon], 200);
    }

    public function couponDetail($id)
    {
        $data = Coupon::findOrFail($id)->makeHidden(['created_at', 'updated_at']);
        $data->event_name = Event::find($data->event_id)->name;
        return response()->json(['success' => true, 'msg' => 'Update Coupon Successfully', 'data' => $data], 200);
    }

    public function deleteCoupon($id)
    {
        $data = Coupon::find($id);
        $data->delete();
        return response()->json(['success' => true, 'msg' => 'Coupon Deleted Successfully.'], 200);
    }

    public function  removeGalleryImage(Request $request)
    {
        $request->validate([
            'image' => 'bail|required',
            'id' => 'bail|required',
        ]);
        $gallery = array_filter(explode(',', Event::find($request->id)->gallery));
        if (count(array_keys($gallery, $request->image)) > 0) {
            if (($key = array_search($request->image, $gallery)) !== false) {
                unset($gallery[$key]);
            }
        }
        $aa = implode(',', $gallery);
        $data = Event::find($request->id);
        $data->gallery = $aa;
        $data->update();
        return response()->json(['success' => true, 'msg' => null], 200);
    }

    /**
     * Create order for organizer with tax options
     *
     * Tax Options:
     * - with_tax: Apply all active taxes (default)
     * - without_tax: No taxes applied
     * - complimentary: Free order (payment = 0)
     * - custom_amount: Apply custom discount (tax_custom_amount required)
     *
     * Parameters:
     * - ticket_id (required): ID of the ticket
     * - email (required): Customer email
     * - name (optional): Customer name
     * - phone (required): Customer phone
     * - quantity (required): Number of tickets (min: 1)
     * - tax_option (optional): with_tax|without_tax|complimentary|custom_amount (default: with_tax)
     * - tax_custom_amount (required if tax_option=custom_amount): Discount amount
     *
     * Example requests:
     * 1. With tax (default):
     *    {"ticket_id": 1, "email": "user@example.com", "phone": "1234567890", "quantity": 2}
     *
     * 2. Without tax:
     *    {"ticket_id": 1, "email": "user@example.com", "phone": "1234567890", "quantity": 2, "tax_option": "without_tax"}
     *
     * 3. Complimentary (free):
     *    {"ticket_id": 1, "email": "user@example.com", "phone": "1234567890", "quantity": 2, "tax_option": "complimentary"}
     *
     * 4. Custom discount:
     *    {"ticket_id": 1, "email": "user@example.com", "phone": "1234567890", "quantity": 2, "tax_option": "custom_amount", "tax_custom_amount": 50}
     *
     * Referenced from UserController::organizerCreateOrder
     */
    // public function createOrderOrganizer(Request $request)
    // {
    //     Log::info('=== createOrderOrganizer request received ===', $request->all());

    //     $request->validate([
    //         'ticket_id' => 'bail|required',
    //         'email' => 'bail|required|email',
    //         'name' => 'bail|nullable|string',
    //         'phone' => 'bail|required|string',
    //         'quantity' => 'bail|required',
    //         'tax_option' => 'bail|nullable|in:with_tax,without_tax,complimentary,custom_amount',
    //         'tax_custom_amount' => 'required_if:tax_option,custom_amount|nullable|numeric|min:0',
    //         'seat_ids' => 'nullable',
    //         'event_seat_id' => 'nullable',
    //         'venue_seat_ids' => 'nullable',
    //         'book_seats' => 'nullable',
    //     ]);

    //     $data = $request->except([
    //         'tax_data', 'tax_ids', 'venue_seat_ids', 'guest_hold_key',
    //         'email', 'name', 'phone', 'seat_ids', 'event_seat_id'
    //     ]);

    //     $rawTicketIds = is_array($request->ticket_id) ? $request->ticket_id : explode(',', (string) $request->ticket_id);
    //     $firstTicketId = (int) reset($rawTicketIds);
    //     $ticket = Ticket::find($firstTicketId);

    //     if (!$ticket) {
    //         return response()->json([
    //             'success' => false,
    //             'msg' => 'Ticket not found'
    //         ], 404);
    //     }

    //     // Validate seat availability if specific venue map seats are passed
    //     $seatIds = [];
    //     if ($request->has('seat_details') && !empty($request->seat_details)) {
    //         $detailsArray = is_string($request->seat_details) ? json_decode($request->seat_details, true) : $request->seat_details;
    //         if (is_array($detailsArray)) {
    //             foreach ($detailsArray as $sDetail) {
    //                 if (is_array($sDetail) && isset($sDetail['seat_id'])) {
    //                     $seatIds[] = (int) $sDetail['seat_id'];
    //                 } elseif (is_object($sDetail) && isset($sDetail->seat_id)) {
    //                     $seatIds[] = (int) $sDetail->seat_id;
    //                 }
    //             }
    //         }
    //     }

    //     if (empty($seatIds)) {
    //         $rawSeatInput = $request->input('venue_seat_ids', $request->input('book_seats', $request->input('seat_ids', $request->input('event_seat_id'))));
    //         if (!empty($rawSeatInput)) {
    //             if (is_array($rawSeatInput)) {
    //                 $seatIds = array_map('intval', $rawSeatInput);
    //             } else {
    //                 $seatIds = array_filter(array_map('intval', explode(',', (string) $rawSeatInput)));
    //             }
    //         }
    //     }

    //     $requestedSeats = collect();
    //     if (!empty($seatIds)) {
    //         $requestedSeats = \App\Models\EventVenueSeat::where('event_id', $ticket->event_id)
    //             ->whereIn('id', $seatIds)
    //             ->get()
    //             ->values();

    //         if ($requestedSeats->count() !== count($seatIds)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'msg' => 'One or more requested seats were not found for this event.'
    //             ], 400);
    //         }

    //         foreach ($requestedSeats as $seatItem) {
    //             if ($seatItem->status === \App\Models\EventVenueSeat::STATUS_BOOKED) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'msg' => "Seat '{$seatItem->seat_label}' is already booked."
    //                 ], 400);
    //             }
    //             if ($seatItem->status === \App\Models\EventVenueSeat::STATUS_BLOCKED) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'msg' => "Seat '{$seatItem->seat_label}' is blocked and unavailable."
    //                 ], 400);
    //             }
    //         }
    //     }

    //     // Validate ticket quantity availability
    //     $totalTicketQuantity = $ticket->quantity;

    //     // Sum of all quantities from orders for this ticket (handle comma-separated ticket_ids)
    //     $bookedQuantity = 0;
    //     $orders = Order::where('event_id', $ticket->event_id)->get();

    //     foreach ($orders as $order) {
    //         $ticketIds = explode(',', $order->ticket_id);
    //         $quantities = explode(',', $order->quantity);

    //         foreach ($ticketIds as $index => $tid) {
    //             if ((int)trim($tid) === $ticket->id) {
    //                 if (isset($quantities[$index])) {
    //                     $bookedQuantity += (int)trim($quantities[$index], '"');
    //                 }
    //             }
    //         }
    //     }

    //     $requestedQuantity = is_array($request->quantity) ? (int) array_sum($request->quantity) : (int) $request->quantity;
    //     $availableQuantity = $totalTicketQuantity - $bookedQuantity;

    //     // Check if requested quantity exceeds available quantity
    //     if ($requestedQuantity > $availableQuantity) {
    //         return response()->json([
    //             'success' => false,
    //             'msg' => "Only {$availableQuantity} tickets available. You requested {$requestedQuantity} tickets."
    //         ], 400);
    //     }

    //     $event = Event::find($ticket->event_id);

    //     if (!$event) {
    //         return response()->json([
    //             'success' => false,
    //             'msg' => 'Event not found'
    //         ], 404);
    //     }

    //     $org = User::find($event->user_id);

    //     // Verify that the authenticated user is admin or owns this event
    //     // Admin check: user_id = 1 OR has 'admin' role
    //     $isAdmin = (Auth::user()->id === 1) || Auth::user()->hasRole('admin');
    //     $isEventOwner = Auth::user()->id === $org->id;

    //     if (!$isAdmin && !$isEventOwner) {
    //         return response()->json([
    //             'success' => false,
    //             'msg' => 'Unauthorized. You can only create orders for your own events.',
    //             'debug' => [
    //                 'user_id' => Auth::user()->id,
    //                 'is_admin' => $isAdmin,
    //                 'is_event_owner' => $isEventOwner,
    //                 'event_owner_id' => $org->id
    //             ]
    //         ], 403);
    //     }

    //     // Find or create customer by email
    //     $email = $request->email;
    //     $appUser = AppUser::where('email', $email)->first();

    //     if (!$appUser) {
    //         // Create new customer
    //         $appUser = AppUser::create([
    //             'email' => $email,
    //             'password' => bcrypt('123456'),
    //             'name' => $request->name ?? 'Customer',
    //             'phone' => $request->phone,
    //             'provider' => 'LOCAL',
    //             'is_verify' => 1,
    //         ]);
    //     } else {
    //         // Update existing customer info
    //         $appUser->update([
    //             'phone' => $request->phone,
    //             'name' => $request->name ?? $appUser->name,
    //         ]);
    //     }

    //     // Calculate base payment (ticket price * quantity)
    //     $basePayment = $ticket->price * $requestedQuantity;

    //     // Get tax option (default: with_tax)
    //     $taxOption = $request->tax_option ?? 'with_tax';
    //     $totalTax = [];

    //     // Handle tax based on tax_option
    //     if ($taxOption === 'with_tax') {
    //         // Apply taxes normally
    //         $allTax = Tax::where(['status' => 1, 'allow_all_bill' => 1])->get();
    //         foreach ($allTax as $key => $value) {
    //             if ($value->amount_type == 'percentage') {
    //                 $totalTax[$key]['id'] = $value->id;
    //                 $totalTax[$key]['price'] = $basePayment * $value->price / 100;
    //             }
    //             if ($value->amount_type == 'price') {
    //                 $totalTax[$key]['id'] = $value->id;
    //                 $totalTax[$key]['price'] = $value->price;
    //             }
    //         }
    //     } elseif ($taxOption === 'without_tax') {
    //         // No tax applied
    //         $totalTax = [];
    //     } elseif ($taxOption === 'custom_amount') {
    //         // custom_amount: treat as replacement price per ticket
    //         $customPricePerTicket = isset($request->tax_custom_amount) ? floatval($request->tax_custom_amount) : 0;
    //         $customTotal = $customPricePerTicket * $requestedQuantity;

    //         // Validate custom price per ticket doesn't exceed original ticket price
    //         if ($customPricePerTicket > $ticket->price) {
    //             return response()->json([
    //                 'success' => false,
    //                 'msg' => "Custom amount per ticket ({$customPricePerTicket}) cannot exceed ticket price ({$ticket->price})."
    //             ], 400);
    //         }

    //         $totalDiscount = $basePayment - $customTotal; // e.g. (100-50)*6 = 300
    //         if ($totalDiscount > 0) {
    //             // Store as negative value so it subtracts from base payment
    //             $totalTax[] = ['id' => 0, 'price' => -$totalDiscount];
    //         } else {
    //             $totalTax = [];
    //         }
    //     } elseif ($taxOption === 'complimentary') {
    //         // Set payment to 0 and no tax
    //         $basePayment = 0;
    //         $totalTax = [];
    //     }

    //     // Calculate total tax amount
    //     $taxAmount = array_sum(array_column($totalTax, 'price'));

    //     // Calculate commission on base payment (before tax)
    //     $com = Setting::find(1, ['org_commission_type', 'org_commission']);
    //     $orgCommission = 0;

    //     if ($taxOption === 'complimentary') {
    //         $orgCommission = 0;
    //     } else {
    //         if ($com->org_commission_type == "percentage") {
    //             $orgCommission = $basePayment * $com->org_commission / 100;
    //         } else if ($com->org_commission_type == "amount") {
    //             $orgCommission = $com->org_commission;
    //         }
    //     }

    //     // Set final payment amount
    //     if ($taxOption === 'complimentary') {
    //         $finalPayment = 0;
    //     } else {
    //         $finalPayment = $basePayment + $taxAmount;
    //     }

    //     // Prepare order data
    //     $data['order_id'] = '#' . rand(9999, 100000);
    //     $data['event_id'] = $event->id;
    //     $data['customer_id'] = $appUser->id;
    //     $data['organization_id'] = $org->id;
    //     // $data['order_status'] = 'Pending';
    //      $data['order_status'] = 'Complete';
    //     $data['ticket_id'] = is_array($request->ticket_id) ? implode(',', $request->ticket_id) : $ticket->id;
    //     $data['quantity'] = $requestedQuantity;
    //     $data['payment'] = $finalPayment;
    //     $data['tax'] = $taxAmount;
    //     $data['org_commission'] = $orgCommission;
    //     $data['tax_option'] = $taxOption;

    //     if (isset($data['book_seats']) && is_array($data['book_seats'])) {
    //         $data['book_seats'] = json_encode($data['book_seats']);
    //     }
    //     if (isset($data['seat_details']) && is_array($data['seat_details'])) {
    //         $data['seat_details'] = json_encode($data['seat_details']);
    //     }

    //     $data['tax_custom_amount'] = $taxOption === 'custom_amount' ? floatval($request->tax_custom_amount ?? 0) : 0;

    //     // Set payment type and status based on tax_option
    //     if ($taxOption === 'complimentary') {
    //         $data['payment_type'] = 'FREE';
    //         $data['payment_status'] = 0; // 0 = FREE / Pending
    //     } else {
    //         $data['payment_type'] = 'LOCAL';
    //         $data['payment_status'] = 0; // 0 = Pending
    //     }

    //     try {
    //         $order = Order::create($data);

    //         // Build map of seat_id -> ticket_id from seat_details if present
    //         $seatTicketMap = [];
    //         if ($request->has('seat_details') && !empty($request->seat_details)) {
    //             $detailsArray = is_string($request->seat_details) ? json_decode($request->seat_details, true) : $request->seat_details;
    //             if (is_array($detailsArray)) {
    //                 foreach ($detailsArray as $sDetail) {
    //                     $sId = (int) ($sDetail['seat_id'] ?? $sDetail['seat_id'] ?? 0);
    //                     if (is_object($sDetail)) {
    //                         $sId = (int) ($sDetail->seat_id ?? 0);
    //                         $tId = (int) ($sDetail->ticket_id ?? 0);
    //                     } else {
    //                         $tId = (int) ($sDetail['ticket_id'] ?? 0);
    //                     }
    //                     if ($sId > 0 && $tId > 0) {
    //                         $seatTicketMap[$sId] = $tId;
    //                     }
    //                 }
    //             }
    //         }

    //         // Build array of ticket_ids corresponding to each child index
    //         $expandedTicketIds = [];
    //         if (is_array($request->ticket_id) && is_array($request->quantity)) {
    //             foreach ($request->ticket_id as $idx => $tId) {
    //                 $qty = isset($request->quantity[$idx]) ? (int) $request->quantity[$idx] : 1;
    //                 for ($q = 0; $q < $qty; $q++) {
    //                     $expandedTicketIds[] = (int) $tId;
    //                 }
    //             }
    //         }

    //         // Create OrderChild records for each ticket quantity
    //         for ($i = 0; $i < $requestedQuantity; $i++) {
    //             $child = [];
    //             $child['ticket_number'] = uniqid();
    //             $child['order_id'] = $order->id;
    //             $child['customer_id'] = $appUser->id;

    //             $assignedSeat = null;
    //             $childTicketId = $expandedTicketIds[$i] ?? $ticket->id;

    //             if (isset($requestedSeats[$i])) {
    //                 $assignedSeat = $requestedSeats[$i];
    //                 if (isset($seatTicketMap[$assignedSeat->id])) {
    //                     $childTicketId = $seatTicketMap[$assignedSeat->id];
    //                 }
    //                 $seatLabel = $assignedSeat->seat_label ?: trim($assignedSeat->section_name . ' ' . $assignedSeat->row_name . ' Seat ' . $assignedSeat->seat_number);

    //                 $child['event_venue_seat_id'] = $assignedSeat->id;
    //                 $child['seat_id'] = $assignedSeat->id;
    //                 $child['Book_Seat_Id'] = $seatLabel;
    //             }

    //             $childTicket = Ticket::find($childTicketId) ?: $ticket;
    //             $child['ticket_id'] = $childTicket->id;
    //             $child['checkin'] = $childTicket->maximum_checkins ?? null;
    //             $child['paid'] = 1;

    //             $createdChild = \App\Models\OrderChild::create($child);

    //             if ($assignedSeat) {
    //                 $assignedSeat->update([
    //                     'status' => \App\Models\EventVenueSeat::STATUS_BOOKED,
    //                     'booked_order_id' => $order->id,
    //                     'booked_order_child_id' => $createdChild->id,
    //                     'booked_at' => now(),
    //                     'hold_token' => null,
    //                     'held_by_session_id' => null,
    //                     'held_by_app_user_id' => null,
    //                     'held_by_guest_user_id' => null,
    //                     'held_at' => null,
    //                     'hold_expires_at' => null,
    //                 ]);
    //             }
    //         }

    //         // Create OrderTax records
    //         if (!empty($totalTax)) {
    //             foreach ($totalTax as $value) {
    //                 $tax['order_id'] = $order->id;
    //                 $tax['tax_id'] = $value['id'];
    //                 $tax['price'] = $value['price'];
    //                 OrderTax::create($tax);
    //             }
    //         }

    //         // Send email notifications
    //         $setting = Setting::find(1);

    //         // Send user notification
    //         $ticketBookTemplate = NotificationTemplate::where('title', 'Book Ticket')->first();
    //         $detail['user_name'] = $appUser->name . ' ' . ($appUser->last_name ?? '');
    //         $detail['quantity'] = $requestedQuantity;
    //         $detail['event_name'] = $event->name;
    //         $detail['date'] = $event->start_time->format('d F Y h:i a');
    //         $detail['app_name'] = $setting->app_name;
    //         $noti_data = ["{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
    //         $message1 = $ticketBookTemplate
    //             ? str_replace($noti_data, $detail, $ticketBookTemplate->message_content)
    //             : "Ticket booked for {$event->name}.";

    //         $notification = array();
    //         $notification['organizer_id'] = null;
    //         $notification['user_id'] = $appUser->id;
    //         $notification['order_id'] = $order->id;
    //         $notification['title'] = 'Ticket Booked';
    //         $notification['message'] = $message1;
    //         Notification::create($notification);

    //         // Send push notification
    //         if ($setting->push_notification == 1 && $appUser->device_token != null) {
    //             (new AppHelper)->sendOneSignal('user', $appUser->device_token, $message1);
    //         }

    //         // Send user email
    //         if ($setting->mail_notification == 1) {
    //             (new AppHelper)->mailConfig();

    //             try {
    //                 $details['user_name'] = $appUser->name . ' ' . ($appUser->last_name ?? '');
    //                 $details['quantity'] = $requestedQuantity;
    //                 $details['event_name'] = $event->name;
    //                 $details['date'] = $event->start_time->format('d F Y h:i a');
    //                 $details['app_name'] = $setting->app_name;

    //                 if ($ticketBookTemplate) {
    //                     $qrcode = $order->order_id;
    //                     Mail::to($appUser->email)->send(new TicketBook($ticketBookTemplate->mail_content, $details, $ticketBookTemplate->subject, $qrcode));
    //                 }
    //             } catch (\Throwable $th) {
    //                 // Silent fail
    //             }

    //             // Send QR PDFs + invoice PDF in ONE email
    //             try {
    //                 $this->sendTicketQrAndInvoiceMailApi($order->id);
    //             } catch (\Throwable $th) {
    //                 // Silent fail
    //             }
    //         }

    //         // Send organizer notification
    //         $organizerBookTemplate = NotificationTemplate::where('title', 'Organizer Book Ticket')->first();
    //         $or_detail['organizer_name'] = $org->organization_name ?? $org->first_name . ' ' . $org->last_name;
    //         $or_detail['user_name'] = $appUser->name . ' ' . ($appUser->last_name ?? '');
    //         $or_detail['quantity'] = $requestedQuantity;
    //         $or_detail['event_name'] = $event->name;
    //         $or_detail['date'] = $event->start_time->format('d F Y h:i a');
    //         $or_detail['app_name'] = $setting->app_name;
    //         $or_noti_data = ["{{organizer_name}}", "{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
    //         $or_message1 = $organizerBookTemplate
    //             ? str_replace($or_noti_data, $or_detail, $organizerBookTemplate->message_content)
    //             : "New ticket booked for {$event->name}.";

    //         $or_notification = array();
    //         $or_notification['organizer_id'] = $org->id;
    //         $or_notification['user_id'] = null;
    //         $or_notification['order_id'] = $order->id;
    //         $or_notification['title'] = 'New Ticket Booked';
    //         $or_notification['message'] = $or_message1;
    //         Notification::create($or_notification);

    //         // Send organizer push notification
    //         if ($setting->push_notification == 1 && $org->device_token != null) {
    //             (new AppHelper)->sendOneSignal('organizer', $org->device_token, $or_message1);
    //         }

    //         // Send organizer email
    //         if ($setting->mail_notification == 1) {
    //             try {
    //                 $details1['organizer_name'] = $org->organization_name ?? $org->first_name . ' ' . $org->last_name;
    //                 $details1['user_name'] = $appUser->name . ' ' . ($appUser->last_name ?? '');
    //                 $details1['quantity'] = $requestedQuantity;
    //                 $details1['event_name'] = $event->name;
    //                 $details1['date'] = $event->start_time->format('d F Y h:i a');
    //                 $details1['app_name'] = $setting->app_name;

    //                 if ($organizerBookTemplate) {
    //                     Mail::to($org->email)->send(new TicketBookOrg($organizerBookTemplate->mail_content, $details1, $organizerBookTemplate->subject));
    //                 }
    //             } catch (\Throwable $th) {
    //                 // Silent fail
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'msg' => 'Order created successfully',
    //             'data' => [
    //                 'order' => $order,
    //                 'tax_option' => $taxOption,
    //                 'base_payment' => $basePayment,
    //                 'tax_amount' => $taxAmount,
    //                 'final_payment' => $finalPayment,
    //                 'order_id' => $order->order_id,
    //                 'payment_type' => $data['payment_type']
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to create organizer API order', [
    //             'ticket_id' => $request->ticket_id,
    //             'email' => $request->email,
    //             'quantity' => $request->quantity,
    //             'tax_option' => $request->tax_option,
    //             'error' => $e->getMessage(),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'msg' => 'Failed to create order. Please try again.'
    //         ], 500);
    //     }
    // }
     public function createOrderOrganizer(Request $request)
    {
        Log::info('=== createOrderOrganizer request received ===', $request->all());

        // Support strategy alias if provided under tax_configuration_strategy or human-readable names
        if (!$request->has('tax_option') && $request->has('tax_configuration_strategy')) {
            $request->merge(['tax_option' => $request->input('tax_configuration_strategy')]);
        }

        if ($request->has('tax_option')) {
            $strategyMap = [
                'standard configuration (with tax)' => 'with_tax',
                'standard pricing system (with tax)' => 'with_tax',
                'tax exempt (without tax)' => 'without_tax',
                'tax exempt operational profile (no tax)' => 'without_tax',
                'complimentary allocation (free)' => 'complimentary',
                'system authorized complimentary pass (free)' => 'complimentary',
                'custom override with manual discount' => 'custom_amount',
                'manual rate negotiation adjustment' => 'custom_amount',
            ];
            $normalizedOption = strtolower(trim((string) $request->input('tax_option')));
            if (isset($strategyMap[$normalizedOption])) {
                $request->merge(['tax_option' => $strategyMap[$normalizedOption]]);
            }
        }

        // Support custom_adjusted_price / custom_price alias for tax_custom_amount
        if (!$request->filled('tax_custom_amount')) {
            if ($request->filled('custom_adjusted_price')) {
                $request->merge(['tax_custom_amount' => $request->input('custom_adjusted_price')]);
            } elseif ($request->filled('custom_price')) {
                $request->merge(['tax_custom_amount' => $request->input('custom_price')]);
            }
        }

        $request->validate([
            'ticket_id' => 'bail|required',
            'email' => 'bail|required|email',
            'name' => 'bail|nullable|string',
            'phone' => 'bail|required|string',
            'quantity' => 'bail|required',
            'tax_option' => 'bail|nullable|in:with_tax,without_tax,complimentary,custom_amount',
            'tax_custom_amount' => 'required_if:tax_option,custom_amount|nullable|numeric|min:0',
            'seat_ids' => 'nullable',
            'event_seat_id' => 'nullable',
            'venue_seat_ids' => 'nullable',
            'book_seats' => 'nullable',
        ]);

        $data = $request->except([
            'tax_data', 'tax_ids', 'venue_seat_ids', 'guest_hold_key',
            'email', 'name', 'phone', 'seat_ids', 'event_seat_id',
            'tax_configuration_strategy', 'custom_adjusted_price', 'custom_price'
        ]);

        $rawTicketIds = is_array($request->ticket_id) ? $request->ticket_id : explode(',', (string) $request->ticket_id);
        $firstTicketId = (int) reset($rawTicketIds);
        $ticket = Ticket::find($firstTicketId);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'msg' => 'Ticket not found'
            ], 404);
        }

        // Validate seat availability if specific venue map seats are passed
        $seatIds = [];
        if ($request->has('seat_details') && !empty($request->seat_details)) {
            $detailsArray = is_string($request->seat_details) ? json_decode($request->seat_details, true) : $request->seat_details;
            if (is_array($detailsArray)) {
                foreach ($detailsArray as $sDetail) {
                    if (is_array($sDetail) && isset($sDetail['seat_id'])) {
                        $seatIds[] = (int) $sDetail['seat_id'];
                    } elseif (is_object($sDetail) && isset($sDetail->seat_id)) {
                        $seatIds[] = (int) $sDetail->seat_id;
                    }
                }
            }
        }

        if (empty($seatIds)) {
            $rawSeatInput = $request->input('venue_seat_ids', $request->input('book_seats', $request->input('seat_ids', $request->input('event_seat_id'))));
            if (!empty($rawSeatInput)) {
                if (is_array($rawSeatInput)) {
                    $seatIds = array_map('intval', $rawSeatInput);
                } else {
                    $seatIds = array_filter(array_map('intval', explode(',', (string) $rawSeatInput)));
                }
            }
        }

        $requestedSeats = collect();
        if (!empty($seatIds)) {
            $requestedSeats = \App\Models\EventVenueSeat::where('event_id', $ticket->event_id)
                ->whereIn('id', $seatIds)
                ->get()
                ->values();

            if ($requestedSeats->count() !== count($seatIds)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'One or more requested seats were not found for this event.'
                ], 400);
            }

            foreach ($requestedSeats as $seatItem) {
                if ($seatItem->status === \App\Models\EventVenueSeat::STATUS_BOOKED) {
                    return response()->json([
                        'success' => false,
                        'msg' => "Seat '{$seatItem->seat_label}' is already booked."
                    ], 400);
                }
                if ($seatItem->status === \App\Models\EventVenueSeat::STATUS_BLOCKED) {
                    return response()->json([
                        'success' => false,
                        'msg' => "Seat '{$seatItem->seat_label}' is blocked and unavailable."
                    ], 400);
                }
            }
        }

        // Validate ticket quantity availability
        $totalTicketQuantity = $ticket->quantity;

        // Sum of all quantities from orders for this ticket (handle comma-separated ticket_ids)
        $bookedQuantity = 0;
        $orders = Order::where('event_id', $ticket->event_id)->get();

        foreach ($orders as $order) {
            $ticketIds = explode(',', $order->ticket_id);
            $quantities = explode(',', $order->quantity);

            foreach ($ticketIds as $index => $tid) {
                if ((int)trim($tid) === $ticket->id) {
                    if (isset($quantities[$index])) {
                        $bookedQuantity += (int)trim($quantities[$index], '"');
                    }
                }
            }
        }

        $requestedQuantity = is_array($request->quantity) ? (int) array_sum($request->quantity) : (int) $request->quantity;
        $availableQuantity = $totalTicketQuantity - $bookedQuantity;

        // Check if requested quantity exceeds available quantity
        if ($requestedQuantity > $availableQuantity) {
            return response()->json([
                'success' => false,
                'msg' => "Only {$availableQuantity} tickets available. You requested {$requestedQuantity} tickets."
            ], 400);
        }

        $event = Event::find($ticket->event_id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'msg' => 'Event not found'
            ], 404);
        }

        $org = User::find($event->user_id);

        // Verify that the authenticated user is admin or owns this event
        // Admin check: user_id = 1 OR has 'admin' role
        $isAdmin = (Auth::user()->id === 1) || Auth::user()->hasRole('admin');
        $isEventOwner = Auth::user()->id === $org->id;

        if (!$isAdmin && !$isEventOwner) {
            return response()->json([
                'success' => false,
                'msg' => 'Unauthorized. You can only create orders for your own events.',
                'debug' => [
                    'user_id' => Auth::user()->id,
                    'is_admin' => $isAdmin,
                    'is_event_owner' => $isEventOwner,
                    'event_owner_id' => $org->id
                ]
            ], 403);
        }

        // Find or create customer by email
        $email = $request->email;
        $appUser = AppUser::where('email', $email)->first();

        if (!$appUser) {
            // Create new customer
            $appUser = AppUser::create([
                'email' => $email,
                'password' => bcrypt('123456'),
                'name' => $request->name ?? 'Customer',
                'phone' => $request->phone,
                'provider' => 'LOCAL',
                'is_verify' => 1,
            ]);
        } else {
            // Update existing customer info
            $appUser->update([
                'phone' => $request->phone,
                'name' => $request->name ?? $appUser->name,
            ]);
        }

        // Calculate base payment (ticket price * quantity)
        $basePayment = $ticket->price * $requestedQuantity;

        // Get tax option (default: with_tax)
        $taxOption = $request->tax_option ?? 'with_tax';
        $totalTax = [];

        // Handle tax based on tax_option
        if ($taxOption === 'with_tax') {
            // Apply taxes normally
            $allTax = Tax::where(['status' => 1, 'allow_all_bill' => 1])->get();
            foreach ($allTax as $key => $value) {
                if ($value->amount_type == 'percentage') {
                    $totalTax[$key]['id'] = $value->id;
                    $totalTax[$key]['price'] = $basePayment * $value->price / 100;
                }
                if ($value->amount_type == 'price') {
                    $totalTax[$key]['id'] = $value->id;
                    $totalTax[$key]['price'] = $value->price;
                }
            }
        } elseif ($taxOption === 'without_tax') {
            // No tax applied
            $totalTax = [];
        } elseif ($taxOption === 'custom_amount') {
            // Custom amount: the entered value IS the final ticket price (replaces the original price)
            $customPricePerTicket = isset($request->tax_custom_amount) ? floatval($request->tax_custom_amount) : 0;
            $basePayment = $customPricePerTicket * $requestedQuantity;
            // No tax entries for custom_amount - price is already set
            $totalTax = [];
        } elseif ($taxOption === 'complimentary') {
            // Set payment to 0 and no tax
            $basePayment = 0;
            $totalTax = [];
        }

        // Calculate total tax amount
        $taxAmount = array_sum(array_column($totalTax, 'price'));

        // Calculate commission on base payment (before tax)
        $com = Setting::find(1, ['org_commission_type', 'org_commission']);
        $orgCommission = 0;

        if ($taxOption === 'complimentary') {
            $orgCommission = 0;
        } else {
            if ($com->org_commission_type == "percentage") {
                $orgCommission = $basePayment * $com->org_commission / 100;
            } else if ($com->org_commission_type == "amount") {
                $orgCommission = $com->org_commission;
            }
        }

        // Set final payment amount
        if ($taxOption === 'complimentary') {
            $finalPayment = 0;
        } else {
            $finalPayment = $basePayment + $taxAmount;
        }

        // Prepare order data
        $data['order_id'] = '#' . rand(9999, 100000);
        $data['event_id'] = $event->id;
        $data['customer_id'] = $appUser->id;
        $data['organization_id'] = $org->id;

        $data['order_status'] = 'Complete';

        $data['ticket_id'] = is_array($request->ticket_id) ? implode(',', $request->ticket_id) : $ticket->id;
        $data['quantity'] = $requestedQuantity;
        $data['payment'] = $finalPayment;
        $data['tax'] = $taxAmount;
        $data['org_commission'] = $orgCommission;
        $data['tax_option'] = $taxOption;

        if (isset($data['book_seats']) && is_array($data['book_seats'])) {
            $data['book_seats'] = json_encode($data['book_seats']);
        }
        if (isset($data['seat_details']) && is_array($data['seat_details'])) {
            $data['seat_details'] = json_encode($data['seat_details']);
        }

        $data['tax_custom_amount'] = $taxOption === 'custom_amount' ? floatval($request->tax_custom_amount ?? 0) : 0;

        // Set payment type and status based on tax_option
        if ($taxOption === 'complimentary') {
            $data['payment_type'] = 'FREE';
            $data['payment_status'] = 0; // 0 = FREE / Pending
        } else {
            $data['payment_type'] = 'LOCAL';
            $data['payment_status'] = 0; // 0 = Pending
        }

        try {
            $order = Order::create($data);

            // Build map of seat_id -> ticket_id from seat_details if present
            $seatTicketMap = [];
            if ($request->has('seat_details') && !empty($request->seat_details)) {
                $detailsArray = is_string($request->seat_details) ? json_decode($request->seat_details, true) : $request->seat_details;
                if (is_array($detailsArray)) {
                    foreach ($detailsArray as $sDetail) {
                        $sId = (int) ($sDetail['seat_id'] ?? $sDetail['seat_id'] ?? 0);
                        if (is_object($sDetail)) {
                            $sId = (int) ($sDetail->seat_id ?? 0);
                            $tId = (int) ($sDetail->ticket_id ?? 0);
                        } else {
                            $tId = (int) ($sDetail['ticket_id'] ?? 0);
                        }
                        if ($sId > 0 && $tId > 0) {
                            $seatTicketMap[$sId] = $tId;
                        }
                    }
                }
            }

            // Build array of ticket_ids corresponding to each child index
            $expandedTicketIds = [];
            if (is_array($request->ticket_id) && is_array($request->quantity)) {
                foreach ($request->ticket_id as $idx => $tId) {
                    $qty = isset($request->quantity[$idx]) ? (int) $request->quantity[$idx] : 1;
                    for ($q = 0; $q < $qty; $q++) {
                        $expandedTicketIds[] = (int) $tId;
                    }
                }
            }

            // Create OrderChild records for each ticket quantity
            for ($i = 0; $i < $requestedQuantity; $i++) {
                $child = [];
                $child['ticket_number'] = uniqid();
                $child['order_id'] = $order->id;
                $child['customer_id'] = $appUser->id;

                $assignedSeat = null;
                $childTicketId = $expandedTicketIds[$i] ?? $ticket->id;

                if (isset($requestedSeats[$i])) {
                    $assignedSeat = $requestedSeats[$i];
                    if (isset($seatTicketMap[$assignedSeat->id])) {
                        $childTicketId = $seatTicketMap[$assignedSeat->id];
                    }
                    $seatLabel = $assignedSeat->seat_label ?: trim($assignedSeat->section_name . ' ' . $assignedSeat->row_name . ' Seat ' . $assignedSeat->seat_number);

                    $child['event_venue_seat_id'] = $assignedSeat->id;
                    $child['seat_id'] = $assignedSeat->id;
                    $child['Book_Seat_Id'] = $seatLabel;
                }

                $childTicket = Ticket::find($childTicketId) ?: $ticket;
                $child['ticket_id'] = $childTicket->id;
                $child['checkin'] = $childTicket->maximum_checkins ?? null;
                $child['paid'] = 1;

                $createdChild = \App\Models\OrderChild::create($child);

                if ($assignedSeat) {
                    $assignedSeat->update([
                        'status' => \App\Models\EventVenueSeat::STATUS_BOOKED,
                        'booked_order_id' => $order->id,
                        'booked_order_child_id' => $createdChild->id,
                        'booked_at' => now(),
                        'hold_token' => null,
                        'held_by_session_id' => null,
                        'held_by_app_user_id' => null,
                        'held_by_guest_user_id' => null,
                        'held_at' => null,
                        'hold_expires_at' => null,
                    ]);
                }
            }

            // Create OrderTax records
            if (!empty($totalTax)) {
                foreach ($totalTax as $value) {
                    $tax['order_id'] = $order->id;
                    $tax['tax_id'] = $value['id'];
                    $tax['price'] = $value['price'];
                    OrderTax::create($tax);
                }
            }

            // Send email notifications
            $setting = Setting::find(1);

            // Send user notification
            $ticketBookTemplate = NotificationTemplate::where('title', 'Book Ticket')->first();
            $detail['user_name'] = $appUser->name . ' ' . ($appUser->last_name ?? '');
            $detail['quantity'] = $requestedQuantity;
            $detail['event_name'] = $event->name;
            $detail['date'] = $event->start_time->format('d F Y h:i a');
            $detail['app_name'] = $setting->app_name;
            $noti_data = ["{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
            $message1 = $ticketBookTemplate
                ? str_replace($noti_data, $detail, $ticketBookTemplate->message_content)
                : "Ticket booked for {$event->name}.";

            $notification = array();
            $notification['organizer_id'] = null;
            $notification['user_id'] = $appUser->id;
            $notification['order_id'] = $order->id;
            $notification['title'] = 'Ticket Booked';
            $notification['message'] = $message1;
            Notification::create($notification);

            // Send push notification
            if ($setting->push_notification == 1 && $appUser->device_token != null) {
                (new AppHelper)->sendOneSignal('user', $appUser->device_token, $message1);
            }

            // Send user email
            if ($setting->mail_notification == 1) {
                (new AppHelper)->mailConfig();

                try {
                    $details['user_name'] = $appUser->name . ' ' . ($appUser->last_name ?? '');
                    $details['quantity'] = $requestedQuantity;
                    $details['event_name'] = $event->name;
                    $details['date'] = $event->start_time->format('d F Y h:i a');
                    $details['app_name'] = $setting->app_name;

                    if ($ticketBookTemplate) {
                        $qrcode = $order->order_id;
                        Mail::to($appUser->email)->send(new TicketBook($ticketBookTemplate->mail_content, $details, $ticketBookTemplate->subject, $qrcode));
                    }
                } catch (\Throwable $th) {
                    // Silent fail
                }

                // Send QR PDFs + invoice PDF in ONE email
                try {
                    $this->sendTicketQrAndInvoiceMailApi($order->id);
                } catch (\Throwable $th) {
                    // Silent fail
                }
            }

            // Send organizer notification
            $organizerBookTemplate = NotificationTemplate::where('title', 'Organizer Book Ticket')->first();
            $or_detail['organizer_name'] = $org->organization_name ?? $org->first_name . ' ' . $org->last_name;
            $or_detail['user_name'] = $appUser->name . ' ' . ($appUser->last_name ?? '');
            $or_detail['quantity'] = $requestedQuantity;
            $or_detail['event_name'] = $event->name;
            $or_detail['date'] = $event->start_time->format('d F Y h:i a');
            $or_detail['app_name'] = $setting->app_name;
            $or_noti_data = ["{{organizer_name}}", "{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
            $or_message1 = $organizerBookTemplate
                ? str_replace($or_noti_data, $or_detail, $organizerBookTemplate->message_content)
                : "New ticket booked for {$event->name}.";

            $or_notification = array();
            $or_notification['organizer_id'] = $org->id;
            $or_notification['user_id'] = null;
            $or_notification['order_id'] = $order->id;
            $or_notification['title'] = 'New Ticket Booked';
            $or_notification['message'] = $or_message1;
            Notification::create($or_notification);

            // Send organizer push notification
            if ($setting->push_notification == 1 && $org->device_token != null) {
                (new AppHelper)->sendOneSignal('organizer', $org->device_token, $or_message1);
            }

            // Send organizer email
            if ($setting->mail_notification == 1) {
                try {
                    $details1['organizer_name'] = $org->organization_name ?? $org->first_name . ' ' . $org->last_name;
                    $details1['user_name'] = $appUser->name . ' ' . ($appUser->last_name ?? '');
                    $details1['quantity'] = $requestedQuantity;
                    $details1['event_name'] = $event->name;
                    $details1['date'] = $event->start_time->format('d F Y h:i a');
                    $details1['app_name'] = $setting->app_name;

                    if ($organizerBookTemplate) {
                        Mail::to($org->email)->send(new TicketBookOrg($organizerBookTemplate->mail_content, $details1, $organizerBookTemplate->subject));
                    }
                } catch (\Throwable $th) {
                    // Silent fail
                }
            }

            return response()->json([
                'success' => true,
                'msg' => 'Order created successfully',
                'data' => [
                    'order' => $order,
                    'tax_option' => $taxOption,
                    'base_payment' => $basePayment,
                    'tax_amount' => $taxAmount,
                    'final_payment' => $finalPayment,
                    'order_id' => $order->order_id,
                    'payment_type' => $data['payment_type']
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to create organizer API order', [
                'ticket_id' => $request->ticket_id,
                'email' => $request->email,
                'quantity' => $request->quantity,
                'tax_option' => $request->tax_option,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'Failed to create order. Please try again.'
            ], 500);
        }
    }

    /**
     * Send ticket PDFs with proper layout and QR codes for API order
     */
    private function sendTicketQrMailApi($orderId)
    {
        $order = Order::with(['event', 'organization', 'ticket', 'appUser', 'guestUser'])->find($orderId);

        if (!$order) {
            Log::error("Order not found for ID: " . $orderId);
            return false;
        }

        $order->tax_data = OrderTax::where('order_id', $order->id)->get();
        $order->ticket_data = OrderChild::where('order_id', $order->id)->get();

        $customerEmail = $order->appUser ? $order->appUser->email : ($order->guestUser ? $order->guestUser->email : null);

        if (!$customerEmail) {
            Log::error("No customer email found for order ID: " . $orderId);
            return false;
        }

        try {
            $tempDirectory = storage_path('temp_qrcodes');

            if (!file_exists($tempDirectory)) {
                mkdir($tempDirectory, 0755, true);
            }

            // Initialize the $qrCodeFiles array for ticket PDFs
            $qrCodeFiles = [];

            foreach ($order->ticket_data as $item) {
                // Generate unique ticket file name
                $qrId = $item->id;
                $qrCodeFileName = "qr_code_{$item->ticket_number}.pdf";
                $qrCodeFilePath = "{$tempDirectory}/{$qrCodeFileName}";

                // Generate PDF for the ticket with proper layout
                $qrpdf = FacadePdf::loadView('emails.ticket', compact('qrId'))
                    ->setPaper('a5', 'portrait');

                // Save the PDF file to the desired path
                file_put_contents($qrCodeFilePath, $qrpdf->output());

                // Store file path in array
                $qrCodeFiles[] = $qrCodeFilePath;
            }

            $setting = Setting::select('sender_email', 'app_name')->first();
            $data = [
                'email' => $customerEmail,
                'title' => 'Event Tickets | ' . $setting->app_name,
                'body' => 'Please find your event tickets attached. Show these at the event entrance.'
            ];

            Mail::send('mail', $data, function ($message) use ($data, $qrCodeFiles, $setting) {
                $message->from($setting->sender_email, $setting->app_name)
                    ->to($data["email"])
                    ->subject($data["title"]);

                // Attach ticket PDFs to the email
                foreach ($qrCodeFiles as $qrCodeFile) {
                    $message->attach($qrCodeFile);
                }
            });

            // Clean up temporary QR code files
            $files = File::files($tempDirectory);
            foreach ($files as $file) {
                File::delete($file);
            }

            return true;
        } catch (\Throwable $th) {
            Log::error("Failed to send ticket QR mail: " . $th->getMessage());
            return false;
        }
    }

    /**
     * Send QR tickets and invoice PDF in ONE email
     */
    private function sendTicketQrAndInvoiceMailApi($orderId)
    {
        $order = Order::with(['event', 'organization', 'ticket', 'appUser', 'guestUser'])->find($orderId);

        if (!$order) {
            Log::error("Order not found for ID: " . $orderId);
            return false;
        }

        $order->tax_data = OrderTax::where('order_id', $order->id)->get();
        $order->ticket_data = OrderChild::where('order_id', $order->id)->get();

        $customerEmail = $order->appUser ? $order->appUser->email : ($order->guestUser ? $order->guestUser->email : null);
        if (!$customerEmail) {
            Log::error("No customer email found for order ID: " . $orderId);
            return false;
        }

        try {
            $tempDirectory = storage_path('temp_qrcodes');
            if (!file_exists($tempDirectory)) {
                mkdir($tempDirectory, 0755, true);
            }

            // Generate ticket QR PDFs (files on disk)
            $qrCodeFiles = [];
            foreach ($order->ticket_data as $item) {
                $qrId = $item->id;
                $qrCodeFileName = "qr_code_{$item->ticket_number}.pdf";
                $qrCodeFilePath = "{$tempDirectory}/{$qrCodeFileName}";

                $qrpdf = FacadePdf::loadView('emails.ticket', compact('qrId'))
                    ->setPaper('a5', 'portrait');

                file_put_contents($qrCodeFilePath, $qrpdf->output());
                $qrCodeFiles[] = $qrCodeFilePath;
            }

            // Generate invoice PDF (as bytes) only for paid orders
            $invoiceBytes = null;
            $isPaid = (float) $order->payment > 0;
            if ($isPaid) {
                $customPaper = array(0, 0, 720, 1440);
                $pdf = FacadePdf::loadView('ticketmail', compact('order'))
                    ->setPaper($customPaper, 'portrait');

                $invoiceBytes = $pdf->output();
            }

            $setting = Setting::select('sender_email', 'app_name')->first();
            $data = [
                'email' => $customerEmail,
                'title' => 'Event Tickets & Invoice | ' . ($setting->app_name ?? ''),
                'body' => 'Please find your event tickets and invoice attached.'
            ];

            Mail::send('mail', $data, function ($message) use ($data, $qrCodeFiles, $invoiceBytes, $setting) {
                $message->from($setting->sender_email, $setting->app_name)
                    ->to($data["email"])
                    ->subject($data["title"]);

                foreach ($qrCodeFiles as $qrCodeFile) {
                    $message->attach($qrCodeFile);
                }

                if ($invoiceBytes !== null) {
                    $message->attachData($invoiceBytes, "invoice.pdf");
                }
            });

            // Clean up temporary QR code files
            $files = File::files($tempDirectory);
            foreach ($files as $file) {
                File::delete($file);
            }

            return true;
        } catch (\Throwable $th) {
            Log::error("Failed to send tickets+invoice email: " . $th->getMessage());
            return false;
        }
    }

    // Backward compatibility: keep old invoice sender but it is not used by createOrderOrganizer anymore.
    private function sendInvoiceMailApi($orderId)
    {
        $order = Order::with(['event', 'organization', 'ticket', 'appUser', 'guestUser'])->find($orderId);
        
        // Skip invoice generation for free orders
        if ($order->payment == 0) {
            Log::info("Skipping invoice PDF for free order: " . $orderId);
            return true;
        }
        
        $order->tax_data = OrderTax::where('order_id', $order->id)->get();
        $order->ticket_data = OrderChild::where('order_id', $order->id)->get();

        $customPaper = array(0, 0, 720, 1440);
        $pdf = FacadePdf::loadView('ticketmail', compact('order'))
            ->save(public_path("ticket.pdf"))
            ->setPaper($customPaper, 'portrait');

        $customerEmail = $order->appUser ? $order->appUser->email : ($order->guestUser ? $order->guestUser->email : null);
        $data["email"] = $customerEmail;
        $data["title"] = "Invoice PDF";
        $data["body"] = "";
        $tempp = $pdf->output();
        $sender = Setting::select('sender_email', 'app_name')->first();

        try {
            Mail::send('mail', $data, function ($message) use ($data, $tempp, $sender) {
                $message->from($sender->sender_email, $sender->app_name)
                    ->to($data["email"])
                    ->subject($data["title"])
                    ->attachData($tempp, "invoice.pdf");
            });
            Log::info("Invoice PDF sent successfully for paid order: " . $orderId);
            return true;
        } catch (\Throwable $th) {
            Log::error("Failed to send invoice: " . $th->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Error creating order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'Failed to create order. Please try again.'
            ], 500);
        }
    }

    public function revenueReport(Request $request)
    {
        $userId = Auth::user()->id;
        
        $query = Event::where('is_deleted', 0)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereRaw('FIND_IN_SET(?, REPLACE(user_id, " ", ""))', [$userId]);
            });

        if ($request->filled('event_id')) {
            $query->where('id', $request->event_id);
        }

        $events = $query->orderBy('id', 'DESC')->get(['id', 'name']);
        $currency = Setting::first()->currency_sybmol ?? '$';
        $reportData = [];

        foreach ($events as $event) {
            // Build base query builder closures for orders under this event
            $buildOrderQuery = function (string $paymentType, int $paymentStatus) use ($event, $request) {
                $q = Order::where('event_id', $event->id)
                    ->where('payment_status', $paymentStatus);

                if ($paymentType === 'STRIPE') {
                    $q->where('payment_type', 'STRIPE');
                } else {
                    $q->where('payment_type', '!=', 'STRIPE');
                }

                if ($request->filled('duration')) {
                    $parts = explode(' to ', $request->duration);
                    $start_date = $parts[0];
                    $end_date = count(parts) === 1 ? $parts[0] : $parts[1];
                    $q->whereBetween('created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                }

                return $q;
            };

            // Online orders = STRIPE, payment_status = 1
            $onlineOrders = $buildOrderQuery('STRIPE', 1)->get();

            // Local orders = non-STRIPE, payment_status = 1
            $localOrders = $buildOrderQuery('OFFLINE', 1)->get();

            // Refunded orders
            $onlineRefunded = $buildOrderQuery('STRIPE', 2)->sum('payment');
            $localRefunded  = $buildOrderQuery('OFFLINE', 2)->sum('payment');

            // Online totals
            $onlineTicketPrice   = $onlineOrders->sum(fn($o) => (float)$o->payment + (float)($o->coupon_discount ?? 0));
            $onlineProcessingFee = $onlineOrders->sum(fn($o) => (float)$o->tax);
            $onlinePlatformFee   = \App\Models\OrderFee::whereIn('order_id', $onlineOrders->pluck('id'))->sum('amount');
            $onlineRevenue       = $onlineOrders->sum(fn($o) => (float)$o->org_revenue
                ? ((float)$o->org_revenue + (float)($o->coupon_discount ?? 0))
                : ((float)$o->payment + (float)($o->coupon_discount ?? 0) - (float)$o->tax));

            // Local totals
            $localTicketPrice    = $localOrders->sum(fn($o) => (float)$o->payment + (float)($o->coupon_discount ?? 0));
            $localProcessingFee  = $localOrders->sum(fn($o) => (float)$o->tax);
            $localPlatformFee    = \App\Models\OrderFee::whereIn('order_id', $localOrders->pluck('id'))->sum('amount');
            $localRevenue        = $localOrders->sum(fn($o) => (float)$o->org_revenue
                ? ((float)$o->org_revenue + (float)($o->coupon_discount ?? 0))
                : ((float)$o->payment + (float)($o->coupon_discount ?? 0) - (float)$o->tax));

            $grossRevenue = $onlineRevenue + $localRevenue;

            $grossTicket = $onlineTicketPrice + $localTicketPrice;
            $netPayout   = $grossTicket
                - $localTicketPrice
                - $onlineProcessingFee
                - $localProcessingFee
                - $onlinePlatformFee
                - ($onlineRefunded + $localRefunded)
                - $localPlatformFee;

            $reportData[] = [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'currency' => $currency,
                
                // Totals
                'gross_revenue' => round($grossRevenue, 2),
                'net_payout' => round($netPayout, 2),

                // Online details
                'online_ticket_price' => round($onlineTicketPrice, 2),
                'online_processing_fee' => round($onlineProcessingFee, 2),
                'online_platform_fee' => round($onlinePlatformFee, 2),
                'online_refunded' => round($onlineRefunded, 2),
                'online_revenue' => round($onlineRevenue, 2),

                // Offline/Local details
                'offline_ticket_price' => round($localTicketPrice, 2),
                'offline_processing_fee' => round($localProcessingFee, 2),
                'offline_platform_fee' => round($localPlatformFee, 2),
                'offline_refunded' => round($localRefunded, 2),
                'offline_revenue' => round($localRevenue, 2),
            ];
        }

        return response()->json(['data' => $reportData, 'success' => true], 200);
    }
}
