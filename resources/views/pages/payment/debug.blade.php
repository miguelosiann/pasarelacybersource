@extends('template.app')

@section('title', 'Debug - Pasarela de Pagos')

@push('head')
    @include('components.payment.device-fingerprinting')
@endpush

@section('content')
    @include('components.payment.device-fingerprinting-noscript')
    @include('modules.payment.debug-content')
    
    <style>
        .step-result {
            display: none;
        }
        .step-result.show {
            display: block;
        }
    </style>
    
    <script src="{{ asset('js/modules/payment/debug.js') }}"></script>
@endsection

