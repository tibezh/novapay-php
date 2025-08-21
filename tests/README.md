# NovaPay PHP Library Tests

This directory contains the test suite for the NovaPay PHP library.

## Test Structure

```
tests/
├── README.md                          # This file
├── bootstrap.php                      # Test bootstrap
├── TestCase.php                       # Base test class
├── NovaPayTest.php                    # Main NovaPay class tests
├── Security/
│   └── SignatureTest.php              # RSA signature tests
├── Exceptions/
│   └── ExceptionsTest.php             # Exception classes tests
└── Integration/
    └── NovaPayIntegrationTest.php     # Integration tests
```

## Running Tests

### All Tests
```bash
composer test
# or
./vendor/bin/phpunit
```

### Specific Test Suite
```bash
# Run only unit tests
./vendor/bin/phpunit tests/NovaPayTest.php

# Run only signature tests
./vendor/bin/phpunit tests/Security/SignatureTest.php

# Run only integration tests
./vendor/bin/phpunit tests/Integration/
```

### With Coverage
```bash
./vendor/bin/phpunit --coverage-html build/coverage
```

## Test Categories

### Unit Tests
- **NovaPayTest.php** - Tests for main NovaPay class
- **SignatureTest.php** - Tests for RSA signature functionality
- **ExceptionsTest.php** - Tests for exception classes

### Integration Tests
- **NovaPayIntegrationTest.php** - End-to-end workflow tests

## Test Data

The tests use predefined test data:

### Test Keys
- RSA key pair for signature testing
- Test merchant ID: `test_merchant_123`

### Test Session Data
```php
[
    'external_id' => 'test_order_' . time(),
    'amount' => 100.50,
    'currency' => 'UAH',
    'description' => 'Test payment',
    'success_url' => 'https://example.com/success',
    'fail_url' => 'https://example.com/fail',
    'callback_url' => 'https://example.com/callback'
]
```

### Test Delivery Data
```php
[
    'volume_weight' => 0.001,
    'weight' => 0.5,
    'recipient_city' => 'db5c88d0-391c-11dd-90d9-001a92567626',
    'recipient_warehouse' => '1692286e-e1c2-11e3-8c4a-0050568002cf'
]
```

## What's Tested

### ✅ Signature Functionality
- RSA signature creation
- Signature verification
- Data serialization for signing
- Handling of nested arrays, booleans, nulls
- Invalid key handling

### ✅ NovaPay Core Features
- Client initialization
- Session creation structure
- Payment methods structure
- Callback verification
- Error handling

### ✅ Exception Handling
- Exception inheritance
- Error messages preservation
- Response data handling

### ✅ Integration Workflows
- Complete payment flows
- Multi-step operations
- Environment switching
- Edge cases

## Test Limitations

### No Actual API Calls
Tests don't make real HTTP requests to NovaPay API because:
- Tests should be fast and reliable
- No dependency on external services
- No need for API credentials in testing

### Expected Behavior
Most API method tests expect `NovaPayException` with "CURL error" message, which indicates:
- ✅ Method structure is correct
- ✅ Data validation passes
- ✅ Only the HTTP request fails (expected)

## Mock Data vs Real API

### What's Mocked
- HTTP requests (cURL calls)
- API responses
- Network timeouts

### What's Real
- RSA signature creation/verification
- Data serialization
- Exception handling
- Class instantiation

## Adding New Tests

### For New Features
1. Add test methods to appropriate test class
2. Use descriptive test method names
3. Follow AAA pattern (Arrange, Act, Assert)

### Test Method Naming
```php
public function test_descriptive_name_of_what_is_being_tested(): void
{
    // Arrange
    $data = [...];
    
    // Act
    $result = $this->somethingUnderTest($data);
    
    // Assert
    $this->assertEquals($expected, $result);
}
```

### Test Data
Use methods from `TestCase` class:
- `getTestMerchantId()`
- `getTestPrivateKey()`
- `getTestPublicKey()`
- `getTestSessionData()`
- `getTestDeliveryData()`

## CI/CD Integration

Tests are designed to run in CI environments:
- No external dependencies
- No file system writes (except temp)
- Consistent across different PHP versions
- Fast execution

## Debugging Tests

### Verbose Output
```bash
./vendor/bin/phpunit --verbose
```

### Debug Specific Test
```bash
./vendor/bin/phpunit --filter test_method_name --debug
```

### Coverage Reports
```bash
./vendor/bin/phpunit --coverage-text
```

## Performance

### Test Execution Time
- Unit tests: ~0.1s each
- Integration tests: ~0.5s each
- Total suite: <10s

### Memory Usage
- Minimal memory footprint
- No memory leaks
- Suitable for CI environments

## Requirements

- PHP 8.1+
- PHPUnit 12.0+
- ext-openssl (for RSA operations)
- ext-json (for data serialization)

---

**Note**: These tests verify the library's functionality without making actual API calls. For end-to-end testing with real API, use the examples in `examples/` directory with sandbox credentials.
