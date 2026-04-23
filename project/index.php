<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Logger.php';
require_once __DIR__ . '/classes/SettingsRepository.php';

$logger = new Logger(LOG_FILE);
$settingsRepo = new SettingsRepository(SETTINGS_FILE, $logger);
$settings = $settingsRepo->load();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Finder Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <header class="app-header">
        <div>
            <h1>YouTube Finder Pro</h1>
            <p>Busque vídeos, selecione em lote e exporte links e títulos rapidamente.</p>
        </div>
        <a href="settings.php" class="btn btn-secondary">Configurações</a>
    </header>

    <section class="panel search-panel">
        <div class="form-grid">
            <label>
                Palavra-chave
                <input type="text" id="query" placeholder="Ex: marketing digital" maxlength="120">
            </label>
            <label>
                Páginas por busca
                <input type="number" id="pages" min="1" max="<?= (int)$settings['max_pages'] ?>" value="<?= (int)$settings['default_pages'] ?>">
            </label>
            <button class="btn btn-primary" id="searchBtn">Pesquisar</button>
        </div>

        <div class="pagination-controls">
            <button class="btn btn-secondary" id="prevPageBtn" disabled>Página anterior</button>
            <button class="btn btn-secondary" id="nextPageBtn" disabled>Próxima página</button>
            <span id="pageInfo">Página atual: 1</span>
        </div>
    </section>

    <section class="panel status-panel">
        <div class="status-line">
            <label class="checkbox-inline">
                <input type="checkbox" id="selectAll"> Selecionar todos
            </label>
            <strong id="selectedCount">Selecionados: 0</strong>
        </div>
        <div id="feedback" class="feedback"></div>
        <div id="loader" class="loader hidden"></div>
    </section>

    <section class="results-grid" id="results"></section>

    <section class="panel export-panel">
        <h2>Exportação</h2>
        <div class="textareas">
            <label>
                LINKS
                <textarea id="linksOutput" readonly placeholder="Links selecionados..."></textarea>
            </label>
            <label>
                TÍTULOS
                <textarea id="titlesOutput" readonly placeholder="Títulos selecionados..."></textarea>
            </label>
        </div>
        <div class="actions">
            <button class="btn btn-secondary" id="copyLinksBtn">Copiar links</button>
            <button class="btn btn-secondary" id="copyTitlesBtn">Copiar títulos</button>
            <button class="btn btn-primary" id="copyBothBtn">Copiar ambos</button>
            <button class="btn btn-danger" id="clearSelectionBtn">Limpar seleção</button>
        </div>
    </section>
</div>

<script>
    window.APP_CONFIG = {
        maxPages: <?= (int)$settings['max_pages'] ?>,
        defaultPages: <?= (int)$settings['default_pages'] ?>
    };
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
