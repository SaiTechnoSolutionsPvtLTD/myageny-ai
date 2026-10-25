<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Quotation {{ $quotation->quotation_no }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px 14px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f4f5f7;
            color: #111827;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            width: min(620px, 100%);
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #15803d, #16a34a);
            color: #ffffff;
            padding: 24px 26px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 6px;
            font-size: 22px;
            font-weight: 800;
        }
        .header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.92;
        }
        .body {
            padding: 26px;
        }
        .quote-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 22px;
        }
        .quote-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            font-size: 13px;
        }
        .quote-label {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .quote-val {
            font-weight: 700;
            color: #111827;
        }
        .quote-total {
            grid-column: span 2;
            padding-top: 10px;
            border-top: 1px dashed #d1d5db;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 15px;
        }
        .quote-total .amt {
            font-size: 20px;
            color: #15803d;
            font-weight: 800;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .info-pill-box {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }
        .info-pill-box .icon {
            font-size: 18px;
            flex-shrink: 0;
        }
        .info-pill-box .text {
            color: #374151;
            font-weight: 600;
        }
        .info-pill-box .sub {
            color: #6b7280;
            font-size: 11px;
            margin-top: 2px;
        }
        /* Signature Canvas */
        .signature-container {
            margin-bottom: 20px;
        }
        .canvas-wrap {
            position: relative;
            background: #ffffff;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            overflow: hidden;
            touch-action: none;
            transition: border-color .15s ease;
        }
        .canvas-wrap.active {
            border-color: #16a34a;
        }
        canvas {
            display: block;
            width: 100%;
            height: 180px;
            cursor: crosshair;
        }
        .canvas-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            padding: 0 4px;
        }
        .canvas-hint {
            font-size: 12px;
            color: #6b7280;
        }
        .btn-clear {
            background: none;
            border: 1px solid #d1d5db;
            color: #4b5563;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all .15s ease;
        }
        .btn-clear:hover {
            background: #f3f4f6;
            color: #111827;
        }
        /* Terms Checkbox */
        .checkbox-wrap {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
            color: #374151;
            margin-bottom: 22px;
            line-height: 1.5;
            cursor: pointer;
        }
        .checkbox-wrap input {
            width: 17px;
            height: 17px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: #16a34a;
        }
        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 14px 20px;
            background: #16a34a;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.28);
            transition: background .15s ease, transform .1s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover {
            background: #15803d;
        }
        .btn-submit:active {
            transform: scale(0.99);
        }
        .btn-submit:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            box-shadow: none;
        }
        .security-badge {
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Approve Quotation</h1>
            <p>Please review details and provide your digital signature to confirm acceptance.</p>
        </div>

        <div class="body">
            <!-- Quotation Summary -->
            <div class="quote-card">
                <div class="quote-grid">
                    <div>
                        <div class="quote-label">Quotation No</div>
                        <div class="quote-val">{{ $quotation->quotation_no }}</div>
                    </div>
                    <div>
                        <div class="quote-label">Customer</div>
                        <div class="quote-val">{{ $lead->contact_name ?: 'Customer' }}</div>
                    </div>
                    <div>
                        <div class="quote-label">Date</div>
                        <div class="quote-val">{{ $quotation->quotation_date?->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div class="quote-label">Valid Until</div>
                        <div class="quote-val">{{ $quotation->valid_until?->format('d M Y') }}</div>
                    </div>
                    <div class="quote-total">
                        <span style="font-weight:600; color:#374151;">Total Quotation Amount</span>
                        <span class="amt">₹{{ number_format((float) $quotation->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <form action="{{ $submitUrl }}" method="POST" id="approvalForm">
                @csrf
                <input type="hidden" name="signature_data" id="signatureData">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="location" id="location">

                <!-- 1. Auto-fetched Date & Time -->
                <div class="section-title">
                    <span>📅</span>
                    <span>Approval Date & Time</span>
                </div>
                <div class="info-pill-box">
                    <span class="icon">🕒</span>
                    <div>
                        <div class="text" id="liveDateTime">Loading date and time...</div>
                        <div class="sub">Auto-recorded timestamp of acceptance</div>
                    </div>
                </div>

                <!-- 2. Auto-fetched Customer Location -->
                <div class="section-title">
                    <span>📍</span>
                    <span>Customer Location</span>
                </div>
                <div class="info-pill-box">
                    <span class="icon" id="locIcon">📡</span>
                    <div>
                        <div class="text" id="locationStatus">Detecting your location...</div>
                        <div class="sub" id="locationSub">Coordinates and location details are automatically captured</div>
                    </div>
                </div>

                <!-- 3. Digital Signature Pad -->
                <div class="section-title">
                    <span>✍️</span>
                    <span>Customer Digital Signature <span style="color:#ef4444;">*</span></span>
                </div>
                <div class="signature-container">
                    <div class="canvas-wrap" id="canvasWrap">
                        <canvas id="signaturePad"></canvas>
                    </div>
                    <div class="canvas-footer">
                        <span class="canvas-hint">Draw your signature in the box above using mouse or touch</span>
                        <button type="button" class="btn-clear" id="btnClearSignature">Clear Signature</button>
                    </div>
                </div>

                <!-- 4. Confirmation Agreement -->
                <label class="checkbox-wrap">
                    <input type="checkbox" id="chkAgree" required>
                    <span>
                        I confirm that I have reviewed Quotation <strong>#{{ $quotation->quotation_no }}</strong>
                        and hereby approve the quotation and terms for <strong>₹{{ number_format((float) $quotation->total_amount, 2) }}</strong>.
                    </span>
                </label>

                <!-- 5. Submit Button -->
                <button type="submit" class="btn-submit" id="btnSubmit">
                    <span>✔</span> Confirm & Approve Quotation
                </button>

                <div class="security-badge">
                    <span>🔒</span> Verified secure acceptance with digital signature, location and timestamp
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        // --- 1. Auto Date & Time ---
        function updateDateTime() {
            var now = new Date();
            var options = {
                weekday: 'long',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            var formatted = now.toLocaleDateString('en-IN', options);
            var dtEl = document.getElementById('liveDateTime');
            if (dtEl) {
                dtEl.textContent = formatted;
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // --- 2. Auto Geolocation ---
        var locStatusEl = document.getElementById('locationStatus');
        var locSubEl = document.getElementById('locationSub');
        var locIconEl = document.getElementById('locIcon');
        var latInput = document.getElementById('latitude');
        var lonInput = document.getElementById('longitude');
        var locInput = document.getElementById('location');

        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    var lat = pos.coords.latitude.toFixed(6);
                    var lon = pos.coords.longitude.toFixed(6);
                    latInput.value = lat;
                    lonInput.value = lon;
                    locIconEl.textContent = '📍';
                    locStatusEl.textContent = lat + ', ' + lon;
                    locSubEl.textContent = 'Coordinates acquired. Resolving address...';

                    // Reverse geocoding via OpenStreetMap free API
                    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lon, {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.display_name) {
                            var addr = data.address || {};
                            var city = addr.city || addr.town || addr.village || addr.county || '';
                            var state = addr.state || '';
                            var country = addr.country || '';
                            var shortLoc = [city, state, country].filter(Boolean).join(', ');
                            var finalLoc = shortLoc || data.display_name;

                            locStatusEl.textContent = finalLoc;
                            locSubEl.textContent = 'Coordinates: ' + lat + ', ' + lon;
                            locInput.value = finalLoc;
                        } else {
                            locInput.value = lat + ', ' + lon;
                            locSubEl.textContent = 'GPS location captured';
                        }
                    })
                    .catch(function () {
                        locInput.value = lat + ', ' + lon;
                        locSubEl.textContent = 'GPS coordinates captured';
                    });
                },
                function (err) {
                    locIconEl.textContent = '🌐';
                    locStatusEl.textContent = 'Network Location Active';
                    locSubEl.textContent = 'Browser location permission denied. Client IP location will be recorded.';
                    locInput.value = 'Browser GPS not permitted (Client IP reference)';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            locIconEl.textContent = '🌐';
            locStatusEl.textContent = 'Device GPS Not Supported';
            locSubEl.textContent = 'Client IP address will be recorded upon submission.';
            locInput.value = 'GPS not supported (Client IP reference)';
        }

        // --- 3. Digital Signature Canvas ---
        var canvas = document.getElementById('signaturePad');
        var canvasWrap = document.getElementById('canvasWrap');
        var btnClear = document.getElementById('btnClearSignature');
        var ctx = canvas.getContext('2d');
        var isDrawing = false;
        var hasDrawn = false;
        var lastX = 0;
        var lastY = 0;

        function resizeCanvas() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            var rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * ratio;
            canvas.height = 180 * ratio;
            ctx.scale(ratio, ratio);
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            drawGuidelines();
        }

        function drawGuidelines() {
            var rect = canvas.getBoundingClientRect();
            ctx.save();
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1;
            ctx.setLineDash([4, 4]);
            ctx.beginPath();
            ctx.moveTo(20, 140);
            ctx.lineTo(rect.width - 20, 140);
            ctx.stroke();
            ctx.restore();
        }

        resizeCanvas();
        window.addEventListener('resize', function () {
            if (!hasDrawn) {
                resizeCanvas();
            }
        });

        function getPos(e) {
            var rect = canvas.getBoundingClientRect();
            var clientX = e.clientX;
            var clientY = e.clientY;
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            }
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startDrawing(e) {
            isDrawing = true;
            canvasWrap.classList.add('active');
            var pos = getPos(e);
            lastX = pos.x;
            lastY = pos.y;
            e.preventDefault();
        }

        function draw(e) {
            if (!isDrawing) return;
            var pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            lastX = pos.x;
            lastY = pos.y;
            hasDrawn = true;
            e.preventDefault();
        }

        function stopDrawing() {
            if (isDrawing) {
                isDrawing = false;
                canvasWrap.classList.remove('active');
            }
        }

        // Mouse events
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDrawing);

        // Touch events
        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        window.addEventListener('touchend', stopDrawing);

        // Clear signature
        btnClear.addEventListener('click', function () {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            ctx.clearRect(0, 0, canvas.width / ratio, canvas.height / ratio);
            drawGuidelines();
            hasDrawn = false;
            document.getElementById('signatureData').value = '';
        });

        // --- 4. Form Validation on Submit ---
        var form = document.getElementById('approvalForm');
        var btnSubmit = document.getElementById('btnSubmit');

        form.addEventListener('submit', function (e) {
            if (!hasDrawn) {
                e.preventDefault();
                alert('Please draw your signature in the signature box before submitting.');
                canvasWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            var chk = document.getElementById('chkAgree');
            if (!chk.checked) {
                e.preventDefault();
                alert('Please check the confirmation checkbox to agree to the quotation.');
                chk.focus();
                return false;
            }

            // Export canvas to base64 PNG
            var signatureBase64 = canvas.toDataURL('image/png');
            document.getElementById('signatureData').value = signatureBase64;

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = 'Submitting Approval...';
            return true;
        });
    })();
    </script>
</body>
</html>
