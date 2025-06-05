<?php
// Inclua o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php'); // Ajuste o caminho se necessário

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $idVenda = $_GET['id'];

    try {
        // Prepara a consulta SQL para exclusão
        $sql = "DELETE FROM Vendas WHERE idVenda = :idVenda";
        $stmt = $pdo->prepare($sql);

        // Vincula o parâmetro
        $stmt->bindParam(':idVenda', $idVenda, PDO::PARAM_INT);

        // Executa a consulta
        if ($stmt->execute()) {
            // Redireciona de volta para a lista com mensagem de sucesso
            header('Location: listar_vendas.php?status=success_delete');
            exit();
        } else {
            // Redireciona com mensagem de erro
            header('Location: listar_vendas.php?status=error_delete');
            exit();
        }
    } catch (PDOException $e) {
        // Você pode adicionar um log de erro aqui ($e->getMessage())
        header('Location: listar_vendas.php?status=error_delete');
        exit();
    }
} else {
    // Se o ID não foi fornecido
    header('Location: listar_vendas.php?status=error_delete');
    exit();
}