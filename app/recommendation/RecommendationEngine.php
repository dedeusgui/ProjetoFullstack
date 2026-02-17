<?php

class RecommendationEngine
{
    public static function generate(array $scoreData, array $trendData, array $behaviorData): array
    {
        $risk = $scoreData['risk_level'] ?? 'stable';

        if ($risk === 'high_performer') {
            return [
                'title' => 'Ótimo desempenho! Hora de evoluir.',
                'insight_text' => 'Você está mantendo consistência forte. Considere aumentar levemente o desafio e incluir um hábito complementar.',
                'actions' => [
                    'Aumentar a meta diária em 10%.',
                    'Adicionar 1 hábito complementar de baixa fricção.',
                    'Definir uma meta extra para os próximos 7 dias.'
                ],
                'priority' => 'low'
            ];
        }

        if ($risk === 'stable') {
            return [
                'title' => 'Você está estável. Mantenha o ritmo.',
                'insight_text' => 'Seu progresso está consistente. Pequenos ajustes podem melhorar sua taxa sem aumentar esforço demais.',
                'actions' => [
                    'Manter a meta atual por mais 7 dias.',
                    'Ajustar dificuldade em até 5%.',
                    'Revisar horário do hábito menos concluído.'
                ],
                'priority' => 'medium'
            ];
        }

        if ($risk === 'attention') {
            return [
                'title' => 'Sinal de atenção detectado.',
                'insight_text' => 'Houve queda de consistência recente. Uma pequena simplificação da rotina pode recuperar o ritmo.',
                'actions' => [
                    'Reduzir meta em 10% por 5 dias.',
                    'Quebrar o hábito principal em etapas menores.',
                    'Ativar lembrete em horário fixo.'
                ],
                'priority' => 'high'
            ];
        }

        return [
            'title' => 'Risco de abandono identificado.',
            'insight_text' => 'A combinação de falhas recentes e tendência negativa indica risco. Foque em retomar com menor fricção nos próximos dias.',
            'actions' => [
                'Reduzir meta diária em 20% pelos próximos 5 dias.',
                'Diminuir frequência temporariamente para facilitar adesão.',
                'Começar por uma versão mínima de 2 minutos da tarefa.'
            ],
            'priority' => 'urgent',
            'status' => 'at_risk',
            'context' => [
                'consecutive_failures' => (int) ($behaviorData['consecutive_failures'] ?? 0),
                'trend' => $trendData['trend'] ?? 'neutral'
            ]
        ];
    }
}
