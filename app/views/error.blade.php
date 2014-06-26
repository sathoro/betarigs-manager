@extends('layout')

@section('content')
	<div class="container">
		@if (isset($message))
			<p>{{ $message }}</p>
		@endif
		<p><b>To set your API keys:</b> Please open <b>EXAMPLE.env.php</b>, place your API keys in, and save the file as <b>.env.php</b> (the dot before "env" is important!).</p>
		<p><a href="{{ url('/') }}">Go Back</a></p>
	</div>
@stop