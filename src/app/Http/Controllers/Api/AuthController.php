<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->getAuthPassword())) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tokenResult = $user->createToken('api');

        return response()->json([
            'token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'user' => $this->formatUser($user),
        ], Response::HTTP_OK);
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->token();

        if ($token !== null) {
            $token->revoke();
        }

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(
            $this->formatUser($user),
            Response::HTTP_OK
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        $role = $user->role instanceof UserRoleEnum ? $user->role->value : $user->role;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
        ];
    }
}
