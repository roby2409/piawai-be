<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kebijakan Privasi – Piawai</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <style>
        :root {
            --primary: #2563EB;
            --primary-light: #EFF6FF;
            --accent: #0EA5E9;
            --dark: #0F172A;
            --muted: #64748B;
            --border: #E2E8F0;
            --success: #10B981;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F8FAFC;
            color: var(--dark);
            line-height: 1.7;
        }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, #1E40AF 0%, #0EA5E9 100%);
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

        .hero-meta {
            margin-top: 28px;
            font-size: 13px;
            opacity: 0.7;
        }

        /* Main content */
        .content-wrap {
            max-width: 780px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Cards */
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
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
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

        .icon-blue {
            background: #EFF6FF;
        }

        .icon-green {
            background: #ECFDF5;
        }

        .icon-orange {
            background: #FFF7ED;
        }

        .icon-red {
            background: #FEF2F2;
        }

        .icon-purple {
            background: #F5F3FF;
        }

        .icon-teal {
            background: #F0FDFA;
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

        .data-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 8px;
            padding: 5px 12px;
            font-size: 13px;
            font-weight: 600;
            margin: 4px 4px 4px 0;
        }

        .check-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .check-list li {
            display: flex;
            gap: 10px;
            padding: 6px 0;
            border-bottom: 1px solid #F1F5F9;
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

        .alert-soft {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 0.9rem;
            color: #92400E;
        }

        /* Contact box */
        .contact-box {
            background: linear-gradient(135deg, #1E40AF, #0EA5E9);
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
            color: var(--primary);
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

        /* Footer */
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
            <div class="hero-badge">📋 Kebijakan Privasi</div>
            <h1>Privasi Kamu,<br />Prioritas Kami</h1>
            <p>Piawai berkomitmen untuk melindungi data pribadi kamu. Dokumen ini menjelaskan data apa yang kami
                kumpulkan dan bagaimana kami menggunakannya.</p>
            <div class="hero-meta">Berlaku sejak: 26 Mei 2026 &nbsp;·&nbsp; Versi 1.0</div>
        </div>
    </div>

    <div style="background:#F8FAFC; padding: 48px 0 0;">
        <div class="content-wrap">

            <!-- 1. Intro -->
            <div class="policy-card">
                <div class="card-icon icon-blue">👋</div>
                <h2>Tentang Piawai</h2>
                <p>Piawai adalah platform yang mempertemukan pengguna dengan penyedia layanan (worker) di sekitar
                    mereka berdasarkan lokasi. Aplikasi ini <strong>tidak memiliki fitur chat, transaksi, atau sistem
                        pembayaran</strong>. Komunikasi antara pengguna dilakukan secara langsung melalui WhatsApp.</p>
            </div>

            <!-- 2. Data yang dikumpulkan -->
            <div class="policy-card">
                <div class="card-icon icon-green">📦</div>
                <h2>Data yang Kami Kumpulkan</h2>
                <p>Kami hanya mengumpulkan data yang diperlukan untuk menjalankan fitur aplikasi:</p>
                <div style="margin: 16px 0;">
                    <span class="data-tag">👤 Nama lengkap</span>
                    <span class="data-tag">📧 Alamat email</span>
                    <span class="data-tag">📱 Nomor WhatsApp</span>
                    <span class="data-tag">📍 Lokasi perkiraan</span>
                    <span class="data-tag">🖼️ Foto profil</span>
                    <span class="data-tag">🔧 Jenis layanan</span>
                </div>
                <div class="alert-soft">
                    ⚠️ Nomor WhatsApp hanya digunakan agar pengguna lain dapat menghubungi kamu secara langsung. Kami
                    tidak menyimpan atau memproses percakapan WhatsApp.
                </div>
            </div>

            <!-- 3. Cara penggunaan -->
            <div class="policy-card">
                <div class="card-icon icon-purple">🎯</div>
                <h2>Bagaimana Data Digunakan</h2>
                <ul class="check-list">
                    <li>Menampilkan profil kamu kepada pengguna lain di sekitar lokasi kamu</li>
                    <li>Fitur pencarian dan filter worker berdasarkan jarak, gender, usia, dan jenis layanan</li>
                    <li>Autentikasi akun (login via email/password atau Google Sign-In)</li>
                    <li>Mengelola akun dan profil kamu di dalam aplikasi</li>
                    <li>Menampilkan nomor WhatsApp agar pengguna bisa menghubungi worker secara langsung</li>
                </ul>
            </div>

            <!-- 4. Berbagi data -->
            <div class="policy-card">
                <div class="card-icon icon-orange">🔒</div>
                <h2>Berbagi Data ke Pihak Ketiga</h2>
                <p>Kami <strong>tidak menjual atau membagikan</strong> data kamu ke pihak ketiga untuk keperluan iklan
                    atau komersial. Satu-satunya pihak ketiga yang terlibat adalah:</p>
                <ul class="check-list" style="margin-top:12px;">
                    <li><strong>Google Sign-In (OAuth)</strong> — digunakan untuk verifikasi identitas saat login dengan
                        akun Google. Token diverifikasi ke server Google dan tidak disimpan.</li>
                </ul>
            </div>

            <!-- 5. Keamanan -->
            <div class="policy-card">
                <div class="card-icon icon-teal">🛡️</div>
                <h2>Keamanan Data</h2>
                <ul class="check-list">
                    <li>Semua komunikasi antara aplikasi dan server dienkripsi menggunakan HTTPS/TLS</li>
                    <li>Password disimpan dalam bentuk hash (bcrypt), bukan teks biasa</li>
                    <li>Token autentikasi memiliki masa berlaku dan dapat dicabut saat logout</li>
                    <li>Data lokasi hanya digunakan saat fitur explore aktif</li>
                </ul>
            </div>

            <!-- 6. Hak pengguna -->
            <div class="policy-card">
                <div class="card-icon icon-red">⚖️</div>
                <h2>Hak Kamu sebagai Pengguna</h2>
                <ul class="check-list">
                    <li>Mengakses dan memperbarui data profil kapan saja melalui aplikasi</li>
                    <li>Menonaktifkan visibilitas pekerja pada tab siap bantu di section status</li>
                    <li>Meminta penghapusan akun dan seluruh data terkait</li>
                </ul>
                <p style="margin-top:14px;">Untuk menghapus akun, klik link hapus: <a
                        href="https://radarapp.jokifigma.cloud/delete-account"
                        style="color:var(--primary);font-weight:600;">disini</a>
                </p>
            </div>

            <!-- 7. Retensi -->
            <div class="policy-card">
                <div class="card-icon icon-blue">🗓️</div>
                <h2>Retensi Data</h2>
                <p>Data kamu disimpan selama akun aktif. Jika kamu meminta penghapusan akun, seluruh data termasuk
                    profil, lokasi, dan layanan akan dihapus permanen dalam <strong>7 hari kerja</strong>.</p>
            </div>

            <!-- Kontak -->
            <div class="contact-box">
                <h2>Ada Pertanyaan?</h2>
                <p>Hubungi kami jika ada pertanyaan seputar privasi data kamu.</p>
                <a href="mailto:royalinfinitygroup8@gmail.com">📧 royalinfinitygroup8@gmail.com</a>
            </div>

        </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> <strong>Piawai App</strong> · Semua hak dilindungi
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>

</html>