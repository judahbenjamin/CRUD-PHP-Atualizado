<?php
// Inclui o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php'); // Ajuste o caminho se necessário

// Inicializa um array para armazenar os erros de validação
$erros = [];

// Função para validar strings
function validarString($valor) {
    return filter_var(trim($valor), FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
}

// Função para validar CPF (agora aceitando pontos e traços)
function validarCPF($cpf) {
    // Remove pontos e traços
    $cpf = preg_replace('/[^0-9]/is', '', $cpf);

    // Verifica se o CPF tem 11 dígitos e não é uma sequência de dígitos repetidos
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Validação dos dígitos verificadores
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

// Função para validar data no formato DD/MM/AAAA e converter para AAAA-MM-DD
function validarData($data) {
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $matches)) {
        $dia = intval($matches[1]);
        $mes = intval($matches[2]);
        $ano = intval($matches[3]);
        if (checkdate($mes, $dia, $ano)) {
            return "$ano-$mes-$dia"; // Formato para o banco de dados
        }
    }
    return false;
}

// Validação de data de nascimento futura (movida para o início do bloco POST para ser mais explícita)
if (isset($_POST['txtData']) && !empty($_POST['txtData'])) {
    $data_nascimento_input = $_POST['txtData'];
    $data_nasc_formatada = validarData($data_nascimento_input); // Tenta formatar a data primeiro

    if ($data_nasc_formatada) {
        $data_atual_obj = new DateTime();
        $data_nasc_obj = new DateTime($data_nasc_formatada);

        if ($data_nasc_obj > $data_atual_obj) {
            $erros[] = "A data de nascimento não pode ser uma data futura.";
        }
    } else {
        $erros[] = "Formato de data de nascimento inválido (esperado DD/MM/AAAA).";
    }
} else {
    $erros[] = "Informe a Data de Nascimento."; // Mantendo como campo obrigatório
}


// Função para validar e mover arquivo (AJUSTADA PARA SER OPCIONAL)
function processarUploadArquivo($arquivo, $tiposPermitidos, $tamanhoMaximo, $diretorioDestino, &$erros) {
    // Se nenhum arquivo foi enviado (erro UPLOAD_ERR_NO_FILE é 4), retorna null sem adicionar erro
    if ($arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // Se houve algum outro erro no upload (além de não ter arquivo)
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erros[] = "Erro de upload para " . $arquivo['name'] . " (código: " . $arquivo['error'] . ").";
        return null; // Retorna null em caso de erro, pois o arquivo não foi processado
    }

    // Validações de tipo e tamanho se o arquivo foi enviado com sucesso
    if (!in_array($arquivo['type'], $tiposPermitidos)) {
        $erros[] = "Tipo de arquivo inválido para " . $arquivo['name'] . ". Tipos permitidos: " . implode(', ', $tiposPermitidos);
        return null;
    }
    if ($arquivo['size'] > $tamanhoMaximo) {
        $erros[] = "Arquivo " . $arquivo['name'] . " muito grande (máx " . ($tamanhoMaximo / (1024 * 1024)) . " MB).";
        return null;
    }

    // Criar diretório se não existir
    if (!is_dir($diretorioDestino)) {
        if (!mkdir($diretorioDestino, 0755, true)) {
            $erros[] = "Não foi possível criar o diretório de uploads: " . $diretorioDestino;
            return null;
        }
    }

    $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $nomeUnico = uniqid() . '.' . $extensao; // Nome único para evitar conflitos
    $caminhoCompleto = $diretorioDestino . $nomeUnico;

    if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        return $nomeUnico; // Retorna o nome do arquivo salvo
    } else {
        $erros[] = "Falha ao mover o arquivo " . $arquivo['name'] . ".";
        return null;
    }
}


// --- INÍCIO DO PROCESSAMENTO DO FORMULÁRIO ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e validação dos dados do formulário
    $nome = validarString($_POST["txtNome"] ?? "");
    $email = filter_var($_POST["txtEmail"] ?? "", FILTER_VALIDATE_EMAIL); // Valida e-mail
    $telefone = validarString($_POST["txtTelefone"] ?? "");
    $endereco = validarString($_POST["txtEndereco"] ?? "");
    $cpf = validarString($_POST["txtCPF"] ?? ""); // Pega o CPF como string
    $dtNasc = $data_nasc_formatada; // Usa a data já validada e formatada
    $estado = validarString($_POST["listEstados"] ?? "");
    $sexo = validarString($_POST["sexo"] ?? "");
    $cinema = isset($_POST["checkCinema"]) ? 1 : 0;
    $musica = isset($_POST["checkMusica"]) ? 1 : 0;
    $informatica = isset($_POST["checkInformatica"]) ? 1 : 0;
    $login = validarString($_POST["txtLogin"] ?? "");
    $senha1 = $_POST["txtSenha1"] ?? "";
    $senha2 = $_POST["txtSenha2"] ?? "";

    // Validação dos campos obrigatórios e formatos (além da data já validada acima)
    if (empty($nome)) { $erros[] = "Informe o Nome."; }
    if (!$email) { $erros[] = "Informe um E-mail válido."; }
    // Telefone pode ser vazio, mas se tiver, valida
    if (!empty($telefone) && !preg_match('/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/', $telefone)) {
        // Regex mais flexível para telefone
        $erros[] = "Informe um Telefone válido (ex: (XX) XXXX-XXXX ou XXXXXXXXXXX).";
    }
    if (empty($endereco)) { $erros[] = "Informe o Endereço."; }
    if (empty($cpf) || !validarCPF($cpf)) { $erros[] = "Informe um CPF válido."; }
    // A validação de $dtNasc e data futura já está sendo feita antes do bloco POST,
    // e o erro é adicionado ao array $erros.
    if (empty($estado)) { $erros[] = "Selecione um Estado."; }
    if (empty($sexo)) { $erros[] = "Selecione o Sexo."; }
    if (empty($login)) { $erros[] = "Informe o Login."; }
    if (empty($senha1)) { $erros[] = "Informe a Senha."; }
    if ($senha1 !== $senha2) { $erros[] = "As Senhas não coincidem!"; }

    // Processamento de upload de foto (agora opcional)
    // Usamos isset() para verificar se o campo existe em $_FILES e passamos ele.
    // Se não existir, passamos um array mockado para que a função não dê erro e retorne UPLOAD_ERR_NO_FILE.
    $fotoNome = processarUploadArquivo(
        $_FILES["txtFoto"] ?? ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0],
        ['image/gif', 'image/jpeg', 'image/png', 'image/bmp'],
        5 * 1024 * 1024, // 5 MB
        '../uploads/fotos/', // Caminho relativo a este script
        $erros
    );

    // Processamento de upload de PDF (agora opcional)
    $pdfNome = processarUploadArquivo(
        $_FILES["txtPDF"] ?? ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0],
        ['application/pdf'],
        5 * 1024 * 1024, // 5 MB
        '../uploads/pdfs/', // Caminho relativo a este script
        $erros
    );

    // Se houver erros em qualquer etapa, redireciona com as mensagens de erro
    if (!empty($erros)) {
        $msg_erros = json_encode($erros);
        header('Location: cadastrar_cliente.php?status=error&msg=' . urlencode($msg_erros));
        exit();
    }

    // Se não houver erros, tenta inserir os dados no banco de dados
    try {
        $senhaHash = password_hash($senha1, PASSWORD_DEFAULT);

        // Prepara a consulta SQL com prepared statements para segurança
        // Certifique-se de que as colunas 'foto_nome' e 'pdf_nome' no seu banco de dados
        // aceitam valores NULL ou strings vazias, dependendo do que você configurou.
        // VARCHAR ou TEXT são geralmente apropriados.
        $sql = "INSERT INTO Clientes (nome, email, telefone, cpf, endereco, dtNasc, estado, sexo, cinema, musica, informatica, login, senha, foto_nome, pdf_nome)
                VALUES (:nome, :email, :telefone, :cpf, :endereco, :dtNasc, :estado, :sexo, :cinema, :musica, :informatica, :login, :senha, :foto_nome, :pdf_nome)";

        $stmt = $pdo->prepare($sql);

        // Vincula os parâmetros
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':dtNasc', $dtNasc);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':sexo', $sexo);
        $stmt->bindParam(':cinema', $cinema, PDO::PARAM_BOOL); // Armazenar como boolean
        $stmt->bindParam(':musica', $musica, PDO::PARAM_BOOL);
        $stmt->bindParam(':informatica', $informatica, PDO::PARAM_BOOL);
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':senha', $senhaHash);
        $stmt->bindParam(':foto_nome', $fotoNome); // Será NULL se nenhum arquivo for enviado ou houver erro
        $stmt->bindParam(':pdf_nome', $pdfNome);   // Será NULL se nenhum arquivo for enviado ou houver erro

        // Executa a consulta
        if ($stmt->execute()) {
            // Redireciona de volta para a página de cadastro com mensagem de sucesso
            header('Location: cadastrar_cliente.php?status=success');
            exit();
        } else {
            // Se a execução falhar, pega o erro do PDO
            $errorInfo = $stmt->errorInfo();
            $erros[] = "Erro ao inserir no banco de dados: " . ($errorInfo[2] ?? "Erro desconhecido.");
            header('Location: cadastrar_cliente.php?status=error&msg=' . urlencode(json_encode($erros)));
            exit();
        }
    } catch (PDOException $e) {
        // Captura exceções do PDO (ex: erro de conexão, violação de UNIQUE key)
        $erros[] = "Erro no banco de dados: " . $e->getMessage();
        // Logar o erro em produção: error_log($e->getMessage());
        header('Location: cadastrar_cliente.php?status=error&msg=' . urlencode(json_encode($erros)));
        exit();
    }
} else {
    $erros[] = "Método de requisição inválido.";
    header('Location: cadastrar_cliente.php?status=error&msg=' . urlencode(json_encode($erros)));
    exit();
}

?>