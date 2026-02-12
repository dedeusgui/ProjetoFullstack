    </main>
    
    <!-- FOOTER -->
    <footer class="doitly-footer">
      <div class="container">
        <div class="footer-content">
          <!-- Footer Top Section -->
          <div class="footer-top" style="justify-content: center; border-bottom: none">
            <div class="footer-brand" style="text-align: center; max-width: 600px">
              <h3>Doitly</h3>
              <p>
                Transforme seus objetivos em hábitos consistentes. 
                O gerenciador de hábitos moderno e minimalista que te ajuda a alcançar suas metas.
              </p>
            </div>
          </div>

          <!-- Footer Bottom Section -->
          <div class="footer-bottom">
            <p class="footer-copyright">
              &copy; 2025 Doitly. Todos os direitos reservados.
            </p>

            <div class="footer-social">
              <a
                href="https://github.com/dedeusgui/ProjetoFullstack"
                class="social-link"
                aria-label="GitHub"
                title="GitHub"
                target="_blank"
                rel="noopener noreferrer"
              >
                <svg
                  width="16"
                  height="16"
                  fill="currentColor"
                  viewBox="0 0 16 16"
                >
                  <path
                    d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0 0 16 8c0-4.42-3.58-8-8-8z"
                  />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <!-- Settings Modal JS -->
    <script src="assets/js/settings-modal.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
      // Inicializar AOS com configurações suaves
      AOS.init({
        duration: 600,           // Duração das animações (ms)
        easing: 'ease-in-out-cubic', // Easing suave
        once: true,              // Animar apenas uma vez
        offset: 100,             // Offset do trigger (px)
        delay: 0,                // Delay inicial
        disable: false           // Não desabilitar em mobile
      });
    </script>
</body>
</html>
