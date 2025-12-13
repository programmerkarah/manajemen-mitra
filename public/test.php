<?php
echo "✅ PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Path: " . __FILE__ . "<br>";
echo "Laravel Public Path: " . __DIR__ . "<br>";
echo "<br>";
echo "Checking Laravel...<br>";

if (file_exists(__DIR__ . '/../bootstrap/app.php')) {
    echo "✅ Laravel bootstrap found<br>";
} else {
    echo "❌ Laravel bootstrap NOT found<br>";
}

if (file_exists(__DIR__ . '/../.env')) {
    echo "✅ .env file found<br>";
} else {
    echo "❌ .env file NOT found<br>";
}

echo "<br><a href='/login'>Try Login Page</a><br>";
echo "<a href='/register'>Try Register Page</a><br>";
echo "<a href='/'>Try Home Page</a><br>";
