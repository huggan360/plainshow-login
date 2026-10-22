<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * One-time import of the blog's accounts. Ids are kept, so posts and LaTeX
 * documents keep their owners. The blog stored passwords in plain text; they
 * are hashed here. Running it again skips accounts that already exist.
 */
class ImportBlogUsers extends Command
{
    protected $signature = 'plainshow:import-users
        {--dry-run : Show what would be imported}
        {--rename=* : Import an account under another username, as id:username}';

    protected $description = 'Import the blog (woc) accounts into PlainShow';

    private const ROLES = [5 => 'admin', 4 => 'moderator', 1 => 'user'];

    public function handle(): int
    {
        $rows = DB::connection('woc')->table('user')->orderBy('user_id')->get();
        $imported = 0;
        $renames = [];
        foreach ((array) $this->option('rename') as $rename) {
            [$id, $name] = array_pad(explode(':', $rename, 2), 2, '');
            $renames[(int) $id] = $name;
        }

        foreach ($rows as $row) {
            $username = trim($renames[(int) $row->user_id] ?? (string) $row->username);
            if ($username === '') {
                $this->warn("Skipping user {$row->user_id}: no username.");

                continue;
            }
            if (User::whereKey($row->user_id)->exists() || User::where('username', $username)->exists()) {
                $this->line("Exists: {$row->user_id} {$username}");

                continue;
            }
            $role = self::ROLES[(int) $row->access_level] ?? 'user';
            $this->line(sprintf('%s %d %s as %s', $this->option('dry-run') ? 'Would import' : 'Importing', $row->user_id, $username, $role));
            if ($this->option('dry-run')) {
                continue;
            }

            $user = new User;
            $user->forceFill([
                'id' => (int) $row->user_id,
                'username' => $username,
                'first_name' => (string) $row->firstname,
                'last_name' => (string) $row->lastname,
                // An empty blog password becomes an unusable random one.
                'password' => Hash::make((string) $row->password !== '' ? (string) $row->password : bin2hex(random_bytes(32))),
                'role' => $role,
                'status' => 'active',
                'avatar_path' => $row->image_source ?: null,
                'phone' => $row->phone_number ?: null,
            ])->save();
            $imported++;
        }

        if (! $this->option('dry-run') && $imported > 0) {
            // New accounts must not reuse an imported id.
            $next = (int) DB::table('users')->max('id') + 1;
            DB::statement('ALTER TABLE users AUTO_INCREMENT = '.$next);
            Audit::record('users.imported', null, 'cli', ['count' => $imported]);
        }
        $this->info("Imported $imported accounts.");

        return self::SUCCESS;
    }
}
