@extends('template.app')
@section('body_class', 'payment-challenge-page')
@section('title-page') Autenticación 3D Secure @endsection

@section('content')
@include('modules.payment.challenge-content', ['challengeData' => $challengeData])
@endsection

