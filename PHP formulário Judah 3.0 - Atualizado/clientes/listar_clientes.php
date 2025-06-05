<?php
// Inclua o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php');

// Consulta SQL para selecionar clientes ativos, ordenados por nome
// Adicionamos 'WHERE c.ativo = TRUE' para listar apenas clientes não desativados
$sql = "SELECT c.idCliente, c.nome, c.email, c.telefone, c.cpf, c.endereco, e.nome as nomeEstado, c.dtNasc, c.sexo, c.foto_nome, c.pdf_nome 
        FROM Clientes c 
        LEFT JOIN Estados e ON c.estado = e.sigla 
        WHERE c.ativo = TRUE 
        ORDER BY c.idCliente ASC";
$stmt = $pdo->query($sql);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Clientes</title>
    <link rel="stylesheet" href="../css/listar_clientes.css">
</head>
<body>
    <div class="container">
        <h1>Lista de Clientes</h1>
        <div class="form-divider"></div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'success_delete'): ?>
                <div class="success-message">Cliente excluído com sucesso!</div>
            <?php elseif ($_GET['status'] === 'error_delete'): ?>
                <div class="error-message">
                    Erro ao excluir cliente.
                    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cliente_vinculado'): ?>
                        Este cliente possui vendas associadas e não pode ser excluído diretamente.
                    <?php endif; ?>
                </div>
            <?php elseif ($_GET['status'] === 'success_update'): ?>
                <div class="success-message">
                    Cliente atualizado com sucesso!
                    <?php if (isset($_GET['msg'])): ?>
                        <?php echo htmlspecialchars($_GET['msg']); ?>
                    <?php endif; ?>
                </div>
            <?php elseif ($_GET['status'] === 'error_update'): ?>
                <div class="error-message">
                    Erro ao atualizar cliente.
                    <?php if (isset($_GET['msg'])): ?>
                        <?php echo htmlspecialchars($_GET['msg']); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($clientes)): ?>
            <p class="mensagem">Nenhum cliente ativo cadastrado.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>CPF</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?php echo $cliente['idCliente']; ?></td>
                            <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['telefone']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['cpf']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['nomeEstado']); ?></td>
                            <td class="acoes">
                                <a href="alterar_cliente.php?id=<?php echo $cliente['idCliente']; ?>" class="btn-alterar">Alterar</a>
                                <a href="excluir_cliente_processa.php?id=<?php echo $cliente['idCliente']; ?>" class="btn-excluir" onclick="return confirm('Tem certeza que deseja desativar este cliente? Ele não aparecerá mais na lista.');">Desativar</a>
                                <a href="gerar_pdf_cliente.php?id=<?php echo $cliente['idCliente']; ?>" class="btn-visualizar">Ver Detalhes/PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="button-container">
            <a href="cadastrar_cliente.php">Cadastrar Novo Cliente</a>
            <a href="../menu/index.html">Voltar à Página Inicial</a>
        </div>
    </div>
</body>
</html>