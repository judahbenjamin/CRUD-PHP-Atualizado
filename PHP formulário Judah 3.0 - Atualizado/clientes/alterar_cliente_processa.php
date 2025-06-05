<?php
// Inclua o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php');

// Inicializa um array para armazenar os erros de validação
$erros = [];

// Funções de validação (copiadas de cadastrar_cliente_processa.php)
function validarString($valor) { /* ... */ return filter_var(trim($valor), FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); }
function validarCPF($cpf) { /* ... */
    $cpf = preg_replace('/[^0-9]/is', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) { return false; }
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) { $d += $cpf[$c] * (($t + 1) - $c); }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) { return false; }
    }
    return true;
}
function validarData($data) { /* ... */
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $matches)) {
        $dia = intval($matches[1]); $mes = intval($matches[2]); $ano = intval($matches[3]);
        if (checkdate($mes, $dia, $ano)) { return "$ano-$mes-$dia"; }
    }
    return false;
}
function processarUploadArquivo($arquivo, $tiposPermitidos, $tamanhoMaximo, $diretorioDestino, &$erros) {
    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        if (!in_array($arquivo['type'], $tiposPermitidos)) { $erros[] = "Tipo de arquivo inválido: " . $arquivo['name']; return false; }
        if ($arquivo['size'] > $tamanhoMaximo) { $erros[] = "Arquivo muito grande: " . $arquivo['name'] . " (máx " . ($tamanhoMaximo / (1024 * 1024)) . " MB)"; return false; }
        if (!is_dir($diretorioDestino)) { mkdir($diretorioDestino, 0755, true); }
        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $nomeUnico = uniqid() . '.' . $extensao;
        $caminhoCompleto = $diretorioDestino . $nomeUnico;
        if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) { return $nomeUnico; }
        else { $erros[] = "Falha ao mover o arquivo: " . $arquivo['name']; return false; }
    } elseif ($arquivo['error'] !== UPLOAD_ERR_NO_FILE) {
        $erros[] = "Erro de upload: " . $arquivo['name'] . " (código: " . $arquivo['error'] . ")"; return false;
    }
    return true;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idCliente = $_POST['idCliente'] ?? null;
    $foto_nome_atual = $_POST['foto_nome_atual'] ?? null;
    $pdf_nome_atual = $_POST['pdf_nome_atual'] ?? null;

    // Coleta e validação dos dados do formulário
    $nome = validarString($_POST["txtNome"] ?? "");
    $email = filter_var($_POST["txtEmail"] ?? "", FILTER_VALIDATE_EMAIL);
    $telefone = validarString($_POST["txtTelefone"] ?? "");
    $endereco = validarString($_POST["txtEndereco"] ?? "");
    $cpf = validarString($_POST["txtCPF"] ?? "");
    $dtNasc = validarData($_POST["txtData"] ?? "");
    $estado = validarString($_POST["listEstados"] ?? "");
    $sexo = validarString($_POST["sexo"] ?? "");
    $cinema = isset($_POST["checkCinema"]) ? 1 : 0;
    $musica = isset($_POST["checkMusica"]) ? 1 : 0;
    $informatica = isset($_POST["checkInformatica"]) ? 1 : 0;
    $login = validarString($_POST["txtLogin"] ?? "");
    $senha1 = $_POST["txtSenha1"] ?? "";
    $senha2 = $_POST["txtSenha2"] ?? "";

    // Validação dos campos obrigatórios e formatos
    if (empty($idCliente)) { $erros[] = "ID do cliente não fornecido."; }
    if (empty($nome)) { $erros[] = "Informe o Nome."; }
    if (!$email) { $erros[] = "Informe um E-mail válido."; }
    if (!empty($telefone) && !preg_match('/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/', $telefone)) { $erros[] = "Informe um Telefone válido."; }
    if (empty($endereco)) { $erros[] = "Informe o Endereço."; }
    if (empty($cpf) || !validarCPF($cpf)) { $erros[] = "Informe um CPF válido."; }
    if (!$dtNasc) { $erros[] = "Informe uma Data de Nascimento válida (DD/MM/AAAA)."; }
    if (empty($estado)) { $erros[] = "Selecione um Estado."; }
    if (empty($sexo)) { $erros[] = "Selecione o Sexo."; }
    if (empty($login)) { $erros[] = "Informe o Login."; }

    // Validação de senha apenas se uma nova for fornecida
    $senhaHash = null;
    if (!empty($senha1) || !empty($senha2)) {
        if (empty($senha1)) { $erros[] = "Nova Senha não pode ser vazia se confirmacao for preenchida."; }
        if ($senha1 !== $senha2) { $erros[] = "As Novas Senhas não coincidem!"; }
        else { $senhaHash = password_hash($senha1, PASSWORD_DEFAULT); }
    }

    // Processamento de upload de foto
    $fotoNome = $foto_nome_atual; // Mantém o nome da foto atual por padrão
    if (isset($_FILES["txtFoto"]) && $_FILES["txtFoto"]['error'] !== UPLOAD_ERR_NO_FILE) {
        $novoFotoNome = processarUploadArquivo(
            $_FILES["txtFoto"],
            ['image/gif', 'image/jpeg', 'image/png', 'image/bmp'],
            5 * 1024 * 1024, // 5 MB
            '../uploads/fotos/', // Caminho relativo a este script
            $erros
        );
        if ($novoFotoNome) {
            // Se um novo arquivo foi enviado com sucesso, exclui o antigo se existir
            if (!empty($foto_nome_atual) && file_exists('../uploads/fotos/' . $foto_nome_atual)) {
                unlink('../uploads/fotos/' . $foto_nome_atual);
            }
            $fotoNome = $novoFotoNome;
        } else {
            $erros[] = "Erro no upload da nova foto.";
        }
    }

    // Processamento de upload de PDF
    $pdfNome = $pdf_nome_atual; // Mantém o nome do PDF atual por padrão
    if (isset($_FILES["txtPDF"]) && $_FILES["txtPDF"]['error'] !== UPLOAD_ERR_NO_FILE) {
        $novoPdfNome = processarUploadArquivo(
            $_FILES["txtPDF"],
            ['application/pdf'],
            5 * 1024 * 1024, // 5 MB
            '../uploads/pdfs/', // Caminho relativo a este script
            $erros
        );
        if ($novoPdfNome) {
            // Se um novo arquivo foi enviado com sucesso, exclui o antigo se existir
            if (!empty($pdf_nome_atual) && file_exists('../uploads/pdfs/' . $pdf_nome_atual)) {
                unlink('../uploads/pdfs/' . $pdf_nome_atual);
            }
            $pdfNome = $novoPdfNome;
        } else {
            $erros[] = "Erro no upload do novo PDF.";
        }
    }

    // Se houver erros em qualquer etapa, redireciona com as mensagens de erro
    if (!empty($erros)) {
        $msg_erros = json_encode($erros);
        header('Location: alterar_cliente.php?id=' . $idCliente . '&status=error_update&msg=' . urlencode($msg_erros));
        exit();
    }

    // Se não houver erros, tenta atualizar os dados no banco de dados
    try {
        $sql = "UPDATE Clientes SET 
                    nome = :nome, 
                    email = :email,
                    telefone = :telefone,
                    cpf = :cpf, 
                    endereco = :endereco, 
                    dtNasc = :dtNasc, 
                    estado = :estado, 
                    sexo = :sexo, 
                    cinema = :cinema, 
                    musica = :musica, 
                    informatica = :informatica, 
                    login = :login, 
                    foto_nome = :foto_nome, 
                    pdf_nome = :pdf_nome";
        
        if ($senhaHash !== null) { // Adiciona a senha apenas se foi alterada
            $sql .= ", senha = :senha";
        }
        $sql .= " WHERE idCliente = :idCliente";

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
        $stmt->bindParam(':cinema', $cinema, PDO::PARAM_BOOL);
        $stmt->bindParam(':musica', $musica, PDO::PARAM_BOOL);
        $stmt->bindParam(':informatica', $informatica, PDO::PARAM_BOOL);
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':foto_nome', $fotoNome);
        $stmt->bindParam(':pdf_nome', $pdfNome);
        if ($senhaHash !== null) {
            $stmt->bindParam(':senha', $senhaHash);
        }
        $stmt->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);

        if ($stmt->execute()) {
            header('Location: listar_clientes.php?status=success_update');
            exit();
        } else {
            $errorInfo = $stmt->errorInfo();
            $erros[] = "Erro ao atualizar no banco de dados: " . ($errorInfo[2] ?? "Erro desconhecido.");
            header('Location: alterar_cliente.php?id=' . $idCliente . '&status=error_update&msg=' . urlencode(json_encode($erros)));
            exit();
        }
    } catch (PDOException $e) {
        $erros[] = "Erro no banco de dados: " . $e->getMessage();
        header('Location: alterar_cliente.php?id=' . $idCliente . '&status=error_update&msg=' . urlencode(json_encode($erros)));
        exit();
    }
} else {
    $erros[] = "Método de requisição inválido.";
    header('Location: listar_clientes.php?status=error_update&msg=' . urlencode(json_encode($erros)));
    exit();
}