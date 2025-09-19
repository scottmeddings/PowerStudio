<?php
protected $routeMiddleware = [
    // ...
    'adminlike' => \App\Http\Middleware\EnsureAdminLike::class,
];
