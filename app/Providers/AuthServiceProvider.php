<?php

namespace App\Providers;

use App\Models\AttendanceCode;
use App\Models\AttendanceSession;
use App\Models\OutboundMessage;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Policies\AttendanceCodePolicy;
use App\Policies\AttendanceSessionPolicy;
use App\Policies\OutboundMessagePolicy;
use App\Policies\SchoolPolicy;
use App\Policies\SchoolYearPolicy;
use App\Policies\StudentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Student::class => StudentPolicy::class,
        User::class => UserPolicy::class,
        School::class => SchoolPolicy::class,
        SchoolYear::class => SchoolYearPolicy::class,
        AttendanceCode::class => AttendanceCodePolicy::class,
        AttendanceSession::class => AttendanceSessionPolicy::class,
        OutboundMessage::class => OutboundMessagePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}