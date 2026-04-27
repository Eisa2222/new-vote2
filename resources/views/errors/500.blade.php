@include('errors.layout', [
    'code'    => 500,
    'emoji'   => '⚠️',
    'title'   => __('Something went wrong on our end'),
    'message' => __('A technical issue prevented this page from loading. The team has been notified — please try again shortly.'),
    'ref'     => $ref ?? now()->format('Ymd-His'),
])
