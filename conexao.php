<?php
// conexao.php (SQLite local + PDO) — não precisa de XAMPP/MySQL.
//
// Coloque este arquivo na pasta raiz do projeto (onde estão seus .html).
// Ele cria automaticamente o banco em ./data/merito.db e as tabelas necessárias.
//
// Requisitos no PHP (php.ini): habilite as extensões pdo_sqlite e sqlite3.
//   extension=pdo_sqlite
//   extension=sqlite3

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$DB_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$DB_PATH = $DB_DIR . DIRECTORY_SEPARATOR . 'merito.db';

if (!is_dir($DB_DIR)) {
    mkdir($DB_DIR, 0777, true);
}

try {
    $pdo = new PDO('sqlite:' . $DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Cria tabelas se não existirem
    $pdo->exec('CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        senha TEXT NOT NULL,
        nome  TEXT NOT NULL,
        criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS pacientes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        cpf  TEXT UNIQUE NOT NULL,
        data_nascimento TEXT NOT NULL,
        contato TEXT,
        ativo INTEGER NOT NULL DEFAULT 1,
        criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )');
} catch (Throwable $e) {
    http_response_code(500);
    die('Erro ao abrir o banco local: ' . $e->getMessage());
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;
if (!$action) {
    http_response_code(400);
    die('Ação não especificada.');
}

function post($key, $default=null) {
    return $_POST[$key] ?? $default;
}

try {
    switch ($action) {
        case 'registrar_usuario': {
            $email = trim((string)post('email'));
            $senha = (string)post('senha');
            $nome  = trim((string)post('nome'));

            if ($email === '' || $senha === '' || $nome === '') {
                die('Por favor, preencha todos os campos.');
            }

            // Hash de senha seguro
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $st = $pdo->prepare('INSERT INTO usuarios (email, senha, nome) VALUES (?, ?, ?)');
            $st->execute([$email, $hash, $nome]);
            echo 'Usuário cadastrado com sucesso!';
            break;
        }

        case 'login': {
            $email = trim((string)post('email'));
            $senha = (string)post('senha');
            if ($email === '' || $senha === '') die('Por favor, preencha todos os campos.');

            $st = $pdo->prepare('SELECT id, email, senha, nome FROM usuarios WHERE email = ?');
            $st->execute([$email]);
            $u = $st->fetch(PDO::FETCH_ASSOC);
            if (!$u || !password_verify($senha, $u['senha'])) {
                http_response_code(401);
                die('E-mail ou senha inválidos.');
            }
            echo 'Login OK';
            break;
        }

        case 'criar_paciente': {
            $nome  = trim((string)post('nome'));
            $cpf   = preg_replace('/\D+/', '', (string)post('cpf'));
            $data  = (string)post('data_nascimento');
            $contato = trim((string)post('contato'));
            $ativo = (int) (post('ativo', 1) === 'Não' ? 0 : (post('ativo', 1) ? 1 : 0));

            if ($nome === '' || $cpf === '' || $data === '') {
                die('Por favor, preencha todos os campos obrigatórios.');
            }

            // checa cpf duplicado
            $chk = $pdo->prepare('SELECT id FROM pacientes WHERE cpf = ?');
            $chk->execute([$cpf]);
            if ($chk->fetch()) {
                http_response_code(409);
                die('CPF já cadastrado.');
            }

            $st = $pdo->prepare('INSERT INTO pacientes (nome, cpf, data_nascimento, contato, ativo) VALUES (?,?,?,?,?)');
            $st->execute([$nome, $cpf, $data, $contato, $ativo]);
            echo 'Paciente cadastrado com sucesso!';
            break;
        }

        case 'listar_pacientes': {
            header('Content-Type: application/json; charset=utf-8');
            $rows = $pdo->query('SELECT id, nome, cpf, data_nascimento, contato, ativo, criado_em FROM pacientes ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
            break;
        }

        case 'excluir_paciente': {
            $id = (int) post('id', 0);
            if ($id <= 0) die('ID inválido.');
            $st = $pdo->prepare('DELETE FROM pacientes WHERE id = ?');
            $st->execute([$id]);
            echo 'Paciente excluído com sucesso!';
            break;
        }

        default:
            http_response_code(400);
            echo 'Ação desconhecida: ' . htmlspecialchars((string)$action, ENT_QUOTES, 'UTF-8');
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Erro: ' . $e->getMessage();
}
