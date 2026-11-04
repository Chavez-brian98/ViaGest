@extends('layouts.app')

@section('titulo', 'Nuevo empleado')

@section('contenido')
    <div class="mx-auto flex h-full max-w-5xl flex-col gap-4 overflow-y-auto pb-8">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Empleados', 'url' => route('empleados.index')],
                ['label' => 'Nuevo empleado'],
            ],
        ])

        <div>
            <h1 class="text-xl font-semibold text-ink">Nuevo empleado</h1>
            <p class="text-sm text-ink/45">Registra los datos personales y laborales del nuevo colaborador.</p>
        </div>

        @include('empleados._form')
    </div>
@endsection
