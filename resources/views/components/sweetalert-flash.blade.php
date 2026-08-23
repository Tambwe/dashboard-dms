<script>
    window.appFlashMessages = [
        @if(session()->has('success'))
            { type: 'success', message: @json(session('success')) },
        @endif
        @if(session()->has('status'))
            { type: 'status', message: @json(session('status')) },
        @endif
        @if(session()->has('error'))
            { type: 'error', title: 'Erreur', message: @json(session('error')) },
        @endif
        @if(session()->has('warning'))
            { type: 'warning', title: 'Attention', message: @json(session('warning')) },
        @endif
        @if(session()->has('info'))
            { type: 'info', title: 'Information', message: @json(session('info')) },
        @endif
        @if(isset($errors) && $errors->any())
            { type: 'error', title: 'Formulaire incomplet', message: @json($errors->first()) },
        @endif
    ];
</script>
