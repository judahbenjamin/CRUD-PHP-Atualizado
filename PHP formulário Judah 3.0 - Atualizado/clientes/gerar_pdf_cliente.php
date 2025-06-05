<?php
require_once '../vendor/autoload.php'; // Ajuste o caminho se necessário

use Dompdf\Dompdf;
use Dompdf\Options;

// Inclui o arquivo de conexão com o banco de dados PDO
require_once '../database/conexaobd.php'; // Ajuste o caminho se necessário

define('UPLOAD_DIR_FOTOS', '../uploads/fotos/'); // Caminho relativo ao script
define('UPLOAD_DIR_PDFS', '../uploads/pdfs/'); // Caminho relativo ao script

$erros = [];
$cliente = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $idCliente = $_GET['id'];

    try {
        // Busca os dados do cliente
        $sql = "SELECT c.*, e.nome as nomeEstado FROM Clientes c JOIN Estados e ON c.estado = e.sigla WHERE idCliente = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            $erros[] = "Cliente não encontrado.";
        }
    } catch (PDOException $e) {
        $erros[] = "Erro ao buscar dados do cliente: " . $e->getMessage();
    }
} else {
    $erros[] = "ID do cliente não fornecido.";
}

// Prepara o HTML para exibição e para o PDF
$html = "<div style='font-family: sans-serif; background-color: #f4f4f4; padding: 20px;'>";
$html .= "<h1 style='text-align: center; color: #333;'>Dados do Cliente</h1>";

if (!empty($erros)) {
    $html .= "<h2 style='color: red;'>Erros Encontrados:</h2>";
    $html .= "<ul style='list-style-type: none; padding: 0;'>";
    foreach ($erros as $erro) {
        $html .= "<li style='color: red; margin-bottom: 5px;'>" . htmlspecialchars($erro) . "</li>";
    }
    $html .= "</ul>";
} elseif ($cliente) {
    $html .= "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
    $html .= "<tr><th style='border-bottom: 1px solid #ddd; padding: 10px; text-align: left;'>ID</th><td style='border-bottom: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($cliente['idCliente']) . "</td></tr>";
    $html .= "<tr><th style='border-bottom: 1px solid #ddd; padding: 10px; text-align: left;'>Nome</th><td style='border-bottom: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($cliente['nome']) . "</td></tr>";
    $html .= "<tr><th style='border-bottom: 1px solid #ddd; padding: 10px; text-align: left;'>E-mail</th><td style='border-bottom: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($cliente['email']) . "</td></tr>";
    $html .= "<tr><th style='border-bottom: 1px solid #ddd; padding: 10px; text-align: left;'>Telefone</th><td style='border-bottom: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($cliente['telefone']) . "</td></tr>";
    $html .= "<tr><th style='border-bottom: 1px solid #ddd; padding: 10px; text-align: left;'>CPF</th><td style='border-bottom: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($cliente['cpf']) . "</td></tr>";
    $html .= "<tr><th style='border-bottom: 1px solid #ddd; padding: 10px; text-align: left;'>Endereço</th><td style='border-bottom: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($cliente['endereco']) . "</td></tr>";
    $html .= "<tr><th style='border-bottom: 1px solid #ddd; padding: 10px; text-align: left;'>Estado</th><td style='border-bottom: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($cliente['nomeEstado']) . "</td></tr>";
    $html .= "<tr><th style='border-bottom: 1px solid #ddd; padding: 10px; text-align: left;'>Data de Nascimento</th><td style='border-bottom: 1px solid #ddd; padding: 10px;'>" . date('d/m/Y', strtotime($cliente['dtNasc'])) . "</td></tr>";
    $html .= "<tr><th style='border-bottom: 1px solid #ddd; padding: 10px; text-align: left;'>Sexo</th><td style='border-bottom: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($cliente['sexo']) . "</td></tr>";
    $html .= "<tr><th style='border-bottom: 1px solid #ddd; padding: 10px; text-align: left;'>Login</th><td style='border-bottom: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($cliente['login']) . "</td></tr>";
    $html .= "</table>";

    $html .= "<h2 style='color: #333;'>Interesses</h2>";
    $html .= "<ul style='list-style-type: none; padding: 0;'>";
    if ($cliente['cinema']) { $html .= "<li style='margin-bottom: 5px;'>Cinema</li>"; }
    if ($cliente['musica']) { $html .= "<li style='margin-bottom: 5px;'>Música</li>"; }
    if ($cliente['informatica']) { $html .= "<li style='margin-bottom: 5px;'>Informática</li>"; }
    $html .= "</ul>";

    // Exibir foto
    if (!empty($cliente['foto_nome']) && file_exists(UPLOAD_DIR_FOTOS . $cliente['foto_nome'])) {
        $caminho_foto = UPLOAD_DIR_FOTOS . $cliente['foto_nome'];
        $tipo_imagem = mime_content_type($caminho_foto); // Use mime_content_type para obter o tipo real
        $dados_imagem = base64_encode(file_get_contents($caminho_foto));
        $src_imagem = 'data:' . $tipo_imagem . ';base64,' . $dados_imagem;
        $html .= "<h2 style='color: #333; margin-top: 20px;'>Foto</h2>";
        $html .= "<img src='" . $src_imagem . "' style='max-width:200px; height:auto; border: 1px solid #ddd;'>";
    } else {
        $html .= "<p style='margin-top: 20px; color: #555;'>Foto não disponível.</p>";
    }

    // Exibir link para PDF
    if (!empty($cliente['pdf_nome']) && file_exists(UPLOAD_DIR_PDFS . $cliente['pdf_nome'])) {
        $caminho_pdf_web = str_replace('../', '', UPLOAD_DIR_PDFS) . $cliente['pdf_nome']; // Caminho relativo para o navegador
        $html .= "<p style='margin-top: 20px;'><strong>Currículo PDF:</strong> <a href='" . htmlspecialchars($caminho_pdf_web) . "' target='_blank'>Visualizar PDF</a></p>";
    } else {
        $html .= "<p style='margin-top: 20px; color: #555;'>Currículo PDF não disponível.</p>";
    }
}
$html .= "</div>";

// Verifica se a requisição é para gerar PDF ou exibir HTML
if (isset($_GET['gerar_pdf']) && $_GET['gerar_pdf'] == 'true' && empty($erros)) {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', TRUE); // Habilita o carregamento de imagens remotas (necessário para base64 images)

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("cadastro_cliente_" . $cliente['idCliente'] . ".pdf", array("Attachment" => 0));
    exit();
} else {
    // Exibe o HTML no navegador
    echo $html;
    echo "<div class='button-container' style='text-align:center; margin-top: 20px;'>";
    if ($cliente && empty($erros)) {
        echo "<a href='?id=" . $cliente['idCliente'] . "&gerar_pdf=true' style='background-color:#28a745; color:white; padding:10px 15px; text-decoration:none; border-radius:5px; margin-right:10px;'>Gerar PDF</a>";
    }
    echo "<a href='listar_clientes.php' style='background-color:#6c757d; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;'>Voltar para Lista</a>";
    echo "</div>";
}
?>
<style>
    body {
        font-family: sans-serif;
        background-color: #f0f2f5;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-top: 40px;
    }
    .container {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 700px;
        margin-bottom: 20px;
    }
    h1, h2 {
        color: #1877f2;
        text-align: center;
        margin-bottom: 20px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
        font-weight: bold;
        color: #333;
    }
    ul {
        padding-left: 20px;
    }
    ul li {
        margin-bottom: 5px;
    }
    .error-message {
        color: red;
        font-weight: bold;
        margin-bottom: 20px;
        text-align: center;
    }
    .button-container {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    .button-container a {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        color: white;
        margin: 0 5px;
        transition: background-color 0.3s ease;
    }
    .button-container a:hover {
        opacity: 0.9;
    }
</style>