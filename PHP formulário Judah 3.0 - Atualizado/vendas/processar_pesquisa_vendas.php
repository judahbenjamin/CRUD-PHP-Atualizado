<?php
// Inclua o arquivo de conexão com o banco de dados
require_once ('../database/conexaobd.php');

$resultados = [];
$mensagem = '';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['tipo_pesquisa'])) {
        $tipo_pesquisa = $_GET['tipo_pesquisa'];

        if ($tipo_pesquisa === 'cliente' && isset($_GET['cliente']) && !empty($_GET['cliente'])) {
            $idCliente = $_GET['cliente'];
            $sql = "SELECT v.idVenda, c.nome AS nomeCliente, p.descricao AS nomeProduto, v.qtdeVendida, v.precoTotal, v.dataVenda, fp.descricao AS formaPagto
                    FROM Vendas v
                    INNER JOIN Clientes c ON v.idCliente = c.idCliente
                    INNER JOIN Produtos p ON v.idProduto = p.idProduto
                    LEFT JOIN FormasPagamento fp ON v.idFormaPagto = fp.idFormaPagto
                    WHERE v.idCliente = :idCliente
                    ORDER BY v.dataVenda DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($resultados) === 0) {
                $mensagem = "Nenhuma venda encontrada para este cliente.";
            }
        } elseif ($tipo_pesquisa === 'produto' && isset($_GET['produto']) && !empty($_GET['produto'])) {
            $idProduto = $_GET['produto'];
            $sql = "SELECT v.idVenda, c.nome AS nomeCliente, p.descricao AS nomeProduto, v.qtdeVendida, v.precoTotal, v.dataVenda, fp.descricao AS formaPagto
                    FROM Vendas v
                    INNER JOIN Clientes c ON v.idCliente = c.idCliente
                    INNER JOIN Produtos p ON v.idProduto = p.idProduto
                    LEFT JOIN FormasPagamento fp ON v.idFormaPagto = fp.idFormaPagto
                    WHERE v.idProduto = :idProduto
                    ORDER BY v.dataVenda DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':idProduto', $idProduto, PDO::PARAM_INT);
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($resultados) === 0) {
                $mensagem = "Nenhuma venda encontrada para este produto.";
            }
        } else {
            $mensagem = "Por favor, selecione um cliente ou um produto para pesquisar.";
        }
    } else {
        $mensagem = "Nenhum critério de pesquisa selecionado.";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados da Pesquisa</title>
    <link rel="stylesheet" href="../css/pesquisar_vendas.css">
</head>
<body>
    <div class="container-resultados">
        <h1>Resultados da Pesquisa</h1>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem"><?php echo $mensagem; ?></p>
        <?php endif; ?>

        <?php if (!empty($resultados)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Venda</th>
                        <th>Cliente</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Total</th>
                        <th>Data da Venda</th>
                        <th>Forma de Pagamento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $venda): ?>
                        <tr>
                            <td><?php echo $venda['idVenda']; ?></td>
                            <td><?php echo $venda['nomeCliente']; ?></td>
                            <td><?php echo $venda['nomeProduto']; ?></td>
                            <td><?php echo $venda['qtdeVendida']; ?></td>
                            <td><?php echo number_format($venda['precoTotal'], 2, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($venda['dataVenda'])); ?></td>
                            <td><?php echo $venda['formaPagto'] ? $venda['formaPagto'] : 'idFormaPagto'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p><a href="pesquisar_vendas.php">Nova Pesquisa</a></p>
        <p><a href="../menu/index.html">Voltar à Página Inicial</a></p>
        <p><a href="../vendas/listar_vendas.php">Ver Lista de Vendas</a></p>
    </div>
</body>
</html>