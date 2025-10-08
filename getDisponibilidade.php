<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexao.php';

/**
 * Normaliza o status para uma chave padronizada.
 */
function normalizarStatus(?string $status): ?string
{
    if ($status === null) {
        return null;
    }

    $normalizado = mb_strtolower(trim($status), 'UTF-8');
    // Remover acentos básicos.
    $normalizado = str_replace(['í', 'ú', 'á', 'é', 'ó', 'ã', 'õ'], ['i', 'u', 'a', 'e', 'o', 'a', 'o'], $normalizado);

    switch ($normalizado) {
        case 'pendente':
            return 'pendente';
        case 'confirmado':
            return 'confirmado';
        case 'concluido':
        case 'concluido ':
            return 'concluido';
        case 'indisponivel':
            return 'indisponivel';
        default:
            return null;
    }
}

$horariosPadrao = [];
for ($hora = 8; $hora <= 20; $hora++) {
    $horariosPadrao[] = sprintf('%02d:00', $hora);
}

$sql = 'SELECT data_horario, status FROM agendamentos';

$resultado = $conn->query($sql);
if (!$resultado instanceof mysqli_result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Não foi possível consultar a disponibilidade no momento.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$horariosPorData = [];

while ($row = $resultado->fetch_assoc()) {
    $dataHorario = $row['data_horario'] ?? null;
    if (!$dataHorario) {
        continue;
    }

    $timestamp = strtotime($dataHorario);
    if ($timestamp === false) {
        continue;
    }

    $data = date('Y-m-d', $timestamp);
    $hora = date('H:i', $timestamp);
    $statusNormalizado = normalizarStatus($row['status'] ?? null);

    if ($statusNormalizado === null) {
        continue;
    }

    if (!isset($horariosPorData[$data])) {
        $horariosPorData[$data] = [
            'pendente' => [],
            'confirmado' => [],
            'concluido' => [],
            'indisponivel' => [],
        ];
    }

    $horariosPorData[$data][$statusNormalizado][$hora] = true;
}

$resultado->free();

foreach ($horariosPorData as &$statusLista) {
    foreach ($statusLista as $status => &$horas) {
        if (!is_array($horas)) {
            $horas = [];
            continue;
        }

        $horas = array_keys($horas);
        sort($horas);
    }
    unset($horas);
}
unset($statusLista);

$datasBloqueadas = [];
foreach ($horariosPorData as $data => $statusLista) {
    $bloqueados = $statusLista['indisponivel'] ?? [];
    if (empty($bloqueados)) {
        continue;
    }

    $totalBloqueados = count(array_intersect($horariosPadrao, $bloqueados));
    if ($totalBloqueados === count($horariosPadrao)) {
        $datasBloqueadas[] = $data;
    }
}

$datasBloqueadas = array_values(array_unique($datasBloqueadas));
sort($datasBloqueadas);

echo json_encode([
    'success' => true,
    'horariosPadrao' => $horariosPadrao,
    'horariosPorData' => $horariosPorData,
    'datasBloqueadas' => $datasBloqueadas,
    'updatedAt' => gmdate('c'),
], JSON_UNESCAPED_UNICODE);

