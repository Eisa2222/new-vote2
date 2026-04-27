@include('errors.layout', [
    'code'    => 404,
    'emoji'   => '🔍',
    'title'   => __('Page not found'),
    'message' => __('The page you are looking for has moved or no longer exists.'),
])
