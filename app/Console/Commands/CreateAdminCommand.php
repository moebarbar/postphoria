<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\AdminPlans\Models\AdminPlan;
use Modules\AdminUser\Models\User;
use Modules\AdminUser\Support\PersonalTeamProvisioner;
use Modules\AppAffiliate\Support\AffiliateService;
use Modules\AppPayments\Support\UserPlanTransitionService;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
        {--email= : Admin email address}
        {--password= : Admin password (min 8 chars)}
        {--name=Admin : Display name}
        {--username=admin : Username (letters, digits, dot, underscore, dash)}
        {--timezone=UTC : IANA timezone}
        {--plan-slug=agency-lifetime : Plan to assign so customer-portal features unlock (set to "" to skip)}
        {--seed : Run installer default seeders first (plans, AI templates) — idempotent}';

    protected $description = 'Create or update a super-admin user, optionally seed defaults and assign a plan.';

    public function handle(
        AffiliateService $affiliates,
        PersonalTeamProvisioner $teams,
        UserPlanTransitionService $planTransitions,
    ): int {
        $data = [
            'email' => strtolower(trim((string) $this->option('email'))),
            'password' => (string) $this->option('password'),
            'name' => trim((string) $this->option('name')),
            'username' => strtolower(trim((string) $this->option('username'))),
            'timezone' => (string) $this->option('timezone'),
        ];

        $validator = Validator::make($data, [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
            'timezone' => ['required', 'timezone:all'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($this->option('seed')) {
            foreach ((array) config('installer.default_seeders', []) as $seederClass) {
                $this->info("Seeding {$seederClass}");
                Artisan::call('db:seed', [
                    '--class' => $seederClass,
                    '--force' => true,
                    '--no-interaction' => true,
                ]);
            }
        }

        $user = User::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'email_verified_at' => now(),
                'timezone' => $data['timezone'],
                'is_super_admin' => true,
                'password' => $data['password'],
            ]
        );

        $affiliates->ensureReferralCode($user);
        $affiliates->ensureProfile($user);
        $teams->ensureForUser($user);

        if (! Hash::check($data['password'], $user->fresh()->password)) {
            $this->error('Password verification failed after save — the password cast may not be hashing as expected.');

            return self::FAILURE;
        }

        $planSlug = trim((string) $this->option('plan-slug'));
        $assignedPlan = null;

        if ($planSlug !== '') {
            $plan = AdminPlan::query()->where('slug', $planSlug)->where('status', true)->first();

            if (! $plan) {
                $this->warn("Plan with slug '{$planSlug}' not found or inactive. Run with --seed to create default plans, or pass --plan-slug=\"\" to skip.");
            } else {
                $planTransitions->applyPurchasedPlan($user, $plan);
                $assignedPlan = $plan->slug;
            }
        }

        $this->info(sprintf(
            'Admin ready: id=%d email=%s username=%s super_admin=%s plan=%s',
            $user->id,
            $user->email,
            $user->username,
            $user->is_super_admin ? 'yes' : 'no',
            $assignedPlan ?: 'none'
        ));

        return self::SUCCESS;
    }
}
