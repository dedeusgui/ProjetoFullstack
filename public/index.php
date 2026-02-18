<?php 
// Mostra Login e Cadastrar lado a lado
$showRegisterButton = true;

include_once "includes/header.php";
?>
<!-- ========== HERO SECTION ========== -->
<section class="hero-section">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-12 col-lg-10">
                <h1 class="hero-title mb-4" data-aos="fade-up">Transforme Seus Objetivos em Hábitos Consistentes</h1>
                <p class="hero-subtitle mb-5" data-aos="fade-up" data-aos-delay="100">
                    O Doitly é o gerenciador de hábitos moderno e minimalista que te ajuda a construir 
                    uma rotina produtiva, acompanhar seu progresso e alcançar suas metas com consistência.
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap mb-5" data-aos="fade-up" data-aos-delay="200">
                    <a href="register.php" class="doitly-btn doitly-btn-lg">Começar Gratuitamente</a>
                    <a href="#como-funciona" class="doitly-btn doitly-btn-secondary doitly-btn-lg">Saiba Mais</a>
                </div>
                
                <!-- Estatísticas Rápidas -->
                <div class="row g-4 mt-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <h3 class="stat-number" data-count="1000">0</h3>
                            <p class="stat-label">Usuários Ativos</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <h3 class="stat-number" data-count="95">0</h3>
                            <p class="stat-label">Taxa de Sucesso</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <h3 class="stat-number" data-count="50000">0</h3>
                            <p class="stat-label">Hábitos Criados</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <h3 class="stat-number" data-count="4.9">0</h3>
                            <p class="stat-label">Avaliação Média</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== POR QUE DOITLY ========== -->
<section class="benefits-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Por Que Escolher o Doitly?</h2>
            <p class="section-subtitle">
                Desenvolvido com foco em simplicidade e eficiência para transformar sua rotina
            </p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
                <div class="doitly-card text-center h-100">
                    <div class="benefit-icon mb-3">
                        <i class="bi bi-layout-text-window-reverse"></i>
                    </div>
                    <h5 class="mb-3">Interface Intuitiva</h5>
                    <p class="text-secondary">
                        Design minimalista e moderno que facilita o gerenciamento dos seus hábitos diários.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="doitly-card text-center h-100">
                    <div class="benefit-icon mb-3">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h5 class="mb-3">Acompanhamento Detalhado</h5>
                    <p class="text-secondary">
                        Visualize seu progresso com gráficos e estatísticas que motivam você a continuar.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="doitly-card text-center h-100">
                    <div class="benefit-icon mb-3">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <h5 class="mb-3">Rápido e Eficiente</h5>
                    <p class="text-secondary">
                        Adicione e gerencie hábitos em segundos, sem complicações ou distrações.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="doitly-card text-center h-100">
                    <div class="benefit-icon mb-3">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="mb-3">Seus Dados Seguros</h5>
                    <p class="text-secondary">
                        Privacidade e segurança em primeiro lugar. Seus dados são protegidos e criptografados.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== FUNCIONALIDADES PRINCIPAIS ========== -->
<section class="features-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Funcionalidades Principais</h2>
            <p class="section-subtitle">
                Tudo que você precisa para construir e manter hábitos saudáveis
            </p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="feature-card h-100">
                    <div class="feature-icon">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <h5 class="card-title">Gerenciamento de Hábitos</h5>
                    <p class="card-text">
                        Crie, edite e organize seus hábitos diários de forma simples e intuitiva. 
                        Categorize por tipo, horário e prioridade para manter tudo sob controle.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-card h-100">
                    <div class="feature-icon">
                        <i class="bi bi-bar-chart-line"></i>
                    </div>
                    <h5 class="card-title">Acompanhamento de Progresso</h5>
                    <p class="card-text">
                        Visualize sua evolução através de gráficos detalhados, estatísticas de desempenho 
                        e histórico completo. Veja o quanto você já evoluiu!
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-card h-100">
                    <div class="feature-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h5 class="card-title">Rotina Personalizada</h5>
                    <p class="card-text">
                        Adapte seus objetivos à sua agenda pessoal. Organize sua rotina matinal, 
                        vespertina e noturna para maximizar sua produtividade.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== RECURSOS ADICIONAIS (GRID) ========== -->
<section class="features-grid-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Recursos Adicionais</h2>
            <p class="section-subtitle">
                Funcionalidades extras que tornam sua experiência ainda melhor
            </p>
        </div>

        <div class="row g-4">
            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="0">
                <div class="feature-grid-item">
                    <div class="feature-grid-icon">
                        <i class="bi bi-bell"></i>
                    </div>
                    <h6>Notificações</h6>
                    <p>Lembretes personalizados</p>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="50">
                <div class="feature-grid-item">
                    <div class="feature-grid-icon">
                        <i class="bi bi-moon-stars"></i>
                    </div>
                    <h6>Dark Mode</h6>
                    <p>Interface escura</p>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-grid-item">
                    <div class="feature-grid-icon">
                        <i class="bi bi-download"></i>
                    </div>
                    <h6>Exportar Dados</h6>
                    <p>CSV e PDF</p>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="150">
                <div class="feature-grid-item">
                    <div class="feature-grid-icon">
                        <i class="bi bi-phone"></i>
                    </div>
                    <h6>Responsivo</h6>
                    <p>Mobile e Desktop</p>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-grid-item">
                    <div class="feature-grid-icon">
                        <i class="bi bi-palette"></i>
                    </div>
                    <h6>Temas</h6>
                    <p>Personalização visual</p>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="250">
                <div class="feature-grid-item">
                    <div class="feature-grid-icon">
                        <i class="bi bi-cloud-check"></i>
                    </div>
                    <h6>Sync na Nuvem</h6>
                    <p>Dados sincronizados</p>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-grid-item">
                    <div class="feature-grid-icon">
                        <i class="bi bi-award"></i>
                    </div>
                    <h6>Conquistas</h6>
                    <p>Badges e recompensas</p>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="350">
                <div class="feature-grid-item">
                    <div class="feature-grid-icon">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <h6>Performance</h6>
                    <p>Rápido e leve</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== COMO FUNCIONA (DEMONSTRAÇÃO) ========== -->
<section class="demo-section" id="como-funciona">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Como Funciona o Doitly</h2>
            <p class="section-subtitle">
                Criar e gerenciar hábitos nunca foi tão fácil. Veja como é simples começar sua jornada de transformação.
            </p>
        </div>

        <!-- Passo a Passo -->
        <div class="row g-4 mb-5">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="doitly-card text-center h-100">
                    <div class="step-number">1</div>
                    <h5 class="mb-3">Crie Seu Hábito</h5>
                    <p class="text-secondary">
                        Defina o nome, descrição, categoria e horário do seu novo hábito. 
                        É rápido e você pode personalizar cada detalhe.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="doitly-card text-center h-100">
                    <div class="step-number">2</div>
                    <h5 class="mb-3">Acompanhe Diariamente</h5>
                    <p class="text-secondary">
                        Marque seus hábitos como concluídos todos os dias. 
                        Construa sequências (streaks) e mantenha a consistência.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="doitly-card text-center h-100">
                    <div class="step-number">3</div>
                    <h5 class="mb-3">Veja Sua Evolução</h5>
                    <p class="text-secondary">
                        Acompanhe seu progresso com estatísticas detalhadas e 
                        celebre cada conquista no caminho para seus objetivos.
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulário de Demonstração -->
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8" data-aos="fade-up" data-aos-delay="100">
                <div class="doitly-card demo-disabled">
                    <div class="text-center mb-4">
                        <h3 class="mb-2">Exemplo: Criar Novo Hábito</h3>
                        <p class="text-secondary">
                            Veja como é simples adicionar um novo hábito à sua rotina. 
                            Esta é apenas uma demonstração - faça seu cadastro para começar de verdade!
                        </p>
                    </div>

                    <div>
                        <div class="mb-3">
                            <label class="form-label text-secondary">Nome do Hábito</label>
                            <input 
                                type="text" 
                                class="doitly-input" 
                                placeholder="Ex: Fazer exercícios por 30 minutos" 
                                value="Meditar pela manhã"
                            />
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary">Descrição (Opcional)</label>
                            <textarea 
                                class="doitly-input doitly-textarea" 
                                placeholder="Adicione detalhes sobre seu hábito..."
                            >Praticar 10 minutos de meditação guiada logo após acordar para começar o dia com mais foco e tranquilidade.</textarea>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-secondary">Categoria</label>
                                <select class="doitly-input">
                                    <option>Saúde Mental</option>
                                    <option>Saúde Física</option>
                                    <option>Trabalho</option>
                                    <option>Estudo</option>
                                    <option>Desenvolvimento Pessoal</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary">Horário Preferencial</label>
                                <select class="doitly-input">
                                    <option>Manhã (06:00 - 12:00)</option>
                                    <option>Tarde (12:00 - 18:00)</option>
                                    <option>Noite (18:00 - 00:00)</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="button" class="doitly-btn doitly-btn-secondary flex-grow-1">
                                Cancelar
                            </button>
                            <button type="button" class="doitly-btn flex-grow-1">
                                <i class="bi bi-save"></i> Salvar Hábito
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== EXEMPLO DE HÁBITOS ========== -->
<section class="habits-example-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Seus Hábitos em Ação</h2>
            <p class="section-subtitle">
                Veja como fica sua lista de hábitos diários. Simples, organizado e motivador.
            </p>
        </div>

        <div class="row">
            <div class="col-12 col-lg-10 offset-lg-1" data-aos="fade-up" data-aos-delay="100">
                <!-- Habit Item 1 -->
                <div class="habit-item mb-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <span class="doitly-badge doitly-badge-success">Manhã</span>
                        <div>
                            <span class="d-block">Ler 15 minutos</span>
                            <small class="text-secondary">Desenvolvimento Pessoal</small>
                        </div>
                    </div>
                    <button class="doitly-btn doitly-btn-sm doitly-btn-success">
                        ✓ Concluir
                    </button>
                </div>

                <!-- Habit Item 2 -->
                <div class="habit-item mb-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <span class="doitly-badge doitly-badge-info">Tarde</span>
                        <div>
                            <span class="d-block">Beber 2 litros de água</span>
                            <small class="text-secondary">Saúde Física</small>
                        </div>
                    </div>
                    <button class="doitly-btn doitly-btn-sm">Concluir</button>
                </div>

                <!-- Habit Item 3 -->
                <div class="habit-item mb-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <span class="doitly-badge doitly-badge-warning">Noite</span>
                        <div>
                            <span class="d-block">Estudar programação por 30 minutos</span>
                            <small class="text-secondary">Estudo</small>
                        </div>
                    </div>
                    <button class="doitly-btn doitly-btn-sm">Concluir</button>
                </div>

                <!-- Habit Item 4 (completed) -->
                <div class="habit-item mb-3" style="opacity: 0.6">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <span class="doitly-badge doitly-badge-success">Manhã</span>
                        <div>
                            <span class="d-block" style="text-decoration: line-through">Meditar 10 minutos</span>
                            <small class="text-secondary">Saúde Mental</small>
                        </div>
                    </div>
                    <span class="text-secondary">✓ Concluído</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== VISUALIZE SEU PROGRESSO ========== -->
<section class="progress-visualization-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Visualize Seu Progresso</h2>
            <p class="section-subtitle">
                Acompanhe sua evolução com gráficos detalhados e motivadores
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-10" data-aos="fade-up" data-aos-delay="100">
                <div class="doitly-card chart-card">
                    <div class="chart-header mb-4">
                        <h4 class="mb-2">Progresso Semanal</h4>
                        <p class="text-secondary mb-0">
                            <i class="bi bi-calendar-week me-2"></i>
                            Últimos 7 dias - Exemplo de acompanhamento
                        </p>
                    </div>
                    
                    <!-- Container do Gráfico -->
                    <div id="progressChart"></div>
                    
                    <div class="chart-footer mt-4">
                        <div class="row g-3 text-center">
                            <div class="col-6 col-md-3">
                                <div class="chart-stat">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <h5 class="mb-0 mt-2">85%</h5>
                                    <small class="text-secondary">Taxa de Conclusão</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="chart-stat">
                                    <i class="bi bi-fire text-danger"></i>
                                    <h5 class="mb-0 mt-2">12 dias</h5>
                                    <small class="text-secondary">Maior Streak</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="chart-stat">
                                    <i class="bi bi-trophy-fill text-warning"></i>
                                    <h5 class="mb-0 mt-2">45</h5>
                                    <small class="text-secondary">Hábitos Concluídos</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="chart-stat">
                                    <i class="bi bi-graph-up-arrow text-primary"></i>
                                    <h5 class="mb-0 mt-2">+23%</h5>
                                    <small class="text-secondary">Melhoria Semanal</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Script do Gráfico -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const getThemeChartOptions = () => {
        const styles = getComputedStyle(document.documentElement);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        return {
            accentBlue: styles.getPropertyValue('--accent-blue').trim() || '#4a74ff',
            textSecondary: styles.getPropertyValue('--text-secondary').trim() || '#6c757d',
            border: isDark ? 'rgba(255,255,255,0.18)' : 'rgba(0, 0, 0, 0.08)',
            markerStroke: isDark ? '#161b22' : '#fff',
            tooltipTheme: isDark ? 'dark' : 'light'
        };
    };

    const buildOptions = () => {
        const theme = getThemeChartOptions();
        return {
        series: [{
            name: 'Hábitos Concluídos',
            data: [4, 6, 5, 7, 8, 6, 9]
        }],
        chart: {
            type: 'area',
            height: 350,
            fontFamily: 'Inter, sans-serif',
            toolbar: {
                show: false
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150
                }
            }
        },
        colors: [theme.accentBlue],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
            labels: {
                style: {
                    colors: theme.textSecondary,
                    fontSize: '13px'
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            title: {
                text: 'Hábitos',
                style: {
                    color: theme.textSecondary,
                    fontSize: '13px',
                    fontWeight: 500
                }
            },
            labels: {
                style: {
                    colors: theme.textSecondary,
                    fontSize: '13px'
                }
            }
        },
        grid: {
            borderColor: theme.border,
            strokeDashArray: 4,
            xaxis: {
                lines: {
                    show: false
                }
            }
        },
        tooltip: {
            theme: theme.tooltipTheme,
            y: {
                formatter: function(value) {
                    return value + ' hábitos'
                }
            },
            style: {
                fontSize: '13px',
                fontFamily: 'Inter, sans-serif'
            }
        },
        markers: {
            size: 5,
            colors: [theme.accentBlue],
            strokeColors: theme.markerStroke,
            strokeWidth: 2,
            hover: {
                size: 7
            }
        }
    };
    }

    let chart = new ApexCharts(document.querySelector("#progressChart"), buildOptions());
    chart.render();

    window.addEventListener('doitly:theme-change', () => {
        chart.destroy();
        chart = new ApexCharts(document.querySelector("#progressChart"), buildOptions());
        chart.render();
    });
});
</script>

<!-- ========== DEPOIMENTOS ========== -->
<section class="testimonials-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">O Que Nossos Usuários Dizem</h2>
            <p class="section-subtitle">
                Milhares de pessoas já transformaram suas vidas com o Doitly
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="doitly-card testimonial-card h-100">
                    <div class="testimonial-rating mb-3">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <p class="testimonial-text">
                        "O Doitly mudou completamente minha rotina! Consegui manter uma sequência de 45 dias 
                        de exercícios pela primeira vez na vida. A interface é linda e super fácil de usar."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="author-info">
                            <h6 class="author-name">Ana Silva</h6>
                            <p class="author-role">Designer UX</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="doitly-card testimonial-card h-100">
                    <div class="testimonial-rating mb-3">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <p class="testimonial-text">
                        "Finalmente um app de hábitos que não é complicado! Uso todos os dias para 
                        acompanhar meus estudos e já vi uma melhora enorme na minha produtividade."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="author-info">
                            <h6 class="author-name">Carlos Mendes</h6>
                            <p class="author-role">Estudante de Engenharia</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="doitly-card testimonial-card h-100">
                    <div class="testimonial-rating mb-3">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <p class="testimonial-text">
                        "Adoro o design minimalista e as estatísticas detalhadas. Me ajudou a criar 
                        uma rotina matinal consistente e agora me sinto muito mais organizada!"
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="author-info">
                            <h6 class="author-name">Mariana Costa</h6>
                            <p class="author-role">Empreendedora</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== FAQ ========== -->
<section class="faq-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Perguntas Frequentes</h2>
            <p class="section-subtitle">
                Tire suas dúvidas sobre o Doitly
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8" data-aos="fade-up" data-aos-delay="100">
                <div class="accordion" id="faqAccordion">
                    <!-- FAQ 1 -->
                    <div class="accordion-item doitly-accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                <i class="bi bi-question-circle me-2"></i>
                                O Doitly é gratuito?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sim! O Doitly oferece um plano gratuito completo com todas as funcionalidades 
                                essenciais para gerenciar seus hábitos. Você pode criar hábitos ilimitados, 
                                acompanhar seu progresso e visualizar estatísticas básicas sem pagar nada.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ 2 -->
                    <div class="accordion-item doitly-accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                <i class="bi bi-question-circle me-2"></i>
                                Como funciona o acompanhamento de hábitos?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                É muito simples! Você cria seus hábitos personalizados, define horários e categorias, 
                                e todos os dias marca como concluído. O Doitly registra automaticamente seu progresso, 
                                calcula suas sequências (streaks) e gera gráficos para você visualizar sua evolução.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ 3 -->
                    <div class="accordion-item doitly-accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                <i class="bi bi-question-circle me-2"></i>
                                Posso usar em vários dispositivos?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sim! O Doitly é totalmente responsivo e funciona perfeitamente em computadores, 
                                tablets e smartphones. Seus dados são sincronizados automaticamente na nuvem, 
                                então você pode acessar de qualquer lugar.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ 4 -->
                    <div class="accordion-item doitly-accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                <i class="bi bi-question-circle me-2"></i>
                                Meus dados estão seguros?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Absolutamente! Levamos a segurança muito a sério. Todos os seus dados são 
                                criptografados e armazenados com segurança. Não compartilhamos suas informações 
                                com terceiros e você tem controle total sobre seus dados.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ 5 -->
                    <div class="accordion-item doitly-accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                <i class="bi bi-question-circle me-2"></i>
                                Posso cancelar minha conta a qualquer momento?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sim, você pode cancelar sua conta a qualquer momento sem nenhuma penalidade. 
                                Basta acessar as configurações da conta e solicitar o cancelamento. Seus dados 
                                serão mantidos por 30 dias caso você mude de ideia.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ 6 -->
                    <div class="accordion-item doitly-accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                <i class="bi bi-question-circle me-2"></i>
                                Como posso obter suporte?
                            </button>
                        </h2>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Oferecemos suporte por email e através da nossa central de ajuda com artigos 
                                detalhados. Usuários do plano gratuito têm resposta em até 48 horas, e estamos 
                                sempre trabalhando para melhorar nosso atendimento.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== COMO COMEÇAR ========== -->
<section class="quick-start-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Como Começar</h2>
            <p class="section-subtitle">
                Apenas 3 passos simples para transformar sua rotina
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="quick-start-step text-center">
                    <div class="step-circle">1</div>
                    <div class="step-icon mb-3">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h4 class="mb-3">Cadastre-se Grátis</h4>
                    <p class="text-secondary">
                        Crie sua conta em menos de 1 minuto. Sem cartão de crédito, sem complicações.
                    </p>
                </div>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="quick-start-step text-center">
                    <div class="step-circle">2</div>
                    <div class="step-icon mb-3">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <h4 class="mb-3">Crie Seus Hábitos</h4>
                    <p class="text-secondary">
                        Adicione os hábitos que deseja cultivar. Personalize categorias e horários.
                    </p>
                </div>
            </div>

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="quick-start-step text-center">
                    <div class="step-circle">3</div>
                    <div class="step-icon mb-3">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h4 class="mb-3">Acompanhe Seu Progresso</h4>
                    <p class="text-secondary">
                        Marque hábitos concluídos e veja sua evolução através de gráficos motivadores.
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="300">
            <a href="register.php" class="doitly-btn doitly-btn-lg">
                <i class="bi bi-rocket-takeoff me-2"></i>
                Começar Agora - É Grátis!
            </a>
        </div>
    </div>
</section>

<!-- ========== CTA FINAL ========== -->
<section class="cta-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8" data-aos="zoom-in">
                <div class="doitly-card text-center cta-card">
                    <h2 class="mb-3">Pronto Para Transformar Sua Vida?</h2>
                    <p class="text-secondary mb-4">
                        Junte-se a milhares de pessoas que já estão construindo hábitos consistentes 
                        e alcançando seus objetivos com o Doitly. Comece gratuitamente hoje mesmo!
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="register.php" class="doitly-btn doitly-btn-lg">
                            <i class="bi bi-rocket-takeoff"></i> Criar Minha Conta Grátis
                        </a>
                        <a href="login.php" class="doitly-btn doitly-btn-secondary doitly-btn-lg">
                            Já Tenho Conta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Script do Contador Animado -->
<script>
// Contador Animado para Estatísticas
function animateCounter(element) {
    const target = parseFloat(element.getAttribute('data-count'));
    const duration = 2000; // 2 segundos
    const increment = target / (duration / 16); // 60fps
    let current = 0;
    
    const updateCounter = () => {
        current += increment;
        
        if (current < target) {
            // Formatação baseada no valor
            if (target >= 1000 && target < 10000) {
                element.textContent = Math.floor(current) + '+';
            } else if (target >= 10000) {
                element.textContent = Math.floor(current / 1000) + 'k+';
            } else if (target < 10 && target % 1 !== 0) {
                element.textContent = current.toFixed(1) + '★';
            } else {
                element.textContent = Math.floor(current) + '%';
            }
            
            requestAnimationFrame(updateCounter);
        } else {
            // Valor final
            if (target >= 1000 && target < 10000) {
                element.textContent = target + '+';
            } else if (target >= 10000) {
                element.textContent = (target / 1000) + 'k+';
            } else if (target < 10 && target % 1 !== 0) {
                element.textContent = target.toFixed(1) + '★';
            } else {
                element.textContent = target + '%';
            }
        }
    };
    
    updateCounter();
}

// Intersection Observer para iniciar contador quando visível
const observerOptions = {
    threshold: 0.5
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
            entry.target.classList.add('counted');
            animateCounter(entry.target);
        }
    });
}, observerOptions);

// Observar todos os elementos com data-count
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('[data-count]');
    counters.forEach(counter => observer.observe(counter));
});
</script>


<?php 
include_once "includes/footer.php";
?>
