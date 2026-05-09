<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Swiper css link -->
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <!-- Fancybox css link -->
    <link rel="stylesheet" href="assets/css/jquery.fancybox.min.css">
    <!-- Animation css link -->
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="assets/css/nice-select.css">
    <!-- bootstrap css link -->
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Boxicon css link -->
    <link rel="stylesheet" href="assets/css/boxicons.min.css">
    <!-- My css link -->
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Customize Suit</title>
    <link rel="icon" href="assets/image/thumbnail.svg" type="image/gif" sizes="20x20">
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
       
        /* utility */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .mb-100 {
            margin-bottom: 80px;
        }
        .mt-100 {
            margin-top: 60px;
        }

        

        /* customization layout */
        .customizer-grid {
            display: grid;
            grid-template-columns: 1fr 0.9fr;
            gap: 48px;
        }
        @media (max-width: 992px) {
            .customizer-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        /* left side - options */
        .option-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 32px;
            transition: all 0.2s;
        }
        .card-header {
            padding: 20px 28px;
            border-bottom: 1px solid #eeeae5;
            background: #ffffff;
        }
        .card-header h3 {
            font-size: 1.6rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header h3 i {
            color: #b87c4f;
            font-size: 1.8rem;
        }
        .options-wrapper {
            padding: 24px 28px 32px;
        }
        .option-group {
            margin-bottom: 32px;
        }
        .option-label {
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .badge-price {
            background: #f0ede8;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #5e3a2b;
        }
        .choice-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }
        .choice-item {
            background: #f6f3ef;
            border-radius: 100px;
            padding: 10px 24px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
            font-size: 0.9rem;
        }
        .choice-item.active {
            background: #1e1e2a;
            color: white;
            border-color: #1e1e2a;
            box-shadow: 0 6px 12px rgba(0,0,0,0.08);
        }
        .choice-item:hover:not(.active) {
            background: #e7e0d8;
            transform: translateY(-1px);
        }
        .color-swatch {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ddd;
            border: 2px solid transparent;
            transition: 0.1s;
            cursor: pointer;
        }
        .color-swatch.active {
            border: 3px solid #1e1e2a;
            box-shadow: 0 0 0 2px white, 0 0 0 4px #b87c4f;
            transform: scale(1.02);
        }

        /* measurements section */
        .measure-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .measure-field {
            flex: 1;
        }
        .measure-field label {
            font-size: 0.8rem;
            font-weight: 500;
            color: #5c5c66;
            display: block;
            margin-bottom: 6px;
        }
        .measure-field input {
            width: 100%;
            padding: 12px 12px;
            border: 1px solid #e2dbd2;
            border-radius: 16px;
            font-family: inherit;
            font-weight: 500;
            background: #fff;
            transition: 0.2s;
        }
        .measure-field input:focus {
            outline: none;
            border-color: #b87c4f;
            box-shadow: 0 0 0 3px rgba(184,124,79,0.2);
        }

        /* right side - order summary & preview */
        .summary-sticky {
            position: sticky;
            top: 30px;
        }
        .order-preview-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .preview-image {
            background: #f2efe9;
            text-align: center;
            padding: 40px 20px;
            border-bottom: 1px solid #efebe5;
        }
        .suit-silhouette {
            max-width: 220px;
            margin: 0 auto;
            transition: all 0.2s;
        }
        .suit-silhouette svg {
            width: 100%;
            filter: drop-shadow(0 6px 12px rgba(0,0,0,0.1));
        }
        .order-details {
            padding: 28px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed #ece6df;
        }
        .summary-total {
            font-weight: 800;
            font-size: 1.6rem;
            margin-top: 16px;
            color: #b87c4f;
        }
        .primary-btn {
            background: #1e1e2a;
            color: white;
            border: none;
            border-radius: 48px;
            padding: 16px 28px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: 0.2s;
            margin-top: 28px;
        }
        .primary-btn:hover {
            background: #b87c4f;
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -8px rgba(184,124,79,0.4);
        }
        .note-text {
            font-size: 0.75rem;
            color: #7a6e62;
            text-align: center;
            margin-top: 20px;
        }
        .toast-msg {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e1e2a;
            color: white;
            padding: 12px 28px;
            border-radius: 60px;
            font-weight: 500;
            z-index: 1200;
            opacity: 0;
            transition: 0.25s;
            pointer-events: none;
            box-shadow: 0 8px 18px rgba(0,0,0,0.2);
        }
        @media (max-width: 640px) {
            .container { padding: 0 18px; }
            .breadcrumb-section { padding: 50px 0; }
            .banner-content h1 { font-size: 2.2rem; }
            .choice-item { padding: 6px 16px; font-size: 0.8rem; }
        }
        hr {
            margin: 12px 0;
        }
        .payment-icons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 16px;
            font-size: 1.8rem;
            color: #9a8b7c;
        }
    </style>
</head>
<body>

<?php
    include_once "login-popup.php";
    include_once "includes/header.php";
    include_once "includes/mobile-bottom-nav.php";
    ?>

<!-- breadcrumb / hero -->
  <div class="breadcrumb-section mb-100"
        style="background-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url(assets/image/background/contact_hero.jpg);">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-center">
                    <div class="banner-content style-2 text-center">
                        <h1>Customize Your Suit</h1>
                        <ul class="breadcrumb-list">
                            <li><a href="<?= BASE_URL ?>">Home</a></li>
                            <li><span>/ </span>Customize Your Suit</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- main customizer section -->
<div class="container mb-100">
    <div class="customizer-grid">
        <!-- LEFT COLUMN: customization options -->
        <div class="customize-options">
            <!-- fabric selection -->
            <div class="option-card">
                <div class="card-header">
                    <h3><i class="bi bi-grid-3x3-gap-fill"></i> Fabric & Material</h3>
                </div>
                <div class="options-wrapper">
                    <div class="option-group">
                        <div class="option-label">Choose Fabric <span class="badge-price">premium</span></div>
                        <div class="choice-grid" id="fabricGroup">
                            <div data-fabric="Premium Wool" data-price="0" class="choice-item active">Premium Wool <span style="font-size:0.7rem;">(+$0)</span></div>
                            <div data-fabric="Silk Wool Blend" data-price="89" class="choice-item">Silk Wool Blend <span>(+$89)</span></div>
                            <div data-fabric="Linen Summer" data-price="45" class="choice-item">Linen Summer <span>(+$45)</span></div>
                            <div data-fabric="Velvet Luxe" data-price="149" class="choice-item">Velvet Luxe <span>(+$149)</span></div>
                        </div>
                    </div>
                    <div class="option-group">
                        <div class="option-label">Fabric Color</div>
                        <div class="choice-grid" id="colorGroup" style="gap: 12px;">
                            <div data-color="Navy" data-hex="#1b2a44" style="background:#1b2a44;" class="color-swatch active" title="Navy"></div>
                            <div data-color="Charcoal" data-hex="#3b3b44" style="background:#3b3b44;" class="color-swatch" title="Charcoal"></div>
                            <div data-color="Black" data-hex="#222222" style="background:#222;" class="color-swatch" title="Black"></div>
                            <div data-color="Midnight Blue" data-hex="#232b4b" style="background:#232b4b;" class="color-swatch" title="Midnight Blue"></div>
                            <div data-color="Burgundy" data-hex="#6e2c3a" style="background:#6e2c3a;" class="color-swatch" title="Burgundy"></div>
                            <div data-color="Beige" data-hex="#d9c8a9" style="background:#d9c8a9;" class="color-swatch" title="Beige"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Style & Fit -->
            <div class="option-card">
                <div class="card-header">
                    <h3><i class="bi bi-person-standing-dress"></i> Silhouette & Fit</h3>
                </div>
                <div class="options-wrapper">
                    <div class="option-group">
                        <div class="choice-grid" id="fitGroup">
                            <div data-fit="Slim Fit" data-price="0" class="choice-item active">Slim Fit</div>
                            <div data-fit="Classic Fit" data-price="0" class="choice-item">Classic Fit</div>
                            <div data-fit="Modern Tailored" data-price="25" class="choice-item">Modern Tailored (+$25)</div>
                        </div>
                    </div>
                    <div class="option-group">
                        <div class="option-label">Lapel Style</div>
                        <div class="choice-grid" id="lapelGroup">
                            <div data-lapel="Notch Lapel" data-price="0" class="choice-item active">Notch Lapel</div>
                            <div data-lapel="Peak Lapel" data-price="20" class="choice-item">Peak Lapel (+$20)</div>
                            <div data-lapel="Shawl Collar" data-price="35" class="choice-item">Shawl Collar (+$35)</div>
                        </div>
                    </div>
                    <div class="option-group">
                        <div class="option-label">Jacket Buttons</div>
                        <div class="choice-grid" id="buttonsGroup">
                            <div data-buttons="1 Button" data-price="0" class="choice-item">1 Button</div>
                            <div data-buttons="2 Button" data-price="0" class="choice-item active">2 Button (classic)</div>
                            <div data-buttons="Double Breasted" data-price="45" class="choice-item">Double Breasted (+$45)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Measurements -->
            <div class="option-card">
                <div class="card-header">
                    <h3><i class="bi bi-rulers"></i> Your Custom Measurements</h3>
                </div>
                <div class="options-wrapper">
                    <div class="measure-row">
                        <div class="measure-field"><label>Chest (inches)</label><input type="number" id="chest" placeholder="38" value="38" step="0.5"></div>
                        <div class="measure-field"><label>Waist (inches)</label><input type="number" id="waist" placeholder="32" value="32" step="0.5"></div>
                        <div class="measure-field"><label>Hips (inches)</label><input type="number" id="hips" placeholder="39" value="39" step="0.5"></div>
                    </div>
                    <div class="measure-row" style="margin-top: 12px;">
                        <div class="measure-field"><label>Jacket Length</label><input type="text" id="length" placeholder="Regular / Long" value="Regular"></div>
                        <div class="measure-field"><label>Sleeve (inches)</label><input type="number" id="sleeve" placeholder="25" value="25" step="0.5"></div>
                        <div class="measure-field"><label>Shoulder</label><input type="number" id="shoulder" placeholder="18" value="18" step="0.5"></div>
                    </div>
                    <div class="note-text" style="margin-top: 12px; text-align: left;"><i class="bi bi-info-circle"></i> Our master tailor will contact for final fitting adjustments.</div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: order summary + dynamic preview -->
        <div class="summary-sticky">
            <div class="order-preview-card">
                <div class="preview-image">
                    <div class="suit-silhouette" id="suitPreviewSvg">
                        <!-- dynamic simple svg suit visualization that changes color -->
                        <svg viewBox="0 0 200 260" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path id="suitBase" d="M65 60 L100 35 L135 60 L140 80 L135 100 L130 140 L128 190 L125 220 L100 230 L75 220 L72 190 L70 140 L65 100 L60 80 L65 60Z" fill="#1b2a44" stroke="#2c2c2c" stroke-width="2"/>
                            <path d="M100 35 L100 60" stroke="#e4c9a7" stroke-width="2"/>
                            <path d="M85 75 L115 75 L110 95 L100 100 L90 95 Z" fill="#e4c9a7" opacity="0.8"/>
                            <circle cx="100" cy="85" r="4" fill="#b87c4f"/>
                        </svg>
                    </div>
                    <p style="margin-top: 10px; font-size: 0.8rem; color:#b87c4f; font-weight:500;">Live preview · color & style updates</p>
                </div>
                <div class="order-details">
                    <h4 style="font-weight: 700; margin-bottom: 18px;">Your Bespoke Configuration</h4>
                    <div class="summary-item"><span>Base Suit Price</span><strong>$590</strong></div>
                    <div class="summary-item" id="fabricSummary"><span>Fabric: Premium Wool</span><span>$0</span></div>
                    <div class="summary-item" id="fitSummary"><span>Fit: Slim Fit</span><span>$0</span></div>
                    <div class="summary-item" id="lapelSummary"><span>Lapel: Notch Lapel</span><span>$0</span></div>
                    <div class="summary-item" id="buttonsSummary"><span>Buttons: 2 Button</span><span>$0</span></div>
                    <div class="summary-item" id="colorSummary"><span>Color: Navy</span><span>—</span></div>
                    <div style="margin-top: 16px;">
                        <hr>
                        <div class="summary-total d-flex justify-content-between"><span>Total Amount</span><span id="totalPrice">$590</span></div>
                    </div>
                    <button class="primary-btn" id="addToCartBtn"><i class="bi bi-cart-check-fill"></i> Add to Cart – Custom Suit</button>
                    <div class="payment-icons">
                        <i class="bi bi-credit-card"></i> <i class="bi bi-paypal"></i> <i class="bi bi-apple"></i>
                    </div>
                    <div class="note-text">* Made-to-measure, shipped within 4 weeks. Free adjustments.</div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- footer section strats here -->
    <?php include_once "includes/footer.php"; ?>
    <!-- footer section end here -->


    <!-- Jquery js link -->
    <script data-cfasync="false" src="../../../cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/jquery-ui.js"></script>
    <!-- Counterup js link -->
    <script src="assets/js/waypoints.js"></script>
    <script src="assets/js/jquery.counterup.js"></script>
    <script src="assets/js/jquery.counterup.min.js"></script>
    <!-- Marquee js link -->
    <script src="assets/js/jquery.marquee.min.js"></script>
    <!-- Popper js link -->
    <script src="assets/js/popper.min.js"></script>
    <!-- Swiper js link -->
    <script src="assets/js/swiper-bundle.min.js"></script>
    <!-- Fancybox js link -->
    <script src="assets/js/jquery.fancybox.min.js"></script>
    <script src="assets/js/jquery.nice-select.min.js"></script>
    <!-- Wow js link -->
    <script src="assets/js/wow.min.js"></script>
    <!-- Bootstrap js link -->
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- MAin js link -->
    <script src="assets/js/main.js"></script>

    <script>
        $(".marquee_text2").marquee({
            direction: "left",
            duration: 25000,
            gap: 50,
            delayBeforeStart: 0,
            duplicated: true,
            startVisible: true,
        });
    </script>
    <script defer src="https://static.cloudflareinsights.com/beacon.min.js/v8c78df7c7c0f484497ecbca7046644da1771523124516" integrity="sha512-8DS7rgIrAmghBFwoOTujcf6D9rXvH8xm8JQ1Ja01h9QX8EzXldiszufYa4IFfKdLUKTTrnSFXLDkUEOTrZQ8Qg==" data-cf-beacon='{"version":"2024.11.0","token":"70834e4b23964a2eaf7cf4ec0e5e9a84","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>

<script>
    // pricing core
    const basePrice = 590;

    // state
    let selectedFabric = { name: "Premium Wool", price: 0 };
    let selectedFit = { name: "Slim Fit", price: 0 };
    let selectedLapel = { name: "Notch Lapel", price: 0 };
    let selectedButtons = { name: "2 Button", price: 0 };
    let selectedColor = { name: "Navy", hex: "#1b2a44" };

    // DOM elements
    function updateTotal() {
        let extras = selectedFabric.price + selectedFit.price + selectedLapel.price + selectedButtons.price;
        let total = basePrice + extras;
        document.getElementById("totalPrice").innerText = `$${total}`;
        // update summary lines
        document.getElementById("fabricSummary").innerHTML = `<span>Fabric: ${selectedFabric.name}</span><span>$${selectedFabric.price}</span>`;
        document.getElementById("fitSummary").innerHTML = `<span>Fit: ${selectedFit.name}</span><span>$${selectedFit.price}</span>`;
        document.getElementById("lapelSummary").innerHTML = `<span>Lapel: ${selectedLapel.name}</span><span>$${selectedLapel.price}</span>`;
        document.getElementById("buttonsSummary").innerHTML = `<span>Buttons: ${selectedButtons.name}</span><span>$${selectedButtons.price}</span>`;
        document.getElementById("colorSummary").innerHTML = `<span>Color: ${selectedColor.name}</span><span>—</span>`;
        // update SVG color dynamically
        updateSuitColor(selectedColor.hex);
    }

    function updateSuitColor(colorHex) {
        const svgPath = document.querySelector("#suitBase");
        if(svgPath) {
            svgPath.setAttribute("fill", colorHex);
        }
        // optional: lapel style little change, not needed
    }

    // event binding for fabric
    function initChoiceListeners() {
        // Fabric
        document.querySelectorAll("#fabricGroup .choice-item").forEach(el => {
            el.addEventListener("click", function() {
                document.querySelectorAll("#fabricGroup .choice-item").forEach(c => c.classList.remove("active"));
                this.classList.add("active");
                let fabricName = this.getAttribute("data-fabric");
                let fabricPrice = parseInt(this.getAttribute("data-price")) || 0;
                selectedFabric = { name: fabricName, price: fabricPrice };
                updateTotal();
            });
        });
        // Fit
        document.querySelectorAll("#fitGroup .choice-item").forEach(el => {
            el.addEventListener("click", function() {
                document.querySelectorAll("#fitGroup .choice-item").forEach(c => c.classList.remove("active"));
                this.classList.add("active");
                let fitName = this.getAttribute("data-fit");
                let fitPrice = parseInt(this.getAttribute("data-price")) || 0;
                selectedFit = { name: fitName, price: fitPrice };
                updateTotal();
            });
        });
        // Lapel
        document.querySelectorAll("#lapelGroup .choice-item").forEach(el => {
            el.addEventListener("click", function() {
                document.querySelectorAll("#lapelGroup .choice-item").forEach(c => c.classList.remove("active"));
                this.classList.add("active");
                let lapelName = this.getAttribute("data-lapel");
                let lapelPrice = parseInt(this.getAttribute("data-price")) || 0;
                selectedLapel = { name: lapelName, price: lapelPrice };
                updateTotal();
            });
        });
        // Buttons
        document.querySelectorAll("#buttonsGroup .choice-item").forEach(el => {
            el.addEventListener("click", function() {
                document.querySelectorAll("#buttonsGroup .choice-item").forEach(c => c.classList.remove("active"));
                this.classList.add("active");
                let btnName = this.getAttribute("data-buttons");
                let btnPrice = parseInt(this.getAttribute("data-price")) || 0;
                selectedButtons = { name: btnName, price: btnPrice };
                updateTotal();
            });
        });
        // Color swatches
        document.querySelectorAll("#colorGroup .color-swatch").forEach(sw => {
            sw.addEventListener("click", function() {
                document.querySelectorAll("#colorGroup .color-swatch").forEach(c => c.classList.remove("active"));
                this.classList.add("active");
                let colorName = this.getAttribute("data-color");
                let colorHex = this.getAttribute("data-hex");
                selectedColor = { name: colorName, hex: colorHex };
                updateTotal();
            });
        });
    }

    // measure input changes only for final cart representation
    function getMeasurementsSummary() {
        let chest = document.getElementById("chest").value;
        let waist = document.getElementById("waist").value;
        let hips = document.getElementById("hips").value;
        let sleeve = document.getElementById("sleeve").value;
        let length = document.getElementById("length").value;
        return `Chest:${chest}", Waist:${waist}", Hips:${hips}", Sleeve:${sleeve}", Jacket Length:${length}`;
    }

    // add to cart: show toast and log + simulate local storage
    const addBtn = document.getElementById("addToCartBtn");
    const toastMsg = document.getElementById("liveToast");

    function showToastMessage(text) {
        toastMsg.innerText = text || "✓ Custom suit added! Our team will reach out.";
        toastMsg.style.opacity = "1";
        setTimeout(() => {
            toastMsg.style.opacity = "0";
        }, 2800);
    }

    addBtn.addEventListener("click", (e) => {
        e.preventDefault();
        const total = document.getElementById("totalPrice").innerText;
        const measurements = getMeasurementsSummary();
        const orderDetails = {
            items: [{
                product: "Bespoke Custom Suit",
                fabric: selectedFabric.name,
                fit: selectedFit.name,
                lapel: selectedLapel.name,
                buttons: selectedButtons.name,
                color: selectedColor.name,
                measurements: measurements,
                totalPrice: total,
                timestamp: new Date().toISOString()
            }]
        };
        // store to localStorage for demo (simulate cart)
        let existingCart = localStorage.getItem("customSuitCart");
        if(existingCart) {
            let cart = JSON.parse(existingCart);
            cart.push(orderDetails.items[0]);
            localStorage.setItem("customSuitCart", JSON.stringify(cart));
        } else {
            localStorage.setItem("customSuitCart", JSON.stringify([orderDetails.items[0]]));
        }
        console.log("Saved to local storage:", orderDetails);
        showToastMessage("✨ Bespoke suit added! We'll confirm your measurements.");
        // optional: reset effect or redirect hint
    });

    // initial total update
    updateTotal();
    initChoiceListeners();

    // small fix for preview svg color load
    document.addEventListener("DOMContentLoaded", () => {
        updateSuitColor("#1b2a44");
        // set active states default
    });

    // plus some interactive improvement for active highlight sync
    const fitDefault = document.querySelector('#fitGroup .choice-item.active');
    if(fitDefault && fitDefault.getAttribute('data-fit')) selectedFit = { name: fitDefault.getAttribute('data-fit'), price: parseInt(fitDefault.getAttribute('data-price'))||0 };
    const fabricDefault = document.querySelector('#fabricGroup .choice-item.active');
    if(fabricDefault) selectedFabric = { name: fabricDefault.getAttribute('data-fabric'), price: parseInt(fabricDefault.getAttribute('data-price'))||0 };
    const lapelDefault = document.querySelector('#lapelGroup .choice-item.active');
    if(lapelDefault) selectedLapel = { name: lapelDefault.getAttribute('data-lapel'), price: parseInt(lapelDefault.getAttribute('data-price'))||0 };
    const buttonsDefault = document.querySelector('#buttonsGroup .choice-item.active');
    if(buttonsDefault) selectedButtons = { name: buttonsDefault.getAttribute('data-buttons'), price: parseInt(buttonsDefault.getAttribute('data-price'))||0 };
    updateTotal();
</script>
</body>
</html>