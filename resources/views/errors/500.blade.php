@extends('errors.layout')

@section('code', 'Error 500')
@section('title', 'Algo se nos rompió')
@section('message', 'No es culpa tuya. Ya quedó registrado y lo estamos viendo. Vuelve a intentarlo en un momento.')

@section('actions')
    <button type="button" onclick="location.reload()">Volver a intentar</button>
@endsection
