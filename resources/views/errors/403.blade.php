@include('errors.layout', [
    'code'    => 403,
    'emoji'   => '🔒',
    'title'   => __('Access denied'),
    'message' => __('You do not have permission to view this page.'),
])
