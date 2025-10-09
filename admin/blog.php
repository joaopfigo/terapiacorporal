<?php
session_start();
require_once '../conexao.php';
require_once '../lib/blog_images.php';

// Só terapeuta pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'terapeuta') {
    header('Location: ../login.php');
    exit;
}

// --- CRIAR TABELA SE NÃO EXISTE (para segurança em primeiro deploy) ---
$conn->query("CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    conteudo TEXT NOT NULL,
    data_post DATE NOT NULL,
    imagem VARCHAR(255) DEFAULT NULL,
    categoria VARCHAR(60) DEFAULT NULL,
    publicado TINYINT(1) DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// --- OPERAÇÕES DE ARQUIVAR, DESPUBLICAR, ATUALIZAR, ETC ---
$msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') {
        $msg = 'Post atualizado com sucesso!';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nova publicação
    if (isset($_POST['titulo'], $_POST['conteudo'], $_POST['categoria'], $_POST['data_post'])) {
        $titulo = trim($_POST['titulo']);
        $conteudo = trim($_POST['conteudo']);
        $categoria = trim($_POST['categoria']);
        $data_post = $_POST['data_post'];
        $publicado = isset($_POST['publicado']) ? 1 : 0;

        // Upload imagem
        $imagem = null;
        if (!empty($_FILES['imagem']['name'])) {
            $targetDir = '../uploads/blog/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $imgName = date('YmdHis') . '_' . basename($_FILES['imagem']['name']);
            $targetFile = $targetDir . $imgName;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $targetFile)) {
                $imagem = 'uploads/blog/' . $imgName;
            }
        }

        $stmt = $conn->prepare("INSERT INTO blog_posts (titulo, conteudo, data_post, imagem, categoria, publicado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssi', $titulo, $conteudo, $data_post, $imagem, $categoria, $publicado);
        $stmt->execute();
        $stmt->close();
        $msg = 'Post publicado com sucesso!';
    }
    // Atualizar publicação
    if (isset($_POST['edit_id'], $_POST['salvar_edicao'])) {
        $id = intval($_POST['edit_id']);
        $titulo = trim($_POST['edit_titulo']);
        $conteudo = trim($_POST['edit_conteudo']);
        $categoria = trim($_POST['edit_categoria']);
        $data_post = $_POST['edit_data_post'];
        $publicado = isset($_POST['edit_publicado']) ? 1 : 0;
        $imagem = null;
        if (!empty($_FILES['edit_imagem']['name'])) {
            $targetDir = '../uploads/blog/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $imgName = date('YmdHis') . '_' . basename($_FILES['edit_imagem']['name']);
            $targetFile = $targetDir . $imgName;
            if (move_uploaded_file($_FILES['edit_imagem']['tmp_name'], $targetFile)) {
                $imagem = 'uploads/blog/' . $imgName;
            }
        }
        if ($imagem) {
            $stmt = $conn->prepare("UPDATE blog_posts SET titulo=?, conteudo=?, data_post=?, imagem=?, categoria=?, publicado=? WHERE id=?");
            $stmt->bind_param('ssssssi', $titulo, $conteudo, $data_post, $imagem, $categoria, $publicado, $id);
        } else {
            $stmt = $conn->prepare("UPDATE blog_posts SET titulo=?, conteudo=?, data_post=?, categoria=?, publicado=? WHERE id=?");
            $stmt->bind_param('ssssii', $titulo, $conteudo, $data_post, $categoria, $publicado, $id);
        }
        $stmt->execute();
        $stmt->close();
        header('Location: blog.php?msg=updated');
        exit;
    }
    // Arquivar/despublicar
    if (isset($_POST['toggle_id'])) {
        $id = intval($_POST['toggle_id']);
        $stmt = $conn->prepare("UPDATE blog_posts SET publicado = NOT publicado WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $msg = 'Status alterado.';
    }
}

// Buscar posts existentes
$posts = [];
$res = $conn->query("SELECT * FROM blog_posts ORDER BY data_post DESC, criado_em DESC");
while($row = $res->fetch_assoc()) $posts[] = $row;

function categoria_sel($cat, $valor) {
    return $cat == $valor ? 'selected' : '';
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
      <link rel="icon" type="image/png" href="/favicon-transparente.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Blog - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        body { background: #f8faf4; }
        .container { max-width:800px; margin:94px auto 50px auto; background:#fff; border-radius:15px; box-shadow:0 3px 24px #b7b7b752; padding:38px 30px 30px 30px; }
        h1 { color:#496a4c; text-align:center; }
        .msg-ok { background:#d0f0e0; color:#2c7650; padding:10px 0; border-radius:7px; text-align:center; font-weight:600; margin-bottom:22px; }
        form input, form select, form textarea { width:100%; margin:6px 0 16px 0; padding:11px 12px; border-radius:10px; border:1px solid #d7d0ba; font-size:1em; font-family:'Roboto', sans-serif; color:#2c3a2e; background:#fff; box-shadow:0 2px 8px rgba(73,106,76,0.06); }
        .form-section { background:#fdfcf8; border:1px solid #e7ddc4; border-radius:18px; padding:26px 28px 30px; box-shadow:0 12px 28px rgba(73,106,76,0.08); display:block; margin-top:26px; }
        .form-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px,1fr)); gap:22px 28px; }
        .form-field { display:flex; flex-direction:column; }
        .form-field.full-width { grid-column:1 / -1; }
        .form-field small { color:#6b6f60; font-size:0.85rem; margin-top:6px; }
        .form-actions { display:flex; justify-content:flex-end; margin-top:26px; gap:14px; }
        form label { display:block; font-weight:600; color:#2f3d2f; margin-bottom:8px; letter-spacing:0.2px; }
        .form-checkbox { display:flex; align-items:center; gap:10px; font-weight:600; color:#2f3d2f; margin-top:6px; }
        .form-checkbox input[type="checkbox"] { width:auto; margin:0; accent-color:#256d54; }
        .form-row { display:flex; flex-wrap:wrap; gap:22px; }
        .form-row > * { flex:1 1 240px; }
        textarea { min-height:130px; }
        .btn { background:#496a4c; color:#fff; padding:11px 30px; border:none; border-radius:10px; font-weight:700; font-size:1.05em; cursor:pointer; transition:background .18s; }
        .btn:hover { background:#1a2e1d; }
        .table-posts { width:100%; border-collapse:collapse; margin-top:40px; }
        .table-posts th, .table-posts td { border:1px solid #e8e0cf; padding:8px 10px; }
        .table-posts th { background:#f0e7c9; color:#3c4725; }
        .table-posts td { background:#fff; }
        .img-mini { width:58px; border-radius:8px; }
        .btn-edit, .btn-toggle { background:#e8b14a; color:#fff; border:none; border-radius:6px; padding:4px 13px; margin-right:7px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }
        .btn-toggle.pub { background:#2c7650; }
        .btn-toggle.despub { background:#ae3829; }
        .btn-edit:hover { background:#eb9800; }
        .btn-toggle.pub:hover { background:#247a3c; }
        .btn-toggle.despub:hover { background:#931e16; }
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

        @media (max-width:700px) {
          .container {
            padding:10px;
            margin:69px auto 40px auto;
          }
          .form-section {
            padding:20px 18px 24px;
            margin-top:20px;
          }
          .form-grid {
            grid-template-columns:1fr;
            gap:18px;
          }
          .form-actions {
            flex-direction:column;
            align-items:stretch;
            margin-top:22px;
          }
          .form-actions .btn {
            width:100%;
            line-height:1.5;
          }

          .table-posts th,
          .table-posts td {
            font-size:0.98em;
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

          .btn {
            line-height:1.5;
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
      <a class="menu-btn" href="precos.php">Preços</a>
      <a class="menu-btn active" href="blog.php">Blog</a>
      <a class="menu-btn" href="index.php">Home</a>
    </div>
  </div>
</div>
<div class="container">
  <h1>Painel do Blog</h1>
  <?php if($msg) echo '<div class="msg-ok">'.htmlspecialchars($msg).'</div>'; ?>
  <h2>Nova Publicação</h2>
  <form class="form-section" method="post" enctype="multipart/form-data" autocomplete="off" id="formBlog">
    <div class="form-grid">
      <div class="form-field">
        <label for="titulo">Título</label>
        <input type="text" id="titulo" name="titulo" maxlength="200" required>
      </div>
      <div class="form-field">
        <label for="categoria">Categoria</label>
        <select id="categoria" name="categoria" required>
          <option value="">Selecione...</option>
          <option value="Massoterapia">Massoterapia</option>
          <option value="Técnicas">Técnicas</option>
          <option value="Dicas">Dicas</option>
        </select>
      </div>
      <div class="form-field full-width">
        <label for="editor-container">Conteúdo</label>
        <!-- Quill Editor visual: -->
        <div id="editor-container" style="height:260px;background:#fff;"></div>
        <input type="hidden" name="conteudo" id="conteudo-blog">
      </div>
      <div class="form-field">
        <label for="data_post">Data da Publicação</label>
        <input type="date" id="data_post" name="data_post" required value="<?=date('Y-m-d')?>">
      </div>
      <div class="form-field">
        <label for="imagem">Imagem de Capa</label>
        <input type="file" id="imagem" name="imagem" accept="image/*">
        <small>JPG, PNG ou WEBP até 5MB (opcional).</small>
      </div>
    </div>
    <label class="form-checkbox"><input type="checkbox" name="publicado" checked><span>Publicar agora</span></label>
    <div class="form-actions">
      <button class="btn" type="submit">Publicar</button>
    </div>
  </form>
  <h2 style="margin-top:44px;">Posts Publicados</h2>
<table class="table-posts">
    <tr>
        <th>ID</th>
        <th>Título</th>
        <th>Categoria</th>
        <th>Data</th>
        <th>Imagem</th>
        <th>Status</th>
        <th>Ações</th>
    </tr>
    <?php foreach($posts as $post): ?>
    <tr>
        <td><?=$post['id']?></td>
        <td><?=htmlspecialchars($post['titulo'])?></td>
        <td><?=htmlspecialchars($post['categoria'])?></td>
        <td><?=$post['data_post']?></td>
        <td>
            <?php $thumbSrc = resolve_post_image($post['imagem'] ?? null); ?>
            <img src="<?=htmlspecialchars($thumbSrc)?>" class="img-mini" alt="Miniatura do post" onerror="this.onerror=null;this.src='../iconeFinal.png';">
        </td>
        <td><?=$post['publicado'] ? '<span style="color:#249b43">Publicado</span>' : '<span style="color:#ad3e22">Arquivado</span>'?></td>
        <td>
            <a class="btn-edit" href="blog.php?edit=<?=$post['id']?>#editar-publicacao">Editar</a>
            <form method="post" style="display:inline-block;">
                <input type="hidden" name="toggle_id" value="<?=$post['id']?>">
                <button class="btn-toggle <?=$post['publicado']?'despub':'pub'?>" type="submit">
                    <?=$post['publicado']?'Arquivar':'Publicar'?>
                </button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>

<?php
// Determina se há um post sendo preparado para edição
$edit = null;
$editId = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id']) && !isset($_POST['salvar_edicao'])) {
    $editId = intval($_POST['edit_id']);
}

if ($editId !== null) {
    foreach ($posts as $p) {
        if ($p['id'] == $editId) {
            $edit = $p;
            break;
        }
    }
}

if ($edit):
?>
    <section id="editar-publicacao">
        <h2 style="margin-top:38px;">Editar Publicação</h2>
        <form class="form-section" method="post" enctype="multipart/form-data">
            <input type="hidden" name="edit_id" value="<?=$edit['id']?>">
            <input type="hidden" name="salvar_edicao" value="1">
            <div class="form-grid">
                <div class="form-field">
                    <label for="edit_titulo">Título</label>
                    <input type="text" id="edit_titulo" name="edit_titulo" maxlength="200" value="<?=htmlspecialchars($edit['titulo'])?>" required>
                </div>
                <div class="form-field">
                    <label for="edit_categoria">Categoria</label>
                    <select id="edit_categoria" name="edit_categoria" required>
                        <option value="">Selecione...</option>
                        <option value="Massoterapia" <?=categoria_sel($edit['categoria'],'Massoterapia')?>>Massoterapia</option>
                        <option value="Técnicas" <?=categoria_sel($edit['categoria'],'Técnicas')?>>Técnicas</option>
                        <option value="Dicas" <?=categoria_sel($edit['categoria'],'Dicas')?>>Dicas</option>
                    </select>
                </div>
                <div class="form-field full-width">
                    <label for="editor-edit">Conteúdo</label>
                    <div id="editor-edit" style="height:260px;background:#fff;"></div>
                    <input type="hidden" name="edit_conteudo" id="conteudo-edit">
                </div>
                <div class="form-field">
                    <label for="edit_data_post">Data da Publicação</label>
                    <input type="date" id="edit_data_post" name="edit_data_post" required value="<?=$edit['data_post']?>">
                </div>
                <div class="form-field">
                    <label for="edit_imagem">Imagem de Capa (preencher para trocar)</label>
                    <input type="file" id="edit_imagem" name="edit_imagem" accept="image/*">
                    <small>Substitua apenas se quiser atualizar a imagem.</small>
                </div>
            </div>
            <label class="form-checkbox">
                <input type="checkbox" name="edit_publicado" <?=$edit['publicado']?'checked':''?>>
                <span>Manter publicação visível</span>
            </label>
            <div class="form-actions">
                <button class="btn" type="submit">Salvar Alterações</button>
            </div>
        </form>
    </section>
<?php endif; ?>
<script>
  (function () {
    var editSection = document.getElementById('editar-publicacao');
    if (editSection) {
      editSection.scrollIntoView({ behavior: 'smooth' });
    }
  })();
</script>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
  // Inicia o Quill
  var quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': [1, 2, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'align': [] }],
        ['link', 'image'],
        ['clean']
      ]
    }
  });

  // No submit do form, coloca HTML do editor no campo oculto
  document.getElementById('formBlog').onsubmit = function() {
    document.getElementById('conteudo-blog').value = quill.root.innerHTML;
  };
</script>
<script>
  // Formulário de Edição com Quill (só se existir o form)
  var editorEdit = document.getElementById('editor-edit');
  if(editorEdit) {
    var quillEdit = new Quill('#editor-edit', {
      theme: 'snow',
      modules: {
        toolbar: [
          [{ 'header': [1, 2, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ 'color': [] }, { 'background': [] }],
          [{ 'list': 'ordered' }, { 'list': 'bullet' }],
          [{ 'align': [] }],
          ['link', 'image'],
          ['clean']
        ]
      }
    });
    // Preenche o editor com o conteúdo atual
    quillEdit.root.innerHTML = <?=json_encode($edit['conteudo'] ?? '')?>;
    // No submit, joga o conteúdo do Quill para o campo oculto
    editorEdit.closest('form').onsubmit = function() {
      document.getElementById('conteudo-edit').value = quillEdit.root.innerHTML;
    };
  }
  </script>
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
