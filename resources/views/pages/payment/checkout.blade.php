@extends('template.app')

@section('title', 'Checkout - Pasarela de Pagos')

@push('head')
    @include('components.payment.device-fingerprinting')
@endpush

@section('content')
    @include('components.payment.device-fingerprinting-noscript')
    @include('modules.payment.checkout-form')
@endsection

@push('scripts')
<script src="{{ asset('js/modules/payment/checkout.js') }}"></script>
@endpush

