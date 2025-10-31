@extends('template.app')
@section('body_class', 'payment-processing-page')
@section('title-page') Procesando Pago @endsection

@section('content')
@include('modules.payment.processing-content', [
    'result' => $result ?? null,
    'formData' => $formData ?? []
])
@endsection

