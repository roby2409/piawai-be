<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hapus Akun – Piawai App</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #2563EB;
            --primary-light: #EFF6FF;
            --dark: #0F172A;
            --border: #E2E8F0;
            --danger: #EF4444;
            --danger-light: #FEF2F2;
            --success: #10B981;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F8FAFC;
            color: var(--dark);
            line-height: 1.7;
        }

        .hero {
            background: linear-gradient(135deg, #7F1D1D 0%, #EF4444 100%);
            color: white;
            padding: 64px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -40px;
            width: 220px;
            height: 220px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 99px;
            padding: 4px 16px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            backdrop-filter: blur(8px);
        }

        .hero h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800;
            margin-bottom: 12px;
        }

        .hero p {
            font-size: 1.05rem;
            opacity: 0.85;
            max-width: 520px;
        }

        .content-wrap {
            max-width: 780px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .policy-card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border);
            padding: 32px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.2s;
        }

        .policy-card:hover {
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.08);
        }

        .card-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }

        .icon-red {
            background: #FEF2F2;
        }

        .icon-orange {
            background: #FFF7ED;
        }

        .icon-blue {
            background: #EFF6FF;
        }

        .policy-card h2 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 14px;
            color: var(--dark);
        }

        .policy-card p,
        .policy-card li {
            color: #475569;
            font-size: 0.95rem;
        }

        .check-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .check-list li {
            display: flex;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #F1F5F9;
            color: #475569;
            font-size: 0.95rem;
        }

        .check-list li:last-child {
            border-bottom: none;
        }

        .check-list li::before {
            content: '✓';
            color: var(--success);
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .step-list {
            list-style: none;
            padding: 0;
            margin: 0;
            counter-reset: steps;
        }

        .step-list li {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 10px 0;
            border-bottom: 1px solid #F1F5F9;
            font-size: 0.95rem;
            color: #475569;
            counter-increment: steps;
        }

        .step-list li:last-child {
            border-bottom: none;
        }

        .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--danger-light);
            color: var(--danger);
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .alert-soft {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 0.9rem;
            color: #92400E;
        }

        .email-box {
            background: var(--danger-light);
            border: 1.5px dashed #FCA5A5;
            border-radius: 12px;
            padding: 20px 24px;
            margin: 16px 0;
        }

        .email-box .label {
            font-size: 12px;
            font-weight: 600;
            color: #B91C1C;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .email-box .value {
            font-size: 1rem;
            font-weight: 700;
            color: var(--danger);
        }

        .email-box .subject {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #FCA5A5;
        }

        .contact-box {
            background: linear-gradient(135deg, #7F1D1D, #EF4444);
            border-radius: 16px;
            padding: 36px 32px;
            color: white;
            text-align: center;
            margin-bottom: 48px;
        }

        .contact-box h2 {
            font-weight: 800;
            margin-bottom: 8px;
        }

        .contact-box p {
            opacity: 0.85;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .contact-box a {
            display: inline-block;
            background: white;
            color: var(--danger);
            font-weight: 700;
            border-radius: 10px;
            padding: 10px 28px;
            text-decoration: none;
            font-size: 0.95rem;
            transition: opacity 0.2s;
        }

        .contact-box a:hover {
            opacity: 0.9;
        }

        footer {
            background: var(--dark);
            color: #94A3B8;
            text-align: center;
            padding: 24px 16px;
            font-size: 13px;
        }

        footer strong {
            color: white;
        }
    </style>
</head>

<body>

    <!-- Hero -->
    <div class="hero">
        <div class="content-wrap">
            <div class="hero-badge">🗑️ Hapus Akun</div>
            <h1>Hapus Akun<br />Piawai App</h1>
            <p>Kamu dapat meminta penghapusan akun dan seluruh data terkait kapan saja. Prosesnya mudah dan transparan.</p>
        </div>
    </div>

    <div style="background:#F8FAFC; padding: 48px 0 0;">
        <div class="content-wrap">

            <!-- Data yang dihapus -->
            <div class="policy-card">
                <div class="card-icon icon-red">🗂️</div>
                <h2>Data yang Akan Dihapus</h2>
                <p>Ketika akun dihapus, semua data berikut akan dihapus permanen:</p>
                <ul class="check-list" style="margin-top:12px;">
                    <li>Profil (nama, foto, bio, nomor WhatsApp, lokasi)</li>
                    <li>Daftar layanan yang kamu tawarkan</li>
                    <li>Data akun (email, password, token autentikasi)</li>
                    <li>Seluruh data terkait lainnya di sistem kami</li>
                </ul>
                <div class="alert-soft" style="margin-top:16px;">
                    ⚠️ Penghapusan akun bersifat <strong>permanen dan tidak dapat dibatalkan</strong>. Pastikan kamu sudah yakin sebelum mengajukan permintaan.
                </div>
                <div class="alert-soft" style="margin-top:10px; background:#FEF2F2; border-color:#FCA5A5; color:#B91C1C;">
                    🔒 Kirim permintaan <strong>dari email yang sama</strong> dengan yang terdaftar di akun kamu. Permintaan dari email berbeda tidak akan diproses.
                </div>
            </div>

            <!-- Cara hapus -->
            <div class="policy-card">
                <div class="card-icon icon-orange">📧</div>
                <h2>Cara Mengajukan Penghapusan Akun</h2>
                <p>Kirim email ke alamat berikut dengan informasi yang diperlukan:</p>

                <div class="email-box">
                    <div class="label">Kirim email ke</div>
                    <div class="value">royalinfinitygroup8@gmail.com</div>
                    <div class="subject">
                        <div class="label">Subject email</div>
                        <div class="value" style="font-size:0.9rem;">"Hapus Akun Piawai App"</div>
                    </div>
                </div>

                <p style="margin-top:16px; margin-bottom:12px;">Sertakan informasi berikut di dalam email:</p>
                <ol class="step-list">
                    <li><span class="step-num">1</span> <span><strong>Username</strong> akun kamu di Piawai App</span></li>
                    <li><span class="step-num">2</span> <span><strong>Email</strong> yang terdaftar di akun kamu</span></li>
                    <li><span class="step-num">3</span> <span>Kirim dari <strong>email yang sama</strong> dengan yang terdaftar di akun kamu</span></li>
                </ol>
            </div>

            <!-- Timeline -->
            <div class="policy-card">
                <div class="card-icon icon-blue">⏱️</div>
                <h2>Proses & Timeline</h2>
                <ul class="check-list">
                    <li>Permintaan akan diproses dalam <strong>7 hari kerja</strong></li>
                    <li>Kamu akan mendapat konfirmasi via email setelah akun dihapus</li>
                    <li>Seluruh data dihapus permanen dari sistem kami</li>
                </ul>
            </div>

            <!-- CTA -->
            <div class="contact-box">
                <h2>Siap Menghapus Akun?</h2>
                <p>Kirim permintaan penghapusan akun kamu sekarang.</p>
                <a href="mailto:royalinfinitygroup8@gmail.com?subject=Hapus%20Akun%20Piawai%20App">🗑️ Kirim Permintaan</a>
            </div>

        </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> <strong>Piawai App</strong> · Semua hak dilindungi
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>

</html>