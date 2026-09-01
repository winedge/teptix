@extends('frontend.master', ['activePage' => null])
@section('title', __('Terms & Conditions'))
@section('content')
<title>Terms & Conditions | The Event Palette</title>
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
        <div class="banner-text" bis_skin_checked="1">Terms & Conditions</div>
    </div>
</div>

    <div class="terms-conditions">
    <h1>Terms and Conditions for The Event Palette</h1>
    <p>
        Last updated: <strong style="color:#ff0000;">09/02/2024</strong><br></p>
       <p> Welcome to The Event Palette. These terms and conditions outline the rules and regulations for the use of The Event Palette's Website.</p>
        <p>By accessing this website, we assume you accept these terms and conditions in full. Do not continue to use The Event Palette's website if you do not accept all of the terms and conditions stated on this page.

    </p>
    <h2>1. Intellectual Property Rights</h2>
    <p>
        Other than the content you own, under these Terms, The Event Palette and/or its licensors own all the intellectual property rights and materials contained in this Website. You are granted a limited license only for purposes of viewing the material contained on this Website.
    </p>
    
    <h2>2. Restrictions</h2>
    <p>
        You are specifically restricted from all of the following:
        <ul>
           <li>●	Publishing any Website material in any media</li>
           <li>●	Selling, sublicensing, and/or otherwise commercializing any Website material</li>
           <li>●	Publicly performing and/or showing any Website material</li>
           <li>●	Using this Website in any way that is, or may be, damaging to this Website</li>
           <li>●	Using this Website in any way that impacts user access to this Website</li>
           <li>●	Using this Website contrary to applicable laws and regulations, or in a way that causes, or may cause, harm to the Website, or to any person or business entity</li>
           <li>●	Engaging in any data mining, data harvesting, data extracting, or any other similar activity in relation to this Website, or while using this Website</li>
        </ul>
    </p>
     <h2>3. Your Content</h2>
    <p>
       In these Terms and Conditions, "Your Content" shall mean any audio, video, text, images, or other material you choose to display on this Website. By displaying Your Content, you grant The Event Palette a non-exclusive, worldwide irrevocable, sub-licensable license to use, reproduce, adapt, publish, translate, and distribute it in any and all media.
Your Content must be your own and must not be infringing on any third party’s rights. The Event Palette reserves the right to remove any of Your Content from this Website at any time, and for any reason, without notice.

    </p>
    <h2>4. No warranties</h2>
    <p>
       This Website is provided "as is," with all faults, and The Event Palette makes no express or implied representations or warranties, of any kind related to this Website or the materials contained on this Website.
    </p>
     <h2>5. Limitation of liability</h2>
    <p>
       In no event shall The Event Palette, nor any of its officers, directors, and employees, be liable for anything arising out of or in any way connected with your use of this Website, whether such liability is under contract, tort, or otherwise.
    </p>
     <h2>6. Indemnification</h2>
    <p>
      You hereby indemnify to the fullest extent The Event Palette from and against any and/or all liabilities, costs, demands, causes of action, damages, and expenses arising in any way related to your breach of any of the provisions of these Terms.
      </p>
       <h2>7. Severability</h2>
    <p>
      If any provision of these Terms is found to be unenforceable or invalid under any applicable law, such unenforceability or invalidity shall not render these Terms unenforceable or invalid as a whole, and such provisions shall be deleted without affecting the remaining provisions herein.
      </p>
      <h2>8. Variation of Terms</h2>
    <p>
      The Event Palette is permitted to revise these Terms at any time as it sees fit, and by using this Website, you are expected to review such Terms on a regular basis to ensure you understand all terms and conditions governing use of this Website.</p>
      <h2>9. Assignment</h2>
    <p>
     The Event Palette is allowed to assign, transfer, and subcontract its rights and/or obligations under these Terms without any notification. However, you are not allowed to assign, transfer, or subcontract any of your rights and/or obligations under these Terms.</p>
      <h2>10. Entire Agreement</h2>
    <p>
      These Terms, including any legal notices and disclaimers contained on this Website, constitute the entire agreement between The Event Palette and you in relation to your use of this Website, and supersede all prior agreements and understandings with respect to the same.</p>
      <h2>11. Refund Policy</h2>
      <h2>All Sales Are Final</h2>
    <p>
      The Event Palette operates a strict "No Refunds/All Sales Are Final" policy for all ticket purchases made through our website. By purchasing a ticket, you agree to the following terms:</p>
    <ul>
        <li><STRONG style="color:#ff0000">●	No Refunds:</STRONG> Once a purchase is made, The Event Palette will not provide refunds, credits, or exchanges for any reason, including, but not limited to, personal circumstances, event dissatisfaction, or inability to attend the event.</li>
        <li><STRONG style="color:#ff0000">●	Event Cancellation or Postponement:</STRONG> In the rare event of cancellation or significant postponement, The Event Palette may, at its discretion, offer refunds or alternatives to affected ticket holders. This is not guaranteed and is subject to specific circumstances and availability.</li>
        <li><STRONG style="color:#ff0000">●	Lost or Stolen Tickets:</STRONG> The Event Palette is not responsible for lost, stolen, damaged, or destroyed tickets and will not issue refunds or replacements in these situations.</li>
        <li><STRONG style="color:#ff0000">●	Event Changes:</STRONG> The Event Palette reserves the right to make changes to the event dates, times, venue, and lineup without prior notice. Such changes do not qualify for refunds or exchanges.</li>
        <li><STRONG style="color:#ff0000">●	Acknowledgment of Policy:</STRONG> By completing your purchase, you acknowledge that you have read and understand this No Refunds/All Sales Are Final policy and agree to be bound by it.</li>
    </ul>
    <p>We encourage our customers to carefully review their orders before purchase and to consider any personal or external factors that may affect their ability to attend the event.</p>
    <h2>Modifications to the Refund Policy</h2>
    <p>
    The Event Palette reserves the right to modify this refund policy at its discretion and without prior notice. Any changes will be effective immediately upon posting on our website. It is your responsibility to review this refund policy periodically for any changes.
<br>Your continued use of the website and purchase of tickets following the posting of changes to this policy will indicate your acceptance of those changes.

     </p>
     <h2>Governing Law & Jurisdiction</h2>
    <p>
    These Terms will be governed by and construed in accordance with the laws of [Your Country/State], and you submit to the non-exclusive jurisdiction of the state and federal courts located in [Your Country/State] for the resolution of any disputes.

     </p>
    <!-- Continue adding sections and content as necessary -->
</div>

@endsection