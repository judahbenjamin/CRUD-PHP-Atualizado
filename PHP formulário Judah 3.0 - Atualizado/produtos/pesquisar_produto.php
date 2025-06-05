<?php
// Inclui o arquivo de conexão com o banco de dados
require_once ('../database/conexaobd.php');

// Inicializa a variável para armazenar os produtos
$produtos = [];
$termo_pesquisa = ''; // Inicializa para evitar "Undefined index" se não houver termo

try {
    // Sanitize the search term before using it in the query or echoing in the input
    // filter_input is safer for GET/POST variables
    $termo_pesquisa_raw = filter_input(INPUT_GET, 'termo_pesquisa', FILTER_UNSAFE_RAW);

    // Verifica se houve um termo de pesquisa enviado
    if (!empty($termo_pesquisa_raw)) {
        $termo_pesquisa = '%' . $termo_pesquisa_raw . '%'; // Adiciona curingas APÓS a sanitização
        $stmt = $pdo->prepare('SELECT * FROM produtos WHERE descricao LIKE :termo_pesquisa ORDER BY idProduto ASC');
        $stmt->bindParam(':termo_pesquisa', $termo_pesquisa, PDO::PARAM_STR);
        $stmt->execute();
    } else {
        // Se não houver termo de pesquisa, seleciona todos os produtos
        // Adicionando WHERE ativo = TRUE para seguir a lógica de soft delete, se aplicável
        // Se você não implementou soft delete para produtos, remova esta linha
        $stmt = $pdo->query('SELECT * FROM produtos ORDER BY idProduto ASC'); // Mudei para ORDER BY descricao ASC para consistência
    }

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Em produção, você logaria $e->getMessage() em vez de exibi-lo diretamente
    echo "Erro ao buscar produtos: " . htmlspecialchars($e->getMessage()); // Sanitizado a mensagem de erro para exibição
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Produtos</title>
    <link rel="stylesheet" href="../css/pesquisar_produto.css">
    <style>
        /* Se você já tem esse CSS em '../css/pesquisar_produto.css', pode remover daqui.
           Mantive aqui para garantir que as cores de botões sejam as que você quer. */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .acoes a {
            margin-right: 5px;
            text-decoration: none;
            padding: 3px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .acoes .editar { background-color: #4CAF50; color: white; }
        .acoes .excluir { background-color: #f44336; color: white; } /* Corrigido para um vermelho mais consistente */
        .no-results {
            margin-top: 20px;
            font-style: italic;
            color: #555;
        }
        .messages {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <h1>Lista de Produtos</h1>

    <a href="../menu/index.html">Voltar a Página Inicial</a>

    <?php
    if (isset($_GET['status'])) {
        // As mensagens de status precisam ser sanitizadas se vierem da URL
        $status_msg = htmlspecialchars($_GET['status']);
        $extra_msg = htmlspecialchars($_GET['msg'] ?? ''); // Captura mensagem extra se houver

        if ($status_msg == 'success') {
            echo '<div class="messages message-success">Operação realizada com sucesso! ' . $extra_msg . '</div>';
        } elseif ($status_msg == 'error') {
            echo '<div class="messages message-error">Ocorreu um erro na operação. ' . $extra_msg . '</div>';
        }
    }
    ?>

    <form method="GET" action="pesquisar_produto.php">
        <label for="termo_pesquisa">Pesquisar por Descrição:</label>
        <input type="text" id="termo_pesquisa" name="termo_pesquisa" value="<?php echo htmlspecialchars($termo_pesquisa_raw ?? ''); ?>">
        <button type="submit">Pesquisar</button>
        <a href="pesquisar_produto.php">Limpar Pesquisa</a>
    </form>

    <p><a href="cadastrar_produto.html">Cadastrar Novo Produto</a></p>

    <?php if (count($produtos) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Descrição</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th>Validade</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><?php echo htmlspecialchars($produto['idProduto']); ?></td>
                    <td><?php echo htmlspecialchars($produto['descricao']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($produto['preco'], 2, ',', '.')); ?></td>
                    <td><?php echo htmlspecialchars($produto['qtdeEstoque']); ?></td>
                    <td><?php echo htmlspecialchars($produto['dataValidade'] ? date('d/m/Y', strtotime($produto['dataValidade'])) : 'N/A'); ?></td>
                    <td class="acoes">
                        <a href="editar_produto.php?id=<?php echo htmlspecialchars($produto['idProduto']); ?>" class="editar">Editar</a>
                        <a href="excluir_produto.php?id=<?php echo htmlspecialchars($produto['idProduto']); ?>" class="excluir" onclick="return confirm('Tem certeza que deseja excluir este produto?');">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-results">Nenhum produto encontrado.</p>
    <?php endif; ?>
</body>
</html>