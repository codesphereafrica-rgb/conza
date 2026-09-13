@props(['text' => '', 'class' => '', 'contentClass' => ''])

@php
    $markdown = trim((string) $text);
    $renderedText = $markdown !== ''
        ? \Illuminate\Support\Str::markdown($markdown, ['html_input' => 'strip'])
        : '';
@endphp

<div class="truncated-text {{ $class }}" data-truncated-text>
    <div class="truncate-text {{ $contentClass }}" data-truncated-content>{!! $renderedText !!}</div>
    <button type="button" class="truncate-toggle" data-truncated-toggle aria-expanded="false">
        Voir plus...
    </button>
</div>
