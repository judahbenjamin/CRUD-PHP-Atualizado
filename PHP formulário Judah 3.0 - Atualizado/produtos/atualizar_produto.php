<?php
// Inclui o arquivo de conexão com o banco de dados
require_once ('../database/conexaobd.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // O 'id' recebido é o 'idProduto'
    $idProduto = $_POST['id'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $qtdeEstoque = $_POST['qtdeEstoque'];
    $dataValidade = $_POST['dataValidade'];

    // Prepara uma SQL query para atualizar o produto
    $stmt = $pdo->prepare(
        'UPDATE produtos SET
        descricao = :descricao,
        preco = :preco,
        qtdeEstoque = :qtdeEstoque,
        dataValidade = :dataValidade
        WHERE idProduto = :idProduto' // Usamos idProduto aqui
    );

    // Bind parameters
    $stmt->bindParam(':descricao', $descricao);
    $stmt->bindParam(':preco', $preco);
    $stmt->bindParam(':qtdeEstoque', $qtdeEstoque);
    $stmt->bindParam(':dataValidade', $dataValidade);
    $stmt->bindParam(':idProduto', $idProduto); // Vincula o idProduto

    // Execute the statement
    if ($stmt->execute()) {
        header('Location: pesquisar_produto.php?status=success'); // Redireciona para pesquisar_produto.php
        exit();
    } else {
        header('Location: editar_produto.php?id=' . $idProduto . '&status=error');
        exit();
    }
} else {
    // If the request method is not POST, redirect or show an error
    header('Location: pesquisar_produto.php'); // Redireciona para pesquisar_produto.php
    exit();
}
?>