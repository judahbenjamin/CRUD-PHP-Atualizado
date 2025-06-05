<?php
// Inclua o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php');

$sql = "SELECT
            v.idVenda,
            c.nome AS nomeCliente,
            p.descricao AS nomeProduto,
            v.qtdeVendida,
            v.precoTotal,
            v.dataVenda,
            fp.descricao AS nomeFormaPagamento -- Nome da coluna para a descrição da forma de pagamento
        FROM
            Vendas v
        INNER JOIN
            Clientes c ON v.idCliente = c.idCliente
        INNER JOIN
            Produtos p ON v.idProduto = p.idProduto
        LEFT JOIN
            FormasPagamento fp ON v.idFormaPagto = fp.idFormaPagto -- CORRIGIDO AQUI: v.idFormaPagto
        ORDER BY
            v.idVenda ASC";

$stmt = $pdo->query($sql);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Vendas</title>
    <link rel="stylesheet" href="../css/listar_vendas.css">
    <style>
        /* Adicione ou ajuste seus estilos CSS */
        .container {
            width: 90%;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .acoes {
            width: 150px; /* Ajuste conforme necessário */
            text-align: center;
        }
        .btn-alterar, .btn-excluir {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            text-decoration: none;
        }
        .btn-alterar { background-color: #007bff; }
        .btn-excluir { background-color: #dc3545; }
        .button-container {
            margin-top: 20px;
            text-align: center;
        }
        .button-container a {
            display: inline-block;
            padding: 10px 15px;
            margin: 0 10px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .button-container a:hover {
            background-color: #5a6268;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .mensagem {
            text-align: center;
            margin-top: 20px;
            font-style: italic;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Lista de Vendas</h1>
        <div class="form-divider"></div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'success_delete'): ?>
                <div class="success-message">Venda excluída com sucesso!</div>
            <?php elseif ($_GET['status'] === 'error_delete'): ?>
                <div class="error-message">Erro ao excluir venda.</div>
            <?php elseif ($_GET['status'] === 'success_update'): ?>
                <div class="success-message">Venda atualizada com sucesso!</div>
            <?php elseif ($_GET['status'] === 'error_update'): ?>
                <div class="error-message">Erro ao atualizar venda.</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($vendas)): ?>
            <p class="mensagem">Nenhuma venda cadastrada.</p>
        <?php else: ?>
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
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($venda['idVenda']); ?></td>
                            <td><?php echo htmlspecialchars($venda['nomeCliente']); ?></td>
                            <td><?php echo htmlspecialchars($venda['nomeProduto']); ?></td>
                            <td><?php echo htmlspecialchars($venda['qtdeVendida']); ?></td>
                            <td>R$ <?php echo number_format($venda['precoTotal'], 2, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($venda['dataVenda'])); ?></td>
                            <td><?php echo htmlspecialchars($venda['nomeFormaPagamento'] ?? 'N/A'); ?></td>
                            <td class="acoes">
                                <a href="alterar_venda.php?id=<?php echo htmlspecialchars($venda['idVenda']); ?>" class="btn-alterar">Alterar</a>
                                <a href="excluir_venda_processa.php?id=<?php echo htmlspecialchars($venda['idVenda']); ?>" class="btn-excluir" onclick="return confirm('Tem certeza que deseja excluir esta venda?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="button-container">
            <a href="cadastrar_venda.php">Cadastrar Nova Venda</a>
            <a href="pesquisar_vendas.php">Pesquisar Vendas</a>
            <a href="../menu/index.html">Voltar à Página Inicial</a>
        </div>
    </div>
</body>
</html>