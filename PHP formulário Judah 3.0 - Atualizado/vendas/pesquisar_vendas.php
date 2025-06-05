<?php
// Inclua o arquivo de conexão com o banco de dados
require_once ('../database/conexaobd.php');

// Buscar todos os clientes para o combobox
$sql_clientes = "SELECT idCliente, nome FROM Clientes WHERE ativo = 1 ORDER BY nome";
$stmt_clientes = $pdo->query($sql_clientes);
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Buscar todos os produtos para o combobox
$sql_produtos = "SELECT idProduto, descricao FROM Produtos WHERE ativo = 1 ORDER BY descricao";
$stmt_produtos = $pdo->query($sql_produtos);
$produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisar Vendas</title>
    <link rel="stylesheet" href="../css/pesquisar_vendas.css">
</head>
<body>
    <div class="container-pesquisa">
        <h1>Pesquisar Vendas</h1>
        <form action="processar_pesquisa_vendas.php" method="get">
            <div>
                <label for="tipo_pesquisa">Pesquisar por:</label>
                <select name="tipo_pesquisa" id="tipo_pesquisa">
                    <option value="cliente">Cliente</option>
                    <option value="produto">Produto</option>
                </select>
            </div>

            <div id="div_cliente" class="campo_pesquisa">
                <label for="cliente">Selecione o Cliente:</label>
                <select name="cliente" id="cliente">
                    <option value="">-- Selecione --</option>
                    <?php
                    foreach ($clientes as $cliente) {
                        echo '<option value="' . $cliente["idCliente"] . '">' . $cliente["nome"] . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div id="div_produto" class="campo_pesquisa" style="display: none;">
                <label for="produto">Selecione o Produto:</label>
                <select name="produto" id="produto">
                    <option value="">-- Selecione --</option>
                    <?php
                    foreach ($produtos as $produto) {
                        echo '<option value="' . $produto["idProduto"] . '">' . $produto["descricao"] . '</option>';
                    }
                    ?>
                </select>
            </div>

            <button type="submit">Pesquisar</button>
        </form>
        <p><a href="../menu/index.html">Voltar à Página Inicial</a></p>
        <a href="../vendas/listar_vendas.php">Ver Lista de Vendas</a>
    </div>

    <script>
        const tipoPesquisa = document.getElementById('tipo_pesquisa');
        const divCliente = document.getElementById('div_cliente');
        const divProduto = document.getElementById('div_produto');

        tipoPesquisa.addEventListener('change', function() {
            if (this.value === 'cliente') {
                divCliente.style.display = 'block';
                divProduto.style.display = 'none';
            } else if (this.value === 'produto') {
                divCliente.style.display = 'none';
                divProduto.style.display = 'block';
            }
        });
    </script>
</body>
</html>