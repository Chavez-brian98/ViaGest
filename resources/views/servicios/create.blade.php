@extends('layouts.app')

@section('titulo', 'Nuevo servicio')

@section('contenido')
    <div class="mx-auto flex h-full max-w-5xl flex-col gap-4 overflow-y-auto pb-8">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Servicios', 'url' => route('servicios.index')],
                ['label' => 'Nuevo servicio'],
            ],
        ])

        <div>
            <h1 class="text-xl font-semibold text-ink">Nuevo servicio</h1>
            <p class="text-sm text-ink/45">Registra los datos del servicio y sus condiciones comerciales.</p>
        </div>

        @include('servicios._form')
    </div>
@endsection
