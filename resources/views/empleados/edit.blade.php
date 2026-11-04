@extends('layouts.app')

@section('titulo', 'Editar empleado')

@section('contenido')
    <div class="mx-auto flex h-full max-w-5xl flex-col gap-4 overflow-y-auto pb-8">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Empleados', 'url' => route('empleados.index')],
                ['label' => 'Editar empleado'],
            ],
        ])

        <div>
            <h1 class="text-xl font-semibold text-ink">Editar empleado</h1>
            <p class="text-sm text-ink/45">{{ $empleado->nombreCompleto() }} · {{ $empleado->codigo }}</p>
        </div>

        @include('empleados._form')
    </div>
@endsection
