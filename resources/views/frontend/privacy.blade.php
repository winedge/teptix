@extends('frontend.master', ['activePage' => null])
@section('title', __('Privacy Policy'))
@section('content')
<title>Privacy Policy | The Event Palette</title>
<style>
        .banner {
            position: relative;
            height: 300px; /* Adjust the height as needed */
            background-image: url('https://teptix.com/images/terms-of-use-1 (1).webp');
            background-size: cover;
            background-position: center;
            background-position-y: 0px;
            background-repeat: no-repeat;
        }

        .overlay {
            position: absolute;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5); /* Adjust the opacity as needed */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .banner-text {
            color: white;
            font-size: 2em;
            font-weight: bold;
            padding: 20px;
            border-radius: 8px;
        }

        /* Mobile-friendly styles */
        @media (max-width: 768px) {
            .banner {
                height: 200px; /* Adjust the height for smaller screens */
            }

            .banner-text {
                font-size: 1.5em;
                padding: 10px;
            }
        }
.terms-conditions {
    max-width: 1000px; /* Optimal line length for readability */
    margin: 0 auto; /* Center the content */
    padding: 40px;
    color: #333; /* Soft black for text */
}

.terms-conditions h1 {
    margin-bottom: 20px;
    font-size: 28px;
    color: #ff0000; /* Modern, vibrant color */
}

.terms-conditions h2 {
    margin-top: 20px;
    margin-bottom: 10px;
    font-size: 24px;
    color: #ff0000; /* Slightly darker for subsections */
}

.terms-conditions p, .terms-conditions ul {
    margin-bottom: 20px;
    line-height: 1.6;
}

.terms-conditions li {
    margin-bottom: 5px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .terms-conditions {
        padding: 20px;
    }

    .terms-conditions h1 {
        font-size: 24px;
    }

    .terms-conditions h2 {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .terms-conditions {
        padding: 15px;
        font-size: 14px;
    }

    .terms-conditions h1, .terms-conditions h2 {
        font-size: 18px;
    }
}
    </style>
   <div class="banner" bis_skin_checked="1">
    <div class="overlay" bis_skin_checked="1">
        <div class="banner-text" bis_skin_checked="1">Privacy Policy</div>
    </div>
</div>

    <div class="terms-conditions">
<h1><strong>Privacy Policy for The Event Palette</strong></h1>
<p>Effective Date: Feb 14th 2024</p>
<p>Welcome to The Event Palette. The Event Palette ("us", "we", or "our") operates the Website and is committed to protecting the privacy and security of our users' ("user", "you", or "your") information. This Privacy Policy outlines our practices regarding the collection, use, and disclosure of your information when you use our Website and the choices you have associated with that information.</p>
<h2><strong>Collection of Information</strong></h2>
<h3><strong>Information You Provide to Us</strong></h3>
<p>We collect information you provide directly to us when you use our Website, including but not limited to:</p>
<ul>
<li>Personal Identification Information: Name, email address, phone number, etc.</li>
<li>Transactional Information: Details about purchases you make on the Website.</li>
<li>Other Information: Any other information you choose to provide through the Website.</li>
</ul>
<h3><strong>Information We Collect Automatically</strong></h3>
<p>When you access or use our Website, we may automatically collect information about you, including:</p>
<ul>
<li>Log Information: Details of your visits to our Website, including traffic data, location data, logs, and other communication data.</li>
<li>Device Information: Information about the computer or mobile device you use to access our Website, including the hardware model, operating system, unique device identifiers, and mobile network information.</li>
<li>Cookies and Similar Technologies: We may use cookies, web beacons, and other tracking technologies to collect information about your browsing activities and preferences.</li>
</ul>
<h2><strong>Use of Information</strong></h2>
<p>We use the information we collect to:</p>
<ul>
<li>Provide, maintain, and improve our Website;</li>
<li>Process transactions and send related information, including confirmations and invoices;</li>
<li>Send you technical notices, updates, security alerts, and support and administrative messages;</li>
<li>Respond to your comments, questions, and requests, and provide customer service;</li>
<li>Communicate with you about products, services, offers, promotions, and events offered by The Event Palette and others, and provide news and information we think will be of interest to you;</li>
<li>Monitor and analyze trends, usage, and activities in connection with our Website;</li>
<li>Detect, investigate, and prevent fraudulent transactions and other illegal activities and protect the rights and property of The Event Palette and others.</li>
</ul>
<h2><strong>Sharing of Information</strong></h2>
<p>We may share information about you as follows:</p>
<ul>
<li>With vendors, consultants, and other service providers who need access to such information to carry out work on our behalf;</li>
<li>In response to a request for information if we believe disclosure is in accordance with any applicable law, regulation, or legal process;</li>
<li>If we believe your actions are inconsistent with our user agreements or policies, or to protect the rights, property, and safety of The Event Palette or others;</li>
<li>In connection with, or during negotiations of, any merger, sale of company assets, financing, or acquisition of all or a portion of our business by another company;</li>
<li>Between and among The Event Palette and our current and future parents, affiliates, subsidiaries, and other companies under common control and ownership; and</li>
<li>With your consent or at your direction.</li>
</ul>
<h2><strong>Your Choices</strong></h2>
<ul>
<li>Account Information: You may update, correct or delete information about you at any time by logging into your online account or emailing us at [insert contact email].</li>
<li>Cookies: Most web browsers are set to accept cookies by default. If you prefer, you can usually choose to set your browser to remove or reject browser cookies.</li>
<li>Promotional Communications: You can opt out of receiving promotional emails from The Event Palette by following the instructions in those emails.</li>
</ul>
<h2><strong>Security</strong></h2>
<p>We take reasonable measures to help protect information about you from loss, theft, misuse, and unauthorized access, disclosure, alteration, and destruction.</p>
<h2><strong>International Transfers</strong></h2>
<p>We are based in [insert your location] and the information we collect is governed by [insert your country&rsquo;s law]. By accessing or using our Website or otherwise providing information to us, you consent to the processing and transfer of information in and to the U.S. and other countries.</p>
<h2><strong>Changes to This Privacy Policy</strong></h2>
<p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page. We encourage you to review this Privacy Policy periodically for any changes.</p>
<h2><strong>Contact Us</strong></h2>
<p>If you have any questions about this Privacy Policy, please contact us at +1 (917) 815-3930.</p>
<p>&nbsp;</p>
</div>

@endsection