@extends('errors.layout')

@section('code', 'Error 419')
@section('title', 'Se venció la sesión')
@section('message', 'Pasó demasiado tiempo con la pantalla abierta. Vuelve a cargar y sigue donde ibas; no se perdió nada de lo guardado.')

@section('actions')
    <button type="button" onclick="location.reload()">Volver a cargar</button>
@endsection
