<?php
// admin/pacientes.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php'); exit;
}
require_once '../conexao.php';

// 1. Buscar lista de pacientes (da tabela usuarios) com filtros e ordenação
$busca = isset($_GET['q']) ? trim($_GET['q']) : '';
$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'alfabetica';

$opcoesOrdenacao = [
    'alfabetica' => 'nome ASC',
    'recentes'   => 'criado_em DESC',
    'antigos'    => 'criado_em ASC',
];

if (!array_key_exists($ordenar, $opcoesOrdenacao)) {
    $ordenar = 'alfabetica';
}

$sql = "SELECT id, nome, email, telefone, nascimento, sexo FROM usuarios";
$params = [];
$types = '';

if ($busca !== '') {
    $sql .= " WHERE nome LIKE ? OR email LIKE ?";
    $like = '%' . $busca . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

$sql .= " ORDER BY " . $opcoesOrdenacao[$ordenar];

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Erro na preparação da consulta: ' . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();
$pacientes = [];
while ($row = $res->fetch_assoc()) { $pacientes[] = $row; }
$stmt->close();

?>
<!DOCTYPE html>
<html lang='pt-br'>
<head>
      <link rel="icon" type="image/png" href="/favicon-transparente.png">
<meta charset='UTF-8'>
<title>Painel Admin - Pacientes</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
  body { font-family: Roboto, Arial, sans-serif; background: #fcfaf7; margin:0; }
  .container { max-width:980px; margin:90px auto 0 auto; background:#fff; border-radius:18px; box-shadow:0 2px 32px #0001; padding:38px 3vw 44px 3vw; }
  h1 { color:#57643a; font-size:2.1rem; font-family:Playfair Display,serif; }
  .table-pacientes { width:100%; border-collapse:collapse; margin-top:32px; }
  .table-pacientes th,.table-pacientes td { border-bottom:1px solid #ece3d3; padding:13px 7px; text-align:left; }
  .table-pacientes th { background:#f7e6bc; color:#6A5036; font-size:1.07rem; }
  .table-pacientes tr:hover { background:#f6f4ee; }
  .btn-ver { background:#6A5036; color:#fff; border-radius:8px; border:none; font-size:1rem; font-weight:700; padding:7px 18px; cursor:pointer; transition:background .16s; }
  .btn-ver:hover { background:#8b6f4e; }
  .header-admin {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 63px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #256d54;
    color: #fff;
    font-family: 'Playfair Display', serif;
    font-size: 1.27rem;
    box-shadow: 0 6px 30px #256d5412, 0 2px 10px #1d9a7718;
    z-index: 999;
    padding: 0 4vw;
    gap: 16px;
  }

  .header-admin .header-brand {
    font-weight: 700;
    letter-spacing: .4px;
  }

  .header-admin .menu-container {
    display: flex;
    align-items: center;
  }

  .header-admin .menu-horizontal {
    display: flex;
    gap: 14px;
  }

  .header-admin .menu-toggle {
    display: none;
    background: none;
    border: none;
    color: #fff;
    font-size: 1.7rem;
    cursor: pointer;
    line-height: 1;
    padding: 6px 10px;
    border-radius: 10px;
    transition: background .17s;
  }

  .header-admin .menu-toggle:hover,
  .header-admin.is-open .menu-toggle {
    background: rgba(255, 255, 255, 0.12);
  }

  .header-admin .menu-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.09rem;
    font-weight: 600;
    padding: 8px 17px 7px 17px;
    border-radius: 12px;
    transition: background .17s, color .14s;
    cursor: pointer;
    text-decoration: none;
    outline: none;
    display: inline-block;
  }

  .header-admin .menu-btn.active,
  .header-admin .menu-btn:hover {
    background: #ffd972;
    color: #256d54;
  }

  @media (max-width: 700px) {
    .container {
      margin: 69px auto 0 auto;
      padding: 24px 3vw 44px 3vw;
    }

    .header-admin {
      font-size: 1.07rem;
      height: 49px;
      border-radius: 0 0 16px 16px;
      padding: 0 2vw;
      gap: 10px;
    }

    .header-admin .menu-toggle {
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .header-admin .menu-container {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      padding: 12px 2vw 16px 2vw;
      background: #256d54;
      box-shadow: 0 6px 30px #256d5412, 0 2px 10px #1d9a7718;
      border-radius: 0 0 16px 16px;
    }

    .header-admin .menu-horizontal {
      display: none;
      flex-direction: column;
      gap: 6px;
    }

    .header-admin .menu-btn {
      text-align: left;
      padding: 10px 12px;
    }

    .header-admin.is-open .menu-container {
      display: block;
    }

    .header-admin.is-open .menu-horizontal {
      display: flex;
    }
  }
</style>
</head>
<body>
  <div class="header-admin">
    <div class="header-brand">Painel da Terapeuta</div>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="admin-menu">☰</button>
    <div class="menu-container">
      <div class="menu-horizontal" id="admin-menu">
        <a class="menu-btn" href="agenda.php">Agenda</a>
        <a class="menu-btn active" href="pacientes.php">Pacientes</a>
        <a class="menu-btn" href="precos.php">Preços</a>
        <a class="menu-btn" href="blog.php">Blog</a>
        <a class="menu-btn" href="index.php">Home</a>
      </div>
    </div>
  </div>
<div class='container'>
  <h1>Pacientes</h1>
  <form method="get" class="filtro-pacientes" style="display:flex; flex-wrap:wrap; gap:12px; margin:18px 0 4px 0; align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar por nome ou email" style="flex:1; min-width:220px; padding:10px 12px; border:1px solid #d9cbb4; border-radius:10px; font-size:1rem;" />
    <select name="ordenar" style="padding:10px 12px; border:1px solid #d9cbb4; border-radius:10px; font-size:1rem; background:#fff; color:#4b3f30;">
      <option value="alfabetica" <?= $ordenar === 'alfabetica' ? 'selected' : '' ?>>Ordem alfabética</option>
      <option value="recentes" <?= $ordenar === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
      <option value="antigos" <?= $ordenar === 'antigos' ? 'selected' : '' ?>>Mais antigos</option>
    </select>
    <button type="submit" class="btn-ver" style="padding:10px 22px;">Aplicar</button>
  </form>
  <a href='novo_paciente.php' class='btn-ver' style='background:#38b26d;margin-bottom:17px;display:inline-block;'>Criar paciente</a>
  <table class='table-pacientes'>
    <tr>
      <th>Nome</th>
      <th>Email</th>
      <th>Telefone</th>
      <th>Nascimento</th>
      <th>Sexo</th>
      <th>Ações</th>
    </tr>
    <?php foreach ($pacientes as $pac) { ?>
      <tr>
        <td><?= htmlspecialchars($pac['nome']) ?></td>
        <td><?= htmlspecialchars($pac['email']) ?></td>
        <td><?= htmlspecialchars($pac['telefone']) ?></td>
        <td><?= htmlspecialchars($pac['nascimento']) ?></td>
        <td><?= htmlspecialchars($pac['sexo']) ?></td>
        <td><a href='paciente.php?id=<?= $pac['id'] ?>' class='btn-ver'>Acessar</a></td>
      </tr>
    <?php } ?>
  </table>
</div>
  <script>
    (function () {
      const header = document.querySelector('.header-admin');
      if (!header) return;

      const toggle = header.querySelector('.menu-toggle');
      const menuLinks = header.querySelectorAll('.menu-horizontal a');
      if (!toggle) return;

      const closeMenu = () => {
        header.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      };

      toggle.addEventListener('click', () => {
        const isOpen = header.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });

      menuLinks.forEach((link) => {
        link.addEventListener('click', () => {
          if (header.classList.contains('is-open')) {
            closeMenu();
          }
        });
      });
    })();
  </script>
</body>
</html>
