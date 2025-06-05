<?php
// Inclui o arquivo de conexão com o banco de dados
require_once("../database/conexaobd.php");

// Função para validar data e converter para AAAA-MM-DD
function validarDataParaBanco($data) {
    // A input type="date" envia a data no formato AAAA-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        // Tenta criar um objeto DateTime para verificar a validade da data
        $data_obj = DateTime::createFromFormat('Y-m-d', $data);
        // Verifica se a data é válida e se o formato corresponde ao que foi passado
        if ($data_obj && $data_obj->format('Y-m-d') === $data) {
            return $data; // Retorna a data no formato correto
        }
    }
    return false; // Retorna false se o formato for inválido ou a data for inválida
}


// Inicializa um array para armazenar os erros de validação
$erros = [];

// Variáveis para mensagens de status
$mensagem_status = '';
$tipo_mensagem = ''; // 'success' ou 'error'

// Verifica se o formulário foi submetido via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtém e sanitiza os dados do formulário
    $descricao = filter_var($_POST["descricao"] ?? '', FILTER_UNSAFE_RAW);
    $preco = str_replace(',', '.', ($_POST["preco"] ?? '0')); // Permite vírgula e converte para ponto
    $qtdeEstoque = filter_var($_POST["qtdeEstoque"] ?? '0', FILTER_VALIDATE_INT);
    $dataValidadeInput = $_POST["dataValidade"] ?? '';

    // --- VALIDAÇÃO DOS DADOS ---

    if (empty($descricao)) {
        $erros[] = "A descrição é obrigatória.";
    } elseif (strlen($descricao) > 100) {
        $erros[] = "A descrição não pode ter mais de 100 caracteres.";
    }

    if (!is_numeric($preco) || $preco <= 0) {
        $erros[] = "O preço deve ser um número maior que zero.";
    } else {
        $preco = floatval($preco); // Garante que é um float
    }

    if ($qtdeEstoque === false || $qtdeEstoque < 0) { // filter_var retorna false em caso de falha
        $erros[] = "A quantidade em estoque deve ser um número inteiro não negativo.";
    } else {
        $qtdeEstoque = intval($qtdeEstoque); // Garante que é um inteiro
    }

    // Validação da Data de Validade
    $dataValidadeFormatada = validarDataParaBanco($dataValidadeInput);

    if (!$dataValidadeFormatada) {
        $erros[] = "A data de validade é inválida. Use o formato YYYY-MM-DD.";
    } else {
        $data_atual = new DateTime();
        $data_atual->setTime(0, 0, 0); // Zera o horário para comparar apenas a data

        $data_validade_obj = new DateTime($dataValidadeFormatada);
        $data_validade_obj->setTime(0, 0, 0); // Zera o horário

        if ($data_validade_obj < $data_atual) {
            $erros[] = "A data de validade não pode ser uma data antiga ou a data de hoje. Por favor, insira uma data futura.";
        }
    }

    // Se não houver erros, tenta inserir os dados
    if (empty($erros)) {
        try {
            // Prepara a query SQL para inserção
            $sql = "INSERT INTO produtos (descricao, preco, qtdeEstoque, dataValidade) VALUES (:descricao, :preco, :qtdeEstoque, :dataValidade)";
            $stmt = $pdo->prepare($sql);

            // Bind dos parâmetros
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':preco', $preco);
            $stmt->bindParam(':qtdeEstoque', $qtdeEstoque);
            $stmt->bindParam(':dataValidade', $dataValidadeFormatada); // Usa a data já validada e formatada

            // Executa a query
            if ($stmt->execute()) {
                $mensagem_status = "Produto cadastrado com sucesso!";
                $tipo_mensagem = 'success';
            } else {
                $mensagem_status = "Erro ao cadastrar o produto.";
                $tipo_mensagem = 'error';
                // Para depuração: error_log(implode(" | ", $stmt->errorInfo()));
            }
        } catch (PDOException $e) {
            $mensagem_status = "Erro na operação do banco de dados: " . $e->getMessage();
            $tipo_mensagem = 'error';
            // Para depuração: error_log($e->getMessage());
        }
    } else {
        // Erros de validação - junta todos os erros em uma string para exibição
        $mensagem_status = "<h2>Erros de Validação:</h2>";
        $mensagem_status .= "<ul>";
        foreach ($erros as $erro) {
            $mensagem_status .= "<li>" . htmlspecialchars($erro) . "</li>"; // Saída segura de erros
        }
        $mensagem_status .= "</ul>";
        $tipo_mensagem = 'error';
    }
} else {
    // Se o método de requisição não for POST
    $mensagem_status = "Acesso inválido. Por favor, preencha o formulário de cadastro.";
    $tipo_mensagem = 'error';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status do Cadastro de Produto</title>
    <link rel="stylesheet" href="../css/cadastrar_produto.css">
</head>
<body>
    <div class="form-container">
        <h1>Status do Cadastro de Produto</h1>

        <?php if ($mensagem_status): ?>
            <div class="<?php echo $tipo_mensagem; ?>-message">
                <?php echo $mensagem_status; ?>
            </div>
        <?php endif; ?>

        <p>
            <a href="pesquisar_produto.php">Ver Produtos Cadastrados</a>
        </p>
        <p>
            <a href="cadastrar_produto.html">Cadastrar Outro Produto</a>
        </p>
    </div>
</body>
</html>