<?php
// api/medicos.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config/db.php';

function json_input() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, nome, crm, especialidade, contato FROM medicos ORDER BY id DESC");
    echo json_encode($stmt->fetchAll());
    exit;
  }

  if ($method === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $crm  = trim($_POST['crm'] ?? '');
    $esp  = trim($_POST['especialidade'] ?? '');
    $cont = trim($_POST['contato'] ?? '');

    if ($nome === '' || $crm === '' || $esp === '' || $cont === '') {
      http_response_code(422);
      echo json_encode(['error' => 'Campos obrigatórios não informados.']);
      exit;
    }

    $sql = "INSERT INTO medicos (nome, crm, especialidade, contato)
            VALUES (:nome, :crm, :especialidade, :contato)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nome' => $nome,
      ':crm' => $crm,
      ':especialidade' => $esp,
      ':contato' => $cont,
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
  }

  if ($method === 'PUT' || $method === 'PATCH') {
    $data = json_input();
    $id   = (int)($data['id'] ?? 0);
    $nome = trim($data['nome'] ?? '');
    $crm  = trim($data['crm'] ?? '');
    $esp  = trim($data['especialidade'] ?? '');
    $cont = trim($data['contato'] ?? '');

    if ($id <= 0 || $nome === '' || $crm === '' || $esp === '' || $cont === '') {
      http_response_code(422);
      echo json_encode(['error' => 'Dados inválidos.']);
      exit;
    }

    $sql = "UPDATE medicos
            SET nome = :nome, crm = :crm, especialidade = :especialidade, contato = :contato
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nome' => $nome,
      ':crm'  => $crm,
      ':especialidade' => $esp,
      ':contato' => $cont,
      ':id' => $id
    ]);

    echo json_encode(['success' => true]);
    exit;
  }

  if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
    $id = (int)($qs['id'] ?? 0);
    if ($id <= 0) {
      http_response_code(400);
      echo json_encode(['error' => 'ID inválido.']);
      exit;
    }

    $stmt = $pdo->prepare("DELETE FROM medicos WHERE id = :id");
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
