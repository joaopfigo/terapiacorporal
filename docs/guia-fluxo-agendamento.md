# Guia de Fluxo de Agendamento – TCS (leitura para Codex)

> **Meta:** Documentar o contrato, os invariantes e a ordem de execução do agendamento para evitar regressões. Use este guia **antes** de alterar front, back ou banco.

---

## 0) TL;DR (Checklist de Segurança)

* **Banco** tem que conter: `agendamentos.servicos_csv (TEXT)`; `especialidades` com **id=10** = “Combo 2 Tratamentos”, `preco_30` = preço do combo; colunas `preco_escalda`, `pacote5`, `pacote10` existentes.
* **Constantes** no PHP: `DUO_SERVICE_ID = 10`, `DUO_PRECO = <preço do combo>` alinhados ao banco.
* **Front**: se 2 cards selecionados → enviar `servico_id=10`, `servicos="id1,id2"`, `duracao='30'`. Se 1 card → fluxo antigo (exige duração). **Nunca** usar `pointer-events:none` em contêineres de interação.
* **Back**: validar visitante, criar conta opcional, usar transação, checar `instanceof mysqli_result` antes de `fetch_assoc()`, e gravar `usuario_id=NULL` para visitante sem conta.

---

## 1) Invariantes do Sistema

1. **Um horário = 1 agendamento.** Não existe multi-agendamento por horário.
2. **Até 2 tratamentos por agendamento.** Quando 2, é tratado como **Combo** (preço fixo) e **não** soma valores de cada serviço.
3. **Escalda Pés** é um adicional opcional **sempre acumulável**.
4. **Visão de dados**: joins e telas continuam baseadas em `agendamentos.especialidade_id`; quando combo, o par real é persistido em `agendamentos.servicos_csv` (para exibição/relatórios).

---

## 2) Banco de Dados (mínimo exigido)

### Tabela `agendamentos`

* Colunas relevantes: `id`, `usuario_id (FK→usuarios.id, aceita NULL)`, `nome_visitante`, `email_visitante`, `telefone_visitante`, `idade_visitante`, `especialidade_id`, `servicos_csv (TEXT)`, `data_horario (DATETIME)`, `duracao (INT, NOT NULL)`, `adicional_reflexo (TINYINT)`, `status`, `preco_final (DECIMAL)`.
* **Regra**: visitante sem conta grava `usuario_id = NULL` (não usar 0). Para combo, `servicos_csv = "id1,id2"`.

### Tabela `especialidades`

* Precisa conter: `preco_15`, `preco_30`, `preco_50`, `preco_90`, `preco_escalda`, `pacote5`, `pacote10`, `quick`.
* Linha obrigatória: **id=10** → `nome='Combo 2 Tratamentos'`, `preco_30 = DUO_PRECO`.

---

## 3) Constantes Back‑end

```php
const DUO_SERVICE_ID = 10;
const DUO_PRECO      = 260.00; // ou o valor vigente
```

* **Regra**: valores acima devem refletir o banco de produção. Se mudar no banco, mudar aqui.

---

## 4) Contrato Front → Back

### 4.1 Seleção de serviços

* **1 tratamento**: `servico_id = <ID do serviço>`, `duracao ∈ {15,30,50,90}` (obrigatório), `servicos` ausente.
* **2 tratamentos (Combo)**: `servico_id = DUO_SERVICE_ID (10)`, `servicos = "id1,id2"` (máx. 2; sem espaços), `duracao = '30'` (sentinela/fake para validar back e casar com `preco_30` do combo).

### 4.2 Campos obrigatórios (mínimo)

* `data` (YYYY-MM-DD), `hora` (HH\:MM), `add_reflexo` (0/1), **visitante**: `guest_name`, `guest_email`, `guest_phone`. Se `criar_conta=1`: `guest_senha`, `guest_senha2`, `guest_nascimento`.

### 4.3 Proibições no front

* **Não** usar `pointer-events:none` em cards, calendário, horário, campos; usar só feedback visual.
* **Não** impedir o submit em combo por causa de duração (front deve forçar `'30'`).

---

## 5) Lógica de Preço (resumo)

1. **Combo (2 tratamentos)**: preço = `DUO_PRECO` (ou `especialidades[DUO_SERVICE_ID].preco_30`) **+** (se marcado) `preco_escalda`.
2. **1 tratamento**: buscar preço na `especialidades` pela duração (`preco_15/30/50/90`), somar `preco_escalda` se marcado. Quick Massage pode ter política distinta (via `quick=1`).
3. **Pacotes** (quando usados): se usou pacote ⇒ `preco_final = 0`.

---

## 6) Back‑end (ordem canônica)

1. **Sanitização & sessão**: iniciar sessão, timezone, `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)`.
2. **Carregar constantes/helpers** com `__DIR__` e ordem correta (conexao → constants → helpers).
3. **Validar payload**: data/hora, serviço(s), duração (livre no combo), escalda (0/1). Para combo: parsear `servicos` → até 2 ints; se >2 ⇒ erro.
4. **Preço**: se combo ⇒ `DUO_PRECO` + escalda; senão ⇒ tabela por duração + escalda.
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

## 7) Padrões de Código (para não quebrar)

* **Nunca** assumir que a consulta SQL sempre retorna `mysqli_result` → validar.
* **Sempre** listar as colunas no `INSERT` (ordem explícita). Evita que mudança de schema quebre inserts.
* **Bind types** sem espaços e compatíveis com a quantidade de variáveis.
* **`usuario_id`**: visitante sem conta = `NULL`.
* **`servicos_csv`**: só preencher no combo; `NULL` quando for 1 serviço.
* **Includes com `__DIR__`** para evitar erros de caminho.

---

## 8) Erros Típicos e Diagnóstico

* **HTTP 500 na /agendamento.php**: consulta falhou (coluna inexistente) e chamaram `fetch_assoc()` em `false`. **Solução**: checar `instanceof mysqli_result` e alinhar schema.
* **ERRO\_AGENDAR**: normalmente FK por `usuario_id=0` no visitante ou coluna ausente. **Solução**: visitante puro grava `NULL`; garantir `servicos_csv` e combo id=10; logar `$stmt->error`.
* **Combo não abre janela no WhatsApp**: enviar template aprovado **fora de 24h** e checar tokens/assinaturas no Meta Cloud (fora do escopo do agendamento, mas registre a causa no log).

---

## 9) Testes Ponta‑a‑Ponta (sempre rodar antes de merge)

1. **1 serviço**: escolher serviço + duração + data/hora → visitante sem conta → `SUCESSO|id`. Conferir preço e ausência de `servicos_csv`.
2. **2 serviços (combo)**: escolher 2 cards → front envia `servico_id=10`, `servicos="id1,id2"`, `duracao='30'` → `SUCESSO|id`. Conferir `servicos_csv` preenchido e preço do combo + escalda opcional.
3. **Visitante criando conta**: senhas batendo (≥6) + nascimento válido → cria usuário e agenda com `usuario_id`.
4. **Logado**: agenda sem campos de visitante.
5. **Falhas simuladas**: remover temporariamente uma coluna (em dev) e validar que o front não cai (logs registram e seguem com default onde aplicável).

---

## 10) Política de Mudanças

* Qualquer mudança em **schema** deve vir com:

  1. **Migração SQL** compatível (idempotente, com rollback se aplicável);
  2. Atualização do **dump** `database/*.sql`;
  3. Ajuste de **constantes** e **cálculo de preço** quando necessário;
  4. **Testes E2E** (itens da seção 9).

---

## 11) Glossário Rápido

* **Combo**: agendamento com 2 tratamentos; `servico_id = DUO_SERVICE_ID` e `servicos_csv = "id1,id2"`.
* **Sentinela de duração**: `'30'` no front para combo (casa com `preco_30` do combo). No back, pode normalizar 0 se preferir, mas manter coerência.
* **Visitante**: sem conta (grava `usuario_id=NULL`); com conta opcional (criação via `password_hash`).

---

## 12) Contatos/Observações

* Em caso de regressão: verificar primeiro **schema** (sec. 2), **constantes** (sec. 3) e **contrato** (sec. 4). Qualquer divergência nesses três pontos tende a gerar 500/ERRO\_AGENDAR.
