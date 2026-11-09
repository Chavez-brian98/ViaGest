@extends('layouts.app')

@section('titulo', 'Nuevo Paquete')

@push('scripts')
    @vite('resources/js/paquetes.js')
@endpush

@section('contenido')
    <div class="flex h-full min-h-0 flex-col gap-3">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Paquetes', 'url' => route('paquetes.index')],
                ['label' => 'Nuevo paquete'],
            ],
        ])

        <section class="shrink-0 rounded-2xl border border-ink/10 bg-surface px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary/15 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-ink">Registrar paquete turístico</h1>
                    <p class="text-xs text-ink/45">Crea una nueva propuesta integral de viaje con costeo automático</p>
                </div>
            </div>
        </section>

        <div class="min-h-0 flex-1 overflow-y-auto">
            @include('paquetes._form', [
                'paquete' => $paquete,
                'ciudades' => $ciudades,
                'serviciosDisponibles' => $serviciosDisponibles,
                'serviciosAsignados' => $serviciosAsignados,
            ])
        </div>
    </div>
@endsection
