<?php
// Inclua o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php'); // Ajuste o caminho se necessário

$venda = null;
$clientes = [];
$produtos = [];
$formas_pagamento = [];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $idVenda = $_GET['id'];

    // Buscar os dados da venda para pré-preencher o formulário
    $sql_venda = "SELECT idVenda, idCliente, idProduto, qtdeVendida, precoTotal, dataVenda, idFormaPagto FROM Vendas WHERE idVenda = :idVenda";
    $stmt_venda = $pdo->prepare($sql_venda);
    $stmt_venda->bindParam(':idVenda', $idVenda, PDO::PARAM_INT);
    $stmt_venda->execute();
    $venda = $stmt_venda->fetch(PDO::FETCH_ASSOC);

    if (!$venda) {
        header('Location: listar_vendas.php?status=error_update&msg=not_found');
        exit();
    }

    // Buscar todos os clientes para o combobox
    $sql_clientes = "SELECT idCliente, nome FROM Clientes ORDER BY nome";
    $stmt_clientes = $pdo->query($sql_clientes);
    $clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

    // Buscar todos os produtos para o combobox
    $sql_produtos = "SELECT idProduto, descricao FROM Produtos ORDER BY descricao";
    $stmt_produtos = $pdo->query($sql_produtos);
    $produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);

    // Buscar todas as formas de pagamento para o combobox
    $sql_formas_pagamento = "SELECT idFormaPagto, descricao FROM FormasPagamento ORDER BY descricao";
    $stmt_formas_pagamento = $pdo->query($sql_formas_pagamento);
    $formas_pagamento = $stmt_formas_pagamento->fetchAll(PDO::FETCH_ASSOC);

} else {
    header('Location: listar_vendas.php?status=error_update&msg=no_id');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Venda</title>
    <link rel="stylesheet" href="../css/cadastrar_venda.css"> </head>
<body>
    <div class="container-venda">
        <h1>Alterar Venda</h1>
        <div class="form-divider"></div>
        <form action="alterar_venda_processa.php" method="post">
            <input type="hidden" name="idVenda" value="<?php echo $venda['idVenda']; ?>">

            <div class="form-group">
                <label for="idCliente">Cliente:</label>
                <select name="idCliente" id="idCliente" required>
                    <option value="">Selecione um cliente</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?php echo $cliente['idCliente']; ?>" <?php echo ($cliente['idCliente'] == $venda['idCliente']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cliente['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="idProduto">Produto:</label>
                <select name="idProduto" id="idProduto" required>
                    <option value="">Selecione um produto</option>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?php echo $produto['idProduto']; ?>" <?php echo ($produto['idProduto'] == $venda['idProduto']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($produto['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="qtdeVendida">Quantidade Vendida:</label>
                <input type="number" id="qtdeVendida" name="qtdeVendida" min="1" value="<?php echo htmlspecialchars($venda['qtdeVendida']); ?>" required>
            </div>
            <div class="form-group">
                <label for="precoTotal">Preço Total:</label>
                <input type="number" id="precoTotal" name="precoTotal" step="0.01" value="<?php echo htmlspecialchars($venda['precoTotal']); ?>" required>
            </div>
            <div class="form-group">
                <label for="dataVenda">Data da Venda:</label>
                <input type="date" id="dataVenda" name="dataVenda" value="<?php echo htmlspecialchars($venda['dataVenda']); ?>" required>
            </div>
            <div class="form-group">
                <label for="idFormaPagto">Forma de Pagamento:</label>
                <select name="idFormaPagto" id="idFormaPagto" required>
                    <option value="">Selecione a forma de pagamento</option>
                    <?php if (isset($formas_pagamento) && $formas_pagamento): ?>
                        <?php foreach ($formas_pagamento as $forma): ?>
                            <option value="<?php echo $forma["idFormaPagto"]; ?>" <?php echo ($forma["idFormaPagto"] == $venda['idFormaPagto']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($forma["descricao"]); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="1" <?php echo (1 == $venda['idFormaPagto']) ? 'selected' : ''; ?>>Dinheiro</option>
                        <option value="2" <?php echo (2 == $venda['idFormaPagto']) ? 'selected' : ''; ?>>Cartão de Crédito</option>
                        <option value="3" <?php echo (3 == $venda['idFormaPagto']) ? 'selected' : ''; ?>>Cartão de Débito</option>
                        <option value="4" <?php echo (4 == $venda['idFormaPagto']) ? 'selected' : ''; ?>>Boleto Bancário</option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="submit-button">Salvar Alterações</button>
        </form>
        <div class="button-container">
            <a href="listar_vendas.php">Voltar à Lista de Vendas</a>
            <a href="../menu/index.html">Voltar à Página Inicial</a>
        </div>
    </div>
</body>
</html>