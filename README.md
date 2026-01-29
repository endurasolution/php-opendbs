# OpenDBS PHP Client

Official PHP client library for OpenDBS.

## Installation

Using Composer:

```bash
composer require opendbs/client
```

## Usage

### Initialization

```php
require 'vendor/autoload.php';

use OpenDBS\OpenDBS;

// Initialize with Base URL
$client = new OpenDBS('http://localhost:4402');

// Login
$client->login('admin', 'admin123');
```

### Basic Operations

```php
// Create Database
$client->createDatabase('shop');

// Create Racks
$client->createRack('shop', 'products', 'sql', [
    'name' => ['type' => 'string'],
    'price' => ['type' => 'number']
]);
$client->createRack('shop', 'users', 'nosql');

// Insert Data
$client->insert('shop', 'users', ['name' => 'Alice']);
$client->sql('shop', "INSERT INTO products (name, price) VALUES ('Laptop', 999)");

// Find Data
$users = $client->find('shop', 'users', ['name' => 'Alice']);
```

### Advanced Search

```php
// Fuzzy Search
$results = $client->fuzzySearch('shop', 'users', 'name', 'Alice');

// Vector Search
$results = $client->vectorSearch('shop', 'products', 'embedding', [0.1, 0.2, 0.3]);
```
