<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncSsoUserIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sso:sync-user-ids 
                            {--token= : SSO admin access token (optional, will use service account)}
                            {--dry-run : Dry run without saving changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync SSO user IDs to local users based on email matching';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting SSO User ID sync...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        $token = $this->option('token');
        
        if (! $token) {
            $this->error('❌ SSO access token required.');
            $this->info('💡 Get token by logging in to SSO and running:');
            $this->line('   php artisan tinker');
            $this->line('   $user = User::where(\'email\', \'your@email.com\')->first();');
            $this->line('   $token = $user->createToken(\'sync-command\')->accessToken;');
            $this->line('   echo $token;');
            $this->newLine();
            $this->info('Then run this command with --token option:');
            $this->line('   php artisan sso:sync-user-ids --token=YOUR_TOKEN');
            
            return self::FAILURE;
        }

        $baseUrl = rtrim((string) config('services.sso.base_url'), '/');
        
        if ($baseUrl === '') {
            $this->error('❌ SSO base URL not configured in config/services.php');
            return self::FAILURE;
        }

        $this->info("📡 Fetching users from SSO: {$baseUrl}/api/users");

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/users");

        if ($response->failed()) {
            $this->error('❌ Failed to fetch users from SSO');
            $this->line("   Status: {$response->status()}");
            $this->line("   Body: {$response->body()}");
            return self::FAILURE;
        }

        $ssoUsers = $response->json('data') ?? $response->json();
        
        if (! is_array($ssoUsers)) {
            $this->error('❌ Invalid response format from SSO');
            return self::FAILURE;
        }

        $this->info("✅ Found " . count($ssoUsers) . " users in SSO");
        $this->newLine();

        $matched = 0;
        $updated = 0;
        $skipped = 0;
        $notFound = 0;

        $progressBar = $this->output->createProgressBar(count($ssoUsers));
        $progressBar->start();

        foreach ($ssoUsers as $ssoUser) {
            $ssoUserId = $ssoUser['id'] ?? null;
            $ssoEmail = $ssoUser['email'] ?? null;
            $ssoUsername = $ssoUser['username'] ?? null;

            if (! $ssoUserId || ! $ssoEmail) {
                $skipped++;
                $progressBar->advance();
                continue;
            }

            $localUser = User::where('email', $ssoEmail)->first();

            if (! $localUser) {
                $notFound++;
                $progressBar->advance();
                continue;
            }

            $matched++;

            if ($localUser->sso_user_id === $ssoUserId) {
                $skipped++;
                $progressBar->advance();
                continue;
            }

            if (! $dryRun) {
                $localUser->sso_user_id = $ssoUserId;
                $localUser->save();
            }

            $updated++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('📊 Sync Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total SSO Users', count($ssoUsers)],
                ['Matched by Email', $matched],
                ['Updated', $updated],
                ['Already Synced (Skipped)', $skipped],
                ['Not Found Locally', $notFound],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN - no changes were saved');
            $this->info('💡 Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info('✅ Sync completed successfully!');
        }

        return self::SUCCESS;
    }
}

