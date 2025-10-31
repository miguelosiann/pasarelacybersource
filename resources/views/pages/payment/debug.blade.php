@extends('template.app')

@section('title', 'Debug - Pasarela de Pagos')

@section('content')
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

