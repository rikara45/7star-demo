<?php

namespace App\Http\Controllers;

use App\Models\AssessmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Hanya aktif saat DEMO_MODE=true (config app.demo_mode).
 * Tujuannya: pengunjung bisa mencoba tiap peran tanpa registrasi/password,
 * karena aplikasi aslinya butuh beberapa role untuk menjalankan satu workflow.
 */
class DemoController extends Controller
{
    private const ROLES = ['admin', 'jurusan', 'dosen'];

    public function index()
    {
        abort_unless(config('app.demo_mode'), 404);

        $users = User::whereIn('role', self::ROLES)->orderBy('role')->orderBy('id')->get();
        $pendingRequest = AssessmentRequest::where('is_completed', false)->first();

        return view('demo.index', [
            'users' => $users,
            'pendingRequest' => $pendingRequest,
        ]);
    }

    public function login(string $role)
    {
        abort_unless(config('app.demo_mode'), 404);
        abort_unless(in_array($role, self::ROLES, true), 404);

        $user = User::where('role', $role)->orderBy('id')->firstOrFail();
        Auth::login($user);
        request()->session()->regenerate();

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'jurusan' => redirect()->route('jurusan.dashboard'),
            default => redirect()->route('dashboard.index'),
        };
    }
}
