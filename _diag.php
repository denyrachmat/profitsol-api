<?php

$b = App\Models\CMS\FormMaster::where('cfmt_id', 10237)->where('cfm_type', 'html')->orderBy('id')->first();
$c = json_decode($b->cfm_content, true);
$body = $c['body'] ?? '';
$pos = strpos($body, '</style>');
echo "--- after </style> ---\n";
echo substr($body, $pos + 8, 900) . "\n\n";
echo "--- last 500 chars ---\n";
echo substr($body, -500) . "\n";
