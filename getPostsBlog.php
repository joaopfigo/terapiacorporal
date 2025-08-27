<?php
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$result = $conn->query("SELECT id, titulo, conteudo, data_post, imagem, categoria, publicado FROM blog_posts WHERE publicado = 1 ORDER BY data_post DESC");

$posts = [];
while ($row = $result->fetch_assoc()) {
    // Gera resumo (desc) automático, se não houver
    $desc = '';
    if (!empty($row['conteudo'])) {
        $desc = strip_tags($row['conteudo']);
        $desc = mb_substr($desc, 0, 110) . (mb_strlen($desc) > 110 ? '...' : '');
    }
    $posts[] = [
        "id" => $row['id'],
        "titulo" => $row['titulo'],
        "desc" => $desc,
        "categoria" => $row['categoria'],
        "imagem" => $row['imagem'],
        "conteudo" => $row['conteudo'],
        "publicado" => $row['publicado']
    ];
}

echo json_encode(["posts" => $posts], JSON_UNESCAPED_UNICODE);
