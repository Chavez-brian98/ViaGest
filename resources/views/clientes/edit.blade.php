@extends('layouts.app')

@section('titulo', 'Editar cliente')

@section('contenido')
    <div class="mx-auto flex h-full max-w-5xl flex-col gap-4 overflow-y-auto pb-8">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Clientes', 'url' => route('clientes.index')],
                ['label' => 'Editar cliente'],
            ],
        ])

        <div>
            <h1 class="text-xl font-semibold text-ink">Editar cliente</h1>
            <p class="text-sm text-ink/45">{{ $cliente->nombreMostrado() }} · {{ $cliente->codigo }}</p>
        </div>

        @include('clientes._form')
    </div>
@endsection
