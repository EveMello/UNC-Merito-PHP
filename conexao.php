<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Configuração do banco MySQL
$host = 'localhost';
$db   = 'merito_health';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Criação de tabelas se não existirem
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            nome VARCHAR(255) NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pacientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            cpf VARCHAR(14) UNIQUE NOT NULL,
            data_nascimento DATE NOT NULL,
            contato VARCHAR(50),
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    die("Erro ao conectar ao banco: " . $e->getMessage());
}

// Função auxiliar
function post($key, $default = null) {
    return $_POST[$key] ?? $default;
}

$action = post('action');
if (!$action) die('Ação não especificada.');

try {
    switch ($action) {

        // ====================================
        // REGISTRO DE USUÁRIO
        // ====================================
        case 'registrar_usuario':
            $email = trim(post('email'));
            $senha = post('password');
            $nome  = trim(post('nome'));

            if (!$email || !$senha || !$nome) die('Preencha todos os campos.');

            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (email, senha, nome) VALUES (?, ?, ?)");
            $stmt->execute([$email, $hash, $nome]);
            header('Location: login.html');            
            break;

        // ====================================
        // LOGIN
        // ====================================
        case 'login':
            $email = trim(post('email'));
            $senha = post('password');

            if (!$email || !$senha) {
                die('Preencha todos os campos.');
            }

            $stmt = $pdo->prepare("SELECT id, nome, senha, aceite_termos FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if (!$usuario || !password_verify($senha, $usuario['senha'])) {
                die('E-mail ou senha inválidos.');
            }

            // Sessão iniciada
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];

            // Se ainda não aceitou os termos, redireciona
            if (!$usuario['aceite_termos']) {
                header('Location: consentimentos.html');
                exit;
            }

            // Se já aceitou os termos, vai direto para o dashboard
            header('Location: dashboard.php');
            exit;

        case 'salvar_consentimento':
            if (!isset($_SESSION['usuario_id'])) {
                header('Location: index.html');
                exit;
            }

            if (!isset($_POST['aceite'])) {
                die('Você deve aceitar os termos para continuar.');
            }

            $usuario_id = $_SESSION['usuario_id'];

            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET aceite_termos = 1 WHERE id = ?");
                $stmt->execute([$usuario_id]);

                header('Location: dashboard.php');
                exit;
            } catch (PDOException $e) {
                die('Erro ao registrar o consentimento: ' . $e->getMessage());
            }

        // ====================================
        // LOGOUT
        // ====================================
        case 'logout':
            session_destroy();
            header('Location: index.html');
            exit;

        // ====================================
        // CRIAR PACIENTE
        // ====================================
        case 'criar_paciente':
            $nome  = trim(post('nome'));
            $cpf   = preg_replace('/\D+/', '', post('cpf') ?? '');
            $data  = post('data_nascimento');
            $contato = trim(post('contato'));
            $ativo = (int) ((post('ativo', 1) === 'Não') ? 0 : 1);

            if (!$nome || !$cpf || !$data) die('Por favor, preencha todos os campos obrigatórios.');

            // Checar CPF duplicado
            $chk = $pdo->prepare("SELECT id FROM pacientes WHERE cpf = ?");
            $chk->execute([$cpf]);
            if ($chk->fetch()) {
                http_response_code(409);
                die('CPF já cadastrado.');
            }

            $stmt = $pdo->prepare("INSERT INTO pacientes (nome, cpf, data_nascimento, contato, ativo) VALUES (?,?,?,?,?)");
            $stmt->execute([$nome, $cpf, $data, $contato, $ativo]);
            echo "Paciente cadastrado com sucesso!";
            break;

        // ====================================
        // LISTAR PACIENTES
        // ====================================
        case 'listar_pacientes':
            header('Content-Type: application/json; charset=utf-8');
            $rows = $pdo->query("SELECT id, nome, cpf, data_nascimento, contato, ativo, criado_em FROM pacientes ORDER BY id DESC")->fetchAll();
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
            break;

        // ====================================
        // EXCLUIR PACIENTE
        // ====================================
        case 'excluir_paciente':
            $id = (int) post('id');
            if ($id <= 0) die('ID inválido.');
            $stmt = $pdo->prepare("DELETE FROM pacientes WHERE id = ?");
            $stmt->execute([$id]);
            echo "Paciente excluído com sucesso!";
            break;

        default:
            http_response_code(400);
            die('Ação desconhecida.');
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Erro no banco de dados: ' . $e->getMessage());
} catch (Throwable $e) {
    http_response_code(500);
    die('Erro: ' . $e->getMessage());
}
