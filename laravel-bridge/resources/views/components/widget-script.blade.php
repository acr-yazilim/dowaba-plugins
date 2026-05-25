@props([])
@if($widgetJsUrl)
    <script
        src="{{ $widgetJsUrl }}"
        data-destek-key="{{ $siteApiKey() }}"
        @if($widgetToken)data-user-token="{{ $widgetToken }}"@endif
        data-dowaba="widget-script"
        async
        defer
    ></script>
@endif
