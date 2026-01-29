<?php

require 'vendor/autoload.php';

use OpenDBS\OpenDBS;

// 1. Initialize Client
$client = new OpenDBS('http://localhost:4402', null, [
    // 'ignoreSSL' => true // Uncomment for self-signed certificates
]);

echo "🚀 Starting OpenDBS PHP Client Example...\n";

try {
    // 2. Login
    echo "🔑 Logging in...\n";
    $login = $client->login('admin', 'admin123');
    echo "✅ Logged in as: " . $login['user']['username'] . "\n";

    // 3. Create Database
    echo "📂 Creating database 'php_demo_db'...\n";
    try {
        $client->createDatabase('php_demo_db');
        echo "   Database created.\n";
    } catch (Exception $e) {
        echo "   ℹ️  Database might already exist (" . $e->getMessage() . ")\n";
    }

    // 4. Create Racks
    echo "📦 Creating racks...\n";
    try {
        $client->createRack('php_demo_db', 'users', 'nosql');
        echo "   NoSQL Rack 'users' created.\n";
    } catch (Exception $e) {}

    try {
        $client->createRack('php_demo_db', 'orders', 'sql', [
            'id' => ['type' => 'number', 'required' => true],
            'total' => ['type' => 'number', 'required' => true]
        ]);
        echo "   SQL Rack 'orders' created.\n";
    } catch (Exception $e) {}

    // 5. Insert Data
    echo "📝 Inserting data...\n";
    $client->insert('php_demo_db', 'users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'role' => 'admin'
    ]);
    
    try {
        $client->sql('php_demo_db', "INSERT INTO orders (id, total) VALUES (101, 150.50)");
    } catch (Exception $e) {
        echo "⚠️ SQL Insert failed: " . $e->getMessage() . "\n";
    }
    echo "✅ Data inserted.\n";

    // 6. Search
    echo "🔍 Searching...\n";
    $users = $client->find('php_demo_db', 'users');
    echo "   Found " . count($users) . " users.\n";
    
    // 7. Fuzzy Search
    echo "⚡ Testing Fuzzy Search...\n";
    $fuzzy = $client->fuzzySearch('php_demo_db', 'users', 'name', 'Jne'); // Typo 'Jne'
    echo "   Fuzzy Results for 'Jne': " . json_encode($fuzzy) . "\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if ($e->getCode() === 0 && strpos($e->getMessage(), 'cURL error 7') !== false) {
         echo "   👉 Please ensure the OpenDBS server is running on port 4402.\n";
    }
} finally {
     // 8. Cleanup
     echo "\n🧹 Cleaning up resources...\n";
     try {
         $client->deleteRack('php_demo_db', 'users');
         echo "   🗑️ Deleted Rack: users\n";
     } catch (Exception $e) {}

     try {
         $client->deleteRack('php_demo_db', 'orders');
         echo "   🗑️ Deleted Rack: orders\n";
     } catch (Exception $e) {}

     try {
         $client->deleteDatabase('php_demo_db');
         echo "   🗑️ Deleted Database: php_demo_db\n";
     } catch (Exception $e) {}
     
     echo "\n✨ Example run finished.\n";
}
