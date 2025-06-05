<?php
// Inclua o arquivo de conexão com o banco de dados PDO
require_once ('../database/conexaobd.php'); // Ajuste o caminho se necessário

$estados = [];
try {
    $sql_estados = "SELECT sigla, nome FROM Estados ORDER BY nome";
    $stmt_estados = $pdo->query($sql_estados);
    $estados = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em produção, você logaria o erro, não o exibiria
    // error_log("Erro ao carregar estados: " . $e->getMessage());
    $estados = []; // Garante que $estados é um array vazio em caso de erro
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Clientes</title>
    <link rel="stylesheet" href="../css/cadastrar_cliente.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body>
    <script>
    $(document).ready(function(){
        // Máscara para Telefone (pode ser celular ou fixo)
        // A máscara se adapta a 9 ou 8 dígitos + DDD
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

        // ******************************************************
        // NOVO: Validação de CPF via AJAX ao sair do campo
        // ******************************************************
        $('#txtCPF').on('blur', function() {
            let cpf = $(this).val(); // Pega o valor com máscara
            let cpfLimpo = cpf.replace(/\D/g, ''); // Remove a máscara

            // Limpa mensagens de erro e sucesso anteriores
            limparMensagemApenasCampo('txtCPF');
            $('#cpf_disponivel').remove(); // Remove mensagem de CPF disponível

            if (cpfLimpo.length === 11 && validarCPF(cpfLimpo)) { // Apenas se o CPF tiver 11 dígitos e for sintaticamente válido
                $.ajax({
                    url:'verificar_cpf.php', // Caminho para o seu novo arquivo PHP
                    method: 'POST',
                    data: { cpf: cpf }, // Envia o CPF com máscara ou limpo, o PHP vai limpar
                    dataType: 'json',
                    success: function(response) {
                        if (response.exists) {
                            exibirMensagemErro('txtCPF', response.message);
                            // Opcional: Desativar o botão de enviar ou focar no campo
                            // $('#txtCPF').focus();
                        } else {
                            // Opcional: Mostrar uma mensagem de sucesso
                            let mensagemSucesso = $('<div id="cpf_disponivel" class="success-message-inline"></div>');
                            mensagemSucesso.text('CPF disponível.');
                            $('#txtCPF').after(mensagemSucesso);
                        }
                    },
                    error: function(xhr, status, error) {
                        exibirMensagemErro('txtCPF', 'Erro na verificação do CPF. Tente novamente.');
                        console.error("Erro AJAX: ", status, error, xhr.responseText);
                    }
                });
            } else if (cpfLimpo.length > 0 && cpfLimpo.length !== 11) {
                exibirMensagemErro('txtCPF', 'CPF inválido. Deve conter 11 dígitos.');
            }
        });
        // ******************************************************
    });
    </script>
    <div class="container-cadastro">
        <h1>Cadastro de Clientes</h1>
        <div class="form-divider"></div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'success'): ?>
                <div class="success-message">Cliente cadastrado com sucesso!</div>
            <?php elseif ($_GET['status'] === 'error'): ?>
                <div class="error-message">
                    Erro ao cadastrar cliente:
                    <ul>
                        <?php
                        $erros_raw = isset($_GET['msg']) ? urldecode($_GET['msg']) : '';
                        $erros_array = json_decode($erros_raw, true);
                        if (is_array($erros_array)) {
                            foreach ($erros_array as $erro) {
                                echo "<li>" . htmlspecialchars($erro) . "</li>";
                            }
                        } else {
                            echo "<li>Detalhes do erro desconhecidos.</li>";
                        }
                        ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" name="formCadastro" action="cadastrar_cliente_processa.php" onsubmit="return validarFormulario()" enctype="multipart/form-data">
            <div class="form-group">
                <label for="txtFoto">Foto (GIF, JPEG, PNG, BMP - Max 5MB)</label>
                <input type="file" name="txtFoto" id="txtFoto" accept="image/gif, image/jpeg, image/png, image/bmp">
            </div>

            <div class="form-group">
                <label for="txtNome">Nome Completo:</label>
                <input type="text" name="txtNome" id="txtNome" maxlength="100" required>
            </div>

            <div class="form-group">
                <label for="txtEmail">E-mail:</label>
                <input type="email" name="txtEmail" id="txtEmail" maxlength="100" required>
            </div>

            <div class="form-group">
                <label for="txtTelefone">Telefone:</label>
                <input type="text" name="txtTelefone" id="txtTelefone" placeholder="(XX) XXXXX-XXXX" maxlength="15" required>
            </div>

            <div class="form-group">
                <label for="txtCPF">CPF:</label>
                <input type="text" name="txtCPF" id="txtCPF" placeholder="000.000.000-00" maxlength="14" required>
                <div id="cpf_feedback"></div>
            </div>

            <div class="form-group">
                <label for="txtEndereco">Endereço:</label>
                <textarea name="txtEndereco" id="txtEndereco" cols="30" rows="4" maxlength="200"></textarea>
            </div>

            <div class="form-group">
                <label for="listEstados">Estado:</label>
                <select name="listEstados" id="listEstados" required>
                    <option value="">Selecione</option>
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?php echo htmlspecialchars($estado['sigla']); ?>"><?php echo htmlspecialchars($estado['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="txtData">Data Nascimento:</label>
                <input type="text" name="txtData" id="txtData" placeholder="DD/MM/AAAA" maxlength="10" required>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const dataNascimentoInput = document.getElementById('txtData');

                        if (dataNascimentoInput) {
                            dataNascimentoInput.addEventListener('change', function() {
                                const dataString = this.value;
                                if (!dataString) return;

                                const partes = dataString.split('/');
                                if (partes.length !== 3) {
                                    alert('Formato de data inválido. Use DD/MM/AAAA.');
                                    this.value = '';
                                    this.focus();
                                    return;
                                }

                                const dia = parseInt(partes[0], 10);
                                const mes = parseInt(partes[1], 10);
                                const ano = parseInt(partes[2], 10);

                                const dataSelecionada = new Date(ano, mes - 1, dia);
                                const dataAtual = new Date();
                                dataAtual.setHours(0, 0, 0, 0);

                                if (
                                    isNaN(dataSelecionada.getTime()) ||
                                    dataSelecionada.getDate() !== dia ||
                                    dataSelecionada.getMonth() !== (mes - 1) ||
                                    dataSelecionada.getFullYear() !== ano
                                ) {
                                    alert('Data de nascimento inválida. Verifique o dia/mês/ano.');
                                    this.value = '';
                                    this.focus();
                                    return;
                                }

                                if (dataSelecionada > dataAtual) {
                                    alert('A data de nascimento não pode ser uma data futura!');
                                    this.value = '';
                                    this.focus();
                                }
                            });
                        }
                    });
                </script>
            </div>

            <div class="form-group">
                <label>Sexo:</label><br>
                <label for="sexo_m">
                    <input type="radio" name="sexo" value="M" id="sexo_m" required> Masculino
                </label>
                <label for="sexo_f">
                    <input type="radio" name="sexo" value="F" id="sexo_f"> Feminino
                </label>
            </div>

            <div class="form-group">
                <label>Áreas de Interesse:</label><br>
                <label for="checkCinema">
                    <input type="checkbox" name="interesses[]" value="Cinema" id="checkCinema"> Cinema
                </label><br>
                <label for="checkMusica">
                    <input type="checkbox" name="interesses[]" value="Música" id="checkMusica"> Música
                </label><br>
                <label for="checkInformatica">
                    <input type="checkbox" name="interesses[]" value="Informática" id="checkInformatica"> Informática
                </label>
            </div>

            <div class="form-group">
                <label for="txtLogin">Login:</label>
                <input type="text" name="txtLogin" id="txtLogin" maxlength="50" required>
            </div>

            <div class="form-group">
                <label for="txtSenha1">Senha:</label>
                <input type="password" name="txtSenha1" id="txtSenha1" required>
            </div>

            <div class="form-group">
                <label for="txtSenha2">Confirmação Senha:</label>
                <input type="password" name="txtSenha2" id="txtSenha2" required>
            </div>

            <div class="form-group">
                <label for="txtPDF">Adicionar Currículo (PDF - Max 5MB):</label>
                <input type="file" name="txtPDF" id="txtPDF" accept="application/pdf">
            </div>

            <div class="form-group-buttons">
                <input type="submit" name="btnEnviar" value="Enviar">
                <input type="reset" name="btnLimpar" value="Limpar">
            </div>
        </form>
        <div class="button-container">
            <a href="listar_clientes.php">Ver Clientes Cadastrados</a>
            <a href="../menu/index.html">Voltar à Página Inicial</a>
        </div>
    </div>

    <script>
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

            // // Validação de Foto
            // if (!foto) {
            //     exibirMensagemErro('txtFoto', 'Por favor, selecione uma foto.');
            //     valido = false;
            // } else 
            
            if (!validarImagem(foto)) {
                exibirMensagemErro('txtFoto', 'Arquivo de imagem inválido (GIF, JPEG, PNG, BMP).');
                valido = false;
            } else if (foto.size > 5000000) { // 5 MB
                exibirMensagemErro('txtFoto', 'Tamanho da imagem excede o limite permitido (5 MB).');
                valido = false;
            }

            // Validação de PDF (opcional, se não for obrigatório)
            if (pdf) {
                if (!validarPDF(pdf)) {
                    exibirMensagemErro('txtPDF', 'Arquivo PDF inválido.');
                    valido = false;
                } else if (pdf.size > 5000000) { // 5 MB
                    exibirMensagemErro('txtPDF', 'Tamanho do arquivo PDF excede o limite permitido (5 MB).');
                    valido = false;
                }
            }

            // Validação de Nome
            if (nome === '') {
                exibirMensagemErro('txtNome', 'Por favor, preencha o nome completo.');
                valido = false;
            }

            // Validação de E-mail
            if (email === '') {
                exibirMensagemErro('txtEmail', 'Por favor, preencha o e-mail.');
                valido = false;
            } else if (!validarEmail(email)) {
                exibirMensagemErro('txtEmail', 'E-mail inválido.');
                valido = false;
            }

            // Validação de Telefone
            let telefoneLimpo = telefone.replace(/\D/g, '');
            if (telefone === '') {
                exibirMensagemErro('txtTelefone', 'Por favor, preencha o telefone.');
                valido = false;
            } else if (telefoneLimpo.length < 10 || telefoneLimpo.length > 11) {
                 exibirMensagemErro('txtTelefone', 'Telefone inválido. Deve conter 10 ou 11 dígitos (incluindo DDD).');
                 valido = false;
            }

            // Validação de CPF (incluindo a verificação de existência no servidor)
            let cpfLimpo = cpf.replace(/\D/g, '');
            if (cpf === '') {
                exibirMensagemErro('txtCPF', 'Por favor, preencha o CPF.');
                valido = false;
            } else if (cpfLimpo.length !== 11) {
                exibirMensagemErro('txtCPF', 'CPF inválido. Deve conter 11 dígitos.');
                valido = false;
            } else if (!validarCPF(cpfLimpo)) {
                exibirMensagemErro('txtCPF', 'CPF inválido.');
                valido = false;
            }

            // NOVO: Validação para impedir o envio se o CPF já existe
            // Este é um ponto crucial. Como a verificação AJAX é assíncrona,
            // não podemos simplesmente retornar `false` aqui se a verificação ainda está em andamento.
            // A melhor abordagem é desabilitar o botão de submit enquanto o CPF está sendo verificado
            // ou fazer a validação de existência do CPF no lado do servidor (no cadastrar_cliente_processa.php)
            // ANTES de tentar inserir. Para manter a validação no JS, você pode:
            // 1. Manter uma flag global.
            // 2. Desabilitar o submit se o CPF estiver inválido/existente.
            // Por simplicidade, vamos **recomendar a validação final no lado do servidor também**,
            // mas o feedback visual via AJAX já ajuda muito.
            // Para a validação do `onsubmit`, vamos checar a mensagem de erro que o AJAX colocou.
            if ($('#txtCPF').next('.error-message').length > 0 && $('#txtCPF').next('.error-message').text().includes('Este CPF já está cadastrado.')) {
                valido = false; // Impede o envio se o erro de CPF existente está visível
            }


            // Validação de Data de Nascimento
            if (dataNasc === '') {
                exibirMensagemErro('txtData', 'Por favor, preencha a data de nascimento.');
                valido = false;
            } else if (!validarData(dataNasc)) {
                exibirMensagemErro('txtData', 'Data de nascimento inválida (DD/MM/AAAA).');
                valido = false;
            } else {
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


            // Validação de Estado
            if (estado === '') {
                exibirMensagemErro('listEstados', 'Por favor, selecione um estado.');
                valido = false;
            }

            // Validação de Sexo
            if (!sexoSelected) {
                let sexoParent = document.getElementById('sexo_m').parentNode.parentNode;
                exibirMensagemErroElemento(sexoParent, 'Por favor, selecione o sexo.');
                valido = false;
            }

            // Validação de Login
            if (login === '') {
                exibirMensagemErro('txtLogin', 'Por favor, preencha o login.');
                valido = false;
            }

            // Validação de Senhas
            if (senha1 === '') {
                exibirMensagemErro('txtSenha1', 'Por favor, preencha a senha.');
                valido = false;
            }

            if (senha2 === '') {
                exibirMensagemErro('txtSenha2', 'Por favor, confirme a senha.');
                valido = false;
            } else if (senha1 !== senha2) {
                exibirMensagemErro('txtSenha2', 'As senhas não coincidem.');
                valido = false;
            }

            return valido;
        }

        // --- Funções de Validação JavaScript ---
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
                elemento.parentNode.insertBefore(mensagemErro, elemento.nextSibling);
            }
        }

        function limparMensagensErro() {
            let mensagensErro = document.querySelectorAll('.error-message');
            mensagensErro.forEach(function (mensagem) {
                mensagem.remove();
            });
            // Adicionado para limpar mensagens de sucesso in-line do CPF
            $('#cpf_disponivel').remove();
        }

        function limparMensagemApenasCampo(campoId) {
             let campo = document.getElementById(campoId);
             if (campo && campo.nextSibling && campo.nextSibling.classList && campo.nextSibling.classList.contains('error-message')) {
                 campo.nextSibling.remove();
             }
        }

        function validarCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
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
            if (dataObj.getFullYear() !== ano || dataObj.getMonth() !== mes - 1 || dataObj.getDate() !== dia) {
                return false;
            }

            return true;
        }
    </script>
    <style>
        /* Adicione este estilo ao seu cadastrar_cliente.css ou aqui mesmo no <style> */
        .success-message-inline {
            color: green;
            font-size: 0.9em;
            margin-top: 5px;
            display: block; /* Para aparecer abaixo do input */
        }
    </style>
</body>
</html>