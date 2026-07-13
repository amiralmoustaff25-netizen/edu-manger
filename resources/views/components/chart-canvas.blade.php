@props(['id' => 'chart', 'type' => 'bar', 'data' => []])
<canvas id="{{ $id }}" data-type="{{ $type }}" data-data="{{ json_encode($data) }}"></canvas>
