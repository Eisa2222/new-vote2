@include('errors.layout', [
    'code'    => 429,
    'emoji'   => '🐢',
    'title'   => __('Too many requests'),
    'message' => __('Please slow down and try again in a moment.'),
])
