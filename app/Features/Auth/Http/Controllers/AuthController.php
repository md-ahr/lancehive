<?php

declare(strict_types=1);

namespace App\Features\Auth\Http\Controllers;

use App\Features\Auth\Http\Requests\ForgotPasswordRequest;
use App\Features\Auth\Http\Requests\LoginRequest;
use App\Features\Auth\Http\Requests\ResetPasswordRequest;
use App\Features\Auth\Http\Resources\LoginResource;
use App\Features\Auth\Http\Resources\MessageResource;
use App\Features\Auth\Models\User;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

#[Group('Authentication', weight: 0)]
final class AuthController extends Controller
{
    public function login(LoginRequest $request): LoginResource
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return new LoginResource([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request): MessageResource
    {
        $request->user()->currentAccessToken()->delete();

        return new MessageResource([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): MessageResource
    {
        Password::sendResetLink($request->only('email'));

        return new MessageResource([
            'message' => 'If that email address exists, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): MessageResource
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return new MessageResource([
                'message' => 'Password reset successfully.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
