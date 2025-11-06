Terapia Corporal Sistêmica

Plataforma web completa para divulgação de serviços, agendamento on-line, gestão de pacientes e comunicação entre terapeuta e cliente. O projeto nasceu para ser simples de operar pela profissional e confortável para o paciente — mantendo histórico, formulários e lembretes de forma integrada.

Stack: PHP (procedural) ≥ 7.4 + MySQL + Apache • HTML/CSS/JS (responsivo) • Leaflet.js (GPS/mapas) • Integração WhatsApp Cloud API (webhook + envio de templates).

Visão Geral

Site público: Home, Serviços, Blog, Contato e página “O Espaço”.

Agendamento: escolha de terapias (massoterapia, acupuntura, reflexologia, reiki, etc.), duração (50/90 min), extra (escalda-pés), seleção de data/horário e preço dinâmico (ou desconto de pacotes 5/10 sessões).

Perfil do paciente: dados pessoais, foto, próximas sessões, histórico, pacotes restantes, anamnese recebida e formulário de queixa preenchido.

Painel da terapeuta (Admin): agenda, pacientes, anamnese, preços, pacotes, blog e moderação de conteúdos.

Comunicação: notificações e lembretes via WhatsApp (convites/confirmações/recusas e lembrete −1 dia).

Arquitetura: PHP + MySQL (tabelas: usuarios, agendamentos, formularios_queixa, contatos, especialidades, anamneses, …). Sem frameworks; fácil de hospedar em Hostinger/XAMPP.

👤 O que o paciente/usuário consegue fazer

Criar conta ou agendar como visitante (guest).

Agendar sessão escolhendo terapia(s), duração, extra opcional e horário livre no calendário.

Formulário de Queixas (opcional): sintomas, histórico, 30+ condições físicas e estados emocionais.

Pacotes: usar saldo (5/10 sessões) no agendamento e acompanhar o restante no perfil.

Perfil: editar dados, trocar foto, ver status de consultas (pendente/confirmada/cancelada/concluída), abrir anamnese e queixa dessa sessão.

Blog: ler por categorias, buscar e abrir posts.

Contato: enviar mensagens para a terapeuta (registro no banco + e-mail).

🗂️ O que a terapeuta/administradora controla

Agenda: visualizar mês/semana/dia, aprovar/recusar, remarcar, concluir, bloquear datas/horários específicos e agendamentos fixos.

Pacientes: listar/filtrar, ver perfil completo, adicionar pacotes, registrar e editar anamnese por sessão.

Preços: atualizar tabela de valores (50/90 min), quick-massage (15/30 min), escalda-pés e pacotes.

Conteúdo: gerenciar Blog (criar, editar, publicar/despublicar, upload de imagem de capa).

Relatos clínicos: leitura centralizada de queixas do paciente e anamnese (respostas e orientações ficam atreladas à sessão).

Regra de autoria e segurança
Toda ação é associada ao e-mail do usuário logado. Apenas o autor pode remover suas inserções (ex.: mídia, queixa). A terapeuta pode editar registros no painel, com rastreabilidade.
