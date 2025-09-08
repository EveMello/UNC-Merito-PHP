<?php
// api/receitas.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function json_input() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

try {
  // Bootstrap de dados para montar selects
  if ($method === 'GET' && isset($_GET['bootstrap'])) {
    $pac = $pdo->query("SELECT id, nome, cpf FROM pacientes ORDER BY nome")->fetchAll();
    $med = $pdo->query("SELECT id, nome, crm, especialidade FROM medicos ORDER BY nome")->fetchAll();
    $meds= $pdo->query("SELECT id, nome, dosagem, forma, fabricante FROM medicamentos ORDER BY nome")->fetchAll();
    echo json_encode(['pacientes'=>$pac, 'medicos'=>$med, 'medicamentos'=>$meds], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Obter uma receita completa (para reabrir/ imprimir)
  if ($method === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'ID inválido']); exit; }

    $cab = $pdo->prepare("
      SELECT r.id, r.data_emissao, r.observacoes,
             p.nome AS paciente_nome, p.cpf AS paciente_cpf,
             m.nome AS medico_nome, m.crm AS medico_crm, m.especialidade AS medico_esp
      FROM receitas r
      JOIN pacientes p ON p.id = r.paciente_id
      JOIN medicos   m ON m.id = r.medico_id
      WHERE r.id = :id
    ");
    $cab->execute([':id'=>$id]);
    $header = $cab->fetch();
    if (!$header) { http_response_code(404); echo json_encode(['error'=>'Receita não encontrada']); exit; }

    $it = $pdo->prepare("
      SELECT ri.id, med.nome AS medicamento_nome, ri.dosagem, ri.posologia, ri.quantidade, ri.duracao
      FROM receita_itens ri
      JOIN medicamentos med ON med.id = ri.medicamento_id
      WHERE ri.receita_id = :id
      ORDER BY ri.id
    ");
    $it->execute([':id'=>$id]);
    $itens = $it->fetchAll();

    echo json_encode(['cabecalho'=>$header, 'itens'=>$itens], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Criar receita
  if ($method === 'POST') {
    $data = json_input();
    $paciente_id = (int)($data['paciente_id'] ?? 0);
    $medico_id   = (int)($data['medico_id'] ?? 0);
    $data_emissao= trim($data['data_emissao'] ?? date('Y-m-d'));
    $observacoes = trim($data['observacoes'] ?? '');
    $itens       = $data['itens'] ?? [];

    if ($paciente_id<=0 || $medico_id<=0 || empty($itens)) {
      http_response_code(422);
      echo json_encode(['error'=>'Informe paciente, médico e ao menos 1 item.']);
      exit;
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO receitas (paciente_id, medico_id, data_emissao, observacoes) VALUES (:p,:m,:d,:o)");
    $stmt->execute([':p'=>$paciente_id, ':m'=>$medico_id, ':d'=>$data_emissao, ':o'=>$observacoes]);
    $rid = (int)$pdo->lastInsertId();

    $ins = $pdo->prepare("
      INSERT INTO receita_itens (receita_id, medicamento_id, dosagem, posologia, quantidade, duracao)
      VALUES (:r,:med,:dos,:pos,:qt,:dur)
    ");

    foreach ($itens as $item) {
      $medicamento_id = (int)($item['medicamento_id'] ?? 0);
      $dosagem        = trim($item['dosagem'] ?? '');
      $posologia      = trim($item['posologia'] ?? '');
      $quantidade     = trim($item['quantidade'] ?? '');
      $duracao        = trim($item['duracao'] ?? '');

      if ($medicamento_id<=0 || $dosagem==='' || $posologia==='') {
        $pdo->rollBack();
        http_response_code(422);
        echo json_encode(['error'=>'Itens inválidos: medicamento, dosagem e posologia são obrigatórios.']);
        exit;
      }
      $ins->execute([
        ':r'=>$rid, ':med'=>$medicamento_id, ':dos'=>$dosagem, ':pos'=>$posologia,
        ':qt'=>$quantidade, ':dur'=>$duracao
      ]);
    }

    $pdo->commit();
    echo json_encode(['success'=>true, 'receita_id'=>$rid], JSON_UNESCAPED_UNICODE);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error'=>'Método não permitido']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error'=>'Erro no servidor: '.$e->getMessage()]);
}
