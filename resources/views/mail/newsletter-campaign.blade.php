<x-mail::message>
@if ($subscriber->first_name)
Hallo {{ $subscriber->first_name }},
@endif

{!! $campaign->body !!}

<img src="{{ $pixel }}" width="1" height="1" alt="" style="display:block">

<small>Sie erhalten diese E-Mail, weil Sie sich für den Newsletter von Armory Falter angemeldet haben.
[Abmelden]({{ $subscriber->unsubscribeUrl() }})</small>
</x-mail::message>
