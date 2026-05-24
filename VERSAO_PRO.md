# Lembrete para a versao Pro

Objetivo: evoluir o app sem anuncios, mantendo a proposta de privacidade e dados salvos no proprio dispositivo.

Meta comercial: buscar 70.000 vendas nacionais da versao Pro, com posicionamento simples, confiavel e adequado ao publico brasileiro.

## Pacote Pro sugerido

- Bloqueio por PIN e biometria ao abrir o app.
- Exportacao de relatorio em PDF para consulta medica.
- Backup criptografado com senha, para restaurar em outro celular.
- Multiplos perfis, para organizar dados de familiares.
- Lembretes de medicamentos, consultas e repeticao de exames.
- Graficos mais completos, com filtros por periodo e tendencia.
- Organizacao melhor de anexos, laudos, fotos e PDFs por exame.

## Versao basica gratuita

- Cadastro manual de dados pessoais, consultas, medicamentos, exames e resultados.
- Alertas simples para resultados fora da referencia.
- Tela de pre-consulta.
- Armazenamento local no dispositivo.
- Privacidade clara: sem coleta, sem nuvem e sem compartilhamento.
- Funcionar bem como organizador pessoal simples, mesmo sem compra.

## Principios para separar Base e Pro

- Nao cobrar por seguranca basica, privacidade ou acesso aos proprios dados.
- A versao Pro deve vender conveniencia, capacidade, automacao e relatorios.
- Explicar claramente quais recursos sao Pro antes da compra.
- Evitar frustrar o usuario bloqueando o fluxo principal do app base.
- Manter um unico codigo-base, com recursos liberados por status `free` ou `pro`.

## Prioridade inicial

1. PDF com resumo pessoal, exames, evolucao e alertas.
2. PIN/biometria opcional.
3. Backup criptografado.
4. Multiplos perfis.
5. Lembretes.

## Compra e desbloqueio

- Produto na Play Console: `organizador_saude_pro`.
- Produto equivalente na App Store Connect: definir quando a conta Apple Developer estiver aprovada.
- Tipo: produto unico / nao consumivel.
- Preco sugerido: R$ 9,99.
- Compra feita pela Google Play Billing.
- No iOS, compra feita por StoreKit / In-App Purchases.
- Ao comprar, liberar Pro de forma permanente para a conta Google do usuario.
- No iOS, liberar Pro de forma permanente para o Apple ID do usuario.
- Na abertura do app, consultar a compra para restaurar automaticamente se o usuario trocar de celular ou reinstalar.
- Implementar depois da aprovacao das contas empresa no Google Play e Apple Developer.

## Implementacao futura

- Criar uma camada de feature flags: `free` e `pro`.
- Centralizar a verificacao de acesso Pro em uma funcao unica, para evitar regras espalhadas.
- Preparar telas bloqueadas com explicacao curta e botao para compra/restauracao.
- Ter fallback offline: se a compra ja foi validada antes, manter Pro liberado localmente.
- Sincronizar restauracao de compra quando houver internet e loja disponivel.
- Evitar dependencia de servidor proprio na primeira versao Pro.

## Notificacoes Pro

- Notificacoes locais, sem servidor.
- Pedir permissao de notificacao apenas quando o usuario ativar o recurso.
- Usar mensagens discretas, sem alarmismo medico.
- Exemplos:
  - "Voce tem exames fora da referencia para revisar."
  - "Considere repetir os exames alterados em breve."
  - "Lembrete de medicamento cadastrado."
  - "Consulta agendada para hoje."

## Cuidados

- Nao usar notificacao para diagnostico.
- Nao prometer urgencia medica automatica.
- Manter o app base funcionando sem Pro.
- Mostrar claramente quais recursos sao Pro antes da compra.

## Posicionamento

Versao base: organizador pessoal de saude simples e local.

Versao Pro: organizador completo de saude familiar, com seguranca, relatorios e backup.

## Meta de escala nacional

- Meta: 70.000 vendas no Brasil.
- Manter preco acessivel e facil de entender.
- Priorizar recursos que aumentem conversao sem prejudicar a versao gratuita.
- Comunicar valor em linguagem simples: relatorio para consulta, backup, familiares, lembretes e mais organizacao.
- Evitar promessa medica; vender organizacao, clareza e praticidade.
- Preparar materiais para Play Store e App Store com foco em confianca, privacidade local e utilidade no dia a dia.
