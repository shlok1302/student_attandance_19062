<?php
if (!extension_loaded('mongodb')) {
    http_response_code(500);
    exit('PHP MongoDB extension is missing. Enable it in the XAMPP php.ini file and restart Apache.');
}

function mongo_find(string $collection, array $filter = [], array $options = []): array
{
    global $mongoManager;

    $cursor = $mongoManager->executeQuery(
        'attendance_db.' . $collection,
        new MongoDB\Driver\Query($filter, $options)
    );
    $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

    return $cursor->toArray();
}

function mongo_count(string $collection, array $filter = []): int
{
    global $mongoManager;

    $result = $mongoManager->executeCommand(
        'attendance_db',
        new MongoDB\Driver\Command(['count' => $collection, 'query' => (object) $filter])
    )->toArray();

    return isset($result[0]->n) ? (int) $result[0]->n : 0;
}

function mongo_insert(string $collection, array $document): void
{
    global $mongoManager;

    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->insert($document);
    $mongoManager->executeBulkWrite('attendance_db.' . $collection, $bulk);
}

function mongo_update_one(string $collection, array $filter, array $update, array $options = []): void
{
    global $mongoManager;

    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update($filter, $update, ['multi' => false, 'upsert' => $options['upsert'] ?? false]);
    $mongoManager->executeBulkWrite('attendance_db.' . $collection, $bulk);
}

function mongo_delete(string $collection, array $filter, bool $multi = false): void
{
    global $mongoManager;

    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->delete($filter, ['limit' => $multi ? 0 : 1]);
    $mongoManager->executeBulkWrite('attendance_db.' . $collection, $bulk);
}

$mongoUri = getenv('MONGODB_URI') ?: 'mongodb://127.0.0.1:27017';

try {
    $mongoManager = new MongoDB\Driver\Manager($mongoUri);
    $mongoManager->executeCommand('admin', new MongoDB\Driver\Command(['ping' => 1]));
} catch (Throwable $e) {
    error_log('MongoDB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not connect to MongoDB. Start MongoDB and check the MONGODB_URI setting.');
}
