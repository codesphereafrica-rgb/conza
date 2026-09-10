<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Throwable;

class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        try {
            $status = Password::sendResetLink($validated);
        } catch (Throwable $exception) {
            Log::error('MAIL ERROR FULL', [
                'exception' => $exception,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            error_log('MAIL ERROR FULL '.$exception);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Erreur lors de l’envoi du mail.'], 500);
            }

            return back()->withErrors(['email' => 'Une erreur est survenue pendant l’envoi du mail.']);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Le lien de réinitialisation a été envoyé par e-mail.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => 'Un lien vient déjà d’être demandé. Réessaie dans une minute.']);
        }

        Log::warning('Password reset link was not sent.', [
            'status' => $status,
        ]);

        return back()->withErrors(['email' => 'Impossible d’envoyer le lien pour cette adresse.']);
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($validated, function ($user, $password) {
            $user->forceFill(['password' => $password])->save();
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Votre mot de passe a été réinitialisé.')
            : back()->withErrors(['email' => 'Le lien de réinitialisation est invalide ou expiré.']);
    }
}