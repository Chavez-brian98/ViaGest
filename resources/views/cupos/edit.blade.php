@extends('layouts.app')

@section('titulo', 'Editar Cupo')

@section('contenido')
    <div class="flex h-full min-h-0 flex-col gap-3">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Cupos', 'url' => route('cupos.index')],
                ['label' => 'Editar cupo #' . $cupo->id],
            ],
        ])

        <section class="shrink-0 rounded-2xl border border-ink/10 bg-surface px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary/15 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-ink">Editar cupo #{{ $cupo->id }}</h1>
                    <p class="text-xs text-ink/45">Modifica los detalles, capacidad o vigencia del cupo</p>
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
