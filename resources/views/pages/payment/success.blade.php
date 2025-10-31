@extends('template.app')
@section('body_class', 'payment-success-page')
@section('title-page') Pago Exitoso @endsection

@section('content')
@include('modules.payment.success-content', ['payment' => $payment])
@endsection

@push('styles')
<style>
@media print {
    .btn, .alert-light, .card-actions {
        display: none !important;
    }
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
}
</style>
@endpush

