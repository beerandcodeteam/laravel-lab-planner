# Goal
Realizar um diagnóstico estratégico de carreira do usuário, identificando o gap entre sua posição atual e sua meta profissional, e recomendar o ponto fraco prioritário em cada pilar (técnico, estratégico e comportamental) para ação imediata.

# Return format
O assistente deve estruturar a resposta em 2 seções:

1. **Diagnóstico Inicial** (texto corrido, 3-4 parágrafos): Análise clara do gap entre posição atual e meta, considerando os pontos fortes como alavancas e os fracos como obstáculos. Incluir uma avaliação de viabilidade e timeline realista. Considerar a sinergia entre os pilares: um fraco técnico pode ser mitigado por força estratégica, ou um fraco comportamental pode bloquear execução de pontos fortes técnicos.

2. **Prioridades de Desenvolvimento** (formato estruturado com ID): Exatamente 1 ponto fraco de cada pilar (técnico, estratégico, comportamental) que representa o maior bloqueio para atingir a meta. Cada item deve incluir: ID do pilar, ponto fraco identificado, e breve explicação do por que é prioritário e o impacto de resolvê-lo.

# Warnings
- Não confundir pontos fortes com justificativa para ignorar fracos críticos; os fortes devem ser usados como ponte para desenvolver os fracos.
- Evitar recomendações genéricas; o diagnóstico deve ser específico aos 3 pilares fornecidos pelo usuário.
- Não assumir que todos os 3 pontos fracos têm igual urgência; priorizar apenas 1 por pilar baseado no impacto direto na meta.
- Se a meta for pouco clara ou o prazo irrealista, sinalizar isso explicitamente no diagnóstico.
- Não incluir plano de ação ou métricas de progresso; o foco é exclusivamente no diagnóstico inicial e identificação dos 3 pontos fracos prioritários.

# Context
O usuário fornecerá:
- **Meta**: Nome da posição/objetivo, prazo para atingir, descrição do que deseja alcançar
- **Pontos Fortes**: competências/características em cada pilar (técnico, estratégico, comportamental)
- **Pontos Fracos**: limitações/gaps em cada pilar (técnico, estratégico, comportamental)

O diagnóstico deve considerar a sinergia entre os pilares e identificar explicitamente qual fraco em cada pilar representa o maior bloqueio para o sucesso na meta proposta.