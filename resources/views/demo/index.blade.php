<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo 7Star — Penilaian Kepemimpinan Dosen</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; padding: 2.5rem 1.25rem; background: #f6f7fb; color: #16181d;
               font: 16px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        .wrap { max-width: 46rem; margin: 0 auto; }
        .badge { display: inline-block; background: #fff7e6; border: 1px solid #f0d9a8; color: #7a5a13;
                 border-radius: 999px; padding: 4px 12px; font-size: .78rem; letter-spacing: .04em; text-transform: uppercase; }
        h1 { font-size: clamp(1.5rem, 4vw, 2rem); margin: 1rem 0 .5rem; letter-spacing: -.02em; }
        p.lead { color: #4b5565; margin: 0 0 1.75rem; }
        .grid { display: grid; gap: .875rem; }
        @media (min-width: 40rem) { .grid { grid-template-columns: repeat(3, 1fr); } }
        a.card { display: block; text-decoration: none; color: inherit; background: #fff; border: 1px solid #e3e6ee;
                 border-radius: 12px; padding: 1rem; transition: border-color .15s, transform .15s; }
        a.card:hover { border-color: #9aa4ff; transform: translateY(-2px); }
        .role { font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; color: #6b7280; }
        .name { font-weight: 600; margin-top: .35rem; }
        .meta { font-size: .82rem; color: #6b7280; margin-top: .2rem; }
        .section { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e3e6ee; }
        h2 { font-size: 1rem; margin: 0 0 .6rem; }
        .link { display: inline-block; background: #16181d; color: #fff; text-decoration: none;
                border-radius: 8px; padding: .55rem .9rem; font-size: .9rem; }
        .note { font-size: .85rem; color: #6b7280; margin-top: .75rem; }
        code { background: #eef0f6; border-radius: 5px; padding: 1px 5px; font-size: .85em; }
    </style>
</head>
<body>
<div class="wrap">
    <span class="badge">Mode demo</span>
    <h1>7Star — penilaian kepemimpinan dosen</h1>
    <p class="lead">
        Aplikasi aslinya butuh tiga peran (admin, jurusan, dosen) plus penilai eksternal untuk
        menjalankan satu workflow. Di demo ini semua data sudah disiapkan, jadi kamu bisa masuk
        sebagai peran mana pun tanpa registrasi.
    </p>

    <div class="grid">
        @foreach ($users as $user)
            <a class="card" href="{{ route('demo.login', $user->role) }}">
                <div class="role">{{ $user->role }}</div>
                <div class="name">{{ $user->name }}</div>
                <div class="meta">{{ $user->jabatan ?? '—' }}</div>
                <div class="meta">{{ $user->unit_kerja ?? '' }}</div>
            </a>
        @endforeach
    </div>

    @if ($pendingRequest)
        <div class="section">
            <h2>Coba form penilai tanpa login</h2>
            <p class="note" style="margin-top:0">
                Sama seperti penilai sungguhan yang menerima tautan token lewat email/WhatsApp —
                {{ $pendingRequest->assessor_name }} menilai {{ $pendingRequest->user->name ?? 'dosen' }}
                ({{ $pendingRequest->relationship }}).
            </p>
            <a class="link" href="{{ route('assessment.show', $pendingRequest->access_token) }}">
                Buka form penilaian
            </a>
        </div>
    @endif

    <div class="section">
        <h2>Yang bisa dicoba</h2>
        <p class="note" style="margin-top:0">
            Dosen: unggah portofolio, undang penilai, lihat hasil 7 bintang + PDF.
            Jurusan: verifikasi portofolio dan beri skor.
            Admin: kelola pengguna dan pengaturan universitas.<br><br>
            Catatan jujur: narasi AI di demo ini <strong>disimulasikan</strong> — teks contoh yang sudah
            disiapkan, bukan panggilan ke Gemini. Data dosen/portofolio juga data contoh.
            Kode demo: <code>github.com/rikara45/7star-demo</code>
        </p>
    </div>
</div>
</body>
</html>
