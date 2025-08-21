<?php

/**
 * Simple NovaPay Configuration
 */

return [
  // Sandbox configuration
  'sandbox' => [
    'merchant_id' => 'test_merchant_123',
    'private_key' => __DIR__ . '/keys/private.key',
    'public_key' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtJjoMALd2ywDYK0BCUVS
8hTgkSS6InosHMLe9SC6DLV20ouJggZvBt42X0VlqqN+PvE9xEMnIUW6FDC06D+8
CkfZuYBkt7mFDykeZXhhfWEj94LaoWCc1EvotgZ2y9KxjCNsefRTloctNB5F63dx
TLReatz/dhuSxxPIuuZQYdLBXbfUkxSE4XKb5rREiqBdCpfj1mZ3AliYy9GsmA11
u+n8x2ocCBed6P4WdBnpRuctRU6ed1s7IZu6e1slIlNeyAb7XCEanfK3PisTZcvv
XvN6stL3XuICuOpfVAtyGzzIq2J1h2Ha2ydJY2l1MmmvzyNu/PPZF5WzQ0k08PJU
rwIDAQAB
-----END PUBLIC KEY-----',
    'sandbox' => true
  ],

  // Production configuration
  'production' => [
    'merchant_id' => 'your_production_merchant_id',
    'private_key' => '/path/to/production/private.key',
    'public_key' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtJjoMALd2ywDYK0BCUVS
8hTgkSS6InosHMLe9SC6DLV20ouJggZvBt42X0VlqqN+PvE9xEMnIUW6FDC06D+8
CkfZuYBkt7mFDykeZXhhfWEj94LaoWCc1EvotgZ2y9KxjCNsefRTloctNB5F63dx
TLReatz/dhuSxxPIuuZQYdLBXbfUkxSE4XKb5rREiqBdCpfj1mZ3AliYy9GsmA11
u+n8x2ocCBed6P4WdBnpRuctRU6ed1s7IZu6e1slIlNeyAb7XCEanfK3PisTZcvv
XvN6stL3XuICuOpfVAtyGzzIq2J1h2Ha2ydJY2l1MmmvzyNu/PPZF5WzQ0k08PJU
rwIDAQAB
-----END PUBLIC KEY-----',
    'sandbox' => false
  ],

  // Test cards for sandbox
  'test_cards' => [
    ['pan' => '5269610000007956', 'exp' => '05/24', 'cvv' => '755', 'status' => 'Active'],
    ['pan' => '4134170000013005', 'exp' => '11/24', 'cvv' => '704', 'status' => 'Active'],
    ['pan' => '4134170000013088', 'exp' => '11/24', 'cvv' => '045', 'status' => 'Lost']
  ],

  // Test delivery addresses
  'test_addresses' => [
    'kyiv' => [
      'city' => '7b422fc5-e1b8-11e3-8c4a-0050568002cf',
      'warehouse' => '8d5a980d-391c-11dd-90d9-001a92567626'
    ],
    'odessa' => [
      'city' => 'db5c88d0-391c-11dd-90d9-001a92567626',
      'warehouse' => '1692286e-e1c2-11e3-8c4a-0050568002cf'
    ]
  ]
];
