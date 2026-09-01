<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\BaseCategoryConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->create([
            'name' => trim($validated['name']),
            'email' => mb_strtolower(trim($validated['email'])),
            'password' => $validated['password'],
        ]);

        BaseCategoryConfig::syncToDatabase($user->id);

        return $this->tokenResponse($user, $validated['device_name'] ?? 'AppGastos', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', mb_strtolower(trim($validated['email'])))->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['El correo o la contraseña no son correctos.'],
            ]);
        }

        BaseCategoryConfig::syncToDatabase($user->id);

        return $this->tokenResponse($user, $validated['device_name'] ?? 'AppGastos');
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->serializeUser($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'current_password' => ['required', 'string'],
                'password' => ['required', 'confirmed', 'different:current_password', Password::min(10)->letters()->numbers()],
            ],
            [
                'password.different' => 'La contraseña nueva debe ser diferente de la actual.',
            ],
        );

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $user->update(['password' => $validated['password']]);

        $currentToken = $user->currentAccessToken();
        $currentTokenId = $currentToken && method_exists($currentToken, 'getKey')
            ? $currentToken->getKey()
            : null;

        $user->tokens()
            ->when($currentTokenId !== null, fn ($query) => $query->whereKeyNot($currentTokenId))
            ->delete();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente. Se han cerrado las demás sesiones.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['La contraseña no es correcta.'],
            ]);
        }

        if ($validated['confirmation'] !== 'BORRAR MI CUENTA') {
            throw ValidationException::withMessages([
                'confirmation' => ['Escribe BORRAR MI CUENTA para confirmar.'],
            ]);
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json([
            'message' => 'Tu cuenta y todos sus datos se han eliminado definitivamente.',
        ]);
    }

    private function tokenResponse(User $user, string $deviceName, int $status = 200): JsonResponse
    {
        $token = $user->createToken(trim($deviceName) ?: 'AppGastos')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->serializeUser($user),
        ], $status);
    }

    /** @return array{id: int, name: string, email: string} */
    private function serializeUser(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }
}
