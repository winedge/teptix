

@section('content')
<section class="section">
    <div class="section-header">
        <h1>{{ __('Privacy Policy') }}</h1>
    </div>

    <div class="section-body">
        <div class="card">
            <div class="card-header">
                <h4>{{ __('Privacy Policy for Teptix Scanner (The Event Palette)') }}</h4>
            </div>
            <div class="card-body">
                <div class="privacy-policy-content">
                    <h2>1. Introduction</h2>
                    <p>
                        This Privacy Policy explains how <strong>The Event Palette</strong> (“we”, “our”, or “us”) collects,
                        uses, discloses, and protects information when you use the Teptix Scanner product, website,
                        and related services (collectively, the “Services”).
                    </p>
                    <p>
                        By using the Services, you accept the practices described in this policy.
                    </p>

                    <h2>2. Contact & Company Information</h2>
                    <ul>
                        <li><strong>Company:</strong> The Event Palette (Teptix)</li>
                        <li><strong>Email:</strong> <a href="mailto:info@tickets.theeventpalette.com">info@tickets.theeventpalette.com</a></li>
                        <li><strong>Marketing Email:</strong> <a href="mailto:marketing@theeventpalette.com">marketing@theeventpalette.com</a></li>
                        <li><strong>Phone:</strong> <a href="tel:+12058505531">+1 (205) 850-5531</a></li>
                        <li><strong>Instagram:</strong> <a href="https://www.instagram.com/theeventpaletteaz/" target="_blank">theeventpaletteaz</a></li>
                        <li><strong>Facebook:</strong> <a href="https://www.facebook.com/theeventpalette" target="_blank">The Event Palette</a></li>
                        <li><strong>Copyright:</strong> © 2024 The Event Palette</li>
                    </ul>

                    <h2>3. Information We Collect</h2>
                    <p>We collect the following types of information:</p>
                    <ul>
                        <li>Account and contact information (e.g., name, email, phone number).</li>
                        <li>Billing and payment data (processed via secure third parties).</li>
                        <li>Ticket and scan data used for event verification.</li>
                        <li>App analytics, crash logs, and device data.</li>
                        <li>Location data (if you grant permission).</li>
                    </ul>

                    <h2>4. How We Use Information</h2>
                    <ul>
                        <li>To operate and improve the Teptix Scanner service.</li>
                        <li>To verify event tickets and manage check-ins.</li>
                        <li>To send notifications and important updates.</li>
                        <li>To prevent fraud and ensure security.</li>
                    </ul>

                    <h2>5. Sharing of Information</h2>
                    <p>We do not sell personal information. We may share limited data with:</p>
                    <ul>
                        <li>Event organizers (for event-specific ticket verification).</li>
                        <li>Trusted service providers (email, analytics, hosting).</li>
                        <li>Authorities when legally required.</li>
                    </ul>

                    <h2>6. Data Retention</h2>
                    <p>We retain data only as long as necessary for operations, audits, and legal compliance. Ticket logs may be retained for a limited time or as configured by event organizers.</p>

                    <h2>7. Security</h2>
                    <p>We use encryption, authentication, and secure cloud storage to protect user data. Sensitive credentials (API keys, SMTP passwords) are never exposed publicly and are securely stored on our servers.</p>

                    <h2>8. User Rights</h2>
                    <p>You may request to access, correct, or delete your personal information by contacting us at <a href="mailto:info@tickets.theeventpalette.com">info@tickets.theeventpalette.com</a>.</p>

                    <h2>9. Children’s Privacy</h2>
                    <p>The Services are not intended for children under 13 years of age. If you believe data has been collected from a child, contact us for removal.</p>

                    <h2>10. Changes to This Policy</h2>
                    <p>We may update this Privacy Policy from time to time. Updates will be posted here with a new effective date.</p>

                    <h2>11. Contact Us</h2>
                    <p>
                        For any questions or concerns regarding this Privacy Policy:
                    </p>
                    <ul>
                        <li>Email: <a href="mailto:info@tickets.theeventpalette.com">info@tickets.theeventpalette.com</a></li>
                        <li>Phone: <a href="tel:+12058505531">+1 (205) 850-5531</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.privacy-policy-content {
    font-size: 15px;
    line-height: 1.7;
    color: #333;
}
.privacy-policy-content h2 {
    margin-top: 24px;
    color: #2c3e50;
    font-weight: 600;
}
.privacy-policy-content a {
    color: #007bff;
    text-decoration: none;
}
.privacy-policy-content a:hover {
    text-decoration: underline;
}
.privacy-policy-content ul {
    margin-left: 20px;
    list-style-type: disc;
}
</style>
@endsection
