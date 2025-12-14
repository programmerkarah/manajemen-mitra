<?php

echo '<h2>🔍 Laravel Diagnostics</h2>';
echo '✅ PHP Version: '.phpversion().'<br>';
echo '✅ Document Root: '.$_SERVER['DOCUMENT_ROOT'].'<br>';
echo '✅ Script Path: '.__FILE__.'<br><br>';

// Check Laravel files
$checks = [
    'Laravel bootstrap' => __DIR__.'/../bootstrap/app.php',
    '.env file' => __DIR__.'/../.env',
    'Storage directory' => __DIR__.'/../storage',
    'Storage/logs' => __DIR__.'/../storage/logs',
    'Storage/framework/sessions' => __DIR__.'/../storage/framework/sessions',
    'Bootstrap/cache' => __DIR__.'/../bootstrap/cache',
    'Public/build (Vite)' => __DIR__.'/build',
];

foreach ($checks as $name => $path) {
    $exists = file_exists($path);
    $writable = $exists ? is_writable($path) : false;

    echo ($exists ? '✅' : '❌')." $name: ";
    echo $exists ? 'EXISTS' : 'NOT FOUND';

    if ($exists && is_dir($path)) {
        echo ' | Writable: '.($writable ? '✅ YES' : '❌ NO');
        echo ' | Perms: '.substr(sprintf('%o', fileperms($path)), -4);
    }
    echo '<br>';
}

echo '<br><h3>🧪 Testing Laravel Boot...</h3>';

try {
    require __DIR__.'/../vendor/autoload.php';
    echo '✅ Autoload loaded<br>';

    $app = require_once __DIR__.'/../bootstrap/app.php';
    echo '✅ Laravel app bootstrapped<br>';

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo '✅ HTTP Kernel created<br>';

    // Try to get config
    $app->make('config');
    echo '✅ Config loaded<br>';

    echo '<br>🎉 Laravel can boot successfully!<br>';
    echo 'The 403/500 errors are likely from middleware or routing.<br>';

} catch (Exception $e) {
    echo '❌ ERROR: '.$e->getMessage().'<br>';
    echo '<pre>'.$e->getTraceAsString().'</pre>';
}

echo '<br><h3>📋 Next Steps:</h3>';
echo '1. Run: <code>php artisan config:clear && php artisan cache:clear</code><br>';
echo '2. Check: <code>tail -50 storage/logs/laravel.log</code><br>';
echo '3. Set temporarily: <code>APP_DEBUG=true</code> in .env<br>';
echo '<br>';
echo "<a href='/'>🏠 Try Home Page</a> | ";
echo "<a href='/login'>🔐 Try Login Page</a> | ";
echo "<a href='/register'>📝 Try Register Page</a>";
