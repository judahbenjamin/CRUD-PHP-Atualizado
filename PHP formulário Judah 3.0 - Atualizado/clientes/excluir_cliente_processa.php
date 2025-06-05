<?php
// Inclua o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $idCliente = $_GET['id'];

    try {
        // Não é mais necessário buscar o nome da foto e do PDF para exclusão,
        // pois estamos fazendo um "soft delete" (desativação) e não exclusão física dos arquivos.
        // O cliente e seus arquivos permanecem, apenas o status é alterado.

        // Inicia uma transação para garantir a integridade dos dados (ainda é boa prática)
        $pdo->beginTransaction();

        // Prepara a consulta SQL para "desativar" o cliente (soft delete)
        // A coluna 'ativo' deve existir na sua tabela 'Clientes' e ser do tipo BOOLEAN (ou similar, ex: TINYINT(1) no MySQL)
        $sql_update_status = "UPDATE Clientes SET ativo = FALSE WHERE idCliente = :idCliente";
        $stmt_update_status = $pdo->prepare($sql_update_status);

        // Vincula o parâmetro
        $stmt_update_status->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);

        // Executa a consulta
        if ($stmt_update_status->execute()) {
            $pdo->commit(); // Confirma a transação
            // Redireciona com uma mensagem de sucesso indicando que o cliente foi desativado
            header('Location: listar_clientes.php?status=success_update&msg=' . urlencode('Cliente desativado com sucesso.'));
            exit();
        } else {
            $pdo->rollBack(); // Desfaz a transação em caso de erro
            // Se a execução falhar, pega o erro do PDO para depuração
            $errorInfo = $stmt_update_status->errorInfo();
            header('Location: listar_clientes.php?status=error_update&msg=' . urlencode('Erro ao desativar cliente: ' . ($errorInfo[2] ?? 'desconhecido')));
            exit();
        }
    } catch (PDOException $e) {
        $pdo->rollBack(); // Desfaz a transação em caso de erro
        // Captura exceções do PDO
        // Em um ambiente de produção, você logaria $e->getMessage() para análise.
        header('Location: listar_clientes.php?status=error_update&msg=' . urlencode('Erro no banco de dados ao desativar cliente: ' . $e->getMessage()));
        exit();
    }
} else {
    // Se o ID não foi fornecido ou é inválido
    header('Location: listar_clientes.php?status=error_update&msg=' . urlencode('ID de cliente inválido para desativação.'));
    exit();
}