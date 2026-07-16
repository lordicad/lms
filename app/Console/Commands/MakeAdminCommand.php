<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * The ONLY way an admin (MOE oversight) account comes into existence — there is no admin
 * self-registration. Promotes an existing user by email, or creates a fresh admin account.
 *
 *   php artisan lms:make-admin moe@example.gov.my
 *   php artisan lms:make-admin moe@example.gov.my --name="Pegawai MOE" --password=secret123
 */
class MakeAdminCommand extends Command
{
    protected $signature = 'lms:make-admin {email : Emel akaun admin}
                            {--name= : Nama penuh (jika mencipta akaun baharu)}
                            {--password= : Kata laluan (jika mencipta akaun baharu)}';

    protected $description = 'Cipta atau naik taraf satu akaun kepada peranan admin MOE (CLI sahaja).';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Emel tidak sah: {$email}");

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->isAdmin()) {
                $this->info("{$email} sudah menjadi admin.");

                return self::SUCCESS;
            }

            $user->update(['role' => User::ROLE_ADMIN]);
            $this->info("Akaun {$user->name} <{$email}> kini admin.");

            return self::SUCCESS;
        }

        $name = (string) ($this->option('name') ?: $this->ask('Nama penuh admin'));
        $password = (string) ($this->option('password') ?: $this->secret('Kata laluan (min 8 aksara)'));

        if (trim($name) === '' || strlen($password) < 8) {
            $this->error('Nama diperlukan dan kata laluan mesti sekurang-kurangnya 8 aksara.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'username' => $this->uniqueUsername($email),
            'email' => $email,
            'password' => $password,   // hashed by the model cast
            'role' => User::ROLE_ADMIN,
        ]);

        $this->info("Admin dicipta: {$user->name} <{$email}> (nama pengguna: {$user->username}).");

        return self::SUCCESS;
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '.') ?: 'admin';
        $username = $base;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.$suffix;
            $suffix++;
        }

        return $username;
    }
}
