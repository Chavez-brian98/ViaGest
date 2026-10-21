<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Iniciar sesión | ViaGest</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-ink antialiased">
        <div class="relative min-h-screen overflow-hidden">
            <div
                class="absolute inset-0"
                style="background: radial-gradient(circle at 20% 20%, rgba(28, 163, 170, 0.45), transparent 45%), radial-gradient(circle at 80% 75%, rgba(11, 79, 108, 0.7), transparent 55%), linear-gradient(135deg, #0b4f6c 0%, #1ca3aa 100%);"
                aria-hidden="true"
            ></div>

            <video
                class="absolute inset-0 h-full w-full object-cover opacity-40"
                autoplay
                muted
                loop
                playsinline
                aria-hidden="true"
            >
                <source src="{{ asset('videos/login.mp4') }}" type="video/mp4">
            </video>

            <div class="absolute inset-0 bg-primary/55" aria-hidden="true"></div>

            <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-10">
                <main class="w-full max-w-md overflow-hidden rounded-3xl border border-white/20 bg-surface p-8 shadow-2xl sm:p-10">
                    <div class="-mx-8 -mt-8 mb-8 h-1.5 bg-gradient-to-r from-primary via-secondary to-accent sm:-mx-10 sm:-mt-10"></div>

                    <div class="mb-8 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-lg font-semibold text-white shadow-lg">
                            VG
                        </div>
                        <h1 class="mt-5 text-2xl font-semibold tracking-tight text-ink">
                            Iniciar sesión
                        </h1>
                        <p class="mt-2 text-sm text-ink/60">
                            Acceso interno de empleados a ViaGest
                        </p>
                    </div>

                    @if ($errors->any())
                        <div
                            class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
                            role="alert"
                        >
                            <ul class="list-disc space-y-1 pl-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium text-ink/80">
                                Correo electrónico
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                required
                                autofocus
                                class="block w-full rounded-xl border border-ink/15 bg-background px-4 py-3 text-ink transition placeholder:text-ink/40 focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20 @error('email') border-rose-500 focus:border-rose-500 focus:ring-rose-500/15 @enderror"
                            >
                            @error('email')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-ink/80">
                                Contraseña
                            </label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                required
                                class="block w-full rounded-xl border border-ink/15 bg-background px-4 py-3 text-ink transition placeholder:text-ink/40 focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20 @error('password') border-rose-500 focus:border-rose-500 focus:ring-rose-500/15 @enderror"
                            >
                            @error('password')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="flex items-center gap-2 text-sm text-ink/70">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                @checked(old('remember'))
                                class="h-4 w-4 rounded border-ink/20 text-secondary accent-secondary focus:ring-secondary/30"
                            >
                            Recordarme
                        </label>

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
                        >
                            Entrar
                        </button>
                    </form>

                    <p class="mt-6 text-center text-xs text-ink/45">
                        Sistema de uso exclusivo para personal autorizado.
                    </p>
                </main>
            </div>
        </div>
    </body>
</html>
