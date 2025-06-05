<?php
// Inclua o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php'); // Ajuste o caminho se necessário

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Coleta e sanitização dos dados do formulário com filter_input
    $idVenda = filter_input(INPUT_POST, 'idVenda', FILTER_VALIDATE_INT);
    $idCliente = filter_input(INPUT_POST, 'idCliente', FILTER_VALIDATE_INT);
    $idProduto = filter_input(INPUT_POST, 'idProduto', FILTER_VALIDATE_INT);
    $qtdeVendida = filter_input(INPUT_POST, 'qtdeVendida', FILTER_VALIDATE_INT);
    $precoTotal = filter_input(INPUT_POST, 'precoTotal', FILTER_VALIDATE_FLOAT); // Use FLOAT para preços
    $dataVenda = filter_input(INPUT_POST, 'dataVenda', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    // Aqui está a mudança crucial para idFormaPagto
    $idFormaPagto = filter_input(INPUT_POST, 'idFormaPagto', FILTER_VALIDATE_INT);

    // 2. Validação dos inputs (backend)
    // Se qualquer um desses for false ou null (falha de validação ou não enviado),
    // ele lança uma exceção antes de tentar o DB.
    if (
        $idVenda === false || $idVenda === null ||
        $idCliente === false || $idCliente === null ||
        $idProduto === false || $idProduto === null ||
        $qtdeVendida === false || $qtdeVendida === null || $qtdeVendida <= 0 ||
        $precoTotal === false || $precoTotal === null || $precoTotal <= 0 ||
        empty($dataVenda) ||
        // VALIDAÇÃO PARA idFormaPagto:
        $idFormaPagto === false || $idFormaPagto === null 
    ) {
        header('Location: listar_vendas.php?status=error_update&msg=' . urlencode('Dados da venda incompletos ou inválidos.'));
        exit();
    }

    // Opcional: Validação da data (similar ao cadastrar_venda_processa.php)
    try {
        $dataVendaObj = new DateTime($dataVenda);
        $dataAtualObj = new DateTime();
        $dataAtualObj->setTime(0, 0, 0); 

        if ($dataVendaObj > $dataAtualObj) {
            header('Location: listar_vendas.php?status=error_update&msg=' . urlencode('A data da venda não pode ser futura.'));
            exit();
        }
    } catch (Exception $e) {
        header('Location: listar_vendas.php?status=error_update&msg=' . urlencode('Formato de data inválido.'));
        exit();
    }


    // 3. Prepara a consulta SQL para atualização
    $sql = "UPDATE Vendas SET 
                idCliente = :idCliente, 
                idProduto = :idProduto, 
                qtdeVendida = :qtdeVendida, 
                precoTotal = :precoTotal, 
                dataVenda = :dataVenda, 
                idFormaPagto = :idFormaPagto 
            WHERE idVenda = :idVenda";
    $stmt = $pdo->prepare($sql);

    // 4. Vincula os parâmetros
    $stmt->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
    $stmt->bindParam(':idProduto', $idProduto, PDO::PARAM_INT);
    $stmt->bindParam(':qtdeVendida', $qtdeVendida, PDO::PARAM_INT);
    $stmt->bindParam(':precoTotal', $precoTotal); // PDO pode inferir para float, mas STR funciona
    $stmt->bindParam(':dataVenda', $dataVenda);
    $stmt->bindParam(':idFormaPagto', $idFormaPagto, PDO::PARAM_INT); // Agora idFormaPagto terá um valor válido ou a execução será interrompida
    $stmt->bindParam(':idVenda', $idVenda, PDO::PARAM_INT);

    // 5. Executa a consulta
    try {
        if ($stmt->execute()) {
            header('Location: listar_vendas.php?status=success_update');
            exit();
        } else {
            // Se execute() falhar por outra razão (não PDOException)
            header('Location: listar_vendas.php?status=error_update&msg=' . urlencode('Erro desconhecido ao atualizar a venda.'));
            exit();
        }
    } catch (PDOException $e) {
        // Captura PDOExceptions e exibe uma mensagem mais detalhada
        header('Location: listar_vendas.php?status=error_update&msg=' . urlencode('Erro no banco de dados: ' . $e->getMessage()));
        // Em ambiente de produção, você logaria $e->getMessage() e mostraria uma mensagem genérica
        exit();
    }
} else {
    header('Location: listar_vendas.php?status=error_update&msg=' . urlencode('Método de requisição inválido.'));
    exit();
}