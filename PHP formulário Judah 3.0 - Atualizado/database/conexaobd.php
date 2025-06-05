<?php
$host = 'localhost';
$db   = 'judah_crud'; 
$user = 'root';    
$pass = '';          
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // É mais seguro logar o erro em produção em vez de exibi-lo diretamente
    // error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
    die("Conexão falhou: " . $e->getMessage()); 
}
?>