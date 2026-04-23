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
    <title>Configurações | YouTube Finder Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell small-shell">
    <header class="app-header">
        <div>
            <h1>Configurações</h1>
            <p>Defina API key e parâmetros padrão de busca.</p>
        </div>
        <a href="index.php" class="btn btn-secondary">Voltar</a>
    </header>

    <section class="panel">
        <form id="settingsForm" class="settings-form">
            <label>
                API Key YouTube
                <input type="password" id="apiKey" value="<?= htmlspecialchars((string)$settings['api_key'], ENT_QUOTES, 'UTF-8') ?>" required>
            </label>

            <div class="form-grid two-col">
                <label>
                    Páginas padrão
                    <input type="number" id="defaultPages" min="1" max="<?= (int)$settings['max_pages'] ?>" value="<?= (int)$settings['default_pages'] ?>" required>
                </label>
                <label>
                    Máximo de páginas
                    <input type="number" id="maxPages" min="1" max="<?= ABSOLUTE_MAX_PAGES ?>" value="<?= (int)$settings['max_pages'] ?>" required>
                </label>
            </div>

            <div class="form-grid two-col">
                <label>
                    Região (2 letras)
                    <input type="text" id="region" maxlength="2" value="<?= htmlspecialchars((string)$settings['region'], ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    Idioma de relevância
                    <input type="text" id="language" maxlength="10" value="<?= htmlspecialchars((string)$settings['language'], ENT_QUOTES, 'UTF-8') ?>">
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar configurações</button>
            </div>
        </form>
        <div id="settingsFeedback" class="feedback"></div>
    </section>
</div>

<script>
    const settingsForm = document.getElementById('settingsForm');
    const settingsFeedback = document.getElementById('settingsFeedback');

    settingsForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        settingsFeedback.textContent = 'Salvando...';
        settingsFeedback.className = 'feedback info';

        const payload = {
            api_key: document.getElementById('apiKey').value.trim(),
            default_pages: Number(document.getElementById('defaultPages').value),
            max_pages: Number(document.getElementById('maxPages').value),
            region: document.getElementById('region').value.trim(),
            language: document.getElementById('language').value.trim()
        };

        try {
            const response = await fetch('api/save-settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Falha ao salvar configurações');
            }

            settingsFeedback.textContent = data.message;
            settingsFeedback.className = 'feedback success';
        } catch (error) {
            settingsFeedback.textContent = error.message;
            settingsFeedback.className = 'feedback error';
        }
    });
</script>
</body>
</html>
