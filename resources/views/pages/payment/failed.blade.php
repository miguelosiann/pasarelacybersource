@extends('template.app')
@section('body_class', 'payment-failed-page')
@section('title-page') Pago Fallido @endsection

@section('content')
@include('modules.payment.failed-content', ['error' => $error])
@endsection

