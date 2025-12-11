<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doitly — Home (Wireframe)</title>
<style>
 :root{
  --bg:#ffffff; --surface:#f6f7fb; --text:#0f1724; --muted:#6b7280; --primary:#4f46e5;
  --container:1200px; --gap:24px;
 }
 *{box-sizing:border-box}
 body{font-family:system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial; margin:0; background:var(--bg); color:var(--text); -webkit-font-smoothing:antialiased;}
 .wrap{max-width:var(--container); margin:0 auto; padding:24px;}
 header{display:flex; align-items:center; justify-content:space-between; padding:12px 0;}
 .logo{font-weight:700; font-size:18px}
 nav{display:flex; gap:12px; align-items:center}
 .btn{padding:10px 16px; border-radius:10px; border:0; cursor:pointer}
 .btn-primary{background:var(--primary); color:#fff}
 .hero{display:grid; grid-template-columns:1fr 420px; gap:32px; align-items:center; padding:32px 0;}
 .hero h1{font-size:32px; margin:0 0 8px}
 .hero p{color:var(--muted); margin:0 0 16px}
 .card{background:var(--surface); border-radius:12px; padding:16px; box-shadow:0 1px 0 rgba(0,0,0,0.03)}
 .features{display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin:24px 0}
 .form-area{display:grid; grid-template-columns:1fr 1fr; gap:16px; margin:24px 0}
 .form-area .card{grid-column:1 / -1}
 .inputs{display:flex; flex-direction:column; gap:8px}
 .inputs input, .inputs textarea, .inputs select{padding:10px; border-radius:8px; border:1px solid #e6e9ef; background:#fff}
 .list-grid{display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin:24px 0}
 .habit-card{display:flex; flex-direction:column; gap:8px}
 .habit-line{display:flex; justify-content:space-between; align-items:center; padding:8px; background:#fff; border-radius:8px; border:1px solid #eaeef6}
 footer{padding:24px 0; color:var(--muted)}
 @media (max-width:900px){
  .hero{grid-template-columns:1fr; }
  .features{grid-template-columns:1fr}
  .form-area{grid-template-columns:1fr}
  .list-grid{grid-template-columns:1fr}
 }
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div class="logo">Doitly</div>
    <nav>
      <a href="#">Recursos</a>
      <a href="#">Como funciona</a>
      <button class="btn">Entrar</button>
      <button class="btn btn-primary">Começar</button>
    </nav>
  </header>

  <section class="hero">
    <div>
      <h1>Faça hábitos vira rotina, sem drama.</h1>
      <p>Crie rotinas simples, acompanhe progresso e personalize sua rotina diária.</p>
      <div style="display:flex; gap:12px;">
        <button class="btn btn-primary">Comece grátis</button>
        <button class="btn">Ver demo</button>
      </div>
    </div>
    <div class="card" aria-hidden="true" style="height:260px; display:flex; align-items:center; justify-content:center;">
      Mockup do app
    </div>
  </section>

  <section class="features">
    <div class="card">Criar hábitos e marcar como concluídos</div>
    <div class="card">Acompanhar progresso com gráficos</div>
    <div class="card">Personalize e siga sua rotina diária</div>
  </section>

  <section class="form-area">
    <div class="card">
      <h3>Crie suas rotinas e hábitos</h3>
      <div class="inputs">
        <label>Nome do hábito<input placeholder="Ex: ler 20 min"></label>
        <label>Descrição<textarea rows="3" placeholder="Digite a descrição..."></textarea></label>
        <div style="display:flex; gap:8px;">
          <select><option>Categoria</option></select>
          <input type="time" />
        </div>
        <div style="margin-top:8px">
          <button class="btn btn-primary">Salvar</button>
        </div>
      </div>
    </div>

    <div class="card">
      <h3>Progresso</h3>
      <p style="color:var(--muted)">Gráfico de exemplo</p>
      <div style="height:120px; background:#fff; border-radius:8px; border:1px dashed #e0e6f2"></div>
    </div>
  </section>

  <section class="list-grid">
    <div class="card habit-card">
      <h4>Título da rotina</h4>
      <div class="habit-line"><span>Ler um livro por 15 min</span><button class="btn">Concluir</button></div>
      <div class="habit-line"><span>Estudar por 10 min</span><button class="btn">Concluir</button></div>
      <div class="habit-line"><span>Banho de sol 30 min</span><button class="btn">Concluir</button></div>
    </div>

    <div class="card habit-card">
      <h4>Outra rotina</h4>
      <div class="habit-line"><span>Descreva atividades</span><button class="btn">Concluir</button></div>
      <div class="habit-line"><span>Descreva atividades</span><button class="btn">Concluir</button></div>
    </div>
  </section>

  <footer>
    Doitly • Footer simples — links · termos · contato
  </footer>
</div>
</body>
</html>
