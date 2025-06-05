<?php
// Inclua o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php');

header('Content-Type: application/json'); // Garante que a resposta será JSON

// CORREÇÃO AQUI: Substituir FILTER_SANITIZE_STRING por FILTER_UNSAFE_RAW
$cpf = filter_input(INPUT_POST, 'cpf', FILTER_UNSAFE_RAW);

$response = [
    'exists' => false,
    'message' => ''
];

if (!$cpf) {
    $response['message'] = 'CPF não fornecido.';
    echo json_encode($response);
    exit();
}

// Remove caracteres não numéricos do CPF
$cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);

try {
    // Prepara a consulta SQL para verificar se o CPF já existe
    $sql = "SELECT COUNT(*) FROM Clientes WHERE cpf = :cpf";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':cpf', $cpf_limpo, PDO::PARAM_STR);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $response['exists'] = true;
        $response['message'] = 'Este CPF já está cadastrado.';
    } else {
        $response['message'] = 'CPF disponível.';
    }

} catch (PDOException $e) {
    // Em caso de erro no banco de dados, retorne uma mensagem de erro
    $response['message'] = 'Erro ao verificar CPF: ' . $e->getMessage();
    // Em um ambiente de produção, você logaria o erro detalhado: error_log($e->getMessage());
}

// Retorna a resposta como JSON
echo json_encode($response);

// Fecha a conexão (opcional, pois o script vai terminar)
$pdo = null;
?>