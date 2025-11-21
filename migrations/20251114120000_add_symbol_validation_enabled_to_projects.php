<?php

use MongoDB\Client;

// Подключаем автозагрузчик
require_once __DIR__ . '/../vendor/autoload.php';

// Читаем переменные окружения
$mongoUri = $_ENV['MONGO_URI'] ?? getenv('MONGO_URI');
$mongoDbName = $_ENV['MONGO_DB_NAME'] ?? getenv('MONGO_DB_NAME') ?: 'lokilizer'; // Укажите имя базы, если не 'lokilizer'

if (!$mongoUri) {
    throw new RuntimeException('Environment variable MONGO_URI is required for migration.');
}

// Создаём клиент и подключаемся к базе
$client = new Client($mongoUri);
$database = $client->selectDatabase($mongoDbName);
$collection = $database->selectCollection('projects');

// Обновляем все документы, у которых нет поля 'symbolValidationEnabled'
$result = $collection->updateMany(
    ['symbolValidationEnabled' => ['$exists' => false]], // Условие: поле не существует
    ['$set' => ['symbolValidationEnabled' => true]]      // Установить значение по умолчанию
);

echo "Migration completed: Updated {$result->getModifiedCount()} project documents.\n";