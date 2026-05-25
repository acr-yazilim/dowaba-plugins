@props([])
<iframe
    src="{{ $embedUrl }}"
    class="{{ $class }}"
    style="width: 100%; height: {{ $height }}px; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;"
    data-dowaba="chat-window"
    data-conversation-id="{{ $conversationId }}"
    referrerpolicy="no-referrer-when-downgrade"
    sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
></iframe>
