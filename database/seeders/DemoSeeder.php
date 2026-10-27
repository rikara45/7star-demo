<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh untuk demo publik.
 *
 * Aturan yang dipegang: semua nama/instansi di sini fiktif dan ditandai sebagai
 * data contoh (bukan data klien). Skor dihitung dengan rumus yang sama seperti
 * UserDashboardController::calculateRealtimeScores(), jadi angka yang tampil di
 * halaman hasil konsisten dengan isi tabel penilaian.
 */
class DemoSeeder extends Seeder
{
    private const DIMENSION_SELF = 4;
    private const DIMENSION_SUPERIOR = 5;
    private const DIMENSION_PEER = 4;

    public function run(): void
    {
        $this->truncate();

        $now = now();

        DB::table('university_settings')->insert([
            'uni_name' => 'Universitas Cakrawala Nusantara (contoh)',
            'uni_address' => 'Jl. Contoh Raya No. 7, Bandung',
            'uni_website' => 'https://7star.rioeka.com',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $periodId = DB::table('assessment_periods')->insertGetId([
            'name' => 'Periode Penilaian 2026',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Pertanyaan 7 dimensi (dipakai juga oleh aplikasi aslinya).
        $this->call(QuestionSeeder::class);

        $users = $this->users($now);
        $dosen1 = $users['dosen1'];
        $dosen2 = $users['dosen2'];

        // --- Portofolio: dosen1 sudah dinilai, dosen2 masih menunggu verifikasi ---
        $this->portfolios($dosen1, $dosen2, $now);

        // --- Penilaian 360: dosen1 lengkap (self, atasan, sejawat) ---
        foreach (['self', 'superior', 'peer'] as $relationship) {
            $requestId = $this->request($dosen1, $periodId, $relationship, true, $now);
            $this->responses($requestId, $relationship, $now);
        }

        // --- dosen2: satu tautan penilai yang sengaja dibiarkan belum diisi ---
        $this->request($dosen2, $periodId, 'superior', false, $now, 'demo-penilai-dosen2-superior-7star');

        // --- Hasil 7 bintang untuk dosen1, dihitung dari data di atas ---
        $this->analysis($dosen1, $periodId, $now);
    }

    private function truncate(): void
    {
        // Urutan penting: anak dulu, induk belakangan (FK).
        foreach ([
            'assessment_responses',
            'assessment_requests',
            'ai_analyses',
            'portfolios',
            'master_questions',
            'assessment_periods',
            'university_settings',
            'users',
        ] as $table) {
            DB::table($table)->delete();
        }
    }

    /**
     * @return array<string,int>
     */
    private function users($now): array
    {
        $rows = [
            'admin' => ['Rina Kusuma', 'admin@demo.7star.test', 'admin', 'Administrator Sistem', 'Pusat Teknologi Informasi'],
            'jurusan' => ['Dr. Bagas Prasetyo', 'jurusan@demo.7star.test', 'jurusan', 'Ketua Jurusan', 'Fakultas Teknik'],
            'dosen1' => ['Dr. Ayu Lestari', 'dosen@demo.7star.test', 'dosen', 'Lektor Kepala', 'Teknik Informatika'],
            'dosen2' => ['Rangga Wijaya, M.Kom.', 'dosen2@demo.7star.test', 'dosen', 'Lektor', 'Sistem Informasi'],
            'dosen3' => ['Sinta Maharani, M.T.', 'dosen3@demo.7star.test', 'dosen', 'Asisten Ahli', 'Teknik Elektro'],
        ];

        $ids = [];
        foreach ($rows as $key => [$name, $email, $role, $jabatan, $unit]) {
            $ids[$key] = DB::table('users')->insertGetId([
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'jabatan' => $jabatan,
                'unit_kerja' => $unit,
                'email_verified_at' => $now,
                'password' => Hash::make('password'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $ids;
    }

    private function portfolios(int $dosen1, int $dosen2, $now): void
    {
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 0/Kids[]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

        // 9 kategori dengan skor total 90 (portofolio = min(sum,100)).
        $dinilai = [
            'Pendidikan & Sertifikasi' => 10,
            'Pengalaman Jabatan' => 10,
            'Kinerja Akademik' => 10,
            'Riset & Kolaborasi' => 10,
            'Inovasi Institusional' => 10,
            'Empowerment & Mentoring' => 10,
            'Integritas & Teladan' => 10,
            'Kapasitas Digital' => 10,
            'Dampak Eksternal' => 10,
        ];

        foreach ($dinilai as $category => $score) {
            $slug = str($category)->slug();
            $path = "portfolios/demo-{$slug}.pdf";
            Storage::disk('public')->put($path, $pdf);

            DB::table('portfolios')->insert([
                'user_id' => $dosen1,
                'category' => $category,
                'file_path' => $path,
                'description' => "Berkas contoh untuk kategori {$category}.",
                'score' => $score,
                'status' => 'verified',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // dosen2: dua berkas baru diunggah, belum diverifikasi jurusan.
        foreach (['Pendidikan & Sertifikasi', 'Kapasitas Digital'] as $category) {
            $slug = str($category)->slug();
            $path = "portfolios/demo-dosen2-{$slug}.pdf";
            Storage::disk('public')->put($path, $pdf);

            DB::table('portfolios')->insert([
                'user_id' => $dosen2,
                'category' => $category,
                'file_path' => $path,
                'description' => "Berkas contoh dosen2 untuk kategori {$category}.",
                'score' => 0,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function request(int $userId, int $periodId, string $relationship, bool $completed, $now, ?string $token = null): int
    {
        $penilai = [
            'self' => ['Dr. Ayu Lestari', 'dosen@demo.7star.test'],
            'superior' => ['Dr. Bagas Prasetyo', 'jurusan@demo.7star.test'],
            'peer' => ['Rangga Wijaya, M.Kom.', 'dosen2@demo.7star.test'],
        ][$relationship];

        return DB::table('assessment_requests')->insertGetId([
            'user_id' => $userId,
            'assessment_period_id' => $periodId,
            'assessor_name' => $penilai[0],
            'assessor_email' => $penilai[1],
            'relationship' => $relationship,
            'access_token' => $token ?? bin2hex(random_bytes(16)),
            'is_completed' => $completed,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function responses(int $requestId, string $relationship, $now): void
    {
        $base = match ($relationship) {
            'superior' => self::DIMENSION_SUPERIOR,
            'peer' => self::DIMENSION_PEER,
            default => self::DIMENSION_SELF,
        };

        foreach (DB::table('master_questions')->pluck('id') as $index => $questionId) {
            // Variasi kecil supaya tiap dimensi tidak bernilai sama persis.
            $score = max(1, min(5, $base + (($index % 3) - 1)));

            DB::table('assessment_responses')->insert([
                'assessment_request_id' => $requestId,
                'master_question_id' => $questionId,
                'score' => $score,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function analysis(int $userId, int $periodId, $now): void
    {
        $raw = DB::table('assessment_responses')
            ->join('assessment_requests', 'assessment_responses.assessment_request_id', '=', 'assessment_requests.id')
            ->join('master_questions', 'assessment_responses.master_question_id', '=', 'master_questions.id')
            ->where('assessment_requests.user_id', $userId)
            ->where('assessment_requests.assessment_period_id', $periodId)
            ->where('assessment_requests.is_completed', true)
            ->select('master_questions.dimension', DB::raw('AVG(assessment_responses.score) as avg_score'))
            ->groupBy('master_questions.dimension')
            ->get();

        $dimensionAverages = $raw->pluck('avg_score', 'dimension');
        $behavior = $dimensionAverages->isNotEmpty() ? ($dimensionAverages->avg() / 5) * 100 : 0;

        $portfolio = min((float) DB::table('portfolios')->where('user_id', $userId)->sum('score'), 100);
        $final = round(($behavior * 0.70) + ($portfolio * 0.30), 2);

        $stars = match (true) {
            $final >= 91 => '7 Bintang',
            $final >= 86 => '6 Bintang',
            $final >= 80 => '5 Bintang',
            $final >= 72 => '4 Bintang',
            $final >= 65 => '3 Bintang',
            $final >= 55 => '2 Bintang',
            default => '1 Bintang',
        };

        $tertinggi = $dimensionAverages->sortDesc()->take(2);
        $terendah = $dimensionAverages->sort()->take(1);

        $missing = array_diff([
            'Pendidikan & Sertifikasi', 'Pengalaman Jabatan', 'Kinerja Akademik', 'Riset & Kolaborasi',
            'Inovasi Institusional', 'Empowerment & Mentoring', 'Integritas & Teladan',
            'Kapasitas Digital', 'Komunikasi Dua Arah', 'Dampak Eksternal',
        ], DB::table('portfolios')->where('user_id', $userId)->pluck('category')->all());

        $narrative = "Narasi contoh — di demo ini teks disimulasikan, bukan keluaran AI sungguhan.\n\n"
            . "1. Kekuatan. Skor perilaku 360 kamu {$this->num($behavior)} dari 100 dengan kekuatan utama pada dimensi "
            . $tertinggi->map(fn ($v, $k) => "{$k} (" . $this->num($v) . "/5)")->implode(' dan ')
            . ". Portofolio terkumpul {$this->num($portfolio)} poin dari 10 kategori.\n\n"
            . "2. Celah kinerja. Dimensi terendah: "
            . $terendah->map(fn ($v, $k) => "{$k} (" . $this->num($v) . "/5)")->implode(', ')
            . ($missing ? ". Kategori portofolio yang belum ada: " . implode(', ', $missing) . '.' : '.')
            . "\n\n"
            . "3. Rekomendasi. (a) Lengkapi kategori portofolio yang masih kosong dengan bukti satu semester terakhir. "
            . "(b) Minta satu penilaian sejawat tambahan pada dimensi terendah agar datanya lebih berimbang. "
            . "(c) Dokumentasikan dampak eksternal (kerja sama, pengabdian) supaya skor portofolio naik.";

        DB::table('ai_analyses')->insert([
            'user_id' => $userId,
            'assessment_period_id' => $periodId,
            'score_behavior' => $behavior,
            'score_portfolio' => $portfolio,
            'final_score' => $final,
            'star_rating' => $stars,
            'ai_narrative' => $narrative,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function num(float $value): string
    {
        return number_format($value, 2);
    }
}
