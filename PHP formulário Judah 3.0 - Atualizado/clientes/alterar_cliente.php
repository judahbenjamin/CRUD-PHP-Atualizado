<?php
// Inclua o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php');

$cliente = null;
$estados = [];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $idCliente = $_GET['id'];

    try {
        // Buscar os dados do cliente para pré-preencher o formulário
        $sql_cliente = "SELECT * FROM Clientes WHERE idCliente = :idCliente";
        $stmt_cliente = $pdo->prepare($sql_cliente);
        $stmt_cliente->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
        $stmt_cliente->execute();
        $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            header('Location: listar_clientes.php?status=error_update&msg=not_found');
            exit();
        }

        // Buscar todos os estados para o combobox
        $sql_estados = "SELECT sigla, nome FROM Estados ORDER BY nome";
        $stmt_estados = $pdo->query($sql_estados);
        $estados = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Em produção, você logaria o erro, não o exibiria
        // error_log("Erro ao carregar dados do cliente ou estados: " . $e->getMessage());
        header('Location: listar_clientes.php?status=error_update&msg=db_error');
        exit();
    }
} else {
    header('Location: listar_clientes.php?status=error_update&msg=no_id');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Cliente</title>
    <link rel="stylesheet" href="../css/cadastrar_cliente.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body>
    <div class="container-cadastro">
        <h1>Alterar Cliente</h1>
        <div class="form-divider"></div>
        <form action="alterar_cliente_processa.php" method="post" onsubmit="return validarFormulario()" enctype="multipart/form-data">
            <input type="hidden" name="idCliente" value="<?php echo htmlspecialchars($cliente['idCliente'] ?? ''); ?>">
            <input type="hidden" name="foto_nome_atual" value="<?php echo htmlspecialchars($cliente['foto_nome'] ?? ''); ?>">
            <input type="hidden" name="pdf_nome_atual" value="<?php echo htmlspecialchars($cliente['pdf_nome'] ?? ''); ?>">

            <div class="form-group">
                <label for="txtFoto">Foto (GIF, JPEG, PNG, BMP - Max 5MB) - Deixe em branco para manter a atual:</label>
                <input type="file" name="txtFoto" id="txtFoto" accept="image/gif, image/jpeg, image/png, image/bmp">
                <?php if (!empty($cliente['foto_nome'])): ?>
                    <p>Foto atual: <a href="../uploads/fotos/<?php echo htmlspecialchars($cliente['foto_nome'] ?? ''); ?>" target="_blank"><?php echo htmlspecialchars($cliente['foto_nome'] ?? ''); ?></a></p>
                    <img src="../uploads/fotos/<?php echo htmlspecialchars($cliente['foto_nome'] ?? ''); ?>" alt="Foto do Cliente" style="max-width: 100px; max-height: 100px; margin-top: 10px; border-radius: 5px;">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="txtNome">Nome Completo:</label>
                <input type="text" name="txtNome" id="txtNome" maxlength="100" value="<?php echo htmlspecialchars($cliente['nome'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="txtEmail">E-mail:</label>
                <input type="email" name="txtEmail" id="txtEmail" maxlength="100" value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="txtTelefone">Telefone:</label>
                <input type="text" name="txtTelefone" id="txtTelefone" maxlength="20" value="<?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="txtCPF">CPF (apenas números):</label>
                <input type="text" name="txtCPF" id="txtCPF" maxlength="11" value="<?php echo htmlspecialchars($cliente['cpf'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="txtEndereco">Endereço:</label>
                <textarea name="txtEndereco" id="txtEndereco" cols="30" rows="4" maxlength="200"><?php echo htmlspecialchars($cliente['endereco'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="listEstados">Estado:</label>
                <select name="listEstados" id="listEstados" required>
                    <option value="">Selecione</option>
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?php echo htmlspecialchars($estado['sigla'] ?? ''); ?>" <?php echo (($estado['sigla'] ?? '') == ($cliente['estado'] ?? '')) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($estado['nome'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="txtData">Data Nasc. (DD/MM/AAAA):</label>
                <input type="text" name="txtData" id="txtData" placeholder="DD/MM/AAAA" value="<?php echo (!empty($cliente['dtNasc']) && $cliente['dtNasc'] !== '0000-00-00') ? htmlspecialchars(date('d/m/Y', strtotime($cliente['dtNasc']))) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Sexo:</label><br>
                <label for="sexo_m">
                    <input type="radio" name="sexo" value="M" id="sexo_m" <?php echo (($cliente['sexo'] ?? '') == 'M') ? 'checked' : ''; ?> required> Masculino
                </label>
                <label for="sexo_f">
                    <input type="radio" name="sexo" value="F" id="sexo_f" <?php echo (($cliente['sexo'] ?? '') == 'F') ? 'checked' : ''; ?>> Feminino
                </label>
            </div>

            <div class="form-group">
                <label>Áreas de Interesse:</label><br>
                <label for="checkCinema">
                    <input type="checkbox" name="checkCinema" value="1" id="checkCinema" <?php echo (($cliente['cinema'] ?? 0) == 1) ? 'checked' : ''; ?>> Cinema
                </label><br>
                <label for="checkMusica">
                    <input type="checkbox" name="checkMusica" value="1" id="checkMusica" <?php echo (($cliente['musica'] ?? 0) == 1) ? 'checked' : ''; ?>> Música
                </label><br>
                <label for="checkInformatica">
                    <input type="checkbox" name="checkInformatica" value="1" id="checkInformatica" <?php echo (($cliente['informatica'] ?? 0) == 1) ? 'checked' : ''; ?>> Informática
                </label>
            </div>

            <div class="form-group">
                <label for="txtLogin">Login:</label>
                <input type="text" name="txtLogin" id="txtLogin" maxlength="50" value="<?php echo htmlspecialchars($cliente['login'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="txtSenha1">Nova Senha (deixe em branco para manter a atual):</label>
                <input type="password" name="txtSenha1" id="txtSenha1">
            </div>

            <div class="form-group">
                <label for="txtSenha2">Confirmação da Nova Senha:</label>
                <input type="password" name="txtSenha2" id="txtSenha2">
            </div>

            <div class="form-group">
                <label for="txtPDF">Adicionar Currículo (PDF - Max 5MB) - Deixe em branco para manter o atual:</label>
                <input type="file" name="txtPDF" id="txtPDF" accept="application/pdf">
                <?php if (!empty($cliente['pdf_nome'])): ?>
                    <p>PDF atual: <a href="../uploads/pdfs/<?php echo htmlspecialchars($cliente['pdf_nome'] ?? ''); ?>" target="_blank"><?php echo htmlspecialchars($cliente['pdf_nome'] ?? ''); ?></a></p>
                <?php endif; ?>
            </div>

            <div class="form-group-buttons">
                <input type="submit" name="btnSalvar" value="Salvar Alterações">
            </div>
        </form>
        <div class="button-container">
            <a href="listar_clientes.php">Voltar à Lista de Clientes</a>
            <a href="../index.html">Voltar à Página Inicial</a>
        </div>
    </div>

    <script>
        // Inclua as bibliotecas jQuery e jQuery Mask se ainda não o fez no head ou antes deste script
        // <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        // <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js">

        $(document).ready(function(){
            // Máscara para Telefone (pode ser celular ou fixo)
            var SPMaskBehavior = function (val) {
                return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
            },
            spOptions = {
                onKeyPress: function(val, e, field, options) {
                    field.mask(SPMaskBehavior.apply({}, arguments), options);
                }
            };
            $('#txtTelefone').mask(SPMaskBehavior, spOptions);

            // Máscara para CPF
            $('#txtCPF').mask('000.000.000-00', {reverse: true});

            // Máscara para Data de Nascimento (DD/MM/AAAA)
            $('#txtData').mask('00/00/0000');

            // Adiciona um listener para validar a data ao sair do campo
            $('#txtData').on('blur', function() {
                const dataString = this.value;
                if (!dataString) return;

                if (!validarData(dataString)) {
                    exibirMensagemErro('txtData', 'Data de nascimento inválida (DD/MM/AAAA).');
                    this.value = '';
                    this.focus();
                    return;
                }

                const partes = dataString.split('/');
                const dia = parseInt(partes[0], 10);
                const mes = parseInt(partes[1], 10);
                const ano = parseInt(partes[2], 10);

                const dataSelecionada = new Date(ano, mes - 1, dia);
                const dataAtual = new Date();
                dataAtual.setHours(0, 0, 0, 0); // Zera horas para comparação apenas de data

                if (dataSelecionada > dataAtual) {
                    exibirMensagemErro('txtData', 'A data de nascimento não pode ser uma data futura!');
                    this.value = '';
                    this.focus();
                } else {
                    limparMensagemApenasCampo('txtData'); // Limpa se for válido
                }
            });
        });

        // Reutilizar as funções de validação do cadastrar_cliente.php
        // Apenas para validações JavaScript do lado do cliente
        function validarFormulario() {
            let nome = document.getElementById('txtNome').value.trim();
            let email = document.getElementById('txtEmail').value.trim();
            let telefone = document.getElementById('txtTelefone').value.trim();
            let cpf = document.getElementById('txtCPF').value.trim();
            let dataNasc = document.getElementById('txtData').value.trim();
            let login = document.getElementById('txtLogin').value.trim();
            let senha1 = document.getElementById('txtSenha1').value;
            let senha2 = document.getElementById('txtSenha2').value;
            let estado = document.getElementById('listEstados').value;
            let foto = document.getElementById('txtFoto').files[0];
            let pdf = document.getElementById('txtPDF').files[0];
            let sexoSelected = document.querySelector('input[name="sexo"]:checked');

            limparMensagensErro();
            let valido = true;

            // Validação de Foto (se uma nova foto for selecionada)
            if (foto) {
                if (!validarImagem(foto)) {
                    exibirMensagemErro('txtFoto', 'Arquivo de imagem inválido (GIF, JPEG, PNG, BMP).');
                    valido = false;
                } else if (foto.size > 5000000) { // 5 MB
                    exibirMensagemErro('txtFoto', 'Tamanho da imagem excede o limite permitido (5 MB).');
                    valido = false;
                }
            }

            // Validação de PDF (se um novo PDF for selecionado)
            if (pdf) {
                if (!validarPDF(pdf)) {
                    exibirMensagemErro('txtPDF', 'Arquivo PDF inválido.');
                    valido = false;
                } else if (pdf.size > 5000000) { // 5 MB
                    exibirMensagemErro('txtPDF', 'Tamanho do arquivo PDF excede o limite permitido (5 MB).');
                    valido = false;
                }
            }

            if (nome === '') { exibirMensagemErro('txtNome', 'Por favor, preencha o nome completo.'); valido = false; }
            if (email === '') { exibirMensagemErro('txtEmail', 'Por favor, preencha o e-mail.'); valido = false; }
            else if (!validarEmail(email)) { exibirMensagemErro('txtEmail', 'E-mail inválido.'); valido = false; }

            let telefoneLimpo = telefone.replace(/\D/g, '');
            if (telefone === '') {
                 // Telefone não é obrigatório no alterar? Se for, tire o required do HTML e adicione essa validação
                 // Se for obrigatório, esta validação já deve bastar junto com o required no HTML
                 exibirMensagemErro('txtTelefone', 'Por favor, preencha o telefone.'); valido = false;
            } else if (telefoneLimpo.length < 10 || telefoneLimpo.length > 11) {
                 exibirMensagemErro('txtTelefone', 'Telefone inválido. Deve conter 10 ou 11 dígitos (incluindo DDD).'); valido = false;
            }


            let cpfLimpo = cpf.replace(/\D/g, '');
            if (cpf === '') { exibirMensagemErro('txtCPF', 'Por favor, preencha o CPF.'); valido = false; }
            else if (cpfLimpo.length !== 11) {
                exibirMensagemErro('txtCPF', 'CPF inválido. Deve conter 11 dígitos.');
                valido = false;
            }
            else if (!validarCPF(cpfLimpo)) { exibirMensagemErro('txtCPF', 'CPF inválido.'); valido = false; }

            if (dataNasc === '') { exibirMensagemErro('txtData', 'Por favor, preencha a data de nascimento.'); valido = false; }
            else if (!validarData(dataNasc)) { exibirMensagemErro('txtData', 'Data de nascimento inválida (DD/MM/AAAA).'); valido = false; }
            else {
                const partes = dataNasc.split('/');
                const dia = parseInt(partes[0], 10);
                const mes = parseInt(partes[1], 10);
                const ano = parseInt(partes[2], 10);
                const dataSelecionadaObj = new Date(ano, mes - 1, dia);
                const dataAtualObj = new Date();
                dataAtualObj.setHours(0, 0, 0, 0);

                if (dataSelecionadaObj > dataAtualObj) {
                    exibirMensagemErro('txtData', 'A data de nascimento não pode ser uma data futura!');
                    valido = false;
                }
            }

            if (estado === '') { exibirMensagemErro('listEstados', 'Por favor, selecione um estado.'); valido = false; }
            if (!sexoSelected) {
                let sexoParent = document.getElementById('sexo_m').parentNode.parentNode;
                exibirMensagemErroElemento(sexoParent, 'Por favor, selecione o sexo.');
                valido = false;
            }
            if (login === '') { exibirMensagemErro('txtLogin', 'Por favor, preencha o login.'); valido = false; }

            // Validação de senhas apenas se forem preenchidas (para alteração)
            if (senha1 !== '' || senha2 !== '') {
                if (senha1 === '') { exibirMensagemErro('txtSenha1', 'Se você está alterando a senha, preencha a nova senha.'); valido = false; }
                if (senha2 === '') { exibirMensagemErro('txtSenha2', 'Se você está alterando a senha, confirme a nova senha.'); valido = false; }
                else if (senha1 !== senha2) { exibirMensagemErro('txtSenha2', 'As senhas não coincidem.'); valido = false; }
            }

            return valido;
        }

        // --- Funções de Validação JavaScript Auxiliares ---
        function validarImagem(arquivo) {
            const tiposPermitidos = ['image/gif', 'image/jpeg', 'image/png', 'image/bmp'];
            return tiposPermitidos.includes(arquivo.type);
        }

        function validarPDF(arquivo) {
            return arquivo.type === 'application/pdf';
        }

        function validarEmail(email) {
            const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regexEmail.test(email);
        }

        // A função validarTelefone não era usada diretamente no script, mas a lógica já estava na validação do formulário
        // Mantive aqui para referência, mas a validação principal para o campo txtTelefone é feita com a máscara e no submit.
        // function validarTelefone(telefone) {
        //     const telefoneLimpo = telefone.replace(/\D/g, '');
        //     return telefoneLimpo.length >= 10 && telefoneLimpo.length <= 11;
        // }


        function exibirMensagemErro(campoId, mensagem) {
            let campo = document.getElementById(campoId);
            if (campo) {
                // Limpa mensagens de erro existentes para este campo antes de adicionar uma nova
                limparMensagemApenasCampo(campoId);

                let mensagemErro = document.createElement('div');
                mensagemErro.className = 'error-message';
                mensagemErro.textContent = mensagem;
                campo.parentNode.insertBefore(mensagemErro, campo.nextSibling);
            }
        }

        function exibirMensagemErroElemento(elemento, mensagem) {
            if (elemento) {
                let mensagemErro = document.createElement('div');
                mensagemErro.className = 'error-message';
                mensagemErro.textContent = mensagem;
                // Busca se já existe uma mensagem de erro direta para este elemento e remove
                let existingError = elemento.nextElementSibling;
                if (existingError && existingError.classList.contains('error-message')) {
                    existingError.remove();
                }
                elemento.parentNode.insertBefore(mensagemErro, elemento.nextSibling);
            }
        }

        function limparMensagensErro() {
            let mensagensErro = document.querySelectorAll('.error-message');
            mensagensErro.forEach(function (mensagem) {
                mensagem.remove();
            });
        }

        function limparMensagemApenasCampo(campoId) {
            let campo = document.getElementById(campoId);
            if (campo && campo.nextSibling && campo.nextSibling.classList && campo.nextSibling.classList.contains('error-message')) {
                campo.nextSibling.remove();
            }
        }

        function validarCPF(cpf) {
            cpf = cpf.replace(/\D/g, ''); // Garante que só tem dígitos
            if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;
            let soma = 0;
            for (let i = 0; i < 9; i++) soma += parseInt(cpf.charAt(i)) * (10 - i);
            let resto = 11 - (soma % 11);
            let digito1 = resto >= 10 ? 0 : resto;
            if (parseInt(cpf.charAt(9)) !== digito1) return false;
            soma = 0;
            for (let i = 0; i < 10; i++) soma += parseInt(cpf.charAt(i)) * (11 - i);
            resto = 11 - (soma % 11);
            let digito2 = resto >= 10 ? 0 : resto;
            return parseInt(cpf.charAt(10)) === digito2;
        }

        function validarData(data) {
            const regexData = /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[012])\/(19|20)\d{2}$/;
            if (!regexData.test(data)) return false;
            const partes = data.split('/');
            const dia = parseInt(partes[0], 10);
            const mes = parseInt(partes[1], 10);
            const ano = parseInt(partes[2], 10);

            const dataObj = new Date(ano, mes - 1, dia);
            // Verifica se a data construída é a mesma que a inserida, evitando datas inválidas como 31/02
            if (dataObj.getFullYear() !== ano || dataObj.getMonth() !== mes - 1 || dataObj.getDate() !== dia) {
                return false;
            }

            return true;
        }
    </script>
    <style>
        /* Adicione este estilo ao seu cadastrar_cliente.css ou aqui mesmo no <style> */
        .error-message {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
            display: block; /* Garante que a mensagem fique em sua própria linha */
        }
    </style>
</body>
</html>