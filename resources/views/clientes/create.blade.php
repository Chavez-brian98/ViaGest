@extends('layouts.app')

@section('titulo', 'Nuevo cliente')

@section('contenido')
    <div class="mx-auto flex h-full max-w-5xl flex-col gap-4 overflow-y-auto pb-8">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Clientes', 'url' => route('clientes.index')],
                ['label' => 'Nuevo cliente'],
            ],
        ])

        <div>
            <h1 class="text-xl font-semibold text-ink">Nuevo cliente</h1>
            <p class="text-sm text-ink/45">Registra los datos de contacto y documentación del cliente.</p>
        </div>

        @include('clientes._form')
    </div>
@endsection
