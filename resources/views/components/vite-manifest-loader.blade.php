<!-- CSS Files -->
@foreach ($cssFiles as $file)
    <link rel="stylesheet" href="{{ asset($file) }}" data-navigate-track="reload" />
@endforeach

<!-- JS Files -->
@foreach ($jsFiles as $file)
    <script type="module" src="{{ asset($file) }}" data-navigate-track="reload"></script>
@endforeach
