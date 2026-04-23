(() => {
    const state = {
        currentQuery: '',
        tokenHistory: [''],
        pageIndex: 0,
        nextToken: '',
        prevToken: '',
        currentItems: [],
        selectedMap: new Map(),
    };

    const elements = {
        query: document.getElementById('query'),
        pages: document.getElementById('pages'),
        searchBtn: document.getElementById('searchBtn'),
        prevPageBtn: document.getElementById('prevPageBtn'),
        nextPageBtn: document.getElementById('nextPageBtn'),
        pageInfo: document.getElementById('pageInfo'),
        selectAll: document.getElementById('selectAll'),
        selectedCount: document.getElementById('selectedCount'),
        feedback: document.getElementById('feedback'),
        loader: document.getElementById('loader'),
        results: document.getElementById('results'),
        linksOutput: document.getElementById('linksOutput'),
        titlesOutput: document.getElementById('titlesOutput'),
        copyLinksBtn: document.getElementById('copyLinksBtn'),
        copyTitlesBtn: document.getElementById('copyTitlesBtn'),
        copyBothBtn: document.getElementById('copyBothBtn'),
        clearSelectionBtn: document.getElementById('clearSelectionBtn'),
    };

    if (!elements.query) return;

    elements.pages.value = window.APP_CONFIG?.defaultPages || 1;

    function setLoading(isLoading) {
        elements.loader.classList.toggle('hidden', !isLoading);
        elements.searchBtn.disabled = isLoading;
        elements.nextPageBtn.disabled = isLoading || !state.nextToken;
        elements.prevPageBtn.disabled = isLoading || state.pageIndex === 0;
    }

    function showMessage(message, type = 'info') {
        elements.feedback.textContent = message;
        elements.feedback.className = `feedback ${type}`;
    }

    function sanitizePages() {
        const maxPages = Number(window.APP_CONFIG?.maxPages || 5);
        const parsed = Number(elements.pages.value);

        if (!Number.isFinite(parsed) || parsed < 1) {
            elements.pages.value = '1';
            return 1;
        }

        const sanitized = Math.min(maxPages, Math.floor(parsed));
        elements.pages.value = String(sanitized);
        return sanitized;
    }

    function renderResults(items) {
        elements.results.innerHTML = '';

        if (!items.length) {
            showMessage('Nenhum resultado encontrado', 'warning');
            return;
        }

        const fragment = document.createDocumentFragment();

        items.forEach((item) => {
            const card = document.createElement('article');
            card.className = 'video-card';

            const checked = state.selectedMap.has(item.video_id) ? 'checked' : '';

            card.innerHTML = `
                <div class="video-header">
                    <label class="checkbox-inline">
                        <input type="checkbox" class="video-check" data-id="${item.video_id}" ${checked}>
                        Selecionar
                    </label>
                </div>
                <h3 title="${escapeHtml(item.title)}">${escapeHtml(item.title)}</h3>
                <a href="${item.url}" target="_blank" rel="noopener noreferrer">${item.url}</a>
            `;

            fragment.appendChild(card);
        });

        elements.results.appendChild(fragment);
        syncSelectAllState();
    }

    function updateOutputs() {
        const selectedItems = Array.from(state.selectedMap.values());
        elements.linksOutput.value = selectedItems.map((item) => item.url).join('\n');
        elements.titlesOutput.value = selectedItems.map((item) => item.title).join('\n');
        elements.selectedCount.textContent = `Selecionados: ${selectedItems.length}`;
    }

    function syncSelectAllState() {
        if (!state.currentItems.length) {
            elements.selectAll.checked = false;
            return;
        }

        const everySelected = state.currentItems.every((item) => state.selectedMap.has(item.video_id));
        elements.selectAll.checked = everySelected;
    }

    function updatePaginationState() {
        elements.pageInfo.textContent = `Página atual: ${state.pageIndex + 1}`;
        elements.prevPageBtn.disabled = state.pageIndex === 0;
        elements.nextPageBtn.disabled = !state.nextToken;
    }

    function uniqueByVideoId(items) {
        const map = new Map();
        items.forEach((item) => {
            if (!map.has(item.video_id)) {
                map.set(item.video_id, item);
            }
        });
        return Array.from(map.values());
    }

    async function search(pageToken = '', resetHistory = false) {
        const query = elements.query.value.trim();
        const pages = sanitizePages();

        if (!query) {
            showMessage('Digite um termo de busca', 'error');
            return;
        }

        setLoading(true);
        showMessage('Buscando vídeos...', 'info');

        try {
            const response = await fetch('api/search.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query, pages, pageToken })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Erro ao buscar vídeos');
            }

            state.currentQuery = query;
            state.currentItems = uniqueByVideoId(data.items || []);
            state.nextToken = data.next_page_token || '';
            state.prevToken = data.prev_page_token || '';

            if (resetHistory) {
                state.tokenHistory = [''];
                state.pageIndex = 0;
            }

            renderResults(state.currentItems);
            updateOutputs();
            updatePaginationState();

            if (!state.currentItems.length) {
                showMessage(data.message || 'Nenhum resultado encontrado', 'warning');
            } else {
                showMessage(`${state.currentItems.length} vídeos carregados com sucesso.`, 'success');
            }
        } catch (error) {
            const known = ['Digite um termo de busca', 'Nenhum resultado encontrado', 'Erro ao buscar vídeos', 'API não configurada', 'Limite de requisições atingido'];
            const message = known.includes(error.message) ? error.message : 'Erro ao buscar vídeos';
            showMessage(message, 'error');
        } finally {
            setLoading(false);
            updatePaginationState();
        }
    }

    function toggleItemSelection(videoId, checked) {
        const item = state.currentItems.find((entry) => entry.video_id === videoId);
        if (!item) return;

        if (checked) {
            state.selectedMap.set(item.video_id, item);
        } else {
            state.selectedMap.delete(item.video_id);
        }

        updateOutputs();
        syncSelectAllState();
    }

    async function copyText(content, label) {
        if (!content.trim()) {
            showMessage(`Nenhum ${label} para copiar`, 'warning');
            return;
        }

        await navigator.clipboard.writeText(content);
        showMessage(`${label} copiados com sucesso`, 'success');
    }

    function clearSelection() {
        state.selectedMap.clear();
        updateOutputs();
        syncSelectAllState();

        document.querySelectorAll('.video-check').forEach((checkbox) => {
            checkbox.checked = false;
        });

        showMessage('Seleção limpa com sucesso', 'info');
    }

    elements.searchBtn.addEventListener('click', () => {
        search('', true);
    });

    elements.query.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            search('', true);
        }
    });

    elements.results.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || !target.classList.contains('video-check')) return;
        toggleItemSelection(target.dataset.id, target.checked);
    });

    elements.selectAll.addEventListener('change', () => {
        const shouldSelect = elements.selectAll.checked;

        state.currentItems.forEach((item) => {
            if (shouldSelect) {
                state.selectedMap.set(item.video_id, item);
            } else {
                state.selectedMap.delete(item.video_id);
            }
        });

        document.querySelectorAll('.video-check').forEach((checkbox) => {
            checkbox.checked = shouldSelect;
        });

        updateOutputs();
        syncSelectAllState();
    });

    elements.nextPageBtn.addEventListener('click', () => {
        if (!state.nextToken) return;

        const nextIndex = state.pageIndex + 1;
        state.pageIndex = nextIndex;
        state.tokenHistory[nextIndex] = state.nextToken;
        search(state.nextToken, false);
    });

    elements.prevPageBtn.addEventListener('click', () => {
        if (state.pageIndex === 0) return;

        state.pageIndex -= 1;
        const token = state.tokenHistory[state.pageIndex] || '';
        search(token, false);
    });

    elements.copyLinksBtn.addEventListener('click', () => copyText(elements.linksOutput.value, 'Links'));
    elements.copyTitlesBtn.addEventListener('click', () => copyText(elements.titlesOutput.value, 'Títulos'));
    elements.copyBothBtn.addEventListener('click', () => copyText(`${elements.linksOutput.value}\n\n${elements.titlesOutput.value}`.trim(), 'Links e títulos'));
    elements.clearSelectionBtn.addEventListener('click', clearSelection);

    function escapeHtml(text) {
        return text
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
})();
