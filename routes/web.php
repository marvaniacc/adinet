<?php

use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\LawyerPageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportDownloadController;
use App\Livewire\Auth\OtpLogin;
use App\Livewire\Client\AppointmentIndex;
use App\Livewire\Client\RequestCreate;
use App\Livewire\Client\RequestIndex;
use App\Livewire\Client\ReviewCreate as ClientReviewCreate;
use App\Livewire\Client\ReviewIndex as ClientReviewIndex;
use App\Livewire\Dashboard\Admin\AppointmentOversight;
use App\Livewire\Dashboard\Admin\CityManager;
use App\Livewire\Dashboard\Admin\ClientsIndex;
use App\Livewire\Dashboard\Admin\LawyersIndex;
use App\Livewire\Dashboard\Admin\LawyerVerification;
use App\Livewire\Dashboard\Admin\PaymentIndex;
use App\Livewire\Dashboard\Admin\ReportIndex;
use App\Livewire\Dashboard\Admin\RequestOversight;
use App\Livewire\Dashboard\Admin\ReviewModeration;
use App\Livewire\Dashboard\Admin\SpecialtyManager;
use App\Livewire\Dashboard\Lawyer\AppointmentIndex as LawyerAppointmentIndex;
use App\Livewire\Dashboard\Lawyer\AvailabilityIndex;
use App\Livewire\Dashboard\Lawyer\Home;
use App\Livewire\Dashboard\Lawyer\ProfileEdit;
use App\Livewire\Dashboard\Lawyer\RequestIndex as LawyerRequestIndex;
use App\Livewire\Dashboard\Lawyer\ReviewIndex as LawyerReviewIndex;
use App\Livewire\Dashboard\Lawyer\ServiceManager;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Messages\Chat;
use App\Livewire\Messages\Index;
use App\Livewire\Public\LawyerList;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Public marketplace
Route::get('/lawyers', LawyerList::class)->name('lawyers.index');
Route::get('/lawyers/{slug}', [LawyerPageController::class, 'show'])->name('lawyers.show');

Route::middleware('guest')->group(function () {
    // Route defaults inject the registration intent into the full-page
    // component's mount(); without them mount() would fall back to 'client'.
    Route::get('/login', OtpLogin::class)->name('login');
    Route::get('/register/lawyer', OtpLogin::class)
        ->defaults('intent', User::ROLE_LAWYER)
        ->name('lawyer.register');
});

Route::middleware('auth')->group(function () {
    // Client dashboard (clients only - admins/lawyers have their own).
    Route::get('/dashboard', Overview::class)
        ->middleware('role:'.User::ROLE_CLIENT)
        ->name('dashboard');

    // Client: consultation requests & appointments
    Route::middleware('role:'.User::ROLE_CLIENT)->group(function () {
        Route::get('/dashboard/requests', RequestIndex::class)->name('dashboard.requests');
        Route::get('/dashboard/appointments', AppointmentIndex::class)->name('dashboard.appointments');
        Route::get('/dashboard/profile', App\Livewire\Client\ProfileEdit::class)->name('dashboard.profile');

        // NOTE: client review routes exist below; profile nav placeholder stays.

        Route::get('/dashboard/messages', Index::class)->name('messages.index');
        Route::get('/dashboard/messages/{conversationId}', Chat::class)->name('messages.show');

        Route::get('/reviews/create/{requestId}', ClientReviewCreate::class)->name('reviews.create');
        Route::get('/dashboard/reviews', ClientReviewIndex::class)->name('reviews.index');

        Route::get('/lawyers/{slug}/request', RequestCreate::class)->name('lawyers.request.create');
    });

    // Private document downloads (policy-checked, streamed; never public URLs)
    Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'download'])
        ->middleware('auth')
        ->name('documents.download');

    // Lawyer dashboard home
    Route::get('/dashboard/lawyer', Home::class)
        ->middleware('role:'.User::ROLE_LAWYER)
        ->name('dashboard.lawyer.index');

    // Admin dashboard home
    Route::get('/admin', App\Livewire\Dashboard\Admin\Overview::class)
        ->middleware('role:'.User::ROLE_ADMIN)
        ->name('admin.dashboard');

    // Lawyer dashboard
    Route::middleware('role:'.User::ROLE_LAWYER)->prefix('dashboard/lawyer')->name('dashboard.lawyer.')->group(function () {
        Route::get('/profile', ProfileEdit::class)->name('profile');
        Route::get('/services', ServiceManager::class)->name('services');
        Route::get('/requests', LawyerRequestIndex::class)->name('requests');
        Route::get('/appointments', LawyerAppointmentIndex::class)->name('appointments');
        Route::get('/messages', Index::class)->name('messages.index');
        Route::get('/messages/{conversationId}', Chat::class)->name('messages.show');
        Route::get('/reviews', LawyerReviewIndex::class)->name('reviews');
        Route::get('/availability', AvailabilityIndex::class)->name('availability');
    });

    // Admin dashboard
    Route::middleware('role:'.User::ROLE_ADMIN)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/lawyers/verification', LawyerVerification::class)->name('lawyers.verification');
        Route::get('/specialties', SpecialtyManager::class)->name('specialties');
        Route::get('/cities', CityManager::class)->name('cities');
        Route::get('/reviews', ReviewModeration::class)->name('reviews');
        Route::get('/lawyers', LawyersIndex::class)->name('lawyers.index');
        Route::get('/clients', ClientsIndex::class)->name('clients.index');
        Route::get('/consultation-requests', RequestOversight::class)->name('requests.index');
        Route::get('/appointments', AppointmentOversight::class)->name('appointments.index');
        Route::get('/payments', PaymentIndex::class)->name('payments');
        Route::get('/reports', ReportIndex::class)->name('reports.index');
        Route::get('/reports/{report}/download', [ReportDownloadController::class, 'download'])
            ->name('reports.download');
    });

    Route::post('/logout', function () {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('home');
    })->name('logout');
});

// Payments (ZarinPal) - callback is public: identity binds to the secret authority token.
Route::get('/payments/{appointment}/start', [PaymentController::class, 'start'])
    ->middleware(['auth', 'role:'.User::ROLE_CLIENT])->name('payments.start');
Route::get('/payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');
Route::get('/dev/payments/simulate/{authority}', [PaymentController::class, 'fakePage'])
    ->middleware('auth')->name('payments.fake');
