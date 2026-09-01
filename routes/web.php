<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ScannerApiController;
use App\Http\Controllers\TicketVerificationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AppUserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\VenueMapController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Artisan;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\CacheController;
use App\Http\Controllers\AdminActivityController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();
Auth::routes(['verify' => true]);

Route::get('/login', function () {
    if (Auth::check()) {
        return redirect('/admin/home');
    }
    return view('auth.login');
})->name('login');
Route::get('/clear-cache', [CacheController::class, 'clearAllCache']);
Route::get('/user/google-redirect', [\App\Http\Controllers\FrontendController::class, 'redirectToGoogle']);
Route::get('/user/google-login', [\App\Http\Controllers\FrontendController::class, 'handleGoogleCallback']);
Route::post('/user/update-phone', [\App\Http\Controllers\FrontendController::class, 'updatePhoneNumber'])->name('user.updatePhone');
Route::any('/admin/login', [LicenseController::class, 'adminLogin']);
Route::get('/logout', [LicenseController::class, 'adminLogout'])->name('logout123');
Route::post('/saveEnvData', [LicenseController::class, 'saveEnvData']);
Route::post('/saveAdminData', [LicenseController::class, 'saveAdminData']);
// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
// Route::get('/order-invoice-print/{id}', [OrderController::class, 'orderInvoicePrint']);
Route::get('/change-language/{lang}', [UserController::class, 'changeLanguage']);
Route::get('/maintain', [SettingController::class, 'maintain']);
Route::get('/send-mail/{id}', [OrderController::class, 'sendMail']);
Route::get('/login-as-organizer/{id}',[LicenseController::class,'loginAsOrganizer'])->name('loginAsOrganizer');
Route::get('/login-as-appuser/{id}',[LicenseController::class,'loginAsAppuser'])->name('loginAsAppuser');
Route::get('get-code/{code}', [OrderController::class, 'getQrCode']);
Route::post('store-ticket-image', [\App\Http\Controllers\FrontendController::class, 'uploadTicketImage'])->name('uploadTicketImage');

Route::post('/log-js-error', function (Request $request) {
    Log::error('JS Error Logged', [
        'message' => $request->message,
        'source' => $request->source,
        'line' => $request->lineno,
        'column' => $request->colno,
        'error' => $request->error,
        'userAgent' => $request->userAgent,
        'ip' => $request->ip(),
    ]);

    return response()->json(['status' => 'logged']);
});

// You can uncomment the following route to block installer route after site goes live
//Route::any('installer', function (){abort(404);})->name('installer');
// You can comment the following route to block installer route after site goes live
Route::any('installer', [LicenseController::class, 'installer'])->name('installer');

Route::group(['middleware' => ['auth', 'organizer.onboarding']], function () {
    Route::get('/order-invoice-print/{id}', [OrderController::class, 'orderInvoicePrint']);

    // Fee System routes
    Route::resource('fee-type', FeeController::class)->except(['show']);
    Route::get('/fee-type/setdefault/{id}', [FeeController::class, 'setdefault'])->name('fee-type.setdefault');
    Route::get('/event-fee', [FeeController::class, 'eventFees'])->name('event-fee.index');
    Route::get('/event-fee/{id}/edit', [FeeController::class, 'editEventFees'])->name('event-fee.edit');
    Route::put('/event-fee/{id}', [FeeController::class, 'updateEventFees'])->name('event-fee.update');


    Route::get('/admin/home', [UserController::class, 'adminDashboard']);
    Route::get('/admin/activity', [AdminActivityController::class, 'index'])->name('admin.activity.index');
    Route::get('/organization-home', [UserController::class, 'organizationDashboard']);
    Route::get('/manager-home', [UserController::class, 'managerDashboard'])->middleware('manager.permission:dashboard_access');
    Route::get('/scanner-home', [UserController::class, 'scannerDashboard']);
    Route::get('/{id}/{name}/tickets', [TicketController::class, 'index']);
    Route::get('/book-ticket', [UserController::class, 'bookTicket']);
    Route::get('/organizer/{id}/{name}', [UserController::class, 'organizerEventDetails']);
    Route::get('/organizerCheckout/{id}', [UserController::class, 'organizerCheckout']);
    Route::post('/organizerCreateOrder', [UserController::class, 'organizerCreateOrder']);
    Route::delete('/deleteTickets/{id}', [TicketController::class, 'deleteTickets']);
    Route::get('/{id}/ticket/create', [TicketController::class, 'create']);
    Route::post('/ticket/create', [TicketController::class, 'store']);
    Route::get('/ticket/edit/{id}', [TicketController::class, 'edit']);
    Route::post('/ticket/update/{id}', [TicketController::class, 'update']);
    Route::get('/block-user/{id}', [AppUserController::class, 'blockUser']);
    Route::get('/user_delete/{id}', [AppUserController::class, 'user_delete']);
    Route::get('/main_user_block/{id}', [UserController::class, 'main_user_block']);
    Route::get('/block-scanner/{id}', [UserController::class, 'blockScanner'])->middleware('manager.permission:scanner_create');
    Route::get('/admin-setting', [SettingController::class, 'index']);
    Route::get('/license-setting', [LicenseController::class, 'licenseSetting']);
    Route::post('/save-license-setting', [LicenseController::class, 'saveLicenseSetting']);
    Route::post('/save-general-setting', [SettingController::class, 'store']);
    Route::post('/maintenance-setting', [SettingController::class, 'maintenanceMode']);
    Route::post('/save-mail-setting', [SettingController::class, 'saveMailSetting']);
    Route::post('/save-verification-setting', [SettingController::class, 'saveVerificationSetting']);
    Route::post('/save-organization-setting', [SettingController::class, 'saveOrganizationSetting']);
    Route::post('/save-pushNotification-setting', [SettingController::class, 'saveOnesignalSetting']);
    Route::post('/save-sms-setting', [SettingController::class, 'saveSmsSetting']);
    Route::post('/additional-setting', [SettingController::class, 'additionalSetting']);
    Route::post('/support-setting', [SettingController::class, 'supportSetting']);
    Route::post('/save-payment-setting', [SettingController::class, 'savePaymentSetting']);
    Route::post('/socialmedialinks', [SettingController::class, 'socialmedialinks']);
    Route::post('/appuser-privacy-policy', [SettingController::class, 'appuserPrivacyPolicy']);
    Route::get('/profile', [UserController::class, 'viewProfile']);
    Route::post('/edit-profile', [UserController::class, 'editProfile']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::delete('/delete-self-account', [UserController::class, 'deleteSelfAccount']);
    Route::get('/user/onboarding', [UserController::class, 'onboarding'])->name('users.onboarding');
    Route::post('/user/onboarding', [UserController::class, 'saveOnboarding'])->name('users.onboarding.save');
    Route::get('/organizers/pending', [UserController::class, 'organizerPending'])->name('users.organizerPending');
    Route::get('/organizers/recent', [UserController::class, 'recentOrganizers'])->name('users.recentOrganizers');
    Route::get('/organizers/denied', [UserController::class, 'organizerDenied'])->name('users.organizerDenied');
    Route::post('/organizers/{user}/verify', [UserController::class, 'verifyOrganizer'])->name('users.verifyOrganizer');
    Route::post('/organizers/{user}/deny', [UserController::class, 'denyOrganizer'])->name('users.denyOrganizer');
    Route::post('/organizers/{user}/delete', [UserController::class, 'deleteOrganizer'])->name('users.deleteOrganizer');
    Route::post('/organizer/request-verification', [UserController::class, 'requestVerification'])->name('organizer.requestVerification');
    Route::get('/orders', [OrderController::class, 'index'])->middleware('manager.permission:order_view');
    Route::get('/orders/{order_id}/{id}', [OrderController::class, 'show'])->middleware('manager.permission:order_view');
    Route::delete('/orders', [OrderController::class, 'delete'])->middleware('manager.permission:order_view');
    Route::get('/order-invoice/{id}', [OrderController::class, 'orderInvoice']);
    Route::get('/send-mail/{id}', [OrderController::class, 'sendMail']);
    Route::get('/order-send-mail/{id}', [OrderController::class, 'sendMail']);
    Route::post('/order/changestatus', [OrderController::class, 'changeStatus']);
    Route::post('/order/changepaymentstatus', [OrderController::class, 'changePaymentStatus']);
    Route::post('/order/refund', [OrderController::class, 'refundOrder']);
    Route::get('/edit-order-payment/{id}', [OrderController::class, 'editPayment']);
    Route::post('/admin/update-order-payment/{id}', [OrderController::class, 'updatePayment']);
    Route::get('/user-review', [OrderController::class, 'userReview']);
    Route::get('/event-review', [OrderController::class, 'eventReports']);
    Route::get('/event-reports', [OrderController::class, 'eventReports']);
    Route::get('/change-review-status/{id}', [OrderController::class, 'changeReviewStatus']);
    Route::get('/delete-review/{id}', [OrderController::class, 'deleteReview']);
    Route::post('/get-month-event', [EventController::class, 'getMonthEvent']);
    Route::get('/event-gallery/{id}', [EventController::class, 'eventGallery']);
    Route::post('/add-event-gallery', [EventController::class, 'addEventGallery']);
    Route::get('/remove-image/{image}/{id}', [EventController::class, 'removeEventImage']);
    Route::get('/events/remove-logo/{id}/{logo}', [EventController::class, 'removeLogo']);
    Route::get('/scanner', [UserController::class, 'scanner'])->middleware('manager.permission:scanner_create');
    Route::get('/scanner/create', [UserController::class, 'scannerCreate'])->middleware('manager.permission:scanner_create');
    Route::post('/scanner', [UserController::class, 'addScanner'])->middleware('manager.permission:scanner_create');
    Route::get('/getScanner/{id}', [UserController::class, 'getScanner']);
    Route::get('/get-scanners', [EventController::class, 'getScanners']);
    Route::get('/organization-report/customer', [OrderController::class, 'customerReport']);
    Route::post('/organization-report/customer', [OrderController::class, 'customerReport']);
    Route::get('/organization-report/orders', [OrderController::class, 'ordersReport']);
    Route::post('/organization-report/orders', [OrderController::class, 'ordersReport']);
    Route::get('/organization-report/revenue', [OrderController::class, 'orgRevenueReport']);
    Route::post('/organization-report/revenue', [OrderController::class, 'orgRevenueReport']);
    Route::get('/admin-report/customer', [OrderController::class, 'adminCustomerReport']);
    Route::post('/admin-report/customer', [OrderController::class, 'adminCustomerReport']);
    Route::get('/admin-report/organization', [OrderController::class, 'adminOrgReport']);
    Route::post('/admin-report/organization', [OrderController::class, 'adminOrgReport']);
    Route::get('/admin-report/revenue', [OrderController::class, 'adminRevenueReport']);
    Route::post('/admin-report/revenue', [OrderController::class, 'adminRevenueReport']);
    Route::get('/getStatistics/{month}', [OrderController::class, 'getStatistics']);
    Route::get('/admin-report/settlement', [OrderController::class, 'settlementReport'])->name('settlementReport');
    Route::get('/view-user/{id}', [AppUserController::class, 'userDetail']);
    Route::post('/pay-to-org', [OrderController::class, 'payToUser']);
    Route::post('/pay-to-organization', [OrderController::class, 'payToOrganization']);
    Route::post('admin-report/org-key',[UserController::class,'orgKey'])->name('orgKey');
    Route::any('organization/stripe/create-session', [UserController::class, 'checkoutSession'])->name('orgStripe.checkoutSession');
    Route::get('org/stripe/success', [UserController::class, 'stripeSuccess'])->name('orgStripe.success');
    Route::get('/view-settlement/{id}', [OrderController::class, 'viewSettlement']);
    Route::get('/language/download_sample_file', [LanguageController::class, 'download_sample_file']);
    Route::get('/check-email', [UserController::class, 'check_email']);
    Route::post('/about_us', [SettingController::class, 'aboutUs']);
    Route::get('/event/create', [EventController::class, 'create']);
    Route::get('/app_users_edit/{id}', [UserController::class, 'editAppUser']);
    Route::post('/update_appuser', [UserController::class, 'updateAppUser']);
    Route::match(['get', 'post'], '/organization-income', [UserController::class, 'orgincome'])->middleware('manager.permission:revenue_view');
    Route::get('/organizer-setting', [SettingController::class, 'payment_setting']);
    Route::post('/payment-save', [SettingController::class, 'organizer_payment_save']);
    Route::post('/save-debug',[SettingController::class,'saveDebug']);
    Route::get('/wallet-transactions', [WalletController::class, 'allTransactions'])->name('allTransactions');
    Route::match(['get', 'post'], '/stripe-transactions', [OrderController::class, 'stripeTransactions'])->name('stripeTransactions');
    Route::get('/stripe-transaction/{id}', [OrderController::class, 'viewStripeTransaction'])->name('viewStripeTransaction');
    Route::get('/stripe-transaction/delete/{id}', [OrderController::class, 'deleteStripeTransaction'])->name('deleteStripeTransaction');

    // Donations admin
    Route::get('/donations', [\App\Http\Controllers\DonationAdminController::class, 'index'])->name('admin.donations.index');
    Route::get('/donation/{id}', [\App\Http\Controllers\DonationAdminController::class, 'show'])->name('admin.donations.show');
    Route::any('/orders-create-for-user',[UserController::class,'orderCreateForUser'])->name('orderCreateForUser')->middleware('manager.permission:order_create');
    Route::any('/eventTicket',[UserController::class,'eventTicket'])->name('eventTicket');
    Route::get('/admin/order/event/{event}/venue-seat-map', [UserController::class, 'adminVenueSeatMap'])->name('admin.order.venueSeatMap');
    Route::post('/admin/order/venue-seat-map/hold', [UserController::class, 'adminHoldVenueSeats'])->name('admin.order.holdVenueSeats');
    Route::post('/get-tickets-details',[UserController::class,'getTicketsDetails'])->name('getTicketsDetails');

    Route::get('Seat/createseat', [SeatController::class, 'createSeat'])->name('admin.Seat.createSeat');
    Route::post('Seat/storeseat', [SeatController::class, 'storeSeat'])->name('admin.Seat.storeSeat');
    Route::get('Seat/createsponser', [SeatController::class, 'createSponser'])->name('admin.Seat.createSponser');
    Route::post('Seat/storesponser', [SeatController::class, 'storeSponser'])->name('admin.Seat.storeSponser');
    Route::get('Seat/view', [SeatController::class, 'view'])->name('admin.Seat.view');
    Route::delete('Seat/deleteseat/{id}', [SeatController::class, 'deleteSeat'])->name('admin.Seat.deleteSeat');
    Route::delete('Seat/deletesponser/{id}', [SeatController::class, 'deleteSponser'])->name('admin.Seat.deleteSponser');
    Route::get('venue-seat-maps', [VenueMapController::class, 'index'])->name('admin.venue-maps.index');
    Route::delete('venue-seat-maps', [VenueMapController::class, 'destroySelected'])->name('admin.venue-maps.destroy-selected');
    Route::delete('venue-seat-maps/{template}', [VenueMapController::class, 'destroy'])->name('admin.venue-maps.destroy');
    Route::get('venue-seat-maps/create', [VenueMapController::class, 'create'])->name('admin.venue-maps.create');
    Route::post('venue-seat-maps', [VenueMapController::class, 'store'])->name('admin.venue-maps.store');
    Route::get('venue-seat-maps/{template}/edit', [VenueMapController::class, 'edit'])->name('admin.venue-maps.edit');
    Route::post('venue-seat-maps/{template}/sections', [VenueMapController::class, 'storeSectionRow'])->name('admin.venue-maps.sections.store');
    Route::post('venue-seat-maps/{template}/rows/{row}', [VenueMapController::class, 'updateSectionRow'])->name('admin.venue-maps.rows.update');
    Route::post('venue-seat-maps/{template}/auto-arrange', [VenueMapController::class, 'autoArrange'])->name('admin.venue-maps.auto-arrange');
    Route::post('venue-seat-maps/{template}/coordinates', [VenueMapController::class, 'saveCoordinates'])->name('admin.venue-maps.coordinates.store');
    Route::post('venue-seat-maps/{template}/background', [VenueMapController::class, 'updateBackground'])->name('admin.venue-maps.background.update');
    Route::post('venue-seat-maps/{template}/update-settings', [VenueMapController::class, 'updateSettings'])->name('admin.venue-maps.settings.update');
    Route::post('venue-seat-maps/{template}/publish', [VenueMapController::class, 'publish'])->name('admin.venue-maps.publish');
    Route::post('venue-seat-maps/{template}/keep-draft', [VenueMapController::class, 'keepDraft'])->name('admin.venue-maps.keep-draft');

    // Route::get('/organizer/ticket-verification', [ScannerApiController::class, 'showPage'])->name('admin.verification.ticketverify');
    // Route::get('/verify-ticket', [TicketVerificationController::class, 'displayTicketDetails'])->name('verify.ticket');
    // Route::middleware('auth:sanctum')->post('/scan-ticket', [TicketVerificationController::class, 'scanTicket']);
    Route::post('/scan-ticket', [TicketVerificationController::class, 'scanTicket'])->name('scan.ticket')->middleware('manager.permission:ticket_verify');
    Route::get('/organizer/ticket-verification', [TicketVerificationController::class, 'showPage'])->name('admin.verification.ticketverify')->middleware('manager.permission:ticket_verify');

    // Manager CRUD
    Route::get('/managers', [\App\Http\Controllers\ManagerController::class, 'index']);
    Route::get('/managers/create', [\App\Http\Controllers\ManagerController::class, 'create']);
    Route::post('/managers', [\App\Http\Controllers\ManagerController::class, 'store']);
    Route::get('/managers/{id}/edit', [\App\Http\Controllers\ManagerController::class, 'edit']);
    Route::post('/managers/{id}/update', [\App\Http\Controllers\ManagerController::class, 'update']);
    Route::get('/managers/{id}/status', [\App\Http\Controllers\ManagerController::class, 'status']);
    Route::get('/managers/{id}/delete', [\App\Http\Controllers\ManagerController::class, 'destroy']);
    // Route::post('/organizer/verify-ticket', [TicketVerificationController::class, 'scanQrCode'])->name('organizer.ticket.verify');
    // Route::post('/admin/ticket-verification/verify', [TicketVerificationController::class, 'verify'])->name('ticket.verify');
    Route::resources([

        'roles' => RoleController::class,
        'tax' => TaxController::class,
        'faq' => FaqController::class,
        'banner' => BannerController::class,
        'app-user' => AppUserController::class,
        'users' =>  UserController::class,
        'blog' =>  BlogController::class,
        'feedback' =>  FeedbackController::class,
        'coupon' =>  CouponController::class,
        'category' =>  CategoryController::class,
        // 'location' =>  LocationController::class,
        'events' =>  EventController::class,
        'notification-template' =>  NotificationTemplateController::class,
        'language' => LanguageController::class,
        'module' => ModuleController::class,

    ]);
});

Route::get('/tax/setdefault/{id}',[TaxController::class,'setdefault']);
Route::post('/tax/{organizerTax}/approve',[TaxController::class,'approve'])->name('tax.approve');
Route::post('/tax/{organizerTax}/reject',[TaxController::class,'reject'])->name('tax.reject');

Route::get('/notification', [NotificationTemplateController::class, 'notification']);
Route::get('/markAllAsRead', [NotificationTemplateController::class, 'markAllAsRead']);
Route::get('/get-notification', [NotificationTemplateController::class, 'getNotification']);
Route::post('/send-notification', [NotificationTemplateController::class, 'sendNotification'])->name('send.notification');
Route::post('/send-notification-by-email', [NotificationTemplateController::class, 'sendNotificationByEmail'])->name('send.specific.emails');
Route::post('/send-to-all-clients', [NotificationTemplateController::class, 'sendToAllClients'])->name('send.all.clients');
Route::get('/delete-notification/{id}', [NotificationTemplateController::class, 'deleteNotification']);

Route::get('/create-payment/{id}', [UserController::class, 'makePayment']);
Route::any('/payment/{id}', [UserController::class, 'initialize'])->name('pay');
Route::get('/rave/callback/{id}', [UserController::class, 'callback'])->name('callback');

Route::get('FlutterWavepayment/{id}', [UserController::class, 'FlutterWavepayment']);
Route::get('transction_verify/{id}', [UserController::class, 'transction_verify']);
Route::view('/terms-and-conditions', 'frontend.terms')->name('terms');
Route::view('/privacypolicy', 'frontend.privacy')->name('privacy');
