<?php
// Inclua o arquivo de conexão com o banco de dados
require_once ('../database/conexaobd.php');

// Redireciona para a página de cadastro se não for uma requisição POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header('Location: cadastrar_venda.php?status=error&msg=' . urlencode('Método de requisição inválido.'));
    exit();
}

// Inicia uma transação. Isso garante que a inserção da venda e a atualização do estoque
// aconteçam juntas ou nenhuma delas ocorra, prevenindo inconsistências.
$pdo->beginTransaction();

try {
    // 1. Receber e sanitizar os dados do formulário
    // Usando filter_input para uma coleta e validação mais seguras
    $idCliente = filter_input(INPUT_POST, 'idCliente', FILTER_VALIDATE_INT);
    $idProduto = filter_input(INPUT_POST, 'idProduto', FILTER_VALIDATE_INT);
    $qtdeVendida = filter_input(INPUT_POST, 'qtdeVendida', FILTER_VALIDATE_INT);
    $precoTotal = filter_input(INPUT_POST, 'precoTotal', FILTER_VALIDATE_FLOAT); // O preço total virá do JS
    $dataVenda = filter_input(INPUT_POST, 'dataVenda', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formaPagto = filter_input(INPUT_POST, 'formaPagto', FILTER_VALIDATE_INT);

    // Validação básica dos inputs
    if (
        $idCliente === false || $idCliente === null ||
        $idProduto === false || $idProduto === null ||
        $qtdeVendida === false || $qtdeVendida === null || $qtdeVendida <= 0 ||
        $precoTotal === false || $precoTotal === null || $precoTotal <= 0 ||
        empty($dataVenda) ||
        $formaPagto === false || $formaPagto === null
    ) {
        throw new Exception("Dados da venda incompletos ou inválidos.");
    }

    // Validação da data da venda: não pode ser futura
    $dataVendaObj = new DateTime($dataVenda);
    $dataAtualObj = new DateTime();
    $dataAtualObj->setTime(0, 0, 0); // Zera a hora para comparar apenas a data

    if ($dataVendaObj > $dataAtualObj) {
        throw new Exception("A data da venda não pode ser uma data futura.");
    }


    // 2. Validação de Estoque (Backend - Crucial!)
    // Busca o estoque atual e o preço unitário do produto no banco de dados
    $stmt_produto = $pdo->prepare('SELECT preco, qtdeEstoque FROM Produtos WHERE idProduto = :idProduto');
    $stmt_produto->bindParam(':idProduto', $idProduto, PDO::PARAM_INT);
    $stmt_produto->execute();
    $produto_db = $stmt_produto->fetch(PDO::FETCH_ASSOC);

    if (!$produto_db) {
        throw new Exception("Produto não encontrado ou indisponível.");
    }

    $estoque_disponivel = $produto_db['qtdeEstoque'];
    $preco_unitario_db = $produto_db['preco'];

    // Verifica se a quantidade vendida é maior que o estoque disponível
    if ($qtdeVendida > $estoque_disponivel) {
        throw new Exception("Estoque insuficiente para o produto selecionado. Disponível: " . $estoque_disponivel . ".");
    }

    // 3. Revalidação do Preço Total (Backend - Segurança Adicional)
    // Isso evita que um usuário mal-intencionado manipule o preço total no frontend
    $preco_total_calculado_backend = $preco_unitario_db * $qtdeVendida;

    // Comparar com uma pequena tolerância para evitar problemas de ponto flutuante,
    // ou idealmente, usar inteiros para centavos para precisão financeira.
    if (abs($precoTotal - $preco_total_calculado_backend) > 0.01) {
        // Se a diferença for significativa, é um possível problema.
        // Você pode decidir:
        // A) Usar o valor calculado pelo backend (mais seguro):
        $precoTotal = $preco_total_calculado_backend;
        // B) Lançar um erro:
        // throw new Exception("Inconsistência no preço total. Verifique os dados.");
    }

    // 4. Inserir a venda na tabela 'Vendas'
    // A estrutura da sua tabela Vendas deve incluir colunas para idProduto, qtdeVendida, e precoUnitario
    // Exemplo de estrutura de tabela Vendas (assumindo que cada linha é uma venda de um único produto):
    // CREATE TABLE Vendas (
    //     idVenda INT PRIMARY KEY AUTO_INCREMENT,
    //     idCliente INT NOT NULL,
    //     idProduto INT NOT NULL,
    //     qtdeVendida INT NOT NULL,
    //     precoUnitario DECIMAL(10,2) NOT NULL,
    //     precoTotal DECIMAL(10,2) NOT NULL,
    //     dataVenda DATE NOT NULL,
    //     idFormaPagto INT NOT NULL,
    //     FOREIGN KEY (idCliente) REFERENCES Clientes(idCliente),
    //     FOREIGN KEY (idProduto) REFERENCES Produtos(idProduto),
    //     FOREIGN KEY (idFormaPagto) REFERENCES FormasPagamento(idFormaPagto)
    // );

    $sql_insert_venda = "INSERT INTO Vendas (idCliente, idProduto, qtdeVendida, precoUnitario, precoTotal, dataVenda, idFormaPagto) VALUES (:idCliente, :idProduto, :qtdeVendida, :precoUnitario, :precoTotal, :dataVenda, :idFormaPagto)";
    $stmt_insert_venda = $pdo->prepare($sql_insert_venda);

    $stmt_insert_venda->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
    $stmt_insert_venda->bindParam(':idProduto', $idProduto, PDO::PARAM_INT);
    $stmt_insert_venda->bindParam(':qtdeVendida', $qtdeVendida, PDO::PARAM_INT);
    $stmt_insert_venda->bindParam(':precoUnitario', $preco_unitario_db); // Usa o preço unitário obtido do DB
    $stmt_insert_venda->bindParam(':precoTotal', $precoTotal); // Usa o precoTotal (ajustado se necessário)
    $stmt_insert_venda->bindParam(':dataVenda', $dataVenda);
    $stmt_insert_venda->bindParam(':idFormaPagto', $formaPagto, PDO::PARAM_INT);
    $stmt_insert_venda->execute();

    // 5. Atualizar o estoque do produto
    $sql_update_estoque = "UPDATE Produtos SET qtdeEstoque = qtdeEstoque - :qtdeVendida WHERE idProduto = :idProduto";
    $stmt_update_estoque = $pdo->prepare($sql_update_estoque);
    $stmt_update_estoque->bindParam(':qtdeVendida', $qtdeVendida, PDO::PARAM_INT);
    $stmt_update_estoque->bindParam(':idProduto', $idProduto, PDO::PARAM_INT);
    $stmt_update_estoque->execute();

    // Se todas as operações foram bem-sucedidas, confirma a transação
    $pdo->commit();
    header('Location: cadastrar_venda.php?status=success&msg=' . urlencode('Venda cadastrada com sucesso!'));
    exit();

} catch (Exception $e) {
    // Em caso de qualquer erro, reverte a transação
    $pdo->rollBack();
    // Redireciona com uma mensagem de erro
    // Em um ambiente de produção, você logaria $e->getMessage() para depuração
    header('Location: cadastrar_venda.php?status=error&msg=' . urlencode('Erro ao cadastrar venda: ' . $e->getMessage()));
    exit();
}
?>