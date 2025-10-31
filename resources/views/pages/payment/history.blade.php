@extends('template.app')
@section('body_class', 'payment-history-page')
@section('title-page') Historial de Pagos @endsection

@section('content')
@include('modules.payment.history-content', ['payments' => $payments])
@endsection

