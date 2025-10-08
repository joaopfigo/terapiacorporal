<?php
// Painel de administração de preços (admin/precos.php)
// João, código profissional, seguro, limpo e comentado. Acesse SQL real e scripts relacionados para garantir aderência.

session_start();
require_once '../conexao.php'; // ajuste o caminho se necessário

// Proteção: só terapeuta pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php');
    exit;
}

// Atualizar preços via POST (campos: preco_..., promocao_..., pacote_...)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Quick Massage tem valores próprios
    if (isset($_POST['preco_quick_15']) && isset($_POST['preco_quick_30'])) {
        $quick15 = floatval(str_replace(',', '.', $_POST['preco_quick_15']));
        $quick30 = floatval(str_replace(',', '.', $_POST['preco_quick_30']));
        $stmt = $conn->prepare("UPDATE especialidades SET preco_15=?, preco_30=? WHERE nome='Quick Massage'");
        $stmt->bind_param('dd', $quick15, $quick30);
        $stmt->execute();
        $stmt->close();
    }
    // Demais serviços (um valor padrão para 50 e 90min)
    if (isset($_POST['preco_50']) && isset($_POST['preco_90'])) {
        $preco50 = floatval(str_replace(',', '.', $_POST['preco_50']));
        $preco90 = floatval(str_replace(',', '.', $_POST['preco_90']));
        // Atualiza todas as especialidades exceto Quick Massage
        $stmt = $conn->prepare("UPDATE especialidades SET preco_50=?, preco_90=? WHERE nome != 'Quick Massage'");
        $stmt->bind_param('dd', $preco50, $preco90);
        $stmt->execute();
        $stmt->close();
    }
    // Promoção escalda pés
    if (isset($_POST['preco_escalda'])) {
        $precoEscalda = floatval(str_replace(',', '.', $_POST['preco_escalda']));
        $stmt = $conn->prepare("UPDATE especialidades SET preco_escalda=? WHERE nome = 'Escalda Pés'");
        $stmt->bind_param('d', $precoEscalda);
        $stmt->execute();
        $stmt->close();
    }
    // Pacotes
    if (isset($_POST['preco_pacote5']) && isset($_POST['preco_pacote10'])) {
        $preco5 = floatval(str_replace(',', '.', $_POST['preco_pacote5']));
        $preco10 = floatval(str_replace(',', '.', $_POST['preco_pacote10']));
        $stmt = $conn->prepare("UPDATE especialidades SET pacote5 = ?");
        $stmt->bind_param('d', $preco5);
        $stmt->execute();
        $stmt->close();
           $stmt = $conn->prepare("UPDATE especialidades SET pacote10 = ?");
        $stmt->bind_param('d', $preco10);
        $stmt->execute();
        $stmt->close();
    }
    $sucesso = true;
}

// Buscar valores atuais
$precos = [
    'quick_15' => '', 'quick_30' => '',
    'padrao_50' => '', 'padrao_90' => '',
    'escalda' => '', 'pacote5' => '', 'pacote10' => ''
];
$res = $conn->query("SELECT preco_15, preco_30 FROM especialidades WHERE nome = 'Quick Massage' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $precos['quick_15'] = $row['preco_15'];
    $precos['quick_30'] = $row['preco_30'];
}
$res = $conn->query("SELECT preco_50, preco_90 FROM especialidades WHERE nome != 'Quick Massage' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $precos['padrao_50'] = $row['preco_50'];
    $precos['padrao_90'] = $row['preco_90'];
}
$res = $conn->query("SELECT preco_escalda FROM especialidades WHERE nome = 'Escalda Pés' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $precos['escalda'] = $row['preco_escalda'];
}

$res = $conn->query("SELECT pacote5, pacote10 FROM especialidades");
while ($row = $res->fetch_assoc()) {
    $precos['pacote5'] = $row['pacote5'];
    $precos['pacote10'] = $row['pacote10'];
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
      <link rel="icon" type="image/png" href="/favicon-transparente.png">
    <meta charset="UTF-8">
    <title>Painel de Preços - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #faf8f2; }
        .container { max-width: 600px; background: #fff; margin: 94px auto 44px auto; border-radius: 16px; box-shadow: 0 4px 24px #b7b7b74a; padding: 36px 36px 30px 36px; }
        h1 { color: #57643a; font-size: 2rem; margin-bottom: 27px; text-align:center; }
        .form-row { margin-bottom: 22px; display: flex; gap: 24px; align-items: center; }
        .form-row label { flex: 0 0 220px; font-weight: 600; color: #4a4a36; font-size: 1.07em; }
        .form-row input[type="number"] { width: 110px; padding: 7px 8px; border-radius: 7px; border: 1px solid #c8c0a8; font-size: 1.04em; }
        .form-btns { text-align: right; margin-top: 36px; }
        .btn { background: #57643a; color: #fff; padding: 10px 28px; border: none; border-radius: 8px; font-weight: 700; font-size: 1.1em; cursor:pointer; transition: background .18s; }
        .btn:hover { background: #324a1d; }
        .msg-ok { color: #37692e; text-align: center; margin-bottom: 20px; font-weight: 600; font-size:1.04em; }
        @media(max-width:600px){ .container{padding:20px;} .form-row label{font-size:1em;} }
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
            margin: 69px auto 24px auto;
            padding: 32px 24px;
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
          <a class="menu-btn" href="pacientes.php">Pacientes</a>
          <a class="menu-btn active" href="precos.php">Preços</a>
          <a class="menu-btn" href="blog.php">Blog</a>
          <a class="menu-btn" href="index.php">Home</a>
        </div>
      </div>
    </div>
    <div class="container">
        <h1>Painel de Preços</h1>
        <?php if (!empty($sucesso)) echo '<div class="msg-ok">Preços atualizados com sucesso!</div>'; ?>
        <form method="post" autocomplete="off">
            <h2 style="font-size:1.16em;color:#817c52;margin-bottom:13px">Quick Massage (valores diferentes):</h2>
            <div class="form-row">
                <label for="preco_quick_15">Quick Massage 15min:</label>
                <input type="number" step="0.01" min="0" name="preco_quick_15" id="preco_quick_15" value="<?= htmlspecialchars($precos['quick_15']) ?>">
            </div>
            <div class="form-row">
                <label for="preco_quick_30">Quick Massage 30min:</label>
                <input type="number" step="0.01" min="0" name="preco_quick_30" id="preco_quick_30" value="<?= htmlspecialchars($precos['quick_30']) ?>">
            </div>
            <h2 style="font-size:1.16em;color:#817c52;margin:23px 0 13px 0">Demais Serviços (exceto quick massage):</h2>
            <div class="form-row">
                <label for="preco_50">Sessão 50min:</label>
                <input type="number" step="0.01" min="0" name="preco_50" id="preco_50" value="<?= htmlspecialchars($precos['padrao_50']) ?>">
            </div>
            <div class="form-row">
                <label for="preco_90">Sessão 90min:</label>
                <input type="number" step="0.01" min="0" name="preco_90" id="preco_90" value="<?= htmlspecialchars($precos['padrao_90']) ?>">
            </div>
            <h2 style="font-size:1.16em;color:#817c52;margin:23px 0 13px 0">Promoção Escalda Pés:</h2>
            <div class="form-row">
                <label for="preco_escalda">Escalda Pés:</label>
                <input type="number" step="0.01" min="0" name="preco_escalda" id="preco_escalda" value="<?= htmlspecialchars($precos['escalda']) ?>">
            </div>
            <h2 style="font-size:1.16em;color:#817c52;margin:23px 0 13px 0">Pacotes:</h2>
            <div class="form-row">
                <label for="preco_pacote5">Pacote 5 sessões:</label>
                <input type="number" step="0.01" min="0" name="preco_pacote5" id="preco_pacote5" value="<?= htmlspecialchars($precos['pacote5']) ?>">
            </div>
            <div class="form-row">
                <label for="preco_pacote10">Pacote 10 sessões:</label>
                <input type="number" step="0.01" min="0" name="preco_pacote10" id="preco_pacote10" value="<?= htmlspecialchars($precos['pacote10']) ?>">
            </div>
            <div class="form-btns">
                <button type="submit" class="btn">Salvar Preços</button>
            </div>
        </form>
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

