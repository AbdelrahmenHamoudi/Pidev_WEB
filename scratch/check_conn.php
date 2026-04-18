<?php
try {
    $pdo = new PDO("mysql:host=localhost;port=3306;dbname=re7la_3a9", "root", "");
    echo "Connection successful!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
