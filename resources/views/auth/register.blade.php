@extends('layouts.app')

@section('title', 'Inscription - ASBL Forum')

@section('content')
    <main class="container section" style="max-width: 560px;">
        <div class="card">
            <h2>Créer un compte</h2>

            <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="name">Nom ou pseudonyme</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input id="password" name="password" type="password" required>
                    <ul id="password-requirements" style="margin:8px 0 0;padding-left:20px;font-size:.85rem;">
                        <li data-rule="length">8 caractères minimum</li>
                        <li data-rule="uppercase">1 lettre majuscule</li>
                        <li data-rule="number">1 chiffre</li>
                        <li data-rule="special">1 caractère spécial</li>
                    </ul>
                    @error('password')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirmation du mot de passe</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required>
                </div>

                <div class="form-group">
                    <label for="avatar">Photo de profil (facultative)</label>
                    <input id="avatar" name="avatar" type="file" accept="image/*">
                    @error('avatar')
                        <div class="alert alert-error" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <button id="register-submit" type="submit" class="btn" disabled>S'inscrire</button>
            </form>
        </div>
    </main>

    <style>
        #password-requirements li { color: #6b7280; }
        #password-requirements li.is-valid { color: #16a34a; font-weight: 700; }
        #register-submit:disabled { opacity: .55; cursor: not-allowed; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const password = document.getElementById('password');
            const submit = document.getElementById('register-submit');
            const rules = {
                length: value => value.length >= 8,
                uppercase: value => /[A-Z]/.test(value),
                number: value => /[0-9]/.test(value),
                special: value => /[@$!%*#?&]/.test(value),
            };

            function updatePasswordRequirements() {
                const value = password.value;
                let valid = true;
                Object.entries(rules).forEach(([name, test]) => {
                    const item = document.querySelector('[data-rule="' + name + '"]');
                    const matches = test(value);
                    item.classList.toggle('is-valid', matches);
                    valid = valid && matches;
                });
                submit.disabled = !valid;
            }

            password.addEventListener('input', updatePasswordRequirements);
            updatePasswordRequirements();
        });
    </script>
@endsection
