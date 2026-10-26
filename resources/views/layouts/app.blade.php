<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('titulo', 'Dashboard') | ViaGest</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
        <body class="h-screen overflow-hidden bg-background font-sans text-ink antialiased">
        @php
            $icons = [
                'home' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
                'ticket' => 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125h.375c.621 0 1.125.504 1.125 1.125v.75c0 .621-.504 1.125-1.125 1.125h-.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-.375c-.621 0-1.125-.504-1.125-1.125v-.75c0-.621.504-1.125 1.125-1.125h.375c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375z',
                'document' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
                'cube' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
                'globe' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418',
                'building' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
                'calendar' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
                'users' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                'sparkles' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z',
                'credit-card' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
                'receipt' => 'M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
                'wallet' => 'M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3',
                'chart' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                'user-group' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
                'map' => 'M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z',
                'cog' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z',
            ];

            $usuario = auth()->user();
            $fotoSidebar = $usuario?->fotoPerfilUrl();
            $perfilActivo = request()->routeIs('perfil.*');
        @endphp

        <div class="flex h-screen overflow-hidden">
            <aside id="appSidebar" class="app-sidebar flex flex-col border-r border-ink/10 bg-surface">
                <div class="flex h-16 shrink-0 items-center gap-3 border-b border-ink/10 px-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary text-sm font-semibold text-white shadow-lg shadow-primary/30">
                        VG
                    </div>
                    <div class="sidebar-text min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ink">ViaGest</p>
                        <p class="truncate text-xs text-ink/45">Agencia de viajes</p>
                    </div>
                    <button
                        type="button"
                        id="sidebarToggle"
                        class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg text-ink/45 transition-colors hover:bg-background hover:text-ink lg:flex"
                        title="Colapsar menu"
                        aria-label="Colapsar menu"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 sidebar-toggle-icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4">
                    @foreach (config('navegacion.grupos') as $grupo)
                        <div>
                            <p class="sidebar-section-title mb-1 px-2 text-[11px] font-bold uppercase tracking-widest text-ink/45">
                                {{ $grupo['titulo'] }}
                            </p>
                            @foreach ($grupo['items'] as $item)
                                @php
                                    $activo = $item['ruta'] && request()->routeIs($item['ruta']);
                                    $clase = 'flex items-center gap-3 rounded-lg px-2 py-2 text-sm font-medium transition-colors '
                                        . ($activo ? 'bg-primary text-white shadow-sm' : 'text-ink/70 hover:bg-secondary/10 hover:text-primary');
                                @endphp
                                <a
                                    href="{{ $item['ruta'] ? route($item['ruta']) : '#' }}"
                                    title="{{ $item['label'] }}"
                                    class="{{ $clase }}"
                                    @if (! $item['ruta']) aria-disabled="true" @endif
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icono']] ?? $icons['home'] }}" />
                                    </svg>
                                    <span class="sidebar-text truncate">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </nav>

                <div class="sidebar-footer flex shrink-0 items-center gap-2 border-t border-ink/10 px-3 py-3">
                    <a
                        href="{{ route('perfil.edit') }}"
                        class="flex min-w-0 flex-1 items-center gap-3 rounded-xl p-1 transition-colors {{ $perfilActivo ? 'bg-secondary/10' : 'hover:bg-background' }}"
                        title="Abrir mi perfil"
                        @if ($perfilActivo) aria-current="page" @endif
                    >
                        @if ($fotoSidebar)
                            <img
                                src="{{ $fotoSidebar }}"
                                alt="Foto de {{ $usuario->nombreCompleto() }}"
                                class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-secondary/20"
                            >
                        @else
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary/15 text-sm font-semibold text-primary">
                                {{ $usuario?->iniciales() ?? 'U' }}
                            </span>
                        @endif
                        <span class="sidebar-text min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-ink">{{ $usuario?->nombreCompleto() ?? 'Usuario' }}</span>
                            <span class="block truncate text-xs text-ink/45">{{ $usuario?->email }}</span>
                        </span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="sidebar-logout shrink-0">
                        @csrf
                        <button
                            type="submit"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition-colors hover:bg-rose-50 hover:text-rose-600"
                            title="Cerrar sesion"
                            aria-label="Cerrar sesion"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>
                </div>
            </aside>

            <div id="sidebarBackdrop" class="sidebar-backdrop" aria-hidden="true"></div>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex h-16 shrink-0 items-center gap-3 border-b border-ink/10 bg-surface px-4 sm:px-6">
                    <button
                        type="button"
                        id="sidebarOpenBtn"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-ink/50 transition-colors hover:bg-background hover:text-ink lg:hidden"
                        title="Abrir menu"
                        aria-label="Abrir menu"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <div class="min-w-0 flex-1">
                        <h1 class="truncate text-lg font-semibold text-ink">@yield('titulo', 'Dashboard')</h1>
                    </div>

                    <span class="hidden text-sm text-ink/45 sm:block">{{ now()->format('d/m/Y') }}</span>
                </header>

                <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:overflow-hidden">
                    @yield('contenido')
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
