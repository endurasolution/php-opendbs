<?php

namespace OpenDBS;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

class OpenDBS
{
    private $client;
    private $token;
    private $baseURL;
    private $options;

    public function __construct($baseURL, $token = null, $options = [])
    {
        $this->baseURL = rtrim($baseURL, '/');
        $this->token = $token;
        $this->options = $options;

        $guzzleConfig = [
            'base_uri' => $this->baseURL,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            // 'verify' => false, // Uncomment if you want to ignore SSL verify by default or pass in options
        ];

        if (isset($options['ignoreSSL']) && $options['ignoreSSL'] === true) {
            $guzzleConfig['verify'] = false;
        }

        if ($this->token) {
            $guzzleConfig['headers']['Authorization'] = 'Bearer ' . $this->token;
        }

        $this->client = new GuzzleClient($guzzleConfig);
    }

    public function setToken($token)
    {
        $this->token = $token;
        // Re-initialize client to update headers, or you could use middleware. 
        // Simple re-init for this example.
        $config = $this->client->getConfig();
        $config['headers']['Authorization'] = 'Bearer ' . $token;
        $this->client = new GuzzleClient($config);
    }

    private function request($method, $endpoint, $data = [], $query = [])
    {
        try {
            $options = [];
            if (!empty($data)) {
                $options['json'] = $data;
            }
            if (!empty($query)) {
                $options['query'] = $query;
            }

            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = json_decode($response->getBody(), true);
                throw new \Exception(isset($body['error']) ? $body['error'] : $e->getMessage(), $response->getStatusCode());
            }
            throw new \Exception($e->getMessage());
        }
    }

    // --- Authentication ---

    public function login($username, $password)
    {
        $response = $this->request('POST', '/api/auth/login', [
            'username' => $username,
            'password' => $password
        ]);

        if (isset($response['token'])) {
            $this->setToken($response['token']);
        }

        return $response;
    }

    public function register($userData)
    {
        return $this->request('POST', '/api/auth/register', $userData);
    }

    // --- Database Management ---

    public function createDatabase($name)
    {
        return $this->request('POST', '/api/databases', ['name' => $name]);
    }

    public function listDatabases($includeRacks = false)
    {
        $response = $this->request('GET', '/api/databases', [], ['include_racks' => $includeRacks ? 'true' : 'false']);
        return $response['databases'] ?? [];
    }

    public function deleteDatabase($name)
    {
        return $this->request('DELETE', "/api/databases/{$name}");
    }

    // --- Rack Management ---

    public function createRack($database, $name, $type = 'nosql', $schema = null)
    {
        $data = [
            'name' => $name,
            'type' => $type
        ];
        if ($schema) {
            $data['schema'] = $schema;
        }
        return $this->request('POST', "/api/databases/{$database}/racks", $data);
    }

    public function listRacks($database)
    {
        $response = $this->request('GET', "/api/databases/{$database}/racks");
        return $response['racks'] ?? [];
    }

    public function deleteRack($database, $rack)
    {
        return $this->request('DELETE', "/api/databases/{$database}/racks/{$rack}");
    }

    // --- Documents (NoSQL) ---

    public function insert($database, $rack, $document)
    {
        return $this->request('POST', "/api/databases/{$database}/racks/{$rack}/documents", $document);
    }

    public function find($database, $rack, $query = [], $populate = false)
    {
        if ($populate) {
            $query['populate'] = 'true';
        }
        $response = $this->request('GET', "/api/databases/{$database}/racks/{$rack}/documents", [], $query);
        return $response['results'] ?? [];
    }

    public function findOne($database, $rack, $id, $populate = false)
    {
        $query = ['id' => $id];
        if ($populate) {
            $query['populate'] = 'true';
        }
        $response = $this->request('GET', "/api/databases/{$database}/racks/{$rack}/documents", [], $query);
        $results = $response['results'] ?? [];
        return !empty($results) ? $results[0] : null;
    }

    public function update($database, $rack, $id, $updates)
    {
        return $this->request('PUT', "/api/databases/{$database}/racks/{$rack}/documents/{$id}", $updates);
    }

    public function delete($database, $rack, $id)
    {
        return $this->request('DELETE', "/api/databases/{$database}/racks/{$rack}/documents/{$id}");
    }

    // --- SQL Operations ---

    public function sql($database, $query)
    {
        $response = $this->request('POST', "/api/sql/{$database}/execute", ['query' => $query]);
        return $response['results'] ?? $response;
    }

    // --- Search Features ---

    public function search($database, $rack, $queryBody)
    {
        $response = $this->request('POST', "/api/databases/{$database}/racks/{$rack}/search", $queryBody);
        return $response['results'] ?? [];
    }

    public function fuzzySearch($database, $rack, $field, $query)
    {
        $response = $this->request('POST', "/api/databases/{$database}/racks/{$rack}/search/fuzzy", [
            'field' => $field,
            'query' => $query
        ]);
        return $response['results'] ?? [];
    }

    public function vectorSearch($database, $rack, $field, $vector, $k = 5)
    {
        $response = $this->request('POST', "/api/databases/{$database}/racks/{$rack}/search/vector", [
            'field' => $field,
            'vector' => $vector,
            'k' => $k
        ]);
        return $response['results'] ?? [];
    }

    // --- Backup ---

    public function createBackup()
    {
        return $this->request('POST', '/api/backup/create');
    }

    public function listBackups()
    {
        $response = $this->request('GET', '/api/backup/list');
        return $response['backups'] ?? [];
    }

    public function quickBackupRackUrl($database, $rack)
    {
        // Returns the URL for download, as this usually returns a binary stream/zip
        return $this->baseURL . "/api/backup/quick?database={$database}&rack={$rack}";
    }
}
