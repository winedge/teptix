<?php

use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LicenseController;
use GuzzleHttp\Middleware;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\UserController;
use App\Models\AppUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

Route::group(['middleware' => ['mode', 'XSS']], function () {
    Route::get('/scanner/privacy-policy', [FrontendController::class, 'scannerPrivacy']);
    Route::get('/', [FrontendController::class, 'home'])->name('home');
    Route::post('/send-to-admin', [FrontendController::class, 'sentMessageToAdmin']);
    Route::get('/privacy_policy', [FrontendController::class, 'privacypolicy']);
    Route::get('/FAQ', [FaqController::class, 'show']);
    Route::get('/appuser-privacy-policy', [FrontendController::class, 'appuserPrivacyPolicyShow']);
    Route::get('/show-details/{id}', [OrderController::class, 'showTicket']);
    Route::get('/events_details/{id}', [EventController::class, 'show'])->middleware('auth');
    Route::get('organizer/VerificationConfirm/{id}', [FrontendController::class, 'LoginByMailOrganizer']);
    Route::get('organizer/otp-verify/{userid}', [FrontendController::class, 'otpViewOrganizer']);
    Route::post('organizer/otp-verify', [FrontendController::class, 'otpVerifyOrganizer']);
    Route::get('/google/redirect', [FrontendController::class, 'redirectToGoogle'])->name('google.redirect');
    Route::get('/google/callback', [FrontendController::class, 'handleGoogleCallback'])->name('google.callback');
    Route::get('/app/delete-user', [FrontendController::class, 'deleteUserGoogle'])->name('deleteUserGoogle');
    Route::group(['prefix' => 'user'], function () {

        Route::get('/VerificationConfirm/{id}', [FrontendController::class, 'LoginByMail']);
        Route::get('/register', [FrontendController::class, 'register']);
        Route::post('/register', [FrontendController::class, 'userRegister']);
        Route::get('/otp-verify/{userid}', [FrontendController::class, 'otpView']);
        Route::post('/otp-verify', [FrontendController::class, 'otpVerify']);
        Route::get('login', [FrontendController::class, 'login'])->name('user.login');
        Route::post('/login', [FrontendController::class, 'userLogin']);
        Route::get('/resetPassword', [FrontendController::class, 'resetPassword']);
        Route::post('/resetPassword', [FrontendController::class, 'userResetPassword']);
        Route::get('/org-register', [FrontendController::class, 'orgRegister']);
        Route::post('/org-register', [FrontendController::class, 'organizerRegister']);
        Route::get('/logout', [LicenseController::class, 'adminLogout'])->name('logout');
        Route::get('/logoutuser', [FrontendController::class, 'userLogout'])->name('logoutUser');
        Route::post('/search_event', [FrontendController::class, 'searchEvent']);
        Route::get('/tag/{tagname}', [FrontendController::class, 'eventsByTag']);
        Route::get('/blog-tag/{tagname}', [FrontendController::class, 'blogByTag']);
        Route::get('/FAQs', [FrontendController::class, 'Faqs']);
        Route::any('/stripe/create-session', [FrontendController::class, 'checkoutSession'])->name('stripe.checkoutSession');
        Route::post('/stripe/create-seat-map-payment-intent', [FrontendController::class, 'createSeatMapPaymentIntent'])->name('stripe.createSeatMapPaymentIntent');
        Route::get('/stripe/success', [FrontendController::class, 'stripeSuccess'])->name('stripe.success');
        Route::get('/stripe/cancel', [FrontendController::class, 'striepCancel'])->name('stripe.cancel');
        Route::any('/verify-guestemail', [FrontendController::class, 'verifyGuestemail']);
        Route::post('/verify-otp', [FrontendController::class, 'verifyOtp']);
        Route::any('/resend-otp', [FrontendController::class, 'resendOtp']);
        Route::post('/confirm-guestemail', [FrontendController::class, 'confirmGuestEmail']);
        Route::any('/storesessiontickets', [FrontendController::class, 'storeSessionTickets'])->name('storeSessionTickets');
        Route::post('/store-seat-selections', [FrontendController::class, 'storeSeatSelections']);
        Route::post('/validate-seat-availability', [FrontendController::class, 'validateSeatAvailability']);
        Route::post('/check-seat-availability', [TicketController::class, 'checkSeatAvailability'])->name('check.seat.availability');

        // Donation routes
        Route::post('/donation/create-payment-intent', [\App\Http\Controllers\DonationController::class, 'createPaymentIntent'])->name('donation.createPaymentIntent');
        Route::post('/donation/process', [\App\Http\Controllers\DonationController::class, 'processDonation'])->name('donation.process');
        Route::post('/donation/verify-guest-email', [\App\Http\Controllers\DonationController::class, 'verifyGuestEmail'])->name('donation.verifyGuestEmail');
        Route::post('/donation/verify-guest-otp', [\App\Http\Controllers\DonationController::class, 'verifyGuestOtp'])->name('donation.verifyGuestOtp');
        
    });
    Route::group(['middleware' => 'checkStatus'], function () {

        Route::get('/all-events', [FrontendController::class, 'allEvents']);
        Route::post('/all-events', [FrontendController::class, 'allEvents']);
        Route::get('/events-category/{id}/{name?}', [FrontendController::class, 'categoryEvents']);
        Route::get('/event-type/{type}', [FrontendController::class, 'eventType']);
        Route::get('/event/{id}/seat-selection', [FrontendController::class, 'venueSeatSelection'])->name('event.venueSeatSelection');
        Route::get('/event/{id}/seat-map-partial', [FrontendController::class, 'venueSeatMapPartial'])->name('event.venueSeatMapPartial');
        Route::get('/event/{id}/seat-statuses', [FrontendController::class, 'venueSeatStatuses'])->name('event.venueSeatStatuses');
        Route::get('/event/{id}/{name}', [FrontendController::class, 'eventDetail'])->name('eventDetail');
        Route::get('/events/{id}', [FrontendController::class, 'eventDetail']);
        Route::get('/organization/{id}', [FrontendController::class, 'orgDetail'])->name('organizationDetails');
        Route::post('/report-event', [FrontendController::class, 'reportEvent']);
        Route::get('/all-category', [FrontendController::class, 'allCategory']);
        Route::get('/all-blogs', [FrontendController::class, 'blogs']);
        Route::get('/blog-detail/{id}/{name}', [FrontendController::class, 'blogDetail']);
        Route::get('/contact', [FrontendController::class, 'contact']);
        Route::get('/become-organizer', [FrontendController::class, 'becomeOrganizer'])->name('becomeOrganizer');
        Route::post('/checkout/release-seat-hold', [FrontendController::class, 'releaseCheckoutSeatHold'])->name('checkout.releaseSeatHold');
        Route::get('/checkout/content-partial', [FrontendController::class, 'checkoutContentPartial'])->name('checkout.contentPartial');
        Route::any('/checkout', [FrontendController::class, 'checkout']);
        Route::post('/validate-seats', [FrontendController::class, 'validateSeatsAjax'])->name('validate.seats');
        // Route::get('/checkout/{id}', [FrontendController::class, 'checkout']);
        Route::any('/createOrder', [FrontendController::class, 'createOrder'])->name('createOrderUser');
        Route::any('/ordersuccess', [FrontendController::class, 'orderSuccess'])->name('ordersuccess');
        Route::get('/order-success-partial/{id}', [FrontendController::class, 'orderSuccessContentPartial'])->name('order.successContentPartial');
        Route::any('/store-ticket-image', [FrontendController::class, 'uploadTicketImage'])->name('uploadTicketImage');

        Route::group(['middleware' => 'appuser'], function () {

            Route::any('/ordersuccessuser', [FrontendController::class, 'ordersuccessuser'])->name('ordersuccessuser');
            Route::get('email/verify/{id}/{token}', [FrontendController::class, 'emailVerify']);
            Route::post('/applyCoupon', [FrontendController::class, 'applyCoupon']);
            Route::get('/user/profile', [FrontendController::class, 'userTickets']);
            Route::get('/add-favorite/{id}/{type}', [FrontendController::class, 'addFavorite']);
            Route::get('/add-followList/{id}', [FrontendController::class, 'addFollow']);
            Route::post('/add-bio', [FrontendController::class, 'addBio']);
            Route::get('/change-password', [FrontendController::class, 'changePassword']);
            Route::post('/user-change-password', [FrontendController::class, 'changeUserPassword']);
            Route::post('/upload-profile-image', [FrontendController::class, 'uploadProfileImage']);
            Route::get('/my-tickets', [FrontendController::class, 'userTickets'])->name('myTickets');
            Route::get('/my-ticket/{id}', [FrontendController::class, 'userOrderTicket']);

            Route::get('/update_profile', [FrontendController::class, 'update_profile']);
            Route::post('/update_user_profile', [FrontendController::class, 'update_user_profile']);
            Route::get('/getOrder/{id}', [FrontendController::class, 'getOrder']);
            Route::post('/add-review', [FrontendController::class, 'addReview']);
            Route::get('/web/create-payment/{id}', [FrontendController::class, 'makePayment']);
            Route::post('/web/payment/{id}', [FrontendController::class, 'initialize'])->name('frontendPay');
            Route::get('/web/rave/callback/{id}', [FrontendController::class, 'callback'])->name('frontendCallback');
            Route::post('/log-seat-validation', function(Request $request) {
                \Log::channel('single')->info($request->message);
                return response()->json(['success' => true]);
            });

            # Wallet
            Route::group(['prefix' => 'user'], function () {
                Route::get('/wallet', [WalletController::class, 'wallet'])->name('myWallet');
                Route::get('/wallet/add-money', [WalletController::class, 'addToWallet'])->name('addToWallet');
                Route::post('/deposite', [WalletController::class, 'deposite'])->name('deposite');
                Route::any('/wallet/stripe/create-session', [WalletController::class, 'checkoutSession'])->name('stripe.checkoutSession');
                Route::get('/wallet/stripe/success', [WalletController::class, 'stripeSuccess'])->name('walletStripe.success');
                Route::get('/wallet/stripe/cancel', [WalletController::class, 'striepCancel'])->name('walletStripe.cancel');
            });
        });
    });
});
