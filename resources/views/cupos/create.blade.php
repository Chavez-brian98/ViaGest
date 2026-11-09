@extends('layouts.app')

@section('titulo', 'Nuevo Cupo')

@section('contenido')
    <div class="flex h-full min-h-0 flex-col gap-3">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Cupos', 'url' => route('cupos.index')],
                ['label' => 'Nuevo cupo'],
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
                    <h1 class="text-lg font-semibold text-ink">Registrar cupo de inventario</h1>
                    <p class="text-xs text-ink/45">Asigna bloqueos o compras anticipadas a un servicio</p>
                </div>
            </div>
        </section>

        <div class="min-h-0 flex-1 overflow-y-auto">
            @include('cupos._form', [
                'cupo' => $cupo,
                'servicios' => $servicios,
            ])
        </div>
    </div>
@endsection
