<?php
// api/pacientes.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config/db.php';

// Função auxiliar para ler JSON do corpo
function json_input() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

// Sanitização simples de CPF (mantém só números e formata ###.###.###-##)
function formatar_cpf($cpf) {
  $nums = preg_replace('/\D/', '', $cpf ?? '');
  if (strlen($nums) !== 11) return $cpf; // deixa como veio se não tiver 11 dígitos
  return substr($nums,0,3).'.'.substr($nums,3,3).'.'.substr($nums,6,3).'-'.substr($nums,9,2);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    // LISTAR
    $stmt = $pdo->query("SELECT id, nome, cpf, DATE_FORMAT(data_nascimento,'%Y-%m-%d') AS data_nascimento, contato, ativo FROM pacientes ORDER BY id DESC");
    echo json_encode($stmt->fetchAll());
    exit;
  }

  if ($method === 'POST') {
    // CRIAR
    $nome   = trim($_POST['nome'] ?? '');
    $cpf    = formatar_cpf($_POST['cpf'] ?? '');
    $data_n = $_POST['data_nascimento'] ?? '';
    $cont   = trim($_POST['contato'] ?? '');
    $ativo  = ($_POST['ativo'] ?? 'Sim') === 'Sim' ? 1 : 0;

    if ($nome === '' || $cpf === '' || $data_n === '' || $cont === '') {
      http_response_code(422);
      echo json_encode(['error' => 'Campos obrigatórios não informados.']);
      exit;
    }

    $sql = "INSERT INTO pacientes (nome, cpf, data_nascimento, contato, ativo)
            VALUES (:nome, :cpf, :data_nascimento, :contato, :ativo)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nome' => $nome,
      ':cpf'  => $cpf,
      ':data_nascimento' => $data_n,
      ':contato' => $cont,
      ':ativo' => $ativo,
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
  }

  if ($method === 'PUT' || $method === 'PATCH') {
    // ATUALIZAR
    $data   = json_input();
    $id     = (int)($data['id'] ?? 0);
    $nome   = trim($data['nome'] ?? '');
    $cpf    = formatar_cpf($data['cpf'] ?? '');
    $data_n = $data['data_nascimento'] ?? '';
    $cont   = trim($data['contato'] ?? '');
    $ativo  = ($data['ativo'] ?? 'Sim') === 'Sim' ? 1 : 0;

    if ($id <= 0 || $nome === '' || $cpf === '' || $data_n === '' || $cont === '') {
      http_response_code(422);
      echo json_encode(['error' => 'Dados inválidos.']);
      exit;
    }

    $sql = "UPDATE pacientes
            SET nome = :nome, cpf = :cpf, data_nascimento = :data_nascimento, contato = :contato, ativo = :ativo
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nome' => $nome,
      ':cpf'  => $cpf,
      ':data_nascimento' => $data_n,
      ':contato' => $cont,
      ':ativo' => $ativo,
      ':id' => $id
    ]);

    echo json_encode(['success' => true]);
    exit;
  }

  if ($method === 'DELETE') {
    // EXCLUIR
    parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
    $id = (int)($qs['id'] ?? 0);
    if ($id <= 0) {
      http_response_code(400);
      echo json_encode(['error' => 'ID inválido.']);
      exit;
    }

    $stmt = $pdo->prepare("DELETE FROM pacientes WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error' => 'Método não permitido.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Erro no servidor: '.$e->getMessage()]);
}
