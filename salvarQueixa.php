<?php
include 'conexao.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['agendamento_id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        exit('AGENDAMENTO_INVALIDO');
    }

    $dados = [
        'desconforto_principal' => $_POST['principal_desconforto'] ?? '',
        'queixa_secundaria'     => $_POST['queixa_secundaria'] ?? '',
        'tempo_desconforto'     => $_POST['tempo_desconforto'] ?? '',
        'classificacao_dor'     => $_POST['classificacao_dor'] ?? '',
        'tratamento_medico'     => $_POST['tratamento_medico'] ?? '',
        'termo_aceite'          => 1
    ];

    // Checkboxes
    $checkboxes = [
        'check1'=>'em_cuidados_medicos','check2'=>'medicacao','check3'=>'gravida','check4'=>'lesao','check5'=>'torcicolo',
        'check6'=>'dor_coluna','check7'=>'caimbras','check8'=>'distensoes','check9'=>'fraturas','check10'=>'edemas',
        'check11'=>'outras_dores','check12'=>'cirurgias','check13'=>'prob_pele','check14'=>'digestivo','check15'=>'intestino',
        'check16'=>'prisao_ventre','check17'=>'circulacao','check18'=>'trombose','check19'=>'cardiaco','check20'=>'pressao',
        'check21'=>'artrite','check22'=>'asma','check23'=>'alergia','check24'=>'rinite','check25'=>'diabetes',
        'check26'=>'colesterol','check27'=>'epilepsia','check28'=>'osteoporose','check29'=>'cancer','check30'=>'contagiosa','check31'=>'sono'
    ];
    foreach ($checkboxes as $postKey => $col) {
        $dados[$col] = isset($_POST[$postKey]) ? 1 : 0;
    }

    // Emocionais
    foreach (['ansiedade','tristeza','raiva','preocupacao','medo','irritacao','angustia'] as $campo) {
        $dados[$campo] = isset($_POST[$campo]) ? (int) $_POST[$campo] : null;
    }

    // Verifica se já existe
    $existe = $conn->prepare("SELECT agendamento_id FROM formularios_queixa WHERE agendamento_id = ?");
    $existe->bind_param("i", $id);
    $existe->execute();
    $existe->store_result();

    if ($existe->num_rows > 0) {
        $set = [];
        foreach ($dados as $col => $val) {
            $safe = mysqli_real_escape_string($conn, (string) $val);
            $set[] = "$col = '$safe'";
        }
        $sql = "UPDATE formularios_queixa SET " . implode(", ", $set) . " WHERE agendamento_id = $id";
    } else {
        $dados['agendamento_id'] = $id;
        $cols = implode(", ", array_keys($dados));
        $vals = implode(", ", array_map(fn($v) => "'" . mysqli_real_escape_string($conn, (string) $v) . "'", array_values($dados)));
        $sql = "INSERT INTO formularios_queixa ($cols) VALUES ($vals)";
    }

    $existe->close();

    if ($conn->query($sql)) {
        echo "SALVO";
    } else {
        http_response_code(500);
        echo "Erro: " . $conn->error;
    }
} else {
    http_response_code(405);
    echo "Método não permitido";
}
