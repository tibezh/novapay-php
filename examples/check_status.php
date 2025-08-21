<?php

/**
 * Check Payment Status Example
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tibezh\NovapayPhp\NovaPay;
use Tibezh\NovapayPhp\Exceptions\NovaPayException;

// Configuration
$config = require __DIR__ . '/config.php';

try {
    $novaPay = new NovaPay(
        merchantId: $config['sandbox']['merchant_id'],
        privateKey: $config['sandbox']['private_key'],
        publicKey: $config['sandbox']['public_key'],
        sandbox: $config['sandbox']['sandbox']
    );

    // Replace with actual session ID
    $sessionId = 'your_session_id_here';

    echo "Checking payment status for session: {$sessionId}\n";

    $status = $novaPay->getStatus($sessionId);

    echo "Status: {$status['status']}\n";
    echo "Amount: {$status['amount']} {$status['currency']}\n";
    echo "External ID: {$status['external_id']}\n";

} catch (NovaPayException $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
