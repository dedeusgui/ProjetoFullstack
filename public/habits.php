<?php 
$showRegisterButton = false;
$hideLoginButton = true;

// TODO: Backend - Substituir por dados reais do banco de dados
// Dados mockados para demonstração
$userData = [
    'name' => 'João Silva',
    'email' => 'joao.silva@email.com',
    'initials' => 'JS'
];

$stats = [
    'total_habits' => 8,
    'active_habits' => 8,
    'archived_habits' => 2
];

// Lista completa de hábitos
$habits = [
    [
        'id' => 1,
        'name' => 'Meditar pela manhã',
        'description' => 'Praticar 10 minutos de meditação guiada',
        'category' => 'Saúde Mental',
        'time' => 'Manhã',
        'color' => '#4a74ff',
        'streak' => 12,
        'completed_today' => true,
        'created_at' => '2024-01-15'
    ],
    [
        'id' => 2,
        'name' => 'Ler 30 minutos',
        'description' => 'Leitura de desenvolvimento pessoal ou ficção',
        'category' => 'Desenvolvimento Pessoal',
        'time' => 'Manhã',
        'color' => '#59d186',
        'streak' => 8,
        'completed_today' => true,
        'created_at' => '2024-01-20'
    ],
    [
        'id' => 3,
        'name' => 'Exercícios físicos',
        'description' => '30 minutos de atividade física',
        'category' => 'Saúde Física',
        'time' => 'Tarde',
        'color' => '#ff5757',
        'streak' => 5,
        'completed_today' => false,
        'created_at' => '2024-02-01'
    ],
    [
        'id' => 4,
        'name' => 'Beber 2L de água',
        'description' => 'Manter hidratação adequada ao longo do dia',
        'category' => 'Saúde Física',
        'time' => 'Tarde',
        'color' => '#4a74ff',
        'streak' => 15,
        'completed_today' => true,
        'created_at' => '2024-01-10'
    ],
    [
        'id' => 5,
        'name' => 'Estudar programação',
        'description' => 'Dedicar tempo para aprender novas tecnologias',
        'category' => 'Estudo',
        'time' => 'Noite',
        'color' => '#eed27a',
        'streak' => 7,
        'completed_today' => false,
        'created_at' => '2024-01-25'
    ],
    [
        'id' => 6,
        'name' => 'Gratidão diária',
        'description' => 'Escrever 3 coisas pelas quais sou grato',
        'category' => 'Saúde Mental',
        'time' => 'Noite',
        'color' => '#59d186',
        'streak' => 20,
        'completed_today' => false,
        'created_at' => '2024-01-05'
    ],
    [
        'id' => 7,
        'name' => 'Alongamento',
        'description' => '15 minutos de alongamento e mobilidade',
        'category' => 'Saúde Física',
        'time' => 'Manhã',
        'color' => '#ff5757',
        'streak' => 3,
        'completed_today' => false,
        'created_at' => '2024-02-05'
    ],
    [
        'id' => 8,
        'name' => 'Planejamento do dia',
        'description' => 'Revisar e planejar tarefas do dia',
        'category' => 'Trabalho',
        'time' => 'Manhã',
        'color' => '#4a74ff',
        'streak' => 10,
        'completed_today' => true,
        'created_at' => '2024-01-18'
    ]
];

// Categorias disponíveis
$categories = [
    'Saúde Mental',
    'Saúde Física',
    'Desenvolvimento Pessoal',
    'Estudo',
    'Trabalho',
    'Relacionamentos',
    'Finanças',
    'Hobbies'
];

include_once "includes/header.php";
?>

<!-- Link para CSS do Dashboard -->
<link rel="stylesheet" href="assets/css/dashboard.css" />

<!-- Dashboard Wrapper -->
<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <!-- User Info -->
        <div class="sidebar-header">
            <div class="sidebar-user">
                <div class="user-avatar">
                    <?php echo $userData['initials']; ?>
                </div>
                <div class="user-info">
                    <h4 class="user-name"><?php echo $userData['name']; ?></h4>
                    <p class="user-email"><?php echo $userData['email']; ?></p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h5 class="nav-section-title">Menu Principal</h5>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="bi bi-house-door"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="habits.php" class="nav-link active">
                            <i class="bi bi-list-check"></i>
                            <span>Meus Hábitos</span>
                            <span class="nav-badge"><?php echo $stats['total_habits']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="history.php" class="nav-link">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>Histórico</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h5 class="nav-section-title">Conta</h5>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="bi bi-gear"></i>
                            <span>Configurações</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Sair</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <!-- Header -->
        <div class="dashboard-header" style="margin-bottom: var(--space-lg);">
            <div class="d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: var(--space-md);">
                <div>
                    <h1 class="dashboard-title">Meus Hábitos 📝</h1>
                    <p class="dashboard-subtitle">Gerencie e acompanhe seus hábitos diários</p>
                </div>
                <button class="doitly-btn" onclick="openHabitModal()">
                    <i class="bi bi-plus-circle"></i>
                    Novo Hábito
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats" style="margin-bottom: var(--space-xl);">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total de Hábitos</span>
                    <div class="stat-icon">
                        <i class="bi bi-list-ul"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['total_habits']; ?></h2>
                <div class="stat-change neutral">
                    <i class="bi bi-dash"></i>
                    <span>Ativos</span>
                </div>
            </div>

            <div class="stat-card stat-success">
                <div class="stat-header">
                    <span class="stat-label">Concluídos Hoje</span>
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo count(array_filter($habits, fn($h) => $h['completed_today'])); ?></h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>de <?php echo count($habits); ?> hábitos</span>
                </div>
            </div>

            <div class="stat-card stat-warning">
                <div class="stat-header">
                    <span class="stat-label">Maior Sequência</span>
                    <div class="stat-icon">
                        <i class="bi bi-fire"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo max(array_column($habits, 'streak')); ?></h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>dias consecutivos</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="dashboard-card" style="margin-bottom: var(--space-lg);">
            <div class="card-body">
                <div class="d-flex gap-md align-items-center" style="flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input 
                            type="text" 
                            class="doitly-input" 
                            placeholder="🔍 Buscar hábitos..."
                            id="searchInput"
                            onkeyup="filterHabits()"
                        />
                    </div>
                    <div style="min-width: 180px;">
                        <select class="doitly-input" id="categoryFilter" onchange="filterHabits()">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="min-width: 150px;">
                        <select class="doitly-input" id="timeFilter" onchange="filterHabits()">
                            <option value="">Todos os horários</option>
                            <option value="Manhã">☀️ Manhã</option>
                            <option value="Tarde">🌤️ Tarde</option>
                            <option value="Noite">🌙 Noite</option>
                        </select>
                    </div>
                    <button class="doitly-btn doitly-btn-ghost" onclick="clearFilters()">
                        <i class="bi bi-x-circle"></i>
                        Limpar
                    </button>
                </div>
            </div>
        </div>

        <!-- Habits List -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-list-check"></i>
                    Lista de Hábitos
                </h3>
                <div class="card-actions">
                    <span class="doitly-badge doitly-badge-info" id="habitCount">
                        <?php echo count($habits); ?> hábitos
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div id="habitsList" class="d-flex flex-column gap-md">
                    <?php foreach ($habits as $habit): ?>
                        <div class="habit-item habit-card" 
                             data-category="<?php echo $habit['category']; ?>"
                             data-time="<?php echo $habit['time']; ?>"
                             data-name="<?php echo strtolower($habit['name']); ?>"
                             style="<?php echo $habit['completed_today'] ? 'opacity: 0.8;' : ''; ?>">
                            
                            <!-- Left Side: Info -->
                            <div class="d-flex align-items-center gap-md flex-grow-1" style="min-width: 0;">
                                <!-- Color Indicator -->
                                <div style="width: 4px; height: 48px; background: <?php echo $habit['color']; ?>; border-radius: 4px; flex-shrink: 0;"></div>
                                
                                <!-- Habit Info -->
                                <div style="flex: 1; min-width: 0;">
                                    <div class="d-flex align-items-center gap-sm" style="flex-wrap: wrap; margin-bottom: 4px;">
                                        <h4 style="margin: 0; font-size: 1rem; font-weight: var(--font-medium); <?php echo $habit['completed_today'] ? 'text-decoration: line-through;' : ''; ?>">
                                            <?php echo $habit['name']; ?>
                                        </h4>
                                        
                                        <?php if ($habit['time'] === 'Manhã'): ?>
                                            <span class="doitly-badge doitly-badge-success" style="font-size: 0.75rem;">☀️ Manhã</span>
                                        <?php elseif ($habit['time'] === 'Tarde'): ?>
                                            <span class="doitly-badge doitly-badge-info" style="font-size: 0.75rem;">🌤️ Tarde</span>
                                        <?php else: ?>
                                            <span class="doitly-badge doitly-badge-warning" style="font-size: 0.75rem;">🌙 Noite</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p style="margin: 0 0 4px 0; font-size: 0.875rem; color: var(--text-secondary);">
                                        <?php echo $habit['description']; ?>
                                    </p>
                                    
                                    <div class="d-flex align-items-center gap-md" style="flex-wrap: wrap;">
                                        <small class="text-secondary">
                                            <i class="bi bi-tag"></i> <?php echo $habit['category']; ?>
                                        </small>
                                        <small style="color: var(--accent-gold); font-weight: var(--font-medium);">
                                            <i class="bi bi-fire"></i> <?php echo $habit['streak']; ?> dias
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Side: Actions -->
                            <div class="d-flex align-items-center gap-sm" style="flex-shrink: 0;">
                                <?php if ($habit['completed_today']): ?>
                                    <button class="doitly-btn doitly-btn-sm doitly-btn-success" disabled>
                                        <i class="bi bi-check-circle-fill"></i> Feito
                                    </button>
                                <?php else: ?>
                                    <button class="doitly-btn doitly-btn-sm doitly-btn-success" onclick="toggleHabit(<?php echo $habit['id']; ?>)">
                                        <i class="bi bi-circle"></i> Concluir
                                    </button>
                                <?php endif; ?>
                                
                                <button class="doitly-btn doitly-btn-sm doitly-btn-ghost" onclick="editHabit(<?php echo $habit['id']; ?>)" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <button class="doitly-btn doitly-btn-sm doitly-btn-ghost" onclick="deleteHabit(<?php echo $habit['id']; ?>)" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="empty-state" style="display: none;">
                    <div class="empty-icon">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h4 class="empty-title">Nenhum hábito encontrado</h4>
                    <p class="empty-text">Tente ajustar os filtros ou criar um novo hábito</p>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Criar/Editar Hábito -->
<div id="habitModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; padding: var(--space-lg); overflow-y: auto;">
    <div style="max-width: 600px; margin: 40px auto; background: var(--bg-light); border-radius: var(--radius-large); padding: var(--space-xl); box-shadow: var(--shadow-strong);">
        <div class="d-flex justify-content-between align-items-center" style="margin-bottom: var(--space-lg);">
            <h2 style="margin: 0; font-size: 1.5rem;">
                <i class="bi bi-plus-circle"></i> Novo Hábito
            </h2>
            <button class="doitly-btn doitly-btn-ghost doitly-btn-sm" onclick="closeHabitModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="habitForm" onsubmit="saveHabit(event)">
            <div style="margin-bottom: var(--space-md);">
                <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                    Nome do Hábito *
                </label>
                <input 
                    type="text" 
                    class="doitly-input" 
                    placeholder="Ex: Meditar pela manhã" 
                    required
                    id="habitName"
                />
            </div>

            <div style="margin-bottom: var(--space-md);">
                <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                    Descrição (Opcional)
                </label>
                <textarea 
                    class="doitly-input doitly-textarea" 
                    placeholder="Adicione detalhes sobre seu hábito..."
                    id="habitDescription"
                    rows="3"
                ></textarea>
            </div>

            <div class="row g-3" style="margin-bottom: var(--space-md);">
                <div class="col-md-6">
                    <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                        Categoria *
                    </label>
                    <select class="doitly-input" required id="habitCategory">
                        <option value="">Selecione...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                        Horário *
                    </label>
                    <select class="doitly-input" required id="habitTime">
                        <option value="">Selecione...</option>
                        <option value="Manhã">☀️ Manhã (06:00 - 12:00)</option>
                        <option value="Tarde">🌤️ Tarde (12:00 - 18:00)</option>
                        <option value="Noite">🌙 Noite (18:00 - 00:00)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: var(--space-lg);">
                <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                    Cor do Hábito
                </label>
                <div class="d-flex gap-sm" style="flex-wrap: wrap;">
                    <label style="cursor: pointer;">
                        <input type="radio" name="habitColor" value="#4a74ff" checked style="display: none;">
                        <div style="width: 40px; height: 40px; background: #4a74ff; border-radius: var(--radius-small); border: 3px solid transparent; transition: var(--transition);" class="color-option"></div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="habitColor" value="#59d186" style="display: none;">
                        <div style="width: 40px; height: 40px; background: #59d186; border-radius: var(--radius-small); border: 3px solid transparent; transition: var(--transition);" class="color-option"></div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="habitColor" value="#ff5757" style="display: none;">
                        <div style="width: 40px; height: 40px; background: #ff5757; border-radius: var(--radius-small); border: 3px solid transparent; transition: var(--transition);" class="color-option"></div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="habitColor" value="#eed27a" style="display: none;">
                        <div style="width: 40px; height: 40px; background: #eed27a; border-radius: var(--radius-small); border: 3px solid transparent; transition: var(--transition);" class="color-option"></div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="habitColor" value="#a78bfa" style="display: none;">
                        <div style="width: 40px; height: 40px; background: #a78bfa; border-radius: var(--radius-small); border: 3px solid transparent; transition: var(--transition);" class="color-option"></div>
                    </label>
                </div>
            </div>

            <div class="d-flex gap-md">
                <button type="button" class="doitly-btn doitly-btn-secondary flex-grow-1" onclick="closeHabitModal()">
                    Cancelar
                </button>
                <button type="submit" class="doitly-btn flex-grow-1">
                    <i class="bi bi-save"></i> Salvar Hábito
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.habit-card {
    transition: all 0.3s ease;
}

.color-option {
    transition: all 0.2s ease;
}

.color-option:hover {
    transform: scale(1.1);
}

input[type="radio"]:checked + .color-option {
    border-color: var(--text-primary) !important;
    transform: scale(1.15);
}

@media (max-width: 768px) {
    .habit-item {
        flex-direction: column;
        align-items: stretch !important;
    }
    
    .habit-item > div:last-child {
        justify-content: flex-start;
        margin-top: var(--space-sm);
    }
}
</style>

<script>
// TODO: Backend - Substituir por chamadas AJAX reais

function openHabitModal() {
    document.getElementById('habitModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeHabitModal() {
    document.getElementById('habitModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('habitForm').reset();
}

function saveHabit(event) {
    event.preventDefault();
    
    // TODO: Backend - Enviar dados para API
    const formData = {
        name: document.getElementById('habitName').value,
        description: document.getElementById('habitDescription').value,
        category: document.getElementById('habitCategory').value,
        time: document.getElementById('habitTime').value,
        color: document.querySelector('input[name="habitColor"]:checked').value
    };
    
    console.log('Salvando hábito:', formData);
    
    // Simular sucesso
    alert('✅ Hábito salvo com sucesso!\n\n(TODO: Backend - Implementar salvamento real)');
    closeHabitModal();
}

function editHabit(id) {
    // TODO: Backend - Carregar dados do hábito e abrir modal
    console.log('Editando hábito ID:', id);
    alert('🔧 Editar hábito #' + id + '\n\n(TODO: Backend - Implementar edição)');
}

function deleteHabit(id) {
    if (confirm('Tem certeza que deseja excluir este hábito?')) {
        // TODO: Backend - Deletar hábito
        console.log('Deletando hábito ID:', id);
        alert('🗑️ Hábito excluído!\n\n(TODO: Backend - Implementar exclusão)');
    }
}

function toggleHabit(id) {
    // TODO: Backend - Marcar/desmarcar conclusão
    console.log('Alternando conclusão do hábito ID:', id);
    alert('✅ Hábito marcado como concluído!\n\n(TODO: Backend - Implementar toggle)');
}

function filterHabits() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const timeFilter = document.getElementById('timeFilter').value;
    
    const habits = document.querySelectorAll('.habit-card');
    let visibleCount = 0;
    
    habits.forEach(habit => {
        const name = habit.dataset.name;
        const category = habit.dataset.category;
        const time = habit.dataset.time;
        
        const matchesSearch = name.includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter;
        const matchesTime = !timeFilter || time === timeFilter;
        
        if (matchesSearch && matchesCategory && matchesTime) {
            habit.style.display = 'flex';
            visibleCount++;
        } else {
            habit.style.display = 'none';
        }
    });
    
    // Atualizar contador
    document.getElementById('habitCount').textContent = visibleCount + ' hábito' + (visibleCount !== 1 ? 's' : '');
    
    // Mostrar/ocultar empty state
    document.getElementById('emptyState').style.display = visibleCount === 0 ? 'block' : 'none';
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('timeFilter').value = '';
    filterHabits();
}

// Fechar modal ao clicar fora
document.getElementById('habitModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeHabitModal();
    }
});
</script>

<?php include_once "includes/footer.php"; ?>