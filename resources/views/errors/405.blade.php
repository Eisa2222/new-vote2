@include('errors.layout', [
    'code'    => 405,
    'emoji'   => '🚫',
    'title'   => __('Action not allowed'),
    'message' => __('This action is not allowed from this page. Try going back and using the menu.'),
])
