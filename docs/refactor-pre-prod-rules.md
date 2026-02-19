# Plano de Regras de Refatoração (Qualidade e Manutenibilidade)

## 1) Objetivo deste documento
Este documento estabelece as **regras de engenharia** para conduzir a refatoração da codebase de forma simples, incremental e segura, com foco em:

- aumento de **confiabilidade**;
- melhoria de **manutenibilidade**;
- redução de **acoplamento**;
- melhoria de **correção de bugs** e **usabilidade**;
- preparação da base para evolução profissional do produto.

> Escopo intencional: apenas qualidade interna, arquitetura, padrões de código e documentação técnica.

---

## 2) Princípios obrigatórios de design

### 2.1 SOLID aplicado ao projeto
1. **Single Responsibility Principle (SRP)**  
   Cada arquivo/classe/função deve ter uma responsabilidade única e clara.
2. **Open/Closed Principle (OCP)**  
   Novas funcionalidades devem preferencialmente ser adicionadas por extensão, evitando alteração de comportamentos estáveis.
3. **Liskov Substitution Principle (LSP)**  
   Contratos entre componentes devem ser previsíveis e substituíveis sem efeitos colaterais.
4. **Interface Segregation Principle (ISP)**  
   Interfaces/contratos pequenos e focados, evitando dependências em métodos não utilizados.
5. **Dependency Inversion Principle (DIP)**  
   Módulos de alto nível dependem de abstrações, não de implementações concretas.

### 2.2 Regras complementares
- **Baixo acoplamento e alta coesão** como padrão de decisão.
- **Funções curtas e legíveis**, com nomes orientados ao domínio.
- **Sem lógica de negócio em camada de apresentação**.
- **Código explícito > código implícito** (menos “mágica”, mais clareza).
- **Refatoração incremental** em pequenos passos com validação contínua.

---

## 3) Arquitetura alvo (funcional e atualizada)

### 3.1 Separação por camadas
- **Apresentação/Entrada**: páginas e endpoints HTTP (ex.: `public/`, `actions/`).
- **Aplicação/Serviços**: coordenação de casos de uso.
- **Domínio**: regras de negócio puras, entidades e políticas.
- **Infraestrutura**: persistência, integração externa, detalhes técnicos.

### 3.2 Regras de dependência
- Fluxo permitido: **Apresentação -> Aplicação -> Domínio -> Infraestrutura (via abstrações)**.
- Domínio não pode depender de detalhes HTTP, sessão ou SQL direto.
- Apresentação não deve conter regra de negócio complexa.

### 3.3 Estratégia de desacoplamento
- Quebrar “arquivos centrais” em serviços menores por responsabilidade.
- Transformar blocos de regra em componentes testáveis e reutilizáveis.
- Isolar acesso ao banco em repositórios/gateways.
- Padronizar DTOs/objetos de transferência para fronteiras entre camadas.

---

## 4) Padrões de código para fácil manutenção

### 4.1 Convenções gerais
- Nomes consistentes com o domínio de negócio.
- Evitar duplicação (DRY), mas sem abstração prematura.
- Evitar funções “faz-tudo”.
- Tratar erros de forma previsível e centralizada.
- Comentários apenas quando agregam contexto de decisão técnica.

### 4.2 Regras de organização de arquivos
- 1 arquivo = 1 responsabilidade principal.
- Módulos devem ser organizados por **feature** e por **camada**.
- Dependências compartilhadas devem ser explícitas e mínimas.

### 4.3 Contratos e estabilidade interna
- Toda função pública deve ter contrato claro (entrada, saída, exceções/erros).
- Mudanças de contrato exigem atualização de documentação e validação de impacto.

---

## 5) Padrões de documentação interna

### 5.1 Documentação mínima por módulo
Cada módulo/feature deve manter:
1. **Objetivo funcional** (o que resolve);
2. **Fluxo principal** (entrada -> processamento -> saída);
3. **Dependências** (serviços, banco, integrações);
4. **Pontos de extensão** (onde evoluir sem quebrar);
5. **Riscos conhecidos** e limitações.

### 5.2 Documentação de código
- Funções públicas com descrição curta e objetiva.
- Arquivos de serviço com cabeçalho de contexto quando necessário.
- Diagramas simples (texto/mermaid opcional) para fluxos críticos.

### 5.3 Registro de decisões técnicas
- Decisões de arquitetura relevantes devem ser registradas (ADR simplificado).
- Cada decisão deve conter: problema, opções consideradas, escolha e impacto.

---

## 6) Desmembramento do funcionamento do site

### 6.1 Mapa funcional obrigatório
Documentar e manter atualizado:
- Fluxo de autenticação (cadastro, login, logout, sessão);
- Fluxo de dashboard e visualização de indicadores;
- Fluxo de hábitos (criar, editar, marcar, arquivar);
- Fluxo de configurações de perfil e preferências.

### 6.2 Para cada fluxo, descrever
- Entradas (formulários/endpoints);
- Regras de negócio executadas;
- Componentes envolvidos por camada;
- Saídas esperadas e cenários de falha.

### 6.3 Objetivo do desmembramento
- Facilitar onboarding técnico;
- Simplificar análise de bug;
- Reduzir dependência de conhecimento tácito da equipe.

---

## 7) Diretrizes de refatoração incremental

### 7.1 Estratégia de execução
- Refatorar por partes pequenas e independentes.
- Validar comportamento a cada etapa.
- Evitar “big bang refactor”.

### 7.2 Ordem sugerida (por valor técnico)
1. Separar lógica de negócio da camada de apresentação;
2. Centralizar contratos e serviços de domínio;
3. Isolar persistência em componentes dedicados;
4. Reduzir duplicações e padronizar tratamento de erro;
5. Consolidar documentação técnica por fluxo.

### 7.3 Critérios de conclusão de cada etapa
- Código mais simples que a versão anterior;
- Menor acoplamento entre camadas;
- Documentação atualizada;
- Sem regressão funcional nos fluxos críticos.

---

## 8) Checklist de qualidade para Pull Requests

### 8.1 Requisitos obrigatórios
- [ ] Escopo pequeno e objetivo;
- [ ] Responsabilidade do módulo claramente definida;
- [ ] Sem nova lógica de negócio em camada de apresentação;
- [ ] Dependências reduzidas ou justificadas;
- [ ] Documentação técnica atualizada no mesmo PR;
- [ ] Fluxos impactados descritos com clareza.

### 8.2 Sinais de alerta (bloquear merge)
- PR com múltiplas responsabilidades não relacionadas;
- Aumento de acoplamento entre módulos;
- Alterações sem atualização de documentação;
- Complexidade maior sem ganho técnico explícito.

---

## 9) Definição de pronto da refatoração (DoD)
Uma entrega de refatoração só é considerada pronta quando:

1. Melhora a clareza estrutural do código;
2. Reduz dependência entre componentes;
3. Mantém ou melhora comportamento funcional existente;
4. Atualiza documentação interna correspondente;
5. Permite evolução futura com menor esforço técnico.

---

## 10) Compromisso de evolução da codebase
Este projeto, embora acadêmico hoje, será tratado com padrão técnico de produto profissional em crescimento.  
Toda refatoração deve priorizar confiabilidade, clareza e facilidade de colaboração da equipe, garantindo uma base sustentável para expansão futura.
