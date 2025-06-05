<?php
// Inclui o arquivo de conexão com o banco de dados
require_once ('../database/conexaobd.php');

$produto = null;

// Verifica se o ID foi fornecido e é um número inteiro válido
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id_produto = filter_var($_GET['id'], FILTER_VALIDATE_INT); // Sanitiza o ID da URL

    try {
        // Prepara uma consulta SQL para selecionar o produto com o idProduto fornecido
        $stmt = $pdo->prepare('SELECT * FROM produtos WHERE idProduto = :id_produto');
        $stmt->bindParam(':id_produto', $id_produto, PDO::PARAM_INT); // Vincula como inteiro
        $stmt->execute();

        // Obtém os dados do produto
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se nenhum produto for encontrado com o ID, redireciona ou mostra um erro amigável
        if (!$produto) {
            header('Location: pesquisar_produto.php?status=error&msg=' . urlencode('Produto não encontrado.'));
            exit();
        }
    } catch (PDOException $e) {
        // Em produção, você logaria $e->getMessage()
        header('Location: pesquisar_produto.php?status=error&msg=' . urlencode('Erro ao carregar produto: ' . $e->getMessage()));
        exit();
    }
} else {
    // Se o ID não foi fornecido ou é inválido, redireciona
    header('Location: pesquisar_produto.php?status=error&msg=' . urlencode('ID do produto inválido ou não especificado.'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
    <link rel="stylesheet" href="../css/editar_produto.css">
    <script>
        // JavaScript para a validação da data de validade futura, similar ao cadastro
        document.addEventListener('DOMContentLoaded', function() {
            const dataValidadeInput = document.getElementById('dataValidade');
            // Você pode adicionar um span para mensagens de erro inline aqui também, se quiser
            // Ex: const dataValidadeErrorSpan = document.createElement('span');
            // dataValidadeErrorSpan.className = 'error-message-inline';
            // dataValidadeInput.parentNode.insertBefore(dataValidadeErrorSpan, dataValidadeInput.nextSibling);

            function validarData() {
                const dataSelecionadaStr = dataValidadeInput.value;

                if (!dataSelecionadaStr) { // Se o campo estiver vazio, considera válido para edição (pode ser opcional)
                    // Se você quer que a data seja obrigatória mesmo na edição, pode adicionar uma mensagem aqui
                    return true;
                }

                const dataSelecionada = new Date(dataSelecionadaStr + 'T00:00:00');
                const dataAtual = new Date();
                dataAtual.setHours(0, 0, 0, 0);

                if (isNaN(dataSelecionada.getTime()) || dataSelecionada < dataAtual) {
                    // Aqui você pode mudar para exibir uma mensagem inline
                    alert('A data de validade não pode ser uma data antiga ou a data de hoje. Por favor, insira uma data futura.');
                    dataValidadeInput.focus(); // Coloca o foco de volta
                    return false;
                }
                return true;
            }

            // Valida ao sair do campo
            dataValidadeInput.addEventListener('change', validarData);

            // Valida na submissão do formulário
            document.querySelector('form').addEventListener('submit', function(event) {
                if (!validarData()) {
                    event.preventDefault(); // Impede o envio do formulário
                }
            });
        });
    </script>
</head>
<body>
    <div class="form-container">
        <h1>Editar Produto</h1>
        <form action="atualizar_produto.php" method="post">
            <div>
                <input type="hidden" id="id" name="id" value="<?php echo htmlspecialchars($produto['idProduto']); ?>">
            </div>
            <div>
                <label for="descricao">Descrição:</label>
                <input type="text" id="descricao" name="descricao" maxlength="100" value="<?php echo htmlspecialchars($produto['descricao']); ?>" required>
            </div>
            <div>
                <label for="preco">Preço:</label>
                <input type="number" id="preco" name="preco" step="0.01" value="<?php echo htmlspecialchars($produto['preco']); ?>" required>
            </div>
            <div>
                <label for="qtdeEstoque">Quantidade em Estoque:</label>
                <input type="number" id="qtdeEstoque" name="qtdeEstoque" min="0" value="<?php echo htmlspecialchars($produto['qtdeEstoque']); ?>" required>
            </div>
            <div>
                <label for="dataValidade">Data de Validade:</label>
                <input type="date" id="dataValidade" name="dataValidade" value="<?php echo htmlspecialchars($produto['dataValidade']); ?>">
            </div>
            <button type="submit">Salvar Alterações</button>
            <a href="pesquisar_produto.php">Cancelar</a>
        </form>
    </div>
</body>
</html>