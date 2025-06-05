<?php
// Inclua o arquivo de conexão com o banco de dados usando PDO
require_once ('../database/conexaobd.php');

// Consulta para buscar todos os clientes (sem filtro por 'ativo')
try {
    $sql_clientes = "SELECT idCliente, nome FROM Clientes WHERE ativo = 1 ORDER BY nome"; // <--- Esta é a linha original
    $stmt_clientes = $pdo->query($sql_clientes);
    $clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em um ambiente de produção, você logaria o erro em vez de exibi-lo
    $clientes = []; // Garante que $clientes seja um array mesmo em caso de erro
    echo "Erro ao carregar clientes: " . htmlspecialchars($e->getMessage());
}

// Consulta para buscar todos os produtos, incluindo preço e estoque
// Se você implementou soft delete para produtos, adicione WHERE ativo = TRUE
try {
    $sql_produtos = "SELECT idProduto, descricao, preco, qtdeEstoque FROM Produtos ORDER BY descricao";
    $stmt_produtos = $pdo->query($sql_produtos);
    $produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em um ambiente de produção, você logaria o erro em vez de exibi-lo
    $produtos = []; // Garante que $produtos seja um array mesmo em caso de erro
    echo "Erro ao carregar produtos: " . htmlspecialchars($e->getMessage());
}

// Se você tiver uma tabela de formas de pagamento:
try {
    $sql_formas_pagamento = "SELECT idFormaPagto, descricao FROM FormasPagamento ORDER BY descricao";
    $stmt_formas_pagamento = $pdo->query($sql_formas_pagamento);
    $formas_pagamento = $stmt_formas_pagamento->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em um ambiente de produção, você logaria o erro em vez de exibi-lo
    $formas_pagamento = []; // Garante que $formas_pagamento seja um array mesmo em caso de erro
    echo "Erro ao carregar formas de pagamento: " . htmlspecialchars($e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Vendas</title>
    <link rel="stylesheet" href="../css/cadastrar_venda.css">
    <style>
        /* Estilos básicos para mensagens de status */
        .message-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .error-message-inline {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
            display: block; /* Garante que a mensagem ocupe sua própria linha */
        }
    </style>
</head>
<body>
    <div class="container-venda">
        <h1>Cadastro de Vendas</h1>
        <div class="form-divider"></div>

        <?php
        // Exibe mensagens de status (sucesso/erro) vindas do processamento
        if (isset($_GET['status'])) {
            $status_type = htmlspecialchars($_GET['status']);
            $message = htmlspecialchars($_GET['msg'] ?? '');
            echo '<div class="message-' . $status_type . '">' . $message . '</div>';
        }
        ?>

        <form id="formCadastroVenda" action="cadastrar_venda_processa.php" method="post">
            <div class="form-group">
                <label for="idCliente">Cliente:</label>
                <select name="idCliente" id="idCliente" required>
                    <option value="">Selecione um cliente</option>
                    <?php if ($clientes): ?>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo htmlspecialchars($cliente["idCliente"]); ?>"><?php echo htmlspecialchars($cliente["nome"]); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">Nenhum cliente encontrado</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="idProduto">Produto:</label>
                <select name="idProduto" id="idProduto" required>
                    <option value="">Selecione um produto</option>
                    <?php if ($produtos): ?>
                        <?php foreach ($produtos as $produto): ?>
                            <option
                                value="<?php echo htmlspecialchars($produto["idProduto"]); ?>"
                                data-preco="<?php echo htmlspecialchars(sprintf('%.2f', $produto["preco"])); ?>"
                                data-estoque="<?php echo htmlspecialchars($produto["qtdeEstoque"]); ?>">
                                <?php echo htmlspecialchars($produto["descricao"]); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">Nenhum produto encontrado</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="precoUnitarioDisplay">Preço Unitário:</label>
                <input type="text" id="precoUnitarioDisplay" readonly>
            </div>

            <div class="form-group">
                <label for="estoqueDisponivelDisplay">Estoque Disponível:</label>
                <input type="text" id="estoqueDisponivelDisplay" readonly>
            </div>

            <div class="form-group">
                <label for="qtdeVendida">Quantidade Vendida:</label>
                <input type="number" id="qtdeVendida" name="qtdeVendida" min="1" value="1" required>
                <span id="estoqueError" class="error-message-inline"></span>
            </div>

            <div class="form-group">
                <label for="precoTotal">Preço Total:</label>
                <input type="number" id="precoTotal" name="precoTotal" step="0.01" readonly required>
            </div>

            <div class="form-group">
                <label for="dataVenda">Data da Venda:</label>
                <input type="date" id="dataVenda" name="dataVenda" value="<?php echo date('Y-m-d'); ?>" required>
                <span id="dataVendaError" class="error-message-inline"></span>
            </div>

            <div class="form-group">
                <label for="formaPagto">Forma de Pagamento:</label>
                <select name="formaPagto" id="formaPagto" required>
                    <option value="">Selecione a forma de pagamento</option>
                    <?php if (!empty($formas_pagamento)): ?>
                        <?php foreach ($formas_pagamento as $forma): ?>
                            <option value="<?php echo htmlspecialchars($forma["idFormaPagto"]); ?>"><?php echo htmlspecialchars($forma["descricao"]); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="1">Dinheiro</option>
                        <option value="2">Cartão de Crédito</option>
                        <option value="3">Cartão de Débito</option>
                        <option value="4">Boleto Bancário</option>
                    <?php endif; ?>
                </select>
            </div>

            <button type="submit" class="submit-button">Cadastrar Venda</button>
        </form>
        <div class="button-container">
            <a href="../menu/index.html">Voltar à Página Inicial</a>
            <a href="../vendas/listar_vendas.php">Ver Lista de Vendas</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const idProdutoSelect = document.getElementById('idProduto');
            const precoUnitarioDisplay = document.getElementById('precoUnitarioDisplay');
            const estoqueDisponivelDisplay = document.getElementById('estoqueDisponivelDisplay');
            const qtdeVendidaInput = document.getElementById('qtdeVendida');
            const precoTotalInput = document.getElementById('precoTotal');
            const estoqueErrorSpan = document.getElementById('estoqueError');
            const dataVendaInput = document.getElementById('dataVenda');
            const dataVendaErrorSpan = document.getElementById('dataVendaError');
            const formCadastroVenda = document.getElementById('formCadastroVenda');

            let precoUnitarioAtual = 0;
            let estoqueAtual = 0;

            // Função para calcular o preço total
            function calcularPrecoTotal() {
                const quantidade = parseInt(qtdeVendidaInput.value, 10);
                // CORREÇÃO AQUI: 'quantity' foi mudado para 'quantidade'
                if (!isNaN(quantidade) && quantidade >= 1 && precoUnitarioAtual > 0) {
                    const total = precoUnitarioAtual * quantidade;
                    precoTotalInput.value = total.toFixed(2); // Formata para 2 casas decimais
                } else {
                    precoTotalInput.value = '0.00';
                }
            }

            // Função para validar estoque (frontend)
            function validarEstoque() {
                const quantidade = parseInt(qtdeVendidaInput.value, 10);
                if (isNaN(quantidade) || quantidade < 1) {
                    estoqueErrorSpan.textContent = 'A quantidade deve ser no mínimo 1.';
                    estoqueErrorSpan.style.display = 'block';
                    return false;
                }
                if (quantidade > estoqueAtual) {
                    estoqueErrorSpan.textContent = 'Quantidade solicitada (' + quantidade + ') excede o estoque disponível (' + estoqueAtual + ').';
                    estoqueErrorSpan.style.display = 'block';
                    return false;
                }
                estoqueErrorSpan.style.display = 'none';
                return true;
            }

            // Evento para atualizar preço unitário e estoque ao selecionar um produto
            idProdutoSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const preco = selectedOption.dataset.preco; // Pega o preço do data-atributo
                const estoque = selectedOption.dataset.estoque; // Pega o estoque do data-atributo

                if (preco && estoque) {
                    precoUnitarioAtual = parseFloat(preco);
                    estoqueAtual = parseInt(estoque, 10);

                    precoUnitarioDisplay.value = precoUnitarioAtual.toFixed(2);
                    estoqueDisponivelDisplay.value = estoqueAtual;

                    qtdeVendidaInput.max = estoqueAtual; // Limita o input de quantidade pelo estoque
                    qtdeVendidaInput.value = Math.min(qtdeVendidaInput.value, estoqueAtual); // Ajusta a quantidade se for maior que o novo estoque
                    if (qtdeVendidaInput.value < 1) qtdeVendidaInput.value = 1; // Garante mínimo 1

                    validarEstoque(); // Valida estoque imediatamente
                    calcularPrecoTotal(); // Recalcula o total
                } else {
                    // Limpa os campos se nenhum produto válido for selecionado
                    precoUnitarioAtual = 0;
                    estoqueAtual = 0;
                    precoUnitarioDisplay.value = '';
                    estoqueDisponivelDisplay.value = '';
                    qtdeVendidaInput.value = 1;
                    qtdeVendidaInput.max = ''; // Remove o máximo
                    estoqueErrorSpan.style.display = 'none';
                    calcularPrecoTotal();
                }
            });

            // Evento para recalcular o preço total e validar estoque ao mudar a quantidade
            qtdeVendidaInput.addEventListener('input', function() {
                if (parseInt(this.value, 10) < 1) {
                    this.value = 1; // Garante que a quantidade mínima é 1
                }
                if (this.value > estoqueAtual && estoqueAtual > 0) {
                    this.value = estoqueAtual; // Limita a quantidade ao estoque disponível
                }
                validarEstoque();
                calcularPrecoTotal();
            });

            // Evento para validar a data da venda (não pode ser futura)
            dataVendaInput.addEventListener('change', function() {
                const dataSelecionada = new Date(this.value + 'T00:00:00');
                const dataAtual = new Date();
                dataAtual.setHours(0, 0, 0, 0);

                if (dataSelecionada > dataAtual) {
                    dataVendaErrorSpan.textContent = 'A data da venda não pode ser futura.';
                    dataVendaErrorSpan.style.display = 'block';
                    // this.value = dataAtual.toISOString().split('T')[0]; // Opcional: Define para a data de hoje
                    return false;
                } else {
                    dataVendaErrorSpan.style.display = 'none';
                    return true;
                }
            });

            // Validação final na submissão do formulário
            formCadastroVenda.addEventListener('submit', function(event) {
                // Validações de frontend adicionais antes de enviar
                if (idProdutoSelect.value === "") {
                    alert('Por favor, selecione um produto.');
                    event.preventDefault();
                    idProdutoSelect.focus();
                    return;
                }

                if (!validarEstoque()) { // Revalida o estoque
                    alert('Por favor, corrija a quantidade vendida.');
                    event.preventDefault();
                    qtdeVendidaInput.focus();
                    return;
                }

                if (!dataVendaInput.value || new Date(dataVendaInput.value + 'T00:00:00') > new Date().setHours(0,0,0,0)) {
                    alert('Por favor, insira uma data de venda válida (não futura).');
                    event.preventDefault();
                    dataVendaInput.focus();
                    return;
                }

                if (parseFloat(precoTotalInput.value) <= 0 || isNaN(parseFloat(precoTotalInput.value))) {
                    alert('O Preço Total da venda deve ser maior que zero.');
                    event.preventDefault();
                    return;
                }

                // O campo precoTotal é readonly, mas podemos garantir que seu valor será enviado corretamente
                // e que é o calculado pelo JS. O backend fará a validação final.
            });

            // Dispara o evento change ao carregar a página se um produto já estiver selecionado (útil se você preencher o formulário)
            // idProdutoSelect.dispatchEvent(new Event('change'));
        });
    </script>
</body>
</html>