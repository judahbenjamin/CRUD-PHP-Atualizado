<?php
// Inclui o arquivo de conexão com o banco de dados
require_once ('../database/conexaobd.php');

if (isset($_GET['id'])) {
    $idProduto = $_GET['id']; // O 'id' na URL é o idProduto

    try {
        // Prepara a consulta SQL para excluir o produto
        $stmt = $pdo->prepare('DELETE FROM produtos WHERE idProduto = :idProduto');
        $stmt->bindParam(':idProduto', $idProduto);

        // Executa a declaração
        if ($stmt->execute()) {
            header('Location: pesquisar_produto.php?status=success');
            exit();
        } else {
            error_log("Erro ao excluir produto (idProduto: $idProduto): Falha na execução da query.");
            header('Location: pesquisar_produto.php?status=error');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erro ao excluir produto (idProduto: $idProduto): " . $e->getMessage());
        header('Location: pesquisar_produto.php?status=error');
        exit();
    }
} else {
    header('Location: pesquisar_produto.php?status=error');
    exit();
}
?>