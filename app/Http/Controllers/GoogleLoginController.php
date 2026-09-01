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
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    
    
}