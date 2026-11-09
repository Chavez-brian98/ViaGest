@extends('layouts.app')

@section('titulo', 'Editar proveedor')

@section('contenido')
    <div class="mx-auto flex h-full max-w-5xl flex-col gap-4 overflow-y-auto pb-8">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Proveedores', 'url' => route('proveedores.index')],
                ['label' => 'Editar proveedor'],
            ],
        ])

        <div>
            <h1 class="text-xl font-semibold text-ink">Editar proveedor</h1>
            <p class="text-sm text-ink/45">Actualiza los datos comerciales y de contacto del proveedor.</p>
        </div>

        @include('proveedores._form')
    </div>
@endsection
