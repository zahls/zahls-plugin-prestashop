<?php
spl_autoload_register(function ($class) {
    if (strncmp('Zahls\\ZahlsPaymentGateway\\', $class, 26) === 0) {
        $relative = substr($class, 26);
        $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
    if (strncmp('Zahls\\', $class, 6) === 0) {
        $file = __DIR__ . '/zahls/zahls-php/lib/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});
