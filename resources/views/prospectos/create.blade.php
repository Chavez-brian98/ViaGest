@extends('layouts.app')

@section('titulo', 'Nuevo Prospecto')

@push('scripts')
    @vite('resources/js/prospectos.js')
@endpush

@section('contenido')
    <div class="flex h-full min-h-0 flex-col gap-3">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Prospectos', 'url' => route('prospectos.index')],
                ['label' => 'Nuevo'],
            ],
        ])

        <section class="shrink-0 rounded-2xl border border-ink/10 bg-surface px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary/15 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.765z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-ink">Nuevo Prospecto</h1>
                    <p class="text-xs text-ink/45">Registra un cliente potencial para iniciar su ciclo de cotización y ventas</p>
                </div>
            </div>
        </section>

        <div class="flex-1 overflow-auto">
            @include('prospectos._form')
        </div>
    </div>
@endsection
