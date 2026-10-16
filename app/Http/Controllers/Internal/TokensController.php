<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PlainShow\Kit\KitException;
use PlainShow\Kit\Signed;

/**
 * Personal API tokens, for the account page in core. Only core may call
 * these: every request is signed with the secret the two share and names
 * the person it is for. Login holds the signing keys, so tokens are made here.
 */
class TokensController extends Controller
{
    public const SCOPES = ['core', 'blog', 'split', 'latex', 'read-only'];

    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);

        return response()->json(['tokens' => $this->tokens($user)]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', 'in:'.implode(',', self::SCOPES)],
        ]);
        $scopes = array_values(array_unique($data['scopes']));
        if (! array_diff($scopes, ['read-only'])) {
            return response()->json(['message' => 'Choose at least one service.'], 422);
        }
        if (count($this->tokens($user)) >= 20) {
            return response()->json(['message' => 'You already have 20 tokens. Revoke one first.'], 422);
        }
        $result = $user->createToken(trim(strip_tags($data['name'])) ?: 'API token', $scopes);
        Audit::record('api_token.created', $user->id, $user->username, ['name' => $result->token->name, 'scopes' => $scopes]);

        return response()->json([
            'token' => $result->accessToken,
            'id' => $result->token->id,
            'expiresAt' => $result->token->expires_at?->toIso8601String(),
        ], 201);
    }

    public function destroy(Request $request, string $token): JsonResponse
    {
        $user = $this->user($request);
        $updated = DB::table('oauth_access_tokens')->where('id', $token)->where('user_id', $user->id)
            ->where('client_id', $this->personalClientId())->update(['revoked' => true, 'updated_at' => now()]);
        if ($updated) {
            Audit::record('api_token.revoked', $user->id, $user->username, ['token' => substr($token, 0, 8)]);
        }

        return response()->json(['revoked' => (bool) $updated]);
    }

    private function user(Request $request): User
    {
        try {
            $verified = Signed::verify($request->server->all(), $request->getContent(), (string) config('plainshow.internal_secret'));
        } catch (KitException $error) {
            abort(response()->json(['message' => $error->getMessage()], 401));
        }
        $user = User::find($verified['user']);
        abort_unless($user && $user->status === 'active', response()->json(['message' => 'Unknown or disabled account.'], 404));

        return $user;
    }

    private function tokens(User $user): array
    {
        return DB::table('oauth_access_tokens')
            ->where('user_id', $user->id)->where('client_id', $this->personalClientId())
            ->where('revoked', false)->where('expires_at', '>', now())
            ->orderByDesc('created_at')->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'scopes' => json_decode((string) $row->scopes, true) ?: [],
                'createdAt' => \Illuminate\Support\Carbon::parse($row->created_at)->toIso8601String(),
                'expiresAt' => \Illuminate\Support\Carbon::parse($row->expires_at)->toIso8601String(),
            ])->all();
    }

    private function personalClientId(): ?int
    {
        return DB::table('oauth_clients')->where('personal_access_client', true)->where('revoked', false)->orderByDesc('id')->value('id');
    }
}
