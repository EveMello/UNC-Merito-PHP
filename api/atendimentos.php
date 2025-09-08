<?php
// api/atendimentos.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function json_input() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function save_upload($field, $destDir) {
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }
  $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
  $safe = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($_FILES[$field]['name'], PATHINFO_FILENAME));
  $fname = $safe . '_' . time() . ($ext ? '.'.$ext : '');
  $target = rtrim($destDir,'/\\') . DIRECTORY_SEPARATOR . $fname;
  if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) return null;
  // caminho relativo para servir no front
  return 'uploads/atendimentos/' . $fname;
}

try {
  if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, paciente, profissional, tipo, status, DATE_FORMAT(data_atendimento,'%Y-%m-%d') AS data_atendimento, diagnostico, cid, prescricao, observacoes, anexo FROM atendimentos ORDER BY id DESC");
    echo json_encode($stmt->fetchAll());
    exit;
  }

  if ($method === 'POST') {
    $paciente     = trim($_POST['paciente'] ?? '');
    $profissional = trim($_POST['profissional'] ?? '');
    $tipo         = trim($_POST['tipo'] ?? 'Consulta');
    $status       = trim($_POST['status'] ?? 'Pendente');
    $data_at      = trim($_POST['data_atendimento'] ?? '');
    $diagnostico  = trim($_POST['diagnostico'] ?? '');
    $cid          = trim($_POST['cid'] ?? '');
    $prescricao   = trim($_POST['prescricao'] ?? '');
    $observacoes  = trim($_POST['observacoes'] ?? '');

    if ($paciente==='' || $profissional==='' || $data_at==='') {
      http_response_code(422);
      echo json_encode(['error'=>'Preencha paciente, profissional e data.']);
      exit;
    }

    $anexo = save_upload('anexo', __DIR__ . '/../uploads/atendimentos');

    $sql = "INSERT INTO atendimentos (paciente, profissional, tipo, status, data_atendimento, diagnostico, cid, prescricao, observacoes, anexo)
            VALUES (:paciente, :profissional, :tipo, :status, :data_atendimento, :diagnostico, :cid, :prescricao, :observacoes, :anexo)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':paciente'=>$paciente, ':profissional'=>$profissional, ':tipo'=>$tipo, ':status'=>$status,
      ':data_atendimento'=>$data_at, ':diagnostico'=>$diagnostico, ':cid'=>$cid,
      ':prescricao'=>$prescricao, ':observacoes'=>$observacoes, ':anexo'=>$anexo
    ]);
    echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId(), 'anexo'=>$anexo]);
    exit;
  }

  if ($method === 'PUT' || $method === 'PATCH') {
    $data = json_input();
    $id   = (int)($data['id'] ?? 0);
    if ($id<=0) { http_response_code(422); echo json_encode(['error'=>'ID inválido']); exit; }

    $paciente     = trim($data['paciente'] ?? '');
    $profissional = trim($data['profissional'] ?? '');
    $tipo         = trim($data['tipo'] ?? 'Consulta');
    $status       = trim($data['status'] ?? 'Pendente');
    $data_at      = trim($data['data_atendimento'] ?? '');
    $diagnostico  = trim($data['diagnostico'] ?? '');
    $cid          = trim($data['cid'] ?? '');
    $prescricao   = trim($data['prescricao'] ?? '');
    $observacoes  = trim($data['observacoes'] ?? '');
    $remover_anexo= !empty($data['remover_anexo']);

    if ($paciente==='' || $profissional==='' || $data_at==='') {
      http_response_code(422);
      echo json_encode(['error'=>'Preencha paciente, profissional e data.']);
      exit;
    }

    $sql = "UPDATE atendimentos
            SET paciente=:paciente, profissional=:profissional, tipo=:tipo, status=:status,
                data_atendimento=:data_atendimento, diagnostico=:diagnostico, cid=:cid,
                prescricao=:prescricao, observacoes=:observacoes"
            . ($remover_anexo ? ", anexo = NULL " : " ")
            . "WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':paciente'=>$paciente, ':profissional'=>$profissional, ':tipo'=>$tipo, ':status'=>$status,
      ':data_atendimento'=>$data_at, ':diagnostico'=>$diagnostico, ':cid'=>$cid,
      ':prescricao'=>$prescricao, ':observacoes'=>$observacoes, ':id'=>$id
    ]);
    echo json_encode(['success'=>true]);
    exit;
  }

  if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
    $id = (int)($qs['id'] ?? 0);
    if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'ID inválido']); exit; }
    $stmt = $pdo->prepare("DELETE FROM atendimentos WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    echo json_encode(['success'=>true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error'=>'Método não permitido']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'Erro no servidor: '.$e->getMessage()]);
}
