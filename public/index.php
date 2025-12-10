<?php 
include_once "includes/header.php";
?>

<div class="container mt-5">
    <h1>Bem-vindo ao Doitly</h1>
    <p>Transforme seus hábitos e alcance seus objetivos com Doitly!</p>
</div>


<div class="container mt-5">
    <h2>Nossos principais serviços</h2>
    <div class="row"> 
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-list-check display-4 text-primary mb-3"></i>
                    <h5 class="card-title">Gerenciamento de Hábitos</h5>
                    <p class="card-text">Crie, edite e monitore seus hábitos diários com uma interface intuitiva e fácil de usar.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-graph-up-arrow display-4 text-success mb-3"></i>
                    <h5 class="card-title">Acompanhamento de Progresso</h5>
                    <p class="card-text">Visualize sua evolução através de gráficos detalhados e estatísticas de desempenho.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-calendar-check display-4 text-info mb-3"></i>
                    <h5 class="card-title">Rotina Personalizada</h5>
                    <p class="card-text">Adapte seus objetivos à sua agenda e organize sua rotina para maximizar a produtividade.</p>
                </div>
            </div>
        </div>
    </div>
</div>
 
<hr>
<!-- FORM EXAMPLE -->
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="doitly-card demo-disabled">
                <h3 class="mb-4">Criar Novo Hábito</h3>

                <div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Nome do Hábito</label>
                        <input type="text" class="doitly-input" placeholder="Ex: Fazer exercícios" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary">Descrição</label>
                        <textarea class="doitly-input doitly-textarea" placeholder="Descreva seu hábito..."></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label text-secondary">Categoria</label>
                            <select class="doitly-input">
                                <option>Saúde</option>
                                <option>Trabalho</option>
                                <option>Estudo</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary">Horário</label>
                            <select class="doitly-input">
                                <option>Manhã</option>
                                <option>Tarde</option>
                                <option>Noite</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="button" class="doitly-btn doitly-btn-secondary flex-grow-1">
                            Cancelar
                        </button>
                        <button type="button" class="doitly-btn flex-grow-1">
                            Salvar Hábito
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


 <!-- HABIT LIST usando Bootstrap Grid + Custom Components -->
    <div class="container mt-5">
      <div class="row">
        <div class="col-12 col-lg-10 offset-lg-1">
          <h2 class="mb-4">Seus Hábitos de Hoje</h2>

          <!-- Habit Item 1 -->
          <div class="habit-item mb-3">
            <div class="d-flex align-items-center gap-3 flex-grow-1">
              <span class="doitly-badge doitly-badge-success">Manhã</span>
              <span>Ler 15 minutos</span>
            </div>
            <button class="doitly-btn doitly-btn-sm doitly-btn-success">
              ✓ Concluir
            </button>
          </div>

          <!-- Habit Item 2 -->
          <div class="habit-item mb-3">
            <div class="d-flex align-items-center gap-3 flex-grow-1">
              <span class="doitly-badge doitly-badge-info">Tarde</span>
              <span>Beber 2 litros de água</span>
            </div>
            <button class="doitly-btn doitly-btn-sm">Concluir</button>
          </div>

          <!-- Habit Item 3 -->
          <div class="habit-item mb-3">
            <div class="d-flex align-items-center gap-3 flex-grow-1">
              <span class="doitly-badge doitly-badge-warning">Noite</span>
              <span>Estudar 30 minutos</span>
            </div>
            <button class="doitly-btn doitly-btn-sm">Concluir</button>
          </div>

          <!-- Habit Item 4 (completed example) -->
          <div class="habit-item mb-3" style="opacity: 0.6">
            <div class="d-flex align-items-center gap-3 flex-grow-1">
              <span class="doitly-badge doitly-badge-success">Manhã</span>
              <span style="text-decoration: line-through"
                >Meditar 10 minutos</span
              >
            </div>
            <span class="text-secondary">✓ Concluído</span>
          </div>
        </div>
      </div>
    </div>
<?php 
include_once "includes/footer.php";
?>