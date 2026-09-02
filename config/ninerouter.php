<?php

return [
    'url' => env('NINEROUTER_URL'),
    'api_key' => env('NINEROUTER_API_KEY'),
];

// COPY THIS FILE TO THE LIVE SERVER  ->  config/ninerouter.php
// And in app/Http/Controllers/API/CMS/QuizController.php replace:
//   $apiKey = env('NINEROUTER_API_KEY');  =>  $apiKey = config('ninerouter.api_key');
//   $apiUrl = env('NINEROUTER_URL');      =>  $apiUrl = config('ninerouter.url');
