<?php
/**
 * HousePet — Footer global
 */
$baseUrl = $baseUrl ?? '/housePet2';
?>
</main><!-- /.main-content -->

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="container footer-grid">

        <!-- Coluna 1: Marca e Propósito -->
        <div class="footer-col">
            <div class="footer-brand">🐾 HousePet</div>
            <p class="footer-desc">
                Plataforma integrada de proteção e bem-estar animal. Conectando animais a novos lares,
                apoiando a localização de pets perdidos e organizando denúncias de maus-tratos.
            </p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" aria-label="Twitter/X"><i class="fa-brands fa-x-twitter"></i></a>
            </div>
        </div>

        <!-- Coluna 2: Navegação Rápida -->
        <div class="footer-col">
            <h4 class="footer-title">Navegação</h4>
            <ul class="footer-links">
                <li><a href="<?= $baseUrl ?>/">Página Inicial</a></li>
                <li><a href="<?= $baseUrl ?>/adocao/index.php">Adotar um Animal</a></li>
                <li><a href="<?= $baseUrl ?>/desaparecidos/index.php">Animais Desaparecidos</a></li>
                <li><a href="<?= $baseUrl ?>/denuncias/index.php">Canal de Denúncias</a></li>
                <li><a href="<?= $baseUrl ?>/denuncias/cadastrar.php">Registrar Ocorrência</a></li>
            </ul>
        </div>

        <!-- Coluna 3: Minha Conta -->
        <div class="footer-col">
            <h4 class="footer-title">Acesso Rápido</h4>
            <ul class="footer-links">
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?= $baseUrl ?>/usuario/dashboard.php">Meu Painel</a></li>
                    <li><a href="<?= $baseUrl ?>/adocao/cadastrar.php">Cadastrar para Adoção</a></li>
                    <li><a href="<?= $baseUrl ?>/desaparecidos/cadastrar.php">Divulgar Desaparecido</a></li>
                    <li><a href="<?= $baseUrl ?>/denuncias/minhas_denuncias.php">Minhas Denúncias</a></li>
                    <li><a href="<?= $baseUrl ?>/auth/logout.php">Encerrar Sessão</a></li>
                <?php else: ?>
                    <li><a href="<?= $baseUrl ?>/auth/login.php">Entrar no Sistema</a></li>
                    <li><a href="<?= $baseUrl ?>/auth/cadastro.php">Criar Nova Conta</a></li>
                    <li><a href="<?= $baseUrl ?>/adocao/index.php">Quero Adotar</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Coluna 4: Informações do Projeto Acadêmico -->
        <div class="footer-col">
            <h4 class="footer-title">Projeto Acadêmico</h4>
            <p class="footer-academic-info">
                <strong>IFRO</strong> &mdash; Instituto Federal de Rondônia<br>
                Curso Técnico em Informática Integrado ao Ensino Médio<br>
                Desenvolvimento de Sistemas Web
            </p>
            <p class="footer-disclaimer">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <em>A plataforma organiza informações e incentiva a proteção animal, não substituindo os órgãos e canais oficiais de segurança pública e disque-denúncia.</em>
            </p>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> HousePet &mdash; Desenvolvido com responsabilidade social e dedicação aos animais.</p>
            <p>Atividade acadêmica IFRO &bull; Versão 1.0.0</p>
        </div>
    </div>
</footer>

<!-- JavaScript Principal -->
<script src="<?= $baseUrl ?>/public/assets/js/main.js"></script>
</body>
</html>
