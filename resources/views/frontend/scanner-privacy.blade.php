@extends('frontend.master', ['activePage' => null])
@section('title', __('Privacy Policy'))
@section('content')
<section class="section">
    <div class="section-header text-center">
       <div class="space-y-10 mt-10 mb-5">
                <p class="font-semibold font-poppins text-5xl leading-10 text-black mt-10">{{__('Privacy policy')}}</p>
            </div>
    </div>

    <div class="section-body">
        <div class="card">
            <!--<div class="card-header text-center">-->
            <!--    <h4>{{ __('Privacy Policy for Teptix Scanner (The Event Palette)') }}</h4>-->
            <!--</div>-->
            <div class="card-body">
                <div class="privacy-policy-content">

                    <h2>1. Introduction</h2>
                    <div class="policy-text">
                        <p>
                            This Privacy Policy explains how <strong>The Event Palette</strong> (“we”, “our”, or “us”) collects,
                            uses, discloses, and protects information when you use the Teptix Scanner product, website,
                            and related services (collectively, the “Services”).
                        </p>
                        <p>
                            By using the Services, you accept the practices described in this policy.
                        </p>
                    </div>

                    <h2>2. Contact & Company Information</h2>
                    <div class="policy-text">
                        <ul>
                            <li><strong>Company:</strong> The Event Palette (Teptix)</li>
                            <li><strong>Email:</strong> <a href="mailto:info@tickets.theeventpalette.com">info@tickets.theeventpalette.com</a></li>
                            <li><strong>Marketing Email:</strong> <a href="mailto:marketing@theeventpalette.com">marketing@theeventpalette.com</a></li>
                            <li><strong>Phone:</strong> <a href="tel:+12058505531">+1 (205) 850-5531</a></li>
                            <li><strong>Instagram:</strong> <a href="https://www.instagram.com/theeventpaletteaz/" target="_blank">theeventpaletteaz</a></li>
                            <li><strong>Facebook:</strong> <a href="https://www.facebook.com/theeventpalette" target="_blank">The Event Palette</a></li>
                            <li><strong>Copyright:</strong> © 2024 The Event Palette</li>
                        </ul>
                    </div>

                    <h2>3. Information We Collect</h2>
                    <div class="policy-text">
                        <p>We collect the following types of information:</p>
                        <ul>
                            <li>Account and contact information (e.g., name, email, phone number).</li>
                            <li>Billing and payment data (processed via secure third parties).</li>
                            <li>Ticket and scan data used for event verification.</li>
                            <li>App analytics, crash logs, and device data.</li>
                            <li>Location data (if you grant permission).</li>
                        </ul>
                    </div>

                    <h2>4. How We Use Information</h2>
                    <div class="policy-text">
                        <ul>
                            <li>To operate and improve the Teptix Scanner service.</li>
                            <li>To verify event tickets and manage check-ins.</li>
                            <li>To send notifications and important updates.</li>
                            <li>To prevent fraud and ensure security.</li>
                        </ul>
                    </div>

                    <h2>5. Sharing of Information</h2>
                    <div class="policy-text">
                        <p>We do not sell personal information. We may share limited data with:</p>
                        <ul>
                            <li>Event organizers (for event-specific ticket verification).</li>
                            <li>Trusted service providers (email, analytics, hosting).</li>
                            <li>Authorities when legally required.</li>
                        </ul>
                    </div>

                    <h2>6. Data Retention</h2>
                    <div class="policy-text">
                        <p>We retain data only as long as necessary for operations, audits, and legal compliance. Ticket logs may be retained for a limited time or as configured by event organizers.</p>
                    </div>

                    <h2>7. Security</h2>
                    <div class="policy-text">
                        <p>We use encryption, authentication, and secure cloud storage to protect user data. Sensitive credentials (API keys, SMTP passwords) are never exposed publicly and are securely stored on our servers.</p>
                    </div>

                    <h2>8. User Rights</h2>
                    <div class="policy-text">
                        <p>You may request to access, correct, or delete your personal information by contacting us at <a href="mailto:info@tickets.theeventpalette.com">info@tickets.theeventpalette.com</a>.</p>
                    </div>

                    <h2>9. Children’s Privacy</h2>
                    <div class="policy-text">
                        <p>The Services are not intended for children under 13 years of age. If you believe data has been collected from a child, contact us for removal.</p>
                    </div>

                    <h2>10. Changes to This Policy</h2>
                    <div class="policy-text">
                        <p>We may update this Privacy Policy from time to time. Updates will be posted here with a new effective date.</p>
                    </div>

                    <h2>11. Contact Us</h2>
                    <div class="policy-text">
                        <p>For any questions or concerns regarding this Privacy Policy:</p>
                        <ul>
                            <li>Email: <a href="mailto:info@tickets.theeventpalette.com">info@tickets.theeventpalette.com</a></li>
                            <li>Phone: <a href="tel:+12058505531">+1 (205) 850-5531</a></li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Center the main titles */
.section-header h1,
.card-header h4 {
    text-align: center;
}

/* General content styling */
.privacy-policy-content {
    max-width: 960px;
    margin: 0 auto;
    padding: 0 16px 16px;
    font-size: 15px;
    line-height: 1.7;
    color: #333;
}

/* Keep section headings left-aligned */
.privacy-policy-content h2 {
    margin-top: 24px;
    margin-bottom: 10px;
    color: #2c3e50;
    font-weight: 900;
    text-align: left;
}

/* Keep paragraph/list blocks aligned under section headings */
.policy-text {
    margin-left: 0;
}

/* Link styling */
.privacy-policy-content a {
    color: #007bff;
    text-decoration: none;
}

.privacy-policy-content a:hover {
    text-decoration: underline;
}

/* List styling */
.privacy-policy-content ul {
    margin-left: 20px;
    list-style-type: disc;
}

/* Large screen polish */
@media (min-width: 1200px) {
    .privacy-policy-content {
        max-width: 1000px;
        font-size: 16px;
    }
}

/* Tablet */
@media (max-width: 992px) {
    .space-y-10 .font-poppins {
        font-size: 40px;
        line-height: 1.2;
    }

    .privacy-policy-content {
        padding: 0 14px 14px;
    }

    .privacy-policy-content h2 {
        margin-top: 20px;
        font-size: 24px;
    }
}

/* Mobile */
@media (max-width: 576px) {
    .space-y-10 .font-poppins {
        font-size: 30px;
        line-height: 1.25;
    }

    .privacy-policy-content {
        font-size: 14px;
        line-height: 1.65;
        padding: 0 12px 12px;
    }

    .privacy-policy-content h2 {
        font-size: 20px;
        line-height: 1.35;
    }

    .privacy-policy-content ul {
        margin-left: 18px;
    }
}
</style>
@endsection
