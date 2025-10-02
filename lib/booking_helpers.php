<?php
/**
 * Retorna um mapa id => nome das especialidades, com cache simples para a requisição.
 */
function obterMapaEspecialidades(mysqli $conn): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = [];
    if ($res = $conn->query('SELECT id, nome FROM especialidades')) {
        while ($row = $res->fetch_assoc()) {
            $cache[(int) $row['id']] = $row['nome'];
        }
        $res->free();
    }

    return $cache;
}

/**
 * Monta o nome exibido para serviços selecionados, priorizando os IDs presentes em servicos_csv
 * quando houver. Caso contrário, utiliza o nome informado como fallback.
 *
 * @return array{0:string,1:array<int,string>} [titulo, lista de nomes]
 */
function descreverServicos(mysqli $conn, int $especialidadeId, ?string $servicosCsv, string $fallbackNome): array {
    $nomes = [];

    if ($servicosCsv) {
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $servicosCsv)))));
        if ($ids) {
            $mapa = obterMapaEspecialidades($conn);
            foreach ($ids as $id) {
                if (isset($mapa[$id])) {
                    $nomes[] = $mapa[$id];
                }
            }
        }
    }

    if (!$nomes) {
        $nomes = [$fallbackNome];
    }

    $titulo = count($nomes) > 1 ? implode(' + ', $nomes) : $nomes[0];

    return [$titulo, $nomes];
}
