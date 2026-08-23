@php
    /*
     * Renders a local Streamline Colors SVG when resources/svg/{name}.svg
     * exists; otherwise falls back to the Material Symbols glyph so the UI
     * never breaks while the icon pack is being assembled.
     *
     * Streamline Free icons are CC BY 4.0 - attribution lives in
     * resources/svg/README.md (added with the first committed icons).
     */
    $svgPath = resource_path('svg/'.$name.'.svg');
@endphp

@if (is_file($svgPath))
    {!! preg_replace('/<svg(\s)/', '<svg $1class="'.$class.'" ', file_get_contents($svgPath)) !!}
@else
    <span class="material-symbols-rounded {{ $class }}">{{ $fallback }}</span>
@endif
