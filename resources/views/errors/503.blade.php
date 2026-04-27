@include('errors.layout', [
    'code'    => 503,
    'emoji'   => '🔧',
    'title'   => __('Down for maintenance'),
    'message' => __('We are performing a quick update. The site will be back in a few minutes.'),
])
