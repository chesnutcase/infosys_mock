<body>
<h1>Registration successful!</h1>

<div>You have successfully registered for {{ $event->title }}. Here are the event details:

<ul>
    <li>Location: {{ $event->location }}</li>
    <li>Start Time: {{ $event->start }}</li>
    <li>End Time:: {{ $event->end }}</li>
</ul>

See you there!
</div>
<div>
@if(isset($selfie))
    You have chosen to use facial recognition for attendance taking. No additional steps required!
@endif

@if(isset($qr_link))
    Attached is your QR ticket for attending the event. Keep this in a safe place!
    <br/>
        <img src="{{ $message->embed($qr_link) }}" style="width:300px;height:auto;">
@endif
</div>
<div>If you change your mind and decide not to attend the event, please <a href="{{$unregister_link}}">click here.</a></div>
</body>
