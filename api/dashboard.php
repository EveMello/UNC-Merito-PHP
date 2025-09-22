<?php
// api/dashboard.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

function countSafe(PDO $pdo, string $sql): int {
  try { return (int)$pdo->query($sql)->fetchColumn(); } catch (Throwable $e) { return 0; }
}

$pacientes_total     = countSafe($pdo, "SELECT COUNT(*) FROM pacientes");
$pacientes_ativos    = countSafe($pdo, "SELECT COUNT(*) FROM pacientes WHERE ativo = 1");
$medicos_total       = countSafe($pdo, "SELECT COUNT(*) FROM medicos");
$medicamentos_total  = countSafe($pdo, "SELECT COUNT(*) FROM medicamentos");
$consultas_agendadas  = countSafe($pdo, "SELECT COUNT(*) FROM atendimentos WHERE status = 'Em Andamento'");
$consultas_finalizadas  = countSafe($pdo, "SELECT COUNT(*) FROM atendimentos WHERE status = 'Realizado'");
$consultas_finalizadas_hoje  = countSafe($pdo, "SELECT COUNT(*) FROM atendimentos WHERE status = 'Realizado' AND data_atendimento = CURDATE()");
$receitas_emitidas  = countSafe($pdo, "SELECT COUNT(*) FROM prescricoes");

// Se ainda não existem tabelas de consultas/exames/receitas, devolvemos 0.
echo json_encode([
  'ok'                  => true,
  'pacientes_total'     => $pacientes_total,
  'pacientes_ativos'    => $pacientes_ativos,
  'medicos_total'       => $medicos_total,
  'medicamentos_total'  => $medicamentos_total,
  'consultas_agendadas' => $consultas_agendadas,
  'receitas_emitidas'   => $receitas_emitidas,
  'consultas_finalizadas' => $consultas_finalizadas,
  'consultas_finalizadas_hoje' => $consultas_finalizadas_hoje,
], JSON_UNESCAPED_UNICODE);
