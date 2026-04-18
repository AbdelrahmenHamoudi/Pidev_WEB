<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=re7la_3a9", "root", "");
    $stmt = $pdo->prepare("SELECT e_mail FROM users WHERE e_mail LIKE ?");
    $stmt->execute(['%iheb%']);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['e_mail'] . "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
