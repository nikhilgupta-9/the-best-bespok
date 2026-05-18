<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Policies | The Best Bespok</title>
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/boxicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/image/thumbnail.svg" type="image/gif" sizes="20x20">
    <style>
        .policy-wrap {
            padding: 60px 0 100px;
        }

        .policy-nav {
            position: sticky;
            top: 190px;
        }

        .policy-nav .nav-link {
            color: #555;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all .2s;
        }

        .policy-nav .nav-link:hover,
        .policy-nav .nav-link.active {
            background: #1d2d44;
            color: #fff;
        }

        .policy-nav .nav-link i {
            font-size: 16px;
            color: #c6a43f;
        }

        .policy-nav .nav-link.active i {
            color: #fff;
        }

        .policy-section {
            background: #fff;
            border-radius: 14px;
            padding: 40px 44px;
            box-shadow: 0 4px 28px rgba(0, 0, 0, .06);
            margin-bottom: 32px;
            scroll-margin-top: 110px;
        }

        .policy-section h2 {
            font-size: 22px;
            font-weight: 800;
            color: #1d2d44;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .policy-section h2 i {
            color: #c6a43f;
            font-size: 22px;
        }

        .policy-section .last-updated {
            font-size: 12px;
            color: #aaa;
            margin-bottom: 24px;
        }

        .policy-section h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1d2d44;
            margin: 24px 0 10px;
        }

        .policy-section p {
            font-size: 14.5px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 12px;
        }

        .policy-section ul {
            padding-left: 20px;
        }

        .policy-section ul li {
            font-size: 14px;
            color: #555;
            line-height: 1.9;
        }

        .policy-section table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 14px;
        }

        .policy-section table th {
            background: #1d2d44;
            color: #fff;
            padding: 10px 14px;
            text-align: left;
        }

        .policy-section table td {
            padding: 10px 14px;
            border-bottom: 1px solid #eee;
            color: #444;
        }

        .policy-section table tr:last-child td {
            border-bottom: none;
        }

        .policy-section .highlight-box {
            background: #f8f9fb;
            border-left: 4px solid #c6a43f;
            border-radius: 0 8px 8px 0;
            padding: 16px 20px;
            margin: 16px 0;
            font-size: 14px;
            color: #444;
        }

        .policy-hero {
            background: linear-gradient(135deg, #1d2d44, #2d4a70);
            color: #fff;
            padding: 48px 0 36px;
            text-align: center;
        }

        .policy-hero h1 {
            font-size: 36px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .policy-hero p {
            font-size: 15px;
            color: rgba(255, 255, 255, .75);
            margin: 0;
        }

        .contact-info-box {
            background: linear-gradient(135deg, #1d2d44, #000);
            color: #fff;
            border-radius: 12px;
            padding: 28px 32px;
            margin-top: 32px;
        }

        .contact-info-box h4 {
            font-weight: 800;
            margin-bottom: 16px;
        }

        .contact-info-box a {
            color: #ffe;
        }

        @media (max-width: 991px) {
            .policy-nav {
                position: static;
                margin-bottom: 24px;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .policy-nav .nav-link {
                padding: 8px 12px;
                font-size: 13px;
            }

            .policy-section {
                padding: 28px 20px;
            }
        }
    </style>
</head>

<body>
    <?php
    include_once "login-popup.php";
    include_once "includes/header.php";
    include_once "includes/mobile-bottom-nav.php";
    ?>

    <!-- breadcrumb section strats here -->
    <div class="breadcrumb-section mb-100"
        style="background-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url(assets/image/background/contact_hero.jpg);">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-center">
                    <div class="banner-content style-2 text-center">
                        <h1>Company Policy</h1>
                        <ul class="breadcrumb-list">
                            <li><a href="<?= BASE_URL ?>">Home</a></li>
                            <li><span>/</span> Company Policy</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="policy-wrap">
        <div class="container">
            <div class="row g-4">

                <!-- Sidebar Nav -->
                <div class="col-lg-3">
                    <div class="policy-nav">
                        <a class="nav-link active" href="#privacy"><i class="bi bi-shield-lock"></i> Privacy Policy</a>
                        <a class="nav-link" href="#terms"><i class="bi bi-file-earmark-text"></i> Terms &amp; Conditions</a>
                        <a class="nav-link" href="#shipping"><i class="bi bi-truck"></i> Shipping Policy</a>
                        <a class="nav-link" href="#returns"><i class="bi bi-arrow-counterclockwise"></i> Return &amp; Refund</a>
                        <a class="nav-link" href="#cookies"><i class="bi bi-browser-chrome"></i> Cookie Policy</a>
                    </div>
                </div>

                <!-- Content -->
                <div class="col-lg-9">

                    <!-- Privacy Policy -->
                    <div class="policy-section" id="privacy">
                        <h2><i class="bi bi-shield-lock"></i> Privacy Policy</h2>
                        <div class="last-updated">Last updated: May 2025</div>

                        <p>The Best Bespok ("we", "our", "us") is committed to protecting your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or place an order with us.</p>

                        <h3>1. Information We Collect</h3>
                        <ul>
                            <li><strong>Personal Data:</strong> Name, email address, phone number, shipping address when you register or place an order.</li>
                            <li><strong>Payment Data:</strong> Payment is processed by Razorpay. We do not store your card details on our servers.</li>
                            <li><strong>Usage Data:</strong> IP address, browser type, pages visited, and time spent on the site (via anonymous analytics).</li>
                            <li><strong>Cookies:</strong> We use a visitor ID cookie to maintain your cart and session.</li>
                        </ul>

                        <h3>2. How We Use Your Information</h3>
                        <ul>
                            <li>To process and fulfill your orders</li>
                            <li>To send order confirmation and shipping updates</li>
                            <li>To respond to customer service inquiries</li>
                            <li>To improve website functionality and user experience</li>
                            <li>To comply with legal obligations</li>
                        </ul>

                        <h3>3. Data Sharing</h3>
                        <p>We do not sell or rent your personal data to third parties. We may share data with:</p>
                        <ul>
                            <li><strong>Payment processors (Razorpay)</strong> to complete transactions</li>
                            <li><strong>Logistics partners</strong> for order delivery</li>
                            <li><strong>Legal authorities</strong> when required by law</li>
                        </ul>

                        <h3>4. Data Retention</h3>
                        <p>We retain your personal data for as long as necessary to provide services and comply with legal obligations (typically 7 years for financial records).</p>

                        <h3>5. Your Rights</h3>
                        <ul>
                            <li>Right to access the personal data we hold about you</li>
                            <li>Right to correction of inaccurate data</li>
                            <li>Right to erasure ("right to be forgotten") where applicable</li>
                            <li>Right to opt out of marketing communications</li>
                        </ul>
                        <p>To exercise your rights, email us at <strong>privacy@thebestbespok.com</strong>.</p>

                        <h3>6. Security</h3>
                        <p>We use SSL/TLS encryption, secure servers, and follow industry best practices to protect your data. However, no internet transmission is 100% secure.</p>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="policy-section" id="terms">
                        <h2><i class="bi bi-file-earmark-text"></i> Terms &amp; Conditions</h2>
                        <div class="last-updated">Last updated: May 2025</div>

                        <p>By accessing or using The Best Bespok website and services, you agree to be bound by these Terms and Conditions. Please read them carefully before placing any order.</p>

                        <h3>1. Eligibility</h3>
                        <p>You must be at least 18 years old to use our services. By placing an order, you represent that you are of legal age.</p>

                        <h3>2. Bespoke Orders</h3>
                        <div class="highlight-box">
                            <strong>Important:</strong> All bespoke (custom-made) orders are made-to-order based on your specifications. Once production has begun, orders cannot be cancelled or modified. Please review all customization details carefully before payment.
                        </div>
                        <ul>
                            <li>All measurements, fabric selections, and customizations are final upon order confirmation.</li>
                            <li>Colour and texture may vary slightly from on-screen representations due to monitor calibration.</li>
                            <li>Lead time for bespoke orders is typically 15–21 business days from confirmation.</li>
                        </ul>

                        <h3>3. Pricing and Payment</h3>
                        <ul>
                            <li>All prices are listed in Indian Rupees (INR) inclusive of applicable taxes.</li>
                            <li>We accept payments via Razorpay (credit/debit cards, UPI, net banking, wallets).</li>
                            <li>Payment must be made in full before production begins.</li>
                            <li>We reserve the right to change prices without prior notice.</li>
                        </ul>

                        <h3>4. Intellectual Property</h3>
                        <p>All content on this website — including designs, images, text, and logos — is the intellectual property of The Best Bespok and may not be reproduced without written permission.</p>

                        <h3>5. Limitation of Liability</h3>
                        <p>Our liability is limited to the purchase price of the product. We are not liable for any indirect, incidental, or consequential damages.</p>

                        <h3>6. Governing Law</h3>
                        <p>These Terms are governed by the laws of India. Any disputes shall be subject to the exclusive jurisdiction of courts in [Your City], India.</p>
                    </div>

                    <!-- Shipping Policy -->
                    <div class="policy-section" id="shipping">
                        <h2><i class="bi bi-truck"></i> Shipping Policy</h2>
                        <div class="last-updated">Last updated: May 2025</div>

                        <h3>Domestic Shipping (India)</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Order Value</th>
                                    <th>Shipping Method</th>
                                    <th>Delivery Time</th>
                                    <th>Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>All orders</td>
                                    <td>Standard Courier</td>
                                    <td>5–7 business days</td>
                                    <td>FREE</td>
                                </tr>
                                <tr>
                                    <td>All orders</td>
                                    <td>Express Delivery</td>
                                    <td>2–3 business days</td>
                                    <td>₹299</td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>International Shipping</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Region</th>
                                    <th>Delivery Time</th>
                                    <th>Estimated Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>South Asia (SAARC)</td>
                                    <td>7–10 business days</td>
                                    <td>₹1,500–₹2,500</td>
                                </tr>
                                <tr>
                                    <td>Middle East &amp; Southeast Asia</td>
                                    <td>10–14 business days</td>
                                    <td>₹2,500–₹4,000</td>
                                </tr>
                                <tr>
                                    <td>Europe, USA, Canada, Australia</td>
                                    <td>14–21 business days</td>
                                    <td>₹4,000–₹7,000</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="highlight-box">
                            International customers are responsible for any customs duties, import taxes, or local fees levied by their country. These charges are not included in the shipping cost.
                        </div>

                        <h3>Processing Time</h3>
                        <p>Bespoke orders require <strong>15–21 business days</strong> of production time before shipment. Standard in-stock items are dispatched within <strong>2–3 business days</strong>.</p>

                        <h3>Tracking</h3>
                        <p>Once your order is dispatched, you will receive a tracking number via email. You can use this to monitor your delivery.</p>

                        <h3>Undeliverable Orders</h3>
                        <p>If a delivery fails due to an incorrect address or unclaimed parcel, re-dispatch charges will apply. Please ensure your address is accurate at checkout.</p>
                    </div>

                    <!-- Return & Refund Policy -->
                    <div class="policy-section" id="returns">
                        <h2><i class="bi bi-arrow-counterclockwise"></i> Return &amp; Refund Policy</h2>
                        <div class="last-updated">Last updated: May 2025</div>

                        <div class="highlight-box">
                            <strong>Bespoke &amp; Custom-Made Items:</strong> Because each piece is crafted exclusively to your specifications, we generally do not accept returns or issue refunds for custom orders unless the item is defective or significantly differs from what was ordered.
                        </div>

                        <h3>Eligibility for Returns</h3>
                        <ul>
                            <li>Item received is damaged, defective, or materially different from the order</li>
                            <li>Wrong item delivered</li>
                            <li>Claim must be raised within <strong>48 hours</strong> of delivery with photo evidence</li>
                        </ul>

                        <h3>Non-Returnable Items</h3>
                        <ul>
                            <li>Custom / bespoke tailored garments (made to your measurements)</li>
                            <li>Items that have been worn, washed, or altered</li>
                            <li>Sale or discounted items</li>
                        </ul>

                        <h3>Refund Process</h3>
                        <p>Approved refunds are processed within <strong>7–10 business days</strong> to the original payment method. Razorpay refunds may take an additional 3–5 banking days to reflect.</p>

                        <h3>Alterations</h3>
                        <p>If there is a minor fitting issue with your bespoke garment, we offer complimentary alteration services. You will need to send the garment back to us at our expense. Contact us within 48 hours of delivery.</p>

                        <h3>How to Raise a Concern</h3>
                        <p>Email us at <strong>returns@thebestbespok.com</strong> with your order number, photos of the issue, and a description. Our team will respond within 2 business days.</p>
                    </div>

                    <!-- Cookie Policy -->
                    <div class="policy-section" id="cookies">
                        <h2><i class="bi bi-browser-chrome"></i> Cookie Policy</h2>
                        <div class="last-updated">Last updated: May 2025</div>

                        <p>This website uses cookies to enhance your browsing experience. By continuing to use our site, you consent to our use of cookies as described below.</p>

                        <h3>What Are Cookies?</h3>
                        <p>Cookies are small text files placed on your device by a website. They help the site remember your preferences and activity.</p>

                        <h3>Cookies We Use</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Cookie Name</th>
                                    <th>Purpose</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>visitor_id</code></td>
                                    <td>Identifies your cart and wishlist session</td>
                                    <td>90 days</td>
                                </tr>
                                <tr>
                                    <td><code>PHPSESSID</code></td>
                                    <td>PHP server session (login state)</td>
                                    <td>Session</td>
                                </tr>
                                <tr>
                                    <td>Analytics cookies</td>
                                    <td>Anonymous usage statistics</td>
                                    <td>Up to 2 years</td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>Third-Party Cookies</h3>
                        <ul>
                            <li><strong>Razorpay:</strong> Sets cookies to facilitate secure payment processing</li>
                            <li><strong>Analytics providers:</strong> For anonymous traffic analysis</li>
                        </ul>

                        <h3>Managing Cookies</h3>
                        <p>You can control and/or delete cookies through your browser settings. However, disabling certain cookies (like <code>visitor_id</code>) may prevent your cart and wishlist from working correctly.</p>
                        <ul>
                            <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Chrome</a></li>
                            <li><a href="https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox" target="_blank" rel="noopener">Firefox</a></li>
                            <li><a href="https://support.apple.com/guide/safari/manage-cookies-sfri11471/mac" target="_blank" rel="noopener">Safari</a></li>
                        </ul>
                    </div>

                    <!-- Contact Box -->
                    <div class="contact-info-box">
                        <h4><i class="bi bi-envelope-paper"></i> Policy Questions?</h4>
                        <p style="margin-bottom: 8px; font-size: 14px; color: rgba(255,255,255,.85);">If you have questions about any of our policies, reach out to our team:</p>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 14px;">
                            <li><i class="bi bi-envelope"></i> <a href="mailto:<?= $contact['email'] ?>"><?= $contact['email'] ?></a></li>
                            <li><i class="bi bi-telephone"></i> +91 <?= $contact['phone'] ?></li>
                            <li><i class="bi bi-geo-alt"></i> <?= $contact['address'] ?></li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php include_once "includes/footer.php"; ?>
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Highlight active nav on scroll
        (function() {
            const sections = ['privacy', 'terms', 'shipping', 'returns', 'cookies'];
            const links = {};
            sections.forEach(id => links[id] = document.querySelector('.policy-nav a[href="#' + id + '"]'));

            window.addEventListener('scroll', () => {
                let current = sections[0];
                sections.forEach(id => {
                    const el = document.getElementById(id);
                    if (el && window.scrollY >= el.offsetTop - 130) current = id;
                });
                Object.values(links).forEach(a => a && a.classList.remove('active'));
                links[current] && links[current].classList.add('active');
            });

            // Smooth scroll
            document.querySelectorAll('.policy-nav a').forEach(a => {
                a.addEventListener('click', e => {
                    e.preventDefault();
                    const target = document.getElementById(a.getAttribute('href').slice(1));
                    if (target) target.scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        })();
    </script>
</body>

</html>