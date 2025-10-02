# Guia de Fluxo de Agendamento – TCS (leitura para Codex)

> **Meta:** Documentar o contrato, os invariantes e a ordem de execução do agendamento para evitar regressões. Use este guia **antes** de alterar front, back ou banco.

---

## 0) TL;DR (Checklist de Segurança)

* **Banco** tem que conter: `agendamentos.servicos_csv (TEXT)`; tabela `especialidades` com preços por duração (`preco_15/30/50/90`), `preco_escalda`, `pacote5`, `pacote10`, `quick`.
* **Constantes**: usar somente as compartilhadas pelo projeto (nenhum ID ou preço fixo adicional).
* **Front**: selecionar até 2 cards. Se 2 cards → enviar `servicos="id1,id2"` e manter `servico_id` do serviço que define o preço (mesmo formulário usado para 1 card). **Nunca** usar `pointer-events:none` em contêineres de interação.
* **Back**: validar visitante, criar conta opcional, usar transação, checar `instanceof mysqli_result` antes de `fetch_assoc()`, e gravar `usuario_id=NULL` para visitante sem conta.

---

## 1) Invariantes do Sistema

1. **Um horário = 1 agendamento.** Não existe multi-agendamento por horário.
2. **Até 2 tratamentos por agendamento.** Quando 2, o agendamento usa o serviço principal para preço/duração e registra o par completo no CSV apenas para histórico.
3. **Escalda Pés** é um adicional opcional **sempre acumulável**.
4. **Visão de dados**: joins e telas continuam baseadas em `agendamentos.especialidade_id`; quando houver 2 serviços, o par real é persistido em `agendamentos.servicos_csv` (para exibição/relatórios).
5. **`servicos_csv` não dirige lógica de preço.** Esse campo serve somente para auditoria e relatórios; o cálculo usa exclusivamente o serviço principal.

---

## 2) Banco de Dados (mínimo exigido)

### Tabela `agendamentos`

* Colunas relevantes: `id`, `usuario_id (FK→usuarios.id, aceita NULL)`, `nome_visitante`, `email_visitante`, `telefone_visitante`, `idade_visitante`, `especialidade_id`, `servicos_csv (TEXT)`, `data_horario (DATETIME)`, `duracao (INT, NOT NULL)`, `adicional_reflexo (TINYINT)`, `status`, `preco_final (DECIMAL)`.
* **Regra**: visitante sem conta grava `usuario_id = NULL` (não usar 0). Para 2 serviços, `servicos_csv = "id1,id2"`.

### Tabela `especialidades`

* Precisa conter: `preco_15`, `preco_30`, `preco_50`, `preco_90`, `preco_escalda`, `pacote5`, `pacote10`, `quick`.

---

## 3) Contrato Front → Back

### 3.1 Seleção de serviços

* **1 tratamento**: `servico_id = <ID do serviço>`, `duracao ∈ {15,30,50,90}` (obrigatório), `servicos` ausente.
* **2 tratamentos**: `servico_id = <ID do serviço que define preço>`, `servicos = "id1,id2"` (máx. 2; sem espaços), `duracao` obrigatória e coerente com o serviço principal.

### 3.2 Campos obrigatórios (mínimo)

* `data` (YYYY-MM-DD), `hora` (HH\:MM), `add_reflexo` (0/1), **visitante**: `guest_name`, `guest_email`, `guest_phone`. Se `criar_conta=1`: `guest_senha`, `guest_senha2`, `guest_nascimento`.

### 3.3 Proibições no front

* **Não** usar `pointer-events:none` em cards, calendário, horário, campos; usar só feedback visual.
* **Não** impedir o submit quando houver 2 serviços selecionados; garantir que a duração permaneça coerente com o serviço principal.

---

## 4) Lógica de Preço (resumo)

1. **Até 2 tratamentos**: o preço baseia-se sempre no serviço principal (`servico_id`) e na duração selecionada (`preco_15/30/50/90`).
2. **Serviço adicional**: o segundo serviço só influencia o registro em `servicos_csv`; não altera o preço base.
3. **Escalda Pés**: quando marcado, somar `preco_escalda` do serviço principal.
4. **Pacotes** (quando usados): se usou pacote ⇒ `preco_final = 0`.

---

## 5) Back‑end (ordem canônica)

1. **Sanitização & sessão**: iniciar sessão, timezone, `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)`.
2. **Carregar constantes/helpers** com `__DIR__` e ordem correta (conexao → constants → helpers).
3. **Validar payload**: data/hora, serviço(s), duração (conferir com o serviço principal), escalda (0/1). Para 2 serviços: parsear `servicos` → até 2 ints; se >2 ⇒ erro.
4. **Preço**: buscar na `especialidades` a duração do serviço principal e somar escalda quando aplicável.
5. **Visitante**:

   * Validar `guest_*` mínimos; calcular idade do nascimento quando disponível.
   * Se `criar_conta=1`: validar senhas, `password_hash`, inserir em `usuarios`, capturar `user_id`.
6. **Transação**: `BEGIN` → `INSERT` em `agendamentos` (ramos):

   * **Logado/visitante com conta**: `INSERT(usuario_id, especialidade_id, ..., servicos_csv)`
   * **Visitante sem conta**: `INSERT(usuario_id=NULL, nome_visitante, email_visitante, telefone_visitante, idade_visitante, ..., servicos_csv)`
7. **Pós‑INSERT**: copiar anamnese inicial; disparar hook WhatsApp da terapeuta; `COMMIT`.
8. **Erros**: `catch(Throwable)` → `ROLLBACK`, log detalhado, resposta `ERRO_AGENDAR: <código>`.

**Importante**: Antes de `fetch_assoc()`, checar `instanceof mysqli_result`. Em caso de falha, `error_log` + fallback (nunca fatal).

---

## 6) Padrões de Código (para não quebrar)

* **Nunca** assumir que a consulta SQL sempre retorna `mysqli_result` → validar.
* **Sempre** listar as colunas no `INSERT` (ordem explícita). Evita que mudança de schema quebre inserts.
* **Bind types** sem espaços e compatíveis com a quantidade de variáveis.
* **`usuario_id`**: visitante sem conta = `NULL`.
* **`servicos_csv`**: preencher sempre que houver 2 serviços; `NULL` quando for 1 serviço.
* **Includes com `__DIR__`** para evitar erros de caminho.

---

## 7) Erros Típicos e Diagnóstico

* **HTTP 500 na /agendamento.php**: consulta falhou (coluna inexistente) e chamaram `fetch_assoc()` em `false`. **Solução**: checar `instanceof mysqli_result` e alinhar schema.
* **ERRO\_AGENDAR**: normalmente FK por `usuario_id=0` no visitante ou coluna ausente. **Solução**: visitante puro grava `NULL`; garantir `servicos_csv` e IDs válidos; logar `$stmt->error`.
* **Link do WhatsApp não abre**: enviar template aprovado **fora de 24h** e checar tokens/assinaturas no Meta Cloud (fora do escopo do agendamento, mas registre a causa no log).

---

## 8) Testes Ponta‑a‑Ponta (sempre rodar antes de merge)

1. **1 serviço**: escolher serviço + duração + data/hora → visitante sem conta → `SUCESSO|id`. Conferir preço e ausência de `servicos_csv`.
2. **2 serviços**: escolher 2 cards → front envia `servico_id=<principal>`, `servicos="id1,id2"`, duração coerente → `SUCESSO|id`. Conferir `servicos_csv` preenchido (dois IDs, ordem consistente) e preço atrelado apenas ao serviço principal + escalda opcional.
3. **Visitante criando conta**: senhas batendo (≥6) + nascimento válido → cria usuário e agenda com `usuario_id`.
4. **Logado**: agenda sem campos de visitante.
5. **Falhas simuladas**: remover temporariamente uma coluna (em dev) e validar que o front não cai (logs registram e seguem com default onde aplicável).

---

## 9) Checklist Pós-deploy

1. **Schema**: confirmar em produção a presença de `agendamentos.servicos_csv`, das colunas de preço por duração em `especialidades` e dos campos `pacote5`/`pacote10`.
2. **Preços por duração**: validar via agendamento real (ou inspeção direta no banco) que `preco_15/30/50/90` estão preenchidos para todos os serviços publicados.
3. **Serviços combinados**: realizar um agendamento com 2 serviços e conferir que `servicos_csv` persistiu os dois IDs e que o preço foi calculado com base no serviço principal.
4. **Pacotes**: efetuar (ou simular no banco) um uso de pacote e garantir que `preco_final` foi zerado e a quantidade de sessões decrementada corretamente.

---

## 10) Política de Mudanças

* Qualquer mudança em **schema** deve vir com:

  1. **Migração SQL** compatível (idempotente, com rollback se aplicável);
  2. Atualização do **dump** `database/*.sql`;
  3. Ajuste de **constantes** e **cálculo de preço** quando necessário;
  4. **Testes E2E** (itens da seção 8).

---

## 11) Glossário Rápido

* **Agendamento duplo**: agendamento com 2 tratamentos; `servico_id` representa o serviço principal e `servicos_csv` guarda os dois IDs.
* **Serviço principal**: serviço escolhido para determinar duração e precificação; geralmente é o primeiro card selecionado.
* **Visitante**: sem conta (grava `usuario_id=NULL`); com conta opcional (criação via `password_hash`).

---

## 12) Contatos/Observações

* Em caso de regressão: verificar primeiro **schema** (sec. 2), **contrato** (sec. 3) e **lógica de preço** (sec. 4). Qualquer divergência nesses três pontos tende a gerar 500/ERRO\_AGENDAR.
