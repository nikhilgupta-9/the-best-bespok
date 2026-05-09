<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Marketing Audit Report | Nishant Taneja - Indian Eye Institute</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Segoe UI', sans-serif;
            background-color: #eef2f5;
            padding: 40px 20px;
            color: #1A1A2E;
        }

        /* Print styles - Ctrl+P support */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .report-container {
                max-width: 100%;
                margin: 0;
                padding: 0.5in;
                box-shadow: none;
            }
            .page-break {
                page-break-before: always;
            }
            h1, h2, h3 {
                page-break-after: avoid;
            }
            table, .table-wrapper {
                page-break-inside: avoid;
            }
            .cover-page {
                page-break-after: always;
            }
            @page {
                size: A4;
                margin: 1.2cm;
            }
        }

        /* Print button styling */
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1A3A5C;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: all 0.3s;
            border: none;
        }
        .print-btn:hover {
            background: #2E86C1;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.25);
        }
        .print-btn i {
            font-size: 18px;
        }

        /* Report container */
        .report-container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        /* Content styles */
        .content-inner {
            padding: 50px 60px;
        }

        @media (max-width: 768px) {
            .content-inner {
                padding: 30px 25px;
            }
            .print-btn {
                bottom: 20px;
                right: 20px;
                padding: 10px 20px;
                font-size: 14px;
            }
        }

        /* Typography */
        h1 {
            font-size: 28px;
            color: #1A3A5C;
            margin: 40px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 3px solid #2E86C1;
        }
        h2 {
            font-size: 22px;
            color: #2E86C1;
            margin: 30px 0 15px 0;
        }
        h3 {
            font-size: 18px;
            color: #1A3A5C;
            margin: 20px 0 12px 0;
        }
        p {
            line-height: 1.6;
            margin-bottom: 15px;
            color: #1A1A2E;
            font-size: 14px;
        }
        ul, .bullet-list {
            margin: 15px 0 15px 25px;
        }
        li {
            margin: 8px 0;
            line-height: 1.5;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 13px;
        }
        .data-table th {
            background: #1A3A5C;
            color: white;
            padding: 12px 10px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #1A3A5C;
        }
        .data-table td {
            padding: 10px 8px;
            border: 1px solid #CCCCCC;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) {
            background: #F2F3F4;
        }
        .score-badge {
            text-align: center;
            font-weight: bold;
            border-radius: 4px;
            padding: 4px 8px;
            display: inline-block;
            min-width: 60px;
        }
        .score-high {
            background: #1E8449;
            color: white;
        }
        .score-mid {
            background: #2E86C1;
            color: white;
        }
        .score-low {
            background: #D4830A;
            color: white;
        }
        .score-critical {
            background: #C0392B;
            color: white;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }
        .status-strong {
            background: #1E8449;
            color: white;
        }
        .status-adequate {
            background: #2E86C1;
            color: white;
        }
        .status-weak {
            background: #D4830A;
            color: white;
        }
        .status-critical {
            background: #C0392B;
            color: white;
        }

        /* Cover page */
        .cover-page {
            text-align: center;
            padding: 80px 40px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }
        .cover-title {
            font-size: 42px;
            font-weight: bold;
            color: #1A3A5C;
            margin: 30px 0;
            letter-spacing: 1px;
        }
        .cover-subtitle {
            font-size: 20px;
            color: #2E86C1;
            margin: 20px 0;
        }
        .score-box {
            background: white;
            border: 2px solid #2E86C1;
            border-radius: 12px;
            padding: 25px;
            margin: 40px auto;
            max-width: 400px;
        }
        .score-number {
            font-size: 64px;
            font-weight: bold;
            color: #D4830A;
        }
        hr {
            margin: 30px 0;
            border: none;
            height: 2px;
            background: #2E86C1;
        }

        /* Page break */
        .page-break {
            page-break-before: always;
            margin-top: 40px;
        }

        /* Footer/header simulation */
        .report-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #CCCCCC;
            font-size: 11px;
            color: #666;
        }

        /* Table wrapper for responsive */
        .table-wrapper {
            overflow-x: auto;
            margin: 20px 0;
        }

        /* Highlight boxes */
        .highlight-box {
            background: #EBF5FB;
            border-left: 4px solid #2E86C1;
            padding: 15px 20px;
            margin: 20px 0;
        }
        .warning-box {
            background: #FEF3CD;
            border-left: 4px solid #D4830A;
            padding: 15px 20px;
            margin: 20px 0;
        }
        .critical-box {
            background: #FADBD8;
            border-left: 4px solid #C0392B;
            padding: 15px 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print();">
        <i>🖨️</i> Print / Save as PDF (Ctrl+P)
    </button>

    <div class="report-container">
        <div class="content-inner">
            <!-- COVER PAGE -->
            <div class="cover-page">
                <h1 class="cover-title">DIGITAL MARKETING<br>AUDIT REPORT</h1>
                <hr style="width: 100px; margin: 20px auto;">
                <p class="cover-subtitle">nishanttaneja.com &nbsp;|&nbsp; Indian Eye Institute & Laser Center</p>
                <p><strong>Dr. Nishant Taneja — Tirana, Albania</strong></p>
                
                <div class="score-box">
                    <div style="font-size: 18px; margin-bottom: 10px;">COMPOSITE DIGITAL HEALTH SCORE</div>
                    <div class="score-number">5.4 / 10</div>
                    <div style="margin-top: 15px; font-size: 13px; color: #666;">Adequate clinical authority offline • Underperforming digital presence • High recovery potential</div>
                </div>
                
                <p style="margin-top: 40px;"><strong>Audit Date:</strong> April–May 2026</p>
                <p><strong>Channels Audited:</strong> Google Business Profile • Websites • SEO • Social Media • Performance Marketing</p>
            </div>

            <!-- SECTION 1: EXECUTIVE SUMMARY -->
            <h1>1. EXECUTIVE SUMMARY</h1>
            <hr>
            <p>Indian Eye Institute & Laser Center (IEI), led by Dr. Nishant Taneja, holds a strong clinical reputation in Tirana with 16+ years of operating presence, a flagship Zeiss Visumax 800 platform installed in March 2025, and consistent media visibility across Albania's leading news channels. However, the brand's digital marketing footprint has not kept pace with its clinical strength.</p>
            <p>This audit was conducted across five digital channels. The analysis identifies a material disconnect between IEI's offline reputation and online performance — a gap that competitors are actively exploiting.</p>

            <h2>1.1 Channel-Level Snapshot</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr><th>Channel</th><th>Score</th><th>Status</th><th>Headline Issue</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>Google Business Profile (GMB)</strong></td><td style="text-align:center; background:#D4830A; color:white; font-weight:bold;">5.5/10</td><td style="text-align:center; background:#D4830A; color:white;">Weak</td><td>4.4★ vs competitors at 4.8–5.0★; unresponded negative reviews on wait times</td></tr>
                        <tr><td><strong>Websites (Combined)</strong></td><td style="text-align:center; background:#D4830A; color:white; font-weight:bold;">5.0/10</td><td style="text-align:center; background:#D4830A; color:white;">Weak</td><td>Two-site fragmentation; .al site has duplicate placeholder content on 14 pages</td></tr>
                        <tr><td><strong>SEO (Local + On-Page + Off-Page)</strong></td><td style="text-align:center; background:#D4830A; color:white; font-weight:bold;">5.5/10</td><td style="text-align:center; background:#D4830A; color:white;">Weak</td><td>Strong PR backlink profile; weak on-page optimization & technical SEO</td></tr>
                        <tr><td><strong>Social Media Optimization</strong></td><td style="text-align:center; background:#2E86C1; color:white; font-weight:bold;">6.0/10</td><td style="text-align:center; background:#2E86C1; color:white;">Adequate</td><td>Decent Instagram following (30K combined); zero TikTok / YouTube presence</td></tr>
                        <tr><td><strong>Performance Marketing</strong></td><td style="text-align:center; background:#C0392B; color:white; font-weight:bold;">3.5/10</td><td style="text-align:center; background:#C0392B; color:white;">Critical Gap</td><td>No Google Ads / Meta Ads infrastructure; no dedicated landing pages</td></tr>
                    </tbody>
                </table>
            </div>

            <h2>1.2 Top 5 Critical Findings</h2>
            <ul>
                <li><strong>Google Review Crisis:</strong> IEI sits at 4.4★ with 208 reviews while competitors (Pro-Vision 5.0★, Universal Eye 4.8★) are pulling ahead. Recent reviews cluster around wait times and communication failures.</li>
                <li><strong>Two Parallel Websites Diluting Authority:</strong> indianeyeinstitute.al (March 2025) and nishanttaneja.com (since 2022) split domain authority and confuse Google.</li>
                <li><strong>SMILE Pro Positioning Underleveraged:</strong> Zeiss Visumax 800 advantage has no dedicated landing page or paid campaigns.</li>
                <li><strong>Zero Structured Performance Marketing:</strong> No Google Ads, Meta lead-generation funnels, or conversion tracking.</li>
                <li><strong>On-Page SEO Materially Incomplete:</strong> 14+ service pages on .al site share identical 22-word placeholder descriptions.</li>
            </ul>

            <!-- SECTION 2: WEBSITE AUDIT -->
            <div class="page-break"></div>
            <h1>2. WEBSITE AUDIT</h1>
            <hr>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>nishanttaneja.com</th><th>indianeyeinstitute.al</th><th>Combined Score</th></tr></thead>
                    <tbody><tr><td style="text-align:center; background:#2E86C1; color:white; font-weight:bold;">6.4 / 10 — Adequate</td><td style="text-align:center; background:#D4830A; color:white; font-weight:bold;">4.3 / 10 — Weak</td><td style="text-align:center; background:#D4830A; color:white; font-weight:bold;">5.0 / 10 — Weak</td></tr></tbody>
                </table>
            </div>

            <h2>2.1 nishanttaneja.com — Detailed Assessment</h2>
            <p>The primary brand domain has been live since at least 2022 and carries the strongest link equity due to consistent media coverage (TV Klan, Top Channel, Report TV, Balkan Web, Shqiptarja.com).</p>
            
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Criterion</th><th>Score</th><th>Notes</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Content quality & uniqueness</strong></td><td style="text-align:center; background:#1E8449; color:white;">7.5/10</td><td>Genuine clinical depth on most treatment pages</td></tr>
                        <tr><td><strong>Information architecture</strong></td><td style="text-align:center; background:#1E8449; color:white;">7.0/10</td><td>Logical nested treatment structure</td></tr>
                        <tr><td><strong>Conversion path / CTAs</strong></td><td style="text-align:center; background:#D4830A; color:white;">5.5/10</td><td>CTAs exist but no sticky WhatsApp button</td></tr>
                        <tr><td><strong>Trust signals (PR, awards)</strong></td><td style="text-align:center; background:#1E8449; color:white;">8.0/10</td><td>Excellent — TV mentions strip is a credibility asset</td></tr>
                    </tbody>
                </table>
            </div>

            <h2>2.2 indianeyeinstitute.al — Critical Issues</h2>
            <div class="critical-box">
                <strong>CRITICAL:</strong> All 14 treatment cards display identical 22-word placeholder copy — duplicate thin content penalized by Google.
            </div>
            <ul>
                <li>Media assets load from Hostinger staging URL — incomplete production deployment</li>
                <li>All blog posts dated 24 March 2025 in a single batch; no content added since</li>
                <li>Missing pages: SMILE Pro, team/staff, pricing/packages</li>
            </ul>

            <h2>2.3 The Two-Domain Problem — Strategic Risk</h2>
            <p>Running two parallel websites creates domain authority dilution, duplicate content risk, patient confusion, and doubled maintenance overhead.</p>
            <div class="highlight-box">
                <strong>Recommended Resolution:</strong> Designate indianeyeinstitute.al as primary institutional brand domain. Migrate nishanttaneja.com content via 301 redirects.
            </div>

            <!-- SECTION 3: GMB AUDIT -->
            <div class="page-break"></div>
            <h1>3. GOOGLE BUSINESS PROFILE (GMB) AUDIT</h1>
            <hr>
            <div class="warning-box" style="text-align:center; font-size:18px; font-weight:bold;">Channel Score: 5.5 / 10 — Weak/Adequate Boundary</div>

            <h2>3.1 Listing Overview</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Field</th><th>Details</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Listing Name</strong></td><td>Nishant Taneja, Indian Eye Institute and Laser Center</td></tr>
                        <tr><td><strong>Star Rating</strong></td><td>4.4 ★ (208 reviews) — BELOW competitive threshold of 4.5★</td></tr>
                        <tr><td><strong>Operating Hours</strong></td><td>Mon–Fri 08:00–20:00, Saturday 08:00–16:00</td></tr>
                    </tbody>
                </table>
            </div>

            <h2>3.2 Sub-Criteria Scoring</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Sub-Criterion</th><th>Score</th><th>Observation</th></tr></thead>
                    <tbody>
                        <tr><td>Star Rating vs Competitors</td><td style="text-align:center; background:#C0392B; color:white;">3.5/10</td><td>4.4★ trails Pro-Vision (5.0★), Tirana Eye Clinic (4.9★)</td></tr>
                        <tr><td>Review Sentiment Quality</td><td style="text-align:center; background:#C0392B; color:white;">3.0/10</td><td>4 of 5 most recent reviews are negative</td></tr>
                        <tr><td>Owner Response Discipline</td><td style="text-align:center; background:#D4830A; color:white;">4.0/10</td><td>No consistent professional responses — single most fixable lever</td></tr>
                    </tbody>
                </table>
            </div>

            <h2>3.3 Competitive Review Benchmarking</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Clinic</th><th>Rating</th><th>Reviews</th></tr></thead>
                    <tbody>
                        <tr><td>Pro-Vision (Dr. Hibraj)</td><td>5.0 ★</td><td>257</td></tr>
                        <tr><td>Tirana Eye Clinic (Dr. Lula)</td><td>4.9 ★</td><td>65</td></tr>
                        <tr><td>Universal Eye Hospital</td><td>4.8 ★</td><td>401</td></tr>
                        <tr style="background:#FEF3CD;"><td><strong>INDIAN EYE INSTITUTE (IEI)</strong></td><td><strong>4.4 ★</strong></td><td><strong>208</strong></td></tr>
                        <tr><td>European Eye Clinic</td><td>4.7 ★</td><td>316</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- SECTION 4: SEO AUDIT -->
            <div class="page-break"></div>
            <h1>4. SEO AUDIT — LOCAL, ON-PAGE & OFF-PAGE</h1>
            <hr>
            <div class="warning-box" style="text-align:center; font-weight:bold;">Channel Score: 5.5 / 10 — Weak</div>

            <h2>4.1 Local SEO — Sub-Score: 6.0/10</h2>
            <ul>
                <li>GMB Optimization: 5.5/10 — solid foundation, weak optimization layer</li>
                <li>Local Citations: Listed on Wupdoc, OnMend; missing from Biznesi.al, Albania Yellow Pages</li>
                <li>Map Pack Visibility: Likely outside top-3 for high-intent queries</li>
            </ul>

            <h2>4.2 On-Page SEO — Sub-Score: 5.0/10</h2>
            <ul>
                <li>Title tags missing geo + service combinations</li>
                <li>Heading structure inconsistent on .al site</li>
                <li>Schema markup (MedicalClinic, Physician) appears absent</li>
            </ul>

            <div class="highlight-box">
                <strong>4.3 Off-Page SEO — Sub-Score: 6.5/10 (Strongest Asset)</strong><br>
                Media appearances on TV Klan, Top Channel, Report TV, Balkan Web, Shqiptarja.com have generated high-authority editorial backlinks that competitors cannot easily replicate.
            </div>

            <!-- SECTION 5: SOCIAL MEDIA & PERFORMANCE -->
            <div class="page-break"></div>
            <h1>5. SOCIAL MEDIA & PERFORMANCE MARKETING</h1>
            <hr>
            <h2>5.1 Social Media Optimization — Score: 6.0/10 — Adequate</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Platform</th><th>Following</th><th>Status</th><th>Assessment</th></tr></thead>
                    <tbody>
                        <tr><td>Instagram</td><td>~30K combined</td><td style="background:#1E8449; color:white; text-align:center;">Active</td><td>Strongest asset; content quality inconsistent</td></tr>
                        <tr><td>Facebook</td><td>Present</td><td style="background:#1E8449; color:white; text-align:center;">Active</td><td>Lower engagement; good for local demographic</td></tr>
                        <tr><td>TikTok</td><td>Absent</td><td style="background:#C0392B; color:white; text-align:center;">MISSING</td><td>Critical gap for 18–35 demographic</td></tr>
                        <tr><td>YouTube</td><td>Absent</td><td style="background:#C0392B; color:white; text-align:center;">MISSING</td><td>No long-form video library</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="critical-box">
                <strong>5.2 Performance Marketing — Score: 3.5/10 — CRITICAL GAP</strong><br>
                IEI has NO visible paid advertising infrastructure across any channel. Competitors are actively capturing high-intent patient search traffic.
            </div>
            <ul>
                <li>No Google Search Ads for 'LASIK Tirana', 'kirurgji syri'</li>
                <li>No Meta Lead Generation campaigns</li>
                <li>No Google Tag Manager or Meta Pixel detected</li>
                <li>No conversion-optimized landing pages for SMILE Pro</li>
            </ul>

            <!-- SECTION 6: STRATEGIC ROADMAP -->
            <div class="page-break"></div>
            <h1>6. STRATEGIC ROADMAP — 6 MONTHS</h1>
            <hr>

            <h2 style="background:#1A3A5C; color:white; padding:10px; text-align:center;">PHASE 1 — STABILIZE | Months 1–2</h2>
            <ul>
                <li>Launch 5-template GMB review response framework; sweep all 208 existing reviews within 14 days</li>
                <li>Implement post-consultation review request workflow via WhatsApp/SMS</li>
                <li>Fix .al site duplicate placeholder descriptions</li>
                <li>Deploy GA4, Meta Pixel, and Google Tag Manager on both websites</li>
                <li>Upload 30+ new categorized GMB photos: Visumax 800 equipment, team, clinic interior</li>
            </ul>

            <h2 style="background:#2E86C1; color:white; padding:10px; text-align:center; margin-top:30px;">PHASE 2 — OPTIMIZE | Months 3–4</h2>
            <ul>
                <li>Full on-page SEO rewrite for all 14 service pages — unique 800+ word content per page</li>
                <li>Add Schema.org MedicalClinic, Physician markup across both sites</li>
                <li>Build dedicated SMILE Pro & LASIK conversion landing pages</li>
                <li>Launch Google Search Ads campaigns for top-10 high-intent queries</li>
                <li>Begin TikTok content cadence: 3x per week</li>
            </ul>

            <h2 style="background:#1E8449; color:white; padding:10px; text-align:center; margin-top:30px;">PHASE 3 — SCALE | Months 5–6</h2>
            <ul>
                <li>Off-page link-building from Albanian medical directories and health portals</li>
                <li>Structured patient testimonial program — video-first</li>
                <li>Retargeting campaigns on Meta and Google Display</li>
                <li>Build cost-per-consultation attribution dashboard</li>
            </ul>

            <h2>6.1 Indicative Budget Allocation</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Category</th><th>Phase 1</th><th>Phase 2</th><th>Phase 3</th></tr></thead>
                    <tbody>
                        <tr><td>Google Ads</td><td>€0</td><td>€600–800/mo</td><td>€800–1,200/mo</td></tr>
                        <tr><td>Meta Ads</td><td>€0</td><td>€400–600/mo</td><td>€600–800/mo</td></tr>
                        <tr><td>Content Production</td><td>€200–300/mo</td><td>€400–500/mo</td><td>€400–500/mo</td></tr>
                        <tr style="background:#EBF5FB;"><td><strong>Total Monthly</strong></td><td><strong>€300–400/mo</strong></td><td><strong>€1,550–2,050/mo</strong></td><td><strong>€2,000–2,700/mo</strong></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- SECTION 7: KEY RECOMMENDATIONS -->
            <div class="page-break"></div>
            <h1>7. KEY RECOMMENDATIONS — PRIORITY MATRIX</h1>
            <hr>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Action Item</th><th>Priority</th><th>Effort</th><th>Expected Impact</th></tr></thead>
                    <tbody>
                        <tr><td>Respond to all 208 GMB reviews within 14 days</td><td style="background:#C0392B; color:white; text-align:center;">CRITICAL</td><td>Low</td><td>Immediate rating signal; GMB algorithm boost</td></tr>
                        <tr><td>Remove duplicate .al placeholder content (14 pages)</td><td style="background:#C0392B; color:white; text-align:center;">CRITICAL</td><td>Medium</td><td>SEO recovery within 60–90 days</td></tr>
                        <tr><td>Deploy GA4 + Meta Pixel + GTM</td><td style="background:#C0392B; color:white; text-align:center;">CRITICAL</td><td>Low</td><td>Enables all future conversion tracking</td></tr>
                        <tr><td>Launch post-visit review request workflow</td><td style="background:#D4830A; color:white; text-align:center;">High</td><td>Low</td><td>8–10 new reviews/month</td></tr>
                        <tr><td>Build dedicated SMILE Pro landing page</td><td style="background:#D4830A; color:white; text-align:center;">High</td><td>Medium</td><td>Capture high-value Visumax 800 traffic</td></tr>
                        <tr><td>Launch Google Search Ads for top 10 queries</td><td style="background:#D4830A; color:white; text-align:center;">High</td><td>Medium</td><td>First leads within 2 weeks</td></tr>
                        <tr><td>Full on-page SEO rewrite (14 service pages)</td><td style="background:#D4830A; color:white; text-align:center;">High</td><td>High</td><td>Organic ranking improvement over 90 days</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- CONCLUSION -->
            <div class="page-break"></div>
            <hr>
            <h2 style="text-align:center;">AUDIT CONCLUSION</h2>
            <p style="text-align:center; font-style:italic;">nishanttaneja.com • Indian Eye Institute & Laser Center, Tirana</p>
            <p>Dr. Nishant Taneja and the Indian Eye Institute possess the clinical credibility, media track record, and technological capability (Zeiss Visumax 800) to be the undisputed digital leader in Albanian ophthalmology. The gap is not in the product — it is in the translation of clinical excellence into digital signals that search engines and prospective patients can evaluate and trust.</p>
            <p>The 6-month roadmap outlined in this report, executed in sequence, is projected to raise IEI's Composite Digital Health Score from 5.4/10 to approximately 7.5–8.0/10, with measurable improvements in GMB ranking, organic traffic, and trackable appointment inquiries.</p>
            <p><strong>The single most impactful immediate action remains the GMB review management initiative — it is zero-cost, high-visibility, and directly affects the local pack ranking that drives the largest share of new patient calls.</strong></p>
            
            <hr style="margin: 40px 0;">
            <p style="text-align:center; color:#666;">— End of Report —</p>
            <p style="text-align:center; color:#666; font-size:12px;">Prepared May 2026 • nishanttaneja.com Website Audit • Confidential</p>
        </div>
    </div>

    <script>
        // Keyboard shortcut for Ctrl+P is natively supported by browsers
        // This just adds an additional console log for confirmation
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                console.log('Print dialog triggered (Ctrl+P)');
                // The browser will handle the print dialog natively
            }
        });
        
        // Optional: Add print optimization hint
        window.onbeforeprint = function() {
            console.log('Preparing document for printing...');
        };
        
        window.onafterprint = function() {
            console.log('Print dialog closed.');
        };
    </script>
</body>
</html>