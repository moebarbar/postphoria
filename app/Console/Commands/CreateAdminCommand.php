<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\AdminUser\Models\User;
use Modules\AdminUser\Support\PersonalTeamProvisioner;
use Modules\AppAffiliate\Support\AffiliateService;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
        {--email= : Admin email address}
        {--password= : Admin password (min 8 chars)}
        {--name=Admin : Display name}
        {--username=admin : Username (letters, digits, dot, underscore, dash)}
        {--timezone=UTC : IANA timezone}';

    protected $description = 'Create or update a super-admin user (idempotent).';

    public function handle(AffiliateService $affiliates, PersonalTeamProvisioner $teams): int
    {
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

        $this->info(sprintf(
            'Admin ready: id=%d email=%s username=%s super_admin=%s',
            $user->id,
            $user->email,
            $user->username,
            $user->is_super_admin ? 'yes' : 'no'
        ));

        return self::SUCCESS;
    }
}
