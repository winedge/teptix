@extends('frontend.master', ['activePage' => 'profile'])
@section('title', __('User Profile'))
@section('content')

<div class="d-flex justify-content-center align-items-center mx-auto" style="min-height: 100vh;">
    <div class="card shadow-lg border-0 w-100 p-5 mx-auto" >
        <div class="card-body p-4 mx-auto">
            <h1 class="mb-4 text-primary mx-auto text-center" style="max-width: 600px;">Teptix App – Account Deletion Request</h1>
            
            <p class="mx-auto" style="max-width: 600px;">This page is for users of the <strong>Teptix</strong> mobile app who want to request deletion of their account and associated data.</p>
            
            <h2 class="mt-4 h5 mx-auto text-center " style="max-width: 600px;">How to Delete Your Account</h2><br>
            <p class="mx-auto" style="max-width: 600px;">To request deletion of your account:</p>
            <ol class="mx-auto " style="max-width: 600px;">
                <li>Send an email to <a href="mailto:info@theeventpalette.com">info@theeventpalette.com</a></li>
                <li>Use the subject: <strong>Account Deletion Request</strong></li>
                <li>Include your registered email address used in the Teptix app</li>
            </ol>
            <p class="mx-auto" style="max-width: 600px;">We will process your request within 7 business days and confirm once your data has been deleted.</p>
            
            <h2 class="mt-4 h5 mx-auto text-center" style="max-width: 600px;">What Data Will Be Deleted</h2><br>
            <ul class="mx-auto" style="max-width: 600px;">
                <li>Your name</li>
                <li>Your email address</li>
                <li>Any associated account information</li>
            </ul>
            
            <h2 class="mt-4 h5 mx-auto text-center"style="max-width: 600px;">What Data Will Be Retained</h2><br>
            <p class="mx-auto" style="max-width: 600px;">No personal data is retained after account deletion. We do not store any sensitive or financial information.</p>
            
            <p class="mt-4 mx-auto " style="max-width: 600px;"><strong>Developer:</strong> Teptix</p>
        </div>
    </div>
</div>
@endsection