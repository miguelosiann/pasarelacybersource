@extends('template.app')

@section('title', 'Checkout - Pasarela de Pagos')

@section('content')
    @include('modules.payment.checkout-form')
@endsection

@push('scripts')
<script src="{{ asset('js/modules/payment/checkout.js') }}"></script>
@endpush

