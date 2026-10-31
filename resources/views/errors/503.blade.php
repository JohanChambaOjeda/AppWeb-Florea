@extends('errors.layout')

@section('code', 'Error 503')
@section('title', 'Florea está en mantenimiento')
@section('message', 'Estamos haciendo una mejora rápida. En unos minutos vuelve a estar lista.')

@section('actions')
    <button type="button" onclick="location.reload()">Volver a intentar</button>
@endsection
