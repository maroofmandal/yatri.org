@props(['name', 'size' => 20, 'class' => '', 'filled' => false])
@php
  $svgName = $filled ? "{$name}-fill" : $name;
  $svg = config("icons.{$svgName}") ?: config("icons.{$name}");
@endphp
@if($svg)
<svg class="icon {{ $class }}" width="{{ $size }}" height="{{ $size }}" viewBox="0 -960 960 960" fill="currentColor" style="vertical-align:middle;display:inline-flex;flex-shrink:0">{!! $svg !!}</svg>
@endif
