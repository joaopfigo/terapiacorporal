# Testes manuais: agendamento combinado com e sem Quick Massage

Este roteiro garante que o cálculo de preço usa o serviço principal escolhido pelo cliente e que o registro histórico (`servicos_csv`) preserva os dois tratamentos selecionados.

## Pré-requisitos
- Verificar na tabela `especialidades` os serviços reais publicados (incluindo Quick Massage) e confirmar que todos têm preços preenchidos nas durações ofertadas (`preco_15/30/50/90`).
- Habilitar o console de rede do navegador ou preparar acesso ao banco para inspecionar o registro gravado após cada teste.

## Cenário A – Quick Massage + outro tratamento
1. Abrir `agendamento.php`.
2. Selecionar **Quick Massage** e, em seguida, outro tratamento real do catálogo.
3. Definir uma duração compatível com o tratamento principal (o serviço que deve determinar o preço). Caso o fluxo permita escolher qual serviço é principal, mantenha o tratamento não-Quick como referência.
4. Preencher os dados obrigatórios e concluir o agendamento.
5. Validar a requisição `agendar.php`:
   - `servico_id` deve corresponder ao tratamento principal (geralmente o que não é Quick).
   - `servicos` precisa conter os dois IDs separados por vírgula, na mesma ordem apresentada ao usuário.
6. Conferir o registro gravado:
   - `preco_final` deve usar `preco_<duracao>` do serviço principal.
   - `servicos_csv` precisa registrar os dois IDs, preservando a ordem do payload.

## Cenário B – Dois tratamentos sem Quick Massage
1. Repetir o fluxo escolhendo dois tratamentos padrão (sem Quick).
2. Selecionar a duração que o front-end exibe para o tratamento principal e finalizar o agendamento.
3. Confirmar:
   - `servico_id` igual ao ID do tratamento usado para a precificação.
   - `servicos` e `servicos_csv` com os dois IDs selecionados.
   - `preco_final` calculado a partir do `preco_<duracao>` desse tratamento principal.

## Cenário C – Tratamento único
1. Agendar apenas um serviço qualquer.
2. Garantir que o payload não envia `servicos` e que `servicos_csv` permanece `NULL`/vazio.
3. Validar que o preço corresponde à duração escolhida para esse serviço único.

## Resultados esperados
- O preço nunca é somado entre os serviços; o cálculo sempre segue a duração e a tabela do serviço principal.
- Quick Massage pode ser adicionada como segundo serviço sem alterar o preço final, ficando registrada apenas para histórico em `servicos_csv`.
- Agendamentos com um único serviço continuam funcionando como antes (sem `servicos_csv`).
- - A base de serviços não precisa (nem deve) conter linhas artificiais de "combo"; basta registrar os dois IDs reais em `servicos_csv` quando houver atendimento duplo.
