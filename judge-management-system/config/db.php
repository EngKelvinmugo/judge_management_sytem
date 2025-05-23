<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'judge_management_system';
$username = 'root';
$password = '';

try {
    // Create database connection with error handling
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set charset
    $pdo->exec("SET NAMES utf8mb4");
    
    // Test the connection
    $stmt = $pdo->query("SELECT 1");
    
} catch(PDOException $e) {
    // More detailed error information
    $error_message = "Database Connection Error: " . $e->getMessage();
    $error_message .= "\nHost: $host";
    $error_message .= "\nDatabase: $dbname";
    $error_message .= "\nUsername: $username";
    
    // Log the error
    error_log($error_message);
    
    // Display user-friendly error
    die("
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ff6b6b; background: #ffe0e0; border-radius: 8px;'>
        <h2 style='color: #d63031; margin-bottom: 15px;'>Database Connection Failed</h2>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>Please check:</strong></p>
        <ul>
            <li>MySQL server is running</li>
            <li>Database '$dbname' exists</li>
            <li>Username and password are correct</li>
            <li>Host '$host' is accessible</li>
        </ul>
        <p><strong>To create the database, run:</strong></p>
        <code style='background: #f8f9fa; padding: 10px; display: block; border-radius: 4px;'>
            CREATE DATABASE $dbname;<br>
            USE $dbname;<br>
            -- Then import the database.sql file
        </code>
    </div>
    ");
}
?>