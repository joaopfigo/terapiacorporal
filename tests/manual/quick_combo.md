# Teste manual: Quick Massage combinada com outro tratamento

## Pré-requisitos
- A tabela `especialidades` deve conter pelo menos os serviços "Quick Massage" (id esperado: 1) e um tratamento padrão como "Massoterapia" (id esperado: 2) com preços preenchidos para 50 e 90 minutos.
- Ter acesso ao painel de rede do navegador ou ao banco de dados para confirmar o preço gravado.

## Passos
1. Acesse `agendamento.php` no navegador.
2. Selecione **Quick Massage** e outro tratamento com duração padrão (ex.: Massoterapia), garantindo que ambos os cartões fiquem destacados.
3. Escolha a duração de **50 minutos** (repita o teste com 90 minutos, se desejar) e mantenha o escalda pés desmarcado.
4. Preencha os dados obrigatórios (use um e-mail de teste) e conclua o agendamento.
5. No painel de rede do navegador, inspecione a requisição `agendar.php`:
   - Confirme que o payload envia `servico_id` correspondente ao tratamento não-Quick (ex.: `2` para Massoterapia).
6. Verifique o valor armazenado:
   - Consulte o registro gravado (via banco ou resposta da API) e confirme que o campo de preço coincide com `preco_50` (ou `preco_90`) do tratamento não-Quick selecionado.

## Resultado esperado
- Quando Quick Massage é combinada com outro tratamento e uma duração de 50/90 minutos é escolhida, o backend utiliza o `servico_id` do tratamento padrão e grava o preço respectivo (`preco_50` ou `preco_90`) desse tratamento.
- O campo CSV (`servicos` no payload / `servicos_csv` no banco) mantém ambos os IDs para histórico.
