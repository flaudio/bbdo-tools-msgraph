@extends('layout')

@section('content')
<div class="jumbotron">
 <h1>{{ env('APP_NAME') }}</h1>
 <p class="lead"></p>
 @if(isset($userName))
   <h4>Welcome {{ $userName }} {{ $userId }}!</h4>
 @else
   <a href="{{ route('auth.ms-graph.signin') }}" class="btn btn-primary btn-large">Click here to sign in</a>
 @endif
</div>
@endsection
