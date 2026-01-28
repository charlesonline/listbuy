/**
 * App Lista de Compras - JavaScript Principal
 * SPA-like com Vanilla JS e Fetch API
 */

// ========================================
// CONFIGURAÇÃO E ESTADO
// ========================================

const API_BASE = window.location.origin + '/api/endpoints';

const State = {
    token: localStorage.getItem('auth_token') || null,
    usuario: null,
    listas: [],
    listaAtual: null,
    itensAtual: [],
    itensSelecionados: new Set(),
    itensMarcados: {}, // { item_id: { marcado: bool, marcado_por_nome, marcado_em } }
    pollingInterval: null,
    editandoItem: null,
    historico: [],
    historicoFiltrado: [],
    categorias: [],
    editandoCategoria: null,
    usuarios: [],
    editandoUsuario: null,
    filtroMarcacao: 'todos', // 'todos', 'marcados', 'nao-marcados'
    filtroCategoria: '' // ID da categoria ou vazio para todas
};

// ========================================
// UTILITÁRIOS
// ========================================

const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => document.querySelectorAll(selector);

function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

function formatarData(dataString) {
    const data = new Date(dataString);
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(data);
}

function showToast(message, type = 'success') {
    const toast = $('#toast');
    toast.textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function showLoading(show = true) {
    $('#loadingOverlay').style.display = show ? 'flex' : 'none';
}

function showModal(modalId) {
    $(`#${modalId}`).classList.add('active');
}

function hideModal(modalId) {
    $(`#${modalId}`).classList.remove('active');
}

function changeScreen(screenId) {
    $$('.screen').forEach(screen => screen.classList.remove('active'));
    $(`#${screenId}`).classList.add('active');
}

// ========================================
// API CALLS
// ========================================

async function apiCall(endpoint, method = 'GET', data = null) {
    try {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        // Adicionar token se existir
        if (State.token) {
            options.headers['Authorization'] = `Bearer ${State.token}`;
        }
        
        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(`${API_BASE}/${endpoint}`, options);
        const result = await response.json();
        
        // Se erro de autenticação, fazer logout
        if (result.requer_login) {
            logout();
            return null;
        }
        
        if (!result.success && !response.ok) {
            throw new Error(result.message || result.erro || 'Erro na requisição');
        }
        
        return result;
    } catch (error) {
        console.error('Erro na API:', error);
        showToast(error.message || 'Erro ao conectar com o servidor', 'error');
        throw error;
    }
}

// ========================================
// LISTAS - CRUD
// ========================================

async function carregarListas() {
    showLoading();
    try {
        const result = await apiCall('listas.php');
        State.listas = result.data;
        renderizarListas();
    } catch (error) {
        console.error('Erro ao carregar listas:', error);
    } finally {
        showLoading(false);
    }
}

function renderizarListas() {
    const container = $('#listasContainer');
    const emptyState = $('#emptyState');
    
    if (!State.listas || State.listas.length === 0) {
        container.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    container.innerHTML = State.listas.map(lista => `
        <div class="lista-card" data-id="${lista.id}">
            <div class="lista-card-header">
                <div class="lista-icon">📋</div>
                ${lista.eh_proprietario === 0 ? `
                    <span class="lista-badge lista-badge-compartilhada" title="Compartilhada por ${lista.proprietario_nome}">
                        👥 Compartilhada
                    </span>
                ` : `
                    <span class="lista-badge lista-badge-propria">
                        Minha lista
                    </span>
                `}
            </div>
            <h3>${lista.nome}</h3>
            <p>${lista.descricao || 'Sem descrição'}</p>
            <div class="lista-stats">
                <div class="lista-stat">
                    <span class="lista-stat-icon">📦</span>
                    <span>Ver itens</span>
                </div>
                ${lista.eh_proprietario === 1 ? `
                    <button class="btn-compartilhar-lista" onclick="event.stopPropagation(); abrirModalCompartilhar(${lista.id});" title="Compartilhar lista">
                        <span class="lista-stat-icon">👥</span>
                        Compartilhar
                    </button>
                    <button class="btn-deletar-lista" onclick="event.stopPropagation(); deletarLista(${lista.id}, '${lista.nome}');" title="Deletar lista">
                        <span class="lista-stat-icon">🗑️</span>
                        Deletar
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');
    
    // Event listeners
    $$('.lista-card').forEach(card => {
        card.addEventListener('click', () => {
            abrirLista(parseInt(card.dataset.id));
        });
    });
}

async function criarLista(dados) {
    showLoading();
    try {
        await apiCall('listas.php', 'POST', dados);
        showToast('Lista criada com sucesso!');
        await carregarListas();
        hideModal('modalNovaLista');
        limparFormularioLista();
    } catch (error) {
        console.error('Erro ao criar lista:', error);
    } finally {
        showLoading(false);
    }
}

function limparFormularioLista() {
    $('#inputNomeLista').value = '';
    $('#inputDescricaoLista').value = '';
}

// ========================================
// CATEGORIAS - CRUD
// ========================================

async function abrirCategorias() {
    showLoading();
    try {
        await carregarCategorias();
        changeScreen('telaCategorias');
    } catch (error) {
        console.error('Erro ao abrir categorias:', error);
    } finally {
        showLoading(false);
    }
}

async function carregarCategorias() {
    try {
        const result = await apiCall('categorias.php');
        State.categorias = result.data;
        renderizarCategorias();
    } catch (error) {
        console.error('Erro ao carregar categorias:', error);
    }
}

function renderizarCategorias() {
    const container = $('#categoriasContainer');
    const emptyState = $('#emptyCategorias');
    
    if (!State.categorias || State.categorias.length === 0) {
        container.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    container.innerHTML = State.categorias.map(cat => `
        <div class="categoria-card" style="--categoria-cor: ${cat.cor}">
            <div class="categoria-icone">${cat.icone || '📦'}</div>
            <div class="categoria-nome">${cat.nome}</div>
            <div class="categoria-cor-badge">
                <div class="categoria-cor-sample" style="background: ${cat.cor}"></div>
                <span>${cat.cor}</span>
            </div>
            <div class="categoria-actions">
                <button class="btn-icon-small btn-edit" data-id="${cat.id}" onclick="editarCategoria(${cat.id}); event.stopPropagation();">✏️</button>
                <button class="btn-icon-small btn-delete" data-id="${cat.id}" onclick="deletarCategoria(${cat.id}); event.stopPropagation();">🗑️</button>
            </div>
        </div>
    `).join('');
}

async function criarCategoria(dados) {
    showLoading();
    try {
        await apiCall('categorias.php', 'POST', dados);
        showToast('Categoria criada com sucesso!');
        await carregarCategorias();
        hideModal('modalNovaCategoria');
        limparFormularioCategoria();
    } catch (error) {
        console.error('Erro ao criar categoria:', error);
    } finally {
        showLoading(false);
    }
}

async function atualizarCategoria(id, dados) {
    showLoading();
    try {
        await apiCall(`categorias.php?id=${id}`, 'PUT', dados);
        showToast('Categoria atualizada!');
        await carregarCategorias();
        hideModal('modalNovaCategoria');
        limparFormularioCategoria();
    } catch (error) {
        console.error('Erro ao atualizar categoria:', error);
    } finally {
        showLoading(false);
    }
}

async function deletarCategoria(id) {
    if (!confirm('Deseja realmente excluir esta categoria? Os itens com esta categoria não serão excluídos.')) return;
    
    showLoading();
    try {
        await apiCall(`categorias.php?id=${id}`, 'DELETE');
        showToast('Categoria excluída!');
        await carregarCategorias();
    } catch (error) {
        console.error('Erro ao deletar categoria:', error);
    } finally {
        showLoading(false);
    }
}

function editarCategoria(id) {
    const cat = State.categorias.find(c => c.id === id);
    if (!cat) return;
    
    State.editandoCategoria = id;
    
    $('#tituloModalCategoria').textContent = 'Editar Categoria';
    $('#editCategoriaId').value = id;
    $('#inputNomeCategoria').value = cat.nome;
    $('#inputCorCategoria').value = cat.cor;
    $('#inputCorCategoriaText').value = cat.cor;
    $('#inputIconeCategoria').value = cat.icone || '';
    
    showModal('modalNovaCategoria');
}

function limparFormularioCategoria() {
    State.editandoCategoria = null;
    $('#tituloModalCategoria').textContent = 'Nova Categoria';
    $('#editCategoriaId').value = '';
    $('#inputNomeCategoria').value = '';
    $('#inputCorCategoria').value = '#8B5CF6';
    $('#inputCorCategoriaText').value = '#8B5CF6';
    $('#inputIconeCategoria').value = '';
}

async function carregarCategoriasNoSelect() {
    const select = $('#inputCategoriaItem');
    
    // Se categorias ainda não foram carregadas
    if (State.categorias.length === 0) {
        await carregarCategorias();
    }
    
    select.innerHTML = '<option value="">Selecione...</option>';
    
    State.categorias.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = `${cat.icone || '📦'} ${cat.nome}`;
        select.appendChild(option);
    });
}

async function carregarCategoriasNoFiltro() {
    const select = $('#filtroCategoria');
    
    // Se categorias ainda não foram carregadas
    if (State.categorias.length === 0) {
        await carregarCategorias();
    }
    
    select.innerHTML = '<option value="">Todas as categorias</option>';
    
    State.categorias.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = `${cat.icone || '📦'} ${cat.nome}`;
        select.appendChild(option);
    });
}

// ========================================
// ITENS - CRUD
// ========================================

async function abrirLista(listaId) {
    showLoading();
    try {
        const result = await apiCall(`listas.php?id=${listaId}&itens=1`);
        State.listaAtual = result.data;
        State.itensAtual = result.data.itens || [];
        State.itensSelecionados.clear();
        
        // Resetar filtros ao abrir lista
        State.filtroMarcacao = 'todos';
        State.filtroCategoria = '';
        $('#filtroMarcacao').value = 'todos';
        $('#filtroCategoria').value = '';
        
        $('#tituloLista').textContent = result.data.nome;
        $('#descricaoLista').textContent = result.data.descricao || '';
        
        // Carregar marcações persistidas
        await carregarMarcacoes(listaId);
        
        // Carregar categorias no filtro
        await carregarCategoriasNoFiltro();
        
        // Iniciar polling para sincronização
        iniciarPolling(listaId);
        
        renderizarItens();
        changeScreen('telaItens');
    } catch (error) {
        console.error('Erro ao abrir lista:', error);
    } finally {
        showLoading(false);
    }
}

function renderizarItens() {
    const container = $('#itensContainer');
    const emptyState = $('#emptyItens');
    
    if (!State.itensAtual || State.itensAtual.length === 0) {
        container.innerHTML = '';
        emptyState.style.display = 'block';
        $('#finalizarCompraContainer').style.display = 'none';
        return;
    }
    
    // Aplicar filtros
    const itensFiltrados = State.itensAtual.filter(item => {
        // Filtro por marcação
        const marcado = State.itensMarcados[item.id]?.marcado || false;
        if (State.filtroMarcacao === 'marcados' && !marcado) return false;
        if (State.filtroMarcacao === 'nao-marcados' && marcado) return false;
        
        // Filtro por categoria
        if (State.filtroCategoria && item.categoria_id != State.filtroCategoria) return false;
        
        return true;
    });
    
    // Se não há itens filtrados, mostrar mensagem
    if (itensFiltrados.length === 0) {
        container.innerHTML = '<div class="empty-state" style="display: block;"><div class="empty-icon">🔍</div><p>Nenhum item encontrado com os filtros selecionados</p></div>';
        emptyState.style.display = 'none';
        $('#finalizarCompraContainer').style.display = 'none';
        return;
    }
    
    emptyState.style.display = 'none';
    
    container.innerHTML = itensFiltrados.map(item => {
        const categoriaNome = item.categoria_nome || 'Sem categoria';
        const categoriaCor = item.categoria_cor || '#9CA3AF';
        const categoriaIcone = item.categoria_icone || '📦';
        
        // Verificar se item está marcado
        const marcado = State.itensMarcados[item.id]?.marcado || false;
        const marcadoPor = State.itensMarcados[item.id]?.marcado_por_nome || '';
        
        return `
        <div class="item-card ${marcado ? 'marcado' : ''}" data-id="${item.id}">
            <div class="item-checkbox ${marcado ? 'checked' : ''}" data-id="${item.id}"></div>
            <div class="item-content">
                <div class="item-header">
                    <div class="item-nome ${marcado ? 'riscado' : ''}">${item.nome}</div>
                    <div class="item-preco">${formatarMoeda(item.preco)}</div>
                </div>
                <div class="item-details">
                    ${item.categoria_id ? `<span class="item-categoria" style="background: ${categoriaCor}22; color: ${categoriaCor}">${categoriaIcone} ${categoriaNome}</span>` : ''}
                    <span class="item-quantidade">Qtd: ${item.quantidade}</span>
                    ${marcado && marcadoPor ? `<span class="item-marcado-por">✓ ${marcadoPor}</span>` : ''}
                </div>
            </div>
            <div class="item-actions">
                <button class="btn-icon-small btn-edit" data-id="${item.id}">✏️</button>
                <button class="btn-icon-small btn-delete" data-id="${item.id}">🗑️</button>
            </div>
        </div>
    `;
    }).join('');
    
    // Event listeners
    $$('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleItemMarcado(parseInt(checkbox.dataset.id));
        });
    });
    
    $$('.btn-edit').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            await editarItem(parseInt(btn.dataset.id));
        });
    });
    
    $$('.btn-delete').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            deletarItem(parseInt(btn.dataset.id));
        });
    });
    
    atualizarResumoCompra();
}

async function toggleItemMarcado(itemId) {
    try {
        const marcado = State.itensMarcados[itemId]?.marcado || false;
        const novoEstado = !marcado;
        
        // Atualizar no servidor
        const result = await apiCall(
            `marcacoes.php/${State.listaAtual.id}/toggle`, 
            'POST', 
            { item_id: itemId, marcado: novoEstado }
        );
        
        if (result.success) {
            // Atualizar estado local imediatamente
            if (novoEstado) {
                State.itensMarcados[itemId] = {
                    marcado: true,
                    marcado_por_nome: State.usuario.nome,
                    marcado_por_username: State.usuario.username,
                    marcado_em: new Date().toISOString()
                };
            } else {
                State.itensMarcados[itemId] = {
                    marcado: false,
                    marcado_por_nome: null,
                    marcado_por_username: null,
                    marcado_em: null
                };
            }
            
            renderizarItens();
        }
    } catch (error) {
        console.error('Erro ao marcar item:', error);
        showToast('Erro ao marcar item', 'error');
    }
}

async function carregarMarcacoes(listaId) {
    try {
        const result = await apiCall(`marcacoes.php/${listaId}`, 'GET');
        if (result.success) {
            State.itensMarcados = result.marcacoes || {};
        }
    } catch (error) {
        console.error('Erro ao carregar marcações:', error);
        State.itensMarcados = {};
    }
}

function iniciarPolling(listaId) {
    // Parar polling anterior se existir
    if (State.pollingInterval) {
        clearInterval(State.pollingInterval);
    }
    
    // Atualizar marcações a cada 3 segundos
    State.pollingInterval = setInterval(async () => {
        try {
            const result = await apiCall(`marcacoes.php/${listaId}`, 'GET');
            if (result.success) {
                // Verificar se houve mudanças
                const marcacoesAnteriores = JSON.stringify(State.itensMarcados);
                const novasMarcacoes = JSON.stringify(result.marcacoes);
                
                if (marcacoesAnteriores !== novasMarcacoes) {
                    State.itensMarcados = result.marcacoes || {};
                    renderizarItens();
                }
            }
        } catch (error) {
            console.error('Erro no polling de marcações:', error);
        }
    }, 3000);
}

function pararPolling() {
    if (State.pollingInterval) {
        clearInterval(State.pollingInterval);
        State.pollingInterval = null;
    }
}

async function finalizarCompra() {
    if (!confirm('Finalizar compra e zerar marcações?')) return;
    
    showLoading();
    try {
        const result = await apiCall(
            `marcacoes.php/${State.listaAtual.id}/finalizar`, 
            'POST'
        );
        
        if (result.success) {
            showToast(`Compra finalizada! Total: ${formatarMoeda(result.total)}`);
            
            // Limpar marcações locais
            State.itensMarcados = {};
            renderizarItens();
        } else {
            showToast(result.message || 'Erro ao finalizar compra', 'error');
        }
    } catch (error) {
        console.error('Erro ao finalizar compra:', error);
        showToast('Erro ao finalizar compra', 'error');
    } finally {
        showLoading(false);
    }
}

function atualizarResumoCompra() {
    const container = $('#finalizarCompraContainer');
    
    // Contar itens marcados
    const itensMarcados = Object.values(State.itensMarcados).filter(m => m.marcado).length;
    
    if (itensMarcados === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    
    let total = 0;
    Object.keys(State.itensMarcados).forEach(itemId => {
        const marcacao = State.itensMarcados[itemId];
        if (marcacao.marcado) {
            const item = State.itensAtual.find(i => i.id == itemId);
            if (item) {
                total += parseFloat(item.preco) * parseFloat(item.quantidade);
            }
        }
    });
    
    $('#totalSelecionado').textContent = formatarMoeda(total);
    $('#qtdeSelecionada').textContent = itensMarcados;
}

async function criarItem(dados) {
    showLoading();
    try {
        dados.lista_id = State.listaAtual.id;
        await apiCall('itens.php', 'POST', dados);
        showToast('Item adicionado!');
        await abrirLista(State.listaAtual.id);
        hideModal('modalNovoItem');
        limparFormularioItem();
    } catch (error) {
        console.error('Erro ao criar item:', error);
    } finally {
        showLoading(false);
    }
}

async function atualizarItem(itemId, dados) {
    showLoading();
    try {
        await apiCall(`itens.php?id=${itemId}`, 'PUT', dados);
        showToast('Item atualizado!');
        await abrirLista(State.listaAtual.id);
        hideModal('modalNovoItem');
        limparFormularioItem();
    } catch (error) {
        console.error('Erro ao atualizar item:', error);
    } finally {
        showLoading(false);
    }
}

async function deletarItem(itemId) {
    if (!confirm('Deseja realmente excluir este item?')) return;
    
    showLoading();
    try {
        await apiCall(`itens.php?id=${itemId}`, 'DELETE');
        showToast('Item excluído!');
        await abrirLista(State.listaAtual.id);
    } catch (error) {
        console.error('Erro ao deletar item:', error);
    } finally {
        showLoading(false);
    }
}

async function editarItem(itemId) {
    const item = State.itensAtual.find(i => i.id === itemId);
    if (!item) return;
    
    State.editandoItem = itemId;
    
    $('#tituloModalItem').textContent = 'Editar Item';
    $('#editItemId').value = itemId;
    $('#inputNomeItem').value = item.nome;
    
    // Carregar categorias no select antes de definir o valor
    await carregarCategoriasNoSelect();
    $('#inputCategoriaItem').value = item.categoria_id || '';
    
    $('#inputPrecoItem').value = item.preco;
    $('#inputQuantidadeItem').value = item.quantidade;
    
    showModal('modalNovoItem');
}

function limparFormularioItem() {
    State.editandoItem = null;
    $('#tituloModalItem').textContent = 'Novo Item';
    $('#editItemId').value = '';
    $('#inputNomeItem').value = '';
    $('#inputCategoriaItem').value = '';
    $('#inputPrecoItem').value = '0.00';
    $('#inputQuantidadeItem').value = '1';
}

// ========================================
// FINALIZAR COMPRA
// ========================================

// ========================================
// HISTÓRICO
// ========================================

async function abrirHistorico() {
    showLoading();
    try {
        await Promise.all([
            carregarHistorico(),
            carregarListasParaFiltro()
        ]);
        changeScreen('telaHistorico');
    } catch (error) {
        console.error('Erro ao abrir histórico:', error);
    } finally {
        showLoading(false);
    }
}

async function carregarHistorico() {
    try {
        const result = await apiCall('compras.php?limit=50');
        State.historico = result.data;
        aplicarFiltrosHistorico();
    } catch (error) {
        console.error('Erro ao carregar histórico:', error);
    }
}

async function carregarListasParaFiltro() {
    const select = $('#filtroListaHistorico');
    select.innerHTML = '<option value="">Todas as listas</option>';
    
    State.listas.forEach(lista => {
        select.innerHTML += `<option value="${lista.id}">${lista.nome}</option>`;
    });
}

function aplicarFiltrosHistorico() {
    const listaId = $('#filtroListaHistorico').value;
    const periodo = $('#filtroPeriodo').value;
    
    let filtrado = [...State.historico];
    
    // Filtrar por lista
    if (listaId) {
        filtrado = filtrado.filter(c => c.lista_id == listaId);
    }
    
    // Filtrar por período
    if (periodo !== 'all') {
        const dias = parseInt(periodo);
        const dataLimite = new Date();
        dataLimite.setDate(dataLimite.getDate() - dias);
        
        filtrado = filtrado.filter(c => {
            const dataCompra = new Date(c.realizada_em);
            return dataCompra >= dataLimite;
        });
    }
    
    State.historicoFiltrado = filtrado;
    renderizarHistorico();
    renderizarEstatisticas();
}

function renderizarHistorico() {
    const container = $('#historicoContainer');
    const emptyState = $('#emptyHistorico');
    
    if (!State.historicoFiltrado || State.historicoFiltrado.length === 0) {
        container.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    container.innerHTML = State.historicoFiltrado.map(compra => {
        const nomeLista = State.listas.find(l => l.id == compra.lista_id)?.nome || 'Lista';
        const dataFormatada = formatarData(compra.realizada_em);
        const ticketMedio = compra.total_itens > 0 ? compra.total / compra.total_itens : 0;
        
        return `
            <div class="historico-card" data-id="${compra.id}">
                <div class="historico-header">
                    <div class="historico-info">
                        <h4>
                            🛒 ${nomeLista}
                            <span class="historico-badge">✓ Finalizada</span>
                        </h4>
                        <div class="historico-data">
                            <span>📅</span>
                            <span>${dataFormatada}</span>
                        </div>
                    </div>
                    <div class="historico-total-container">
                        <div class="historico-total-label">Total</div>
                        <div class="historico-total">${formatarMoeda(compra.total)}</div>
                    </div>
                </div>
                
                <div class="historico-stats">
                    <div class="historico-stat">
                        <span class="historico-stat-label">Itens</span>
                        <span class="historico-stat-value">${compra.total_itens}</span>
                    </div>
                    <div class="historico-stat">
                        <span class="historico-stat-label">Ticket Médio</span>
                        <span class="historico-stat-value">${formatarMoeda(ticketMedio)}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Event listeners
    $$('.historico-card').forEach(card => {
        card.addEventListener('click', () => {
            visualizarDetalhesCompra(parseInt(card.dataset.id));
        });
    });
}

function renderizarEstatisticas() {
    const container = $('#estatisticas');
    
    if (State.historicoFiltrado.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    const totalGasto = State.historicoFiltrado.reduce((sum, c) => sum + parseFloat(c.total), 0);
    const totalCompras = State.historicoFiltrado.length;
    const totalItens = State.historicoFiltrado.reduce((sum, c) => sum + parseInt(c.total_itens), 0);
    const ticketMedio = totalCompras > 0 ? totalGasto / totalCompras : 0;
    
    container.innerHTML = `
        <div class="stat-card">
            <div class="stat-label">
                <span>💰</span>
                <span>Total Gasto</span>
            </div>
            <div class="stat-value">${formatarMoeda(totalGasto)}</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-label">
                <span>🛒</span>
                <span>Total de Compras</span>
            </div>
            <div class="stat-value">${totalCompras}</div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-label">
                <span>📊</span>
                <span>Ticket Médio</span>
            </div>
            <div class="stat-value">${formatarMoeda(ticketMedio)}</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-label">
                <span>📦</span>
                <span>Itens Comprados</span>
            </div>
            <div class="stat-value">${totalItens}</div>
        </div>
    `;
}

async function visualizarDetalhesCompra(compraId) {
    showLoading();
    try {
        const result = await apiCall(`compras.php?id=${compraId}`);
        const compra = result.data;
        
        if (!compra) {
            showToast('Compra não encontrada', 'error');
            return;
        }
        
        // Buscar histórico de preços para comparação
        const evolucaoPrecos = await calcularEvolucaoPrecos(compra);
        
        renderizarDetalhesCompra(compra, evolucaoPrecos);
        showModal('modalDetalhesCompra');
        
    } catch (error) {
        console.error('Erro ao visualizar detalhes:', error);
    } finally {
        showLoading(false);
    }
}

async function calcularEvolucaoPrecos(compraAtual) {
    try {
        // Buscar compras anteriores da mesma lista
        const result = await apiCall(`compras.php?lista_id=${compraAtual.lista_id}&limit=10`);
        const comprasAnteriores = result.data.filter(c => 
            c.id != compraAtual.id && 
            new Date(c.realizada_em) < new Date(compraAtual.realizada_em)
        );
        
        if (comprasAnteriores.length === 0) return {};
        
        // Pegar a compra anterior mais recente
        const compraAnterior = comprasAnteriores[0];
        const detalhesAnterior = await apiCall(`compras.php?id=${compraAnterior.id}`);
        
        const evolucao = {};
        
        // Comparar preços de itens com mesmo nome
        compraAtual.itens.forEach(itemAtual => {
            const itemAnterior = detalhesAnterior.data.itens?.find(i => i.nome === itemAtual.nome);
            
            if (itemAnterior) {
                const precoAtual = parseFloat(itemAtual.preco);
                const precoAnterior = parseFloat(itemAnterior.preco);
                const diferenca = precoAtual - precoAnterior;
                const percentual = ((diferenca / precoAnterior) * 100).toFixed(1);
                
                evolucao[itemAtual.nome] = {
                    precoAnterior,
                    diferenca,
                    percentual,
                    tendencia: diferenca > 0.01 ? 'up' : (diferenca < -0.01 ? 'down' : 'stable')
                };
            }
        });
        
        return evolucao;
        
    } catch (error) {
        console.error('Erro ao calcular evolução de preços:', error);
        return {};
    }
}

function renderizarDetalhesCompra(compra, evolucaoPrecos) {
    const container = $('#detalhesCompraContent');
    const nomeLista = State.listas.find(l => l.id == compra.lista_id)?.nome || 'Lista';
    const dataFormatada = formatarData(compra.realizada_em);
    
    const hasEvolucao = Object.keys(evolucaoPrecos).length > 0;
    
    const itensHTML = compra.itens.map(item => {
        const subtotal = parseFloat(item.preco) * parseFloat(item.quantidade);
        const evolucao = evolucaoPrecos[item.nome];
        
        let evolucaoHTML = '';
        if (evolucao) {
            const arrow = evolucao.tendencia === 'up' ? '↑' : 
                         (evolucao.tendencia === 'down' ? '↓' : '↔');
            const cssClass = evolucao.tendencia === 'up' ? 'up' : 
                            (evolucao.tendencia === 'down' ? 'down' : 'stable');
            
            evolucaoHTML = `
                <div class="price-evolution">
                    <span class="price-arrow ${cssClass}">${arrow}</span>
                    <span class="price-diff ${cssClass}">
                        ${evolucao.diferenca > 0 ? '+' : ''}${formatarMoeda(evolucao.diferenca)}
                        (${evolucao.percentual}%)
                    </span>
                </div>
            `;
        }
        
        return `
            <tr>
                <td>${item.nome}</td>
                <td>
                    ${item.categoria ? `<span class="item-categoria" data-categoria="${item.categoria}">${item.categoria}</span>` : '-'}
                </td>
                <td style="text-align: center;">${item.quantidade}</td>
                <td style="text-align: right;">
                    <div>${formatarMoeda(item.preco)}</div>
                    ${evolucaoHTML}
                </td>
                <td style="text-align: right;"><strong>${formatarMoeda(subtotal)}</strong></td>
            </tr>
        `;
    }).join('');
    
    container.innerHTML = `
        <div class="compra-summary">
            <div class="compra-summary-grid">
                <div class="compra-summary-item">
                    <div class="compra-summary-label">📝 Lista</div>
                    <div class="compra-summary-value">${nomeLista}</div>
                </div>
                <div class="compra-summary-item">
                    <div class="compra-summary-label">📅 Data</div>
                    <div class="compra-summary-value" style="font-size: 1rem;">${dataFormatada}</div>
                </div>
                <div class="compra-summary-item">
                    <div class="compra-summary-label">📦 Itens</div>
                    <div class="compra-summary-value">${compra.total_itens}</div>
                </div>
                <div class="compra-summary-item">
                    <div class="compra-summary-label">💰 Total</div>
                    <div class="compra-summary-value">${formatarMoeda(compra.total)}</div>
                </div>
            </div>
        </div>
        
        ${hasEvolucao ? '<p style="margin-bottom: 12px; color: var(--gray-600); font-size: 0.875rem;"><strong>📈 Evolução de Preços:</strong> Comparação com a compra anterior</p>' : ''}
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Categoria</th>
                    <th style="text-align: center;">Qtd</th>
                    <th style="text-align: right;">Preço Unit.</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                ${itensHTML}
            </tbody>
        </table>
    `;
}

// ========================================
// EVENT LISTENERS
// ========================================

function inicializarEventListeners() {
    // Login
    $('#formLogin').addEventListener('submit', fazerLogin);
    
    // Logout
    $('#btnLogout').addEventListener('click', logout);
    
    // Usuários
    $('#btnUsuarios').addEventListener('click', abrirUsuarios);
    $('#btnNovoUsuario').addEventListener('click', abrirModalNovoUsuario);
    $('#btnSalvarUsuario').addEventListener('click', salvarUsuario);
    
    // Chat
    $('#btnAbrirChatHeader').addEventListener('click', abrirChat);
    $('#btnFecharChat').addEventListener('click', fecharChat);
    $('#btnMinimizarChat').addEventListener('click', minimizarChat);
    $('#formChat').addEventListener('submit', enviarMensagem);
    
    // Botão Nova Lista
    $('#btnNovaLista').addEventListener('click', () => {
        limparFormularioLista();
        showModal('modalNovaLista');
    });
    
    // Botão Categorias
    $('#btnCategorias').addEventListener('click', abrirCategorias);
    
    // Botão Histórico
    $('#btnHistorico').addEventListener('click', abrirHistorico);
    
    // Salvar Lista
    $('#btnSalvarLista').addEventListener('click', () => {
        const nome = $('#inputNomeLista').value.trim();
        const descricao = $('#inputDescricaoLista').value.trim();
        
        if (!nome) {
            showToast('Nome é obrigatório', 'warning');
            return;
        }
        
        criarLista({ nome, descricao });
    });
    
    // Voltar para listas
    $('#btnVoltarListas').addEventListener('click', () => {
        pararPolling();
        changeScreen('telaListas');
        State.listaAtual = null;
        State.itensAtual = [];
        State.itensSelecionados.clear();
        State.itensMarcados = {};
    });
    
    // Adicionar Item
    $('#btnAdicionarItem').addEventListener('click', async () => {
        limparFormularioItem();
        await carregarCategoriasNoSelect();
        showModal('modalNovoItem');
    });
    
    // Salvar Item
    $('#btnSalvarItem').addEventListener('click', () => {
        const nome = $('#inputNomeItem').value.trim();
        const categoria_id = $('#inputCategoriaItem').value || null;
        const preco = parseFloat($('#inputPrecoItem').value) || 0;
        const quantidade = parseFloat($('#inputQuantidadeItem').value) || 1;
        const ordem = State.itensAtual.length;
        
        if (!nome) {
            showToast('Nome é obrigatório', 'warning');
            return;
        }
        
        const dados = { nome, categoria_id, preco, quantidade, ordem };
        
        if (State.editandoItem) {
            atualizarItem(State.editandoItem, dados);
        } else {
            criarItem(dados);
        }
    });
    
    // Finalizar Compra
    $('#btnFinalizarCompra').addEventListener('click', finalizarCompra);
    
    // Voltar do Histórico
    $('#btnVoltarHistorico').addEventListener('click', () => {
        changeScreen('telaListas');
    });
    
    // Voltar das Categorias
    $('#btnVoltarCategorias').addEventListener('click', () => {
        changeScreen('telaListas');
    });
    
    // Botão Nova Categoria
    $('#btnNovaCategoria').addEventListener('click', () => {
        limparFormularioCategoria();
        showModal('modalNovaCategoria');
    });
    
    // Salvar Categoria
    $('#btnSalvarCategoria').addEventListener('click', () => {
        const nome = $('#inputNomeCategoria').value.trim();
        const cor = $('#inputCorCategoria').value;
        const icone = $('#inputIconeCategoria').value.trim() || '📦';
        
        if (!nome) {
            showToast('Nome é obrigatório', 'warning');
            return;
        }
        
        const dados = { nome, cor, icone };
        
        if (State.editandoCategoria) {
            atualizarCategoria(State.editandoCategoria, dados);
        } else {
            criarCategoria(dados);
        }
    });
    
    // Compartilhar Lista
    $('#btnCompartilharLista').addEventListener('click', compartilharLista);
    
    // Color Picker - Sincronizar color input com text input
    $('#inputCorCategoria').addEventListener('input', (e) => {
        $('#inputCorCategoriaText').value = e.target.value;
    });
    
    $('#inputCorCategoriaText').addEventListener('input', (e) => {
        const valor = e.target.value;
        if (/^#[0-9A-F]{6}$/i.test(valor)) {
            $('#inputCorCategoria').value = valor;
        }
    });
    
    // Color Presets
    $$('.color-preset').forEach(btn => {
        btn.addEventListener('click', () => {
            const cor = btn.dataset.color;
            $('#inputCorCategoria').value = cor;
            $('#inputCorCategoriaText').value = cor;
        });
    });
    
    // Filtros do Histórico
    $('#filtroListaHistorico').addEventListener('change', aplicarFiltrosHistorico);
    $('#filtroPeriodo').addEventListener('change', aplicarFiltrosHistorico);
    
    // Filtros da lista de itens
    $('#filtroMarcacao').addEventListener('change', (e) => {
        State.filtroMarcacao = e.target.value;
        renderizarItens();
    });
    
    $('#filtroCategoria').addEventListener('change', (e) => {
        State.filtroCategoria = e.target.value;
        renderizarItens();
    });
    
    // Fechar modais
    $$('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            hideModal(btn.dataset.modal);
        });
    });
    
    // Fechar modal ao clicar fora
    $$('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
    
    // Enter para salvar nos formulários
    $('#inputNomeLista').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') $('#btnSalvarLista').click();
    });
    
    $('#inputNomeItem').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') $('#btnSalvarItem').click();
    });
}

// ========================================
// AUTENTICAÇÃO
// ========================================

let captchaAtual = null;

function gerarCaptcha() {
    const num1 = Math.floor(Math.random() * 10) + 1;
    const num2 = Math.floor(Math.random() * 10) + 1;
    const operadores = ['+', '-', '*'];
    const operador = operadores[Math.floor(Math.random() * operadores.length)];
    
    captchaAtual = `${num1} ${operador} ${num2}`;
    $('#captchaTexto').textContent = captchaAtual;
}

async function verificarAutenticacao() {
    if (!State.token) {
        mostrarLogin();
        return false;
    }
    
    try {
        const response = await fetch(`${API_BASE}/verificar.php`, {
            headers: {
                'Authorization': `Bearer ${State.token}`
            }
        });
        
        const result = await response.json();
        
        if (result.autenticado) {
            State.usuario = result.usuario;
            mostrarApp();
            return true;
        } else {
            logout();
            return false;
        }
    } catch (error) {
        console.error('Erro ao verificar autenticação:', error);
        logout();
        return false;
    }
}

function mostrarLogin() {
    $('#telaLogin').classList.add('active');
    $('#telaLogin').style.display = 'flex';
    $('.app-header').style.display = 'none';
    $('.app-main').style.display = 'none';
    gerarCaptcha();
}

function mostrarApp() {
    $('#telaLogin').classList.remove('active');
    $('#telaLogin').style.display = 'none';
    $('.app-header').style.display = 'block';
    $('.app-main').style.display = 'block';
    
    // Mostrar botão de usuários apenas para admins
    if (State.usuario && State.usuario.admin) {
        $('#btnUsuarios').style.display = 'inline-flex';
    } else {
        $('#btnUsuarios').style.display = 'none';
    }
}

async function fazerLogin(e) {
    e.preventDefault();
    
    const username = $('#loginUsername').value;
    const senha = $('#loginSenha').value;
    const captcha_resposta = $('#loginCaptcha').value;
    
    if (!username || !senha || !captcha_resposta) {
        mostrarErroLogin('Preencha todos os campos');
        return;
    }
    
    try {
        showLoading();
        
        const response = await fetch(`${API_BASE}/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username,
                senha,
                captcha: captchaAtual,
                captcha_resposta
            })
        });
        
        const result = await response.json();
        
        if (response.ok && result.token) {
            State.token = result.token;
            State.usuario = result.usuario;
            localStorage.setItem('auth_token', result.token);
            
            $('#formLogin').reset();
            $('#loginErro').style.display = 'none';
            
            mostrarApp();
            carregarListas();
            carregarCategorias();
            
            showToast('Login realizado com sucesso!', 'success');
        } else {
            mostrarErroLogin(result.erro || 'Erro ao fazer login');
            gerarCaptcha();
            $('#loginCaptcha').value = '';
        }
    } catch (error) {
        console.error('Erro no login:', error);
        mostrarErroLogin('Erro ao conectar com o servidor');
        gerarCaptcha();
        $('#loginCaptcha').value = '';
    } finally {
        showLoading(false);
    }
}

function mostrarErroLogin(mensagem) {
    const erroDiv = $('#loginErro');
    erroDiv.textContent = mensagem;
    erroDiv.style.display = 'block';
}

async function logout() {
    try {
        if (State.token) {
            await fetch(`${API_BASE}/logout.php`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${State.token}`
                }
            });
        }
    } catch (error) {
        console.error('Erro ao fazer logout:', error);
    } finally {
        State.token = null;
        State.usuario = null;
        localStorage.removeItem('auth_token');
        mostrarLogin();
        showToast('Logout realizado com sucesso', 'success');
    }
}

// ========================================
// USUÁRIOS (ADMIN)
// ========================================

async function abrirUsuarios() {
    changeScreen('telaUsuarios');
    carregarUsuarios();
}

async function carregarUsuarios() {
    try {
        showLoading();
        const response = await fetch(`${API_BASE}/usuarios.php`, {
            headers: {
                'Authorization': `Bearer ${State.token}`
            }
        });
        const usuarios = await response.json();
        
        State.usuarios = usuarios;
        renderizarUsuarios();
    } catch (error) {
        console.error('Erro ao carregar usuários:', error);
        showToast('Erro ao carregar usuários', 'error');
    } finally {
        showLoading(false);
    }
}

function renderizarUsuarios() {
    const container = $('#usuariosContainer');
    
    if (State.usuarios.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>Nenhum usuário cadastrado</p></div>';
        return;
    }
    
    container.innerHTML = State.usuarios.map(usuario => `
        <div class="usuario-card">
            <div class="usuario-header">
                <div class="usuario-info">
                    <h3>${usuario.nome}</h3>
                    <div class="username">@${usuario.username}</div>
                </div>
            </div>
            <div class="usuario-badges">
                ${usuario.admin ? '<span class="badge admin">Admin</span>' : ''}
                <span class="badge ${usuario.ativo ? 'ativo' : 'inativo'}">
                    ${usuario.ativo ? 'Ativo' : 'Inativo'}
                </span>
            </div>
            <div class="usuario-meta">
                ${usuario.email ? `<div>📧 ${usuario.email}</div>` : ''}
                <div>📅 Criado em ${new Date(usuario.criado_em).toLocaleDateString('pt-BR')}</div>
            </div>
            <div class="usuario-actions">
                <button class="btn btn-sm btn-secondary" onclick="editarUsuario(${usuario.id})">
                    ✏️ Editar
                </button>
                <button class="btn btn-sm btn-danger" onclick="confirmarDeletarUsuario(${usuario.id}, '${usuario.nome}')">
                    🗑️ Deletar
                </button>
            </div>
        </div>
    `).join('');
}

async function abrirModalNovoUsuario() {
    State.editandoUsuario = null;
    $('#tituloModalUsuario').textContent = 'Novo Usuário';
    $('#editUsuarioId').value = '';
    $('#inputUsernameUsuario').value = '';
    $('#inputNomeUsuario').value = '';
    $('#inputEmailUsuario').value = '';
    $('#inputSenhaUsuario').value = '';
    $('#inputAdminUsuario').checked = false;
    $('#inputAtivoUsuario').checked = true;
    $('#senhaOpcional').style.display = 'none';
    $('#inputSenhaUsuario').required = true;
    
    showModal('modalNovoUsuario');
}

async function salvarUsuario() {
    const id = $('#editUsuarioId').value;
    const username = $('#inputUsernameUsuario').value;
    const nome = $('#inputNomeUsuario').value;
    const email = $('#inputEmailUsuario').value;
    const senha = $('#inputSenhaUsuario').value;
    const admin = $('#inputAdminUsuario').checked;
    const ativo = $('#inputAtivoUsuario').checked;
    
    if (!username || !nome) {
        showToast('Preencha os campos obrigatórios', 'error');
        return;
    }
    
    if (!id && !senha) {
        showToast('Senha é obrigatória para novo usuário', 'error');
        return;
    }
    
    const dados = {
        username,
        nome,
        email,
        admin: admin ? 1 : 0,
        ativo: ativo ? 1 : 0
    };
    
    if (senha) {
        dados.senha = senha;
    }
    
    try {
        showLoading();
        
        if (id) {
            dados.id = parseInt(id);
            await fetch(`${API_BASE}/usuarios.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${State.token}`
                },
                body: JSON.stringify(dados)
            });
            showToast('Usuário atualizado com sucesso!', 'success');
        } else {
            await fetch(`${API_BASE}/usuarios.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${State.token}`
                },
                body: JSON.stringify(dados)
            });
            showToast('Usuário criado com sucesso!', 'success');
        }
        
        hideModal('modalNovoUsuario');
        carregarUsuarios();
    } catch (error) {
        console.error('Erro ao salvar usuário:', error);
        showToast('Erro ao salvar usuário', 'error');
    } finally {
        showLoading(false);
    }
}

async function editarUsuario(id) {
    const usuario = State.usuarios.find(u => u.id === id);
    if (!usuario) return;
    
    State.editandoUsuario = usuario;
    $('#tituloModalUsuario').textContent = 'Editar Usuário';
    $('#editUsuarioId').value = usuario.id;
    $('#inputUsernameUsuario').value = usuario.username;
    $('#inputNomeUsuario').value = usuario.nome;
    $('#inputEmailUsuario').value = usuario.email || '';
    $('#inputSenhaUsuario').value = '';
    $('#inputAdminUsuario').checked = usuario.admin;
    $('#inputAtivoUsuario').checked = usuario.ativo;
    $('#senhaOpcional').style.display = 'inline';
    $('#inputSenhaUsuario').required = false;
    
    showModal('modalNovoUsuario');
}

async function confirmarDeletarUsuario(id, nome) {
    if (!confirm(`Tem certeza que deseja deletar o usuário "${nome}"?\nTodas as listas deste usuário serão deletadas.`)) {
        return;
    }
    
    try {
        showLoading();
        await fetch(`${API_BASE}/usuarios.php`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${State.token}`
            },
            body: JSON.stringify({ id })
        });
        
        showToast('Usuário deletado com sucesso!', 'success');
        carregarUsuarios();
    } catch (error) {
        console.error('Erro ao deletar usuário:', error);
        showToast('Erro ao deletar usuário', 'error');
    } finally {
        showLoading(false);
    }
}

function voltarParaListas() {
    changeScreen('telaListas');
}

// ========================================
// DELETAR LISTA
// ========================================

async function deletarLista(listaId, nomeList) {
    if (!confirm(`Tem certeza que deseja deletar a lista "${nomeList}"?\n\nEsta ação não pode ser desfeita e todos os itens da lista serão removidos.`)) {
        return;
    }
    
    try {
        showLoading();
        
        const response = await fetch(`${API_BASE}/listas.php?id=${listaId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${State.token}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Lista deletada com sucesso!', 'success');
            await carregarListas();
        } else {
            showToast(result.message || 'Erro ao deletar lista', 'error');
        }
    } catch (error) {
        console.error('Erro ao deletar lista:', error);
        showToast('Erro ao deletar lista', 'error');
    } finally {
        showLoading(false);
    }
}

// ========================================
// COMPARTILHAMENTO DE LISTAS
// ========================================

async function abrirModalCompartilhar(listaId) {
    $('#compartilharListaId').value = listaId;
    
    // Carregar usuários disponíveis para compartilhar
    await carregarUsuariosParaCompartilhar();
    
    // Carregar usuários que já têm acesso
    await carregarUsuariosComAcesso(listaId);
    
    showModal('modalCompartilhar');
}

async function carregarUsuariosParaCompartilhar() {
    try {
        const response = await fetch(`${API_BASE}/usuarios.php`, {
            headers: {
                'Authorization': `Bearer ${State.token}`
            }
        });
        
        const usuarios = await response.json();
        const select = $('#selectUsuarioCompartilhar');
        
        // Filtrar o próprio usuário
        const outrosUsuarios = usuarios.filter(u => u.id !== State.usuario.id);
        
        select.innerHTML = '<option value="">Escolha um usuário...</option>' +
            outrosUsuarios.map(u => `
                <option value="${u.id}">${u.nome} (@${u.username})</option>
            `).join('');
    } catch (error) {
        console.error('Erro ao carregar usuários:', error);
    }
}

async function carregarUsuariosComAcesso(listaId) {
    try {
        const response = await fetch(`${API_BASE}/compartilhamentos.php?lista_id=${listaId}`, {
            headers: {
                'Authorization': `Bearer ${State.token}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            renderizarUsuariosComAcesso(result.data, listaId);
        }
    } catch (error) {
        console.error('Erro ao carregar compartilhamentos:', error);
    }
}

function renderizarUsuariosComAcesso(usuarios, listaId) {
    const container = $('#usuariosComAcesso');
    
    if (usuarios.length === 0) {
        container.innerHTML = '<p style="color: var(--gray-500); font-size: 0.9rem; text-align: center;">Nenhum usuário com acesso ainda</p>';
        return;
    }
    
    container.innerHTML = usuarios.map(u => `
        <div class="usuario-compartilhado">
            <div class="usuario-compartilhado-info">
                <div class="usuario-compartilhado-nome">${u.nome}</div>
                <div class="usuario-compartilhado-permissao">
                    ${u.pode_editar ? '✏️ Pode editar' : '👁️ Apenas visualizar'}
                </div>
            </div>
            <button class="btn-remover-compartilhamento" onclick="removerCompartilhamento(${listaId}, ${u.usuario_id})">
                Remover
            </button>
        </div>
    `).join('');
}

async function compartilharLista() {
    const listaId = $('#compartilharListaId').value;
    const usuarioId = $('#selectUsuarioCompartilhar').value;
    const podeEditar = $('#checkPodeEditar').checked ? 1 : 0;
    
    if (!usuarioId) {
        showToast('Selecione um usuário', 'warning');
        return;
    }
    
    try {
        showLoading();
        
        const response = await fetch(`${API_BASE}/compartilhamentos.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${State.token}`
            },
            body: JSON.stringify({
                lista_id: parseInt(listaId),
                usuario_id: parseInt(usuarioId),
                pode_editar: podeEditar
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Lista compartilhada com sucesso!', 'success');
            $('#selectUsuarioCompartilhar').value = '';
            await carregarUsuariosComAcesso(listaId);
        } else {
            showToast(result.message || 'Erro ao compartilhar lista', 'error');
        }
    } catch (error) {
        console.error('Erro ao compartilhar:', error);
        showToast('Erro ao compartilhar lista', 'error');
    } finally {
        showLoading(false);
    }
}

async function removerCompartilhamento(listaId, usuarioId) {
    if (!confirm('Deseja remover o acesso deste usuário à lista?')) {
        return;
    }
    
    try {
        showLoading();
        
        const response = await fetch(`${API_BASE}/compartilhamentos.php`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${State.token}`
            },
            body: JSON.stringify({
                lista_id: listaId,
                usuario_id: usuarioId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Compartilhamento removido!', 'success');
            await carregarUsuariosComAcesso(listaId);
        } else {
            showToast(result.message || 'Erro ao remover compartilhamento', 'error');
        }
    } catch (error) {
        console.error('Erro ao remover:', error);
        showToast('Erro ao remover compartilhamento', 'error');
    } finally {
        showLoading(false);
    }
}

// ========================================
// CHAT
// ========================================

const Chat = {
    mensagens: [],
    ultimoId: 0,
    chatAberto: false,
    chatMinimizado: false,
    novasMensagens: 0,
    polling: null
};

function iniciarChat() {
    if (!State.usuario) return;
    
    // Carregar histórico
    carregarHistoricoChat();
    
    // Iniciar polling a cada 2 segundos
    Chat.polling = setInterval(verificarNovasMensagens, 2000);
}

async function carregarHistoricoChat() {
    try {
        const response = await fetch(`${API_BASE}/mensagens.php?limite=50`, {
            headers: {
                'Authorization': `Bearer ${State.token}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            Chat.mensagens = result.data;
            if (Chat.mensagens.length > 0) {
                Chat.ultimoId = Chat.mensagens[Chat.mensagens.length - 1].id;
            }
            renderizarMensagens();
        }
    } catch (error) {
        console.error('Erro ao carregar histórico:', error);
    }
}

async function verificarNovasMensagens() {
    if (!State.usuario || !State.token) return;
    
    try {
        const response = await fetch(`${API_BASE}/mensagens.php?ultimo_id=${Chat.ultimoId}`, {
            headers: {
                'Authorization': `Bearer ${State.token}`
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(msg => {
                Chat.mensagens.push(msg);
                Chat.ultimoId = msg.id;
                
                // Incrementar contador se chat estiver minimizado ou fechado
                if (!Chat.chatAberto || Chat.chatMinimizado) {
                    Chat.novasMensagens++;
                }
            });
            
            renderizarMensagens();
            atualizarBadgeNotificacoes();
            
            // Mostrar notificação se chat não estiver visível
            if (!Chat.chatAberto || Chat.chatMinimizado) {
                const ultimaMsg = result.data[result.data.length - 1];
                if (ultimaMsg.usuario_id !== State.usuario.id) {
                    mostrarNotificacaoChat(ultimaMsg);
                }
            }
        }
    } catch (error) {
        console.error('Erro ao verificar mensagens:', error);
    }
}

function renderizarMensagens() {
    const container = $('#chatMensagens');
    if (!container) return;
    
    container.innerHTML = Chat.mensagens.map(msg => {
        const ehPropria = msg.usuario_id === State.usuario.id;
        const iniciais = msg.nome.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        const hora = new Date(msg.criada_em).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        
        return `
            <div class="mensagem ${ehPropria ? 'propria' : ''}">
                <div class="mensagem-avatar">${iniciais}</div>
                <div class="mensagem-conteudo">
                    ${!ehPropria ? `<div class="mensagem-autor">${msg.nome}</div>` : ''}
                    <div class="mensagem-balao">${escapeHtml(msg.mensagem)}</div>
                    <div class="mensagem-hora">${hora}</div>
                </div>
            </div>
        `;
    }).join('');
    
    // Scroll para o final
    container.scrollTop = container.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function abrirChat() {
    Chat.chatAberto = true;
    Chat.chatMinimizado = false;
    Chat.novasMensagens = 0;
    
    $('#chatWidget').style.display = 'flex';
    $('#chatWidget').classList.remove('minimizado');
    
    atualizarBadgeNotificacoes();
    
    // Focar no input
    setTimeout(() => {
        $('#inputMensagem').focus();
        $('#chatMensagens').scrollTop = $('#chatMensagens').scrollHeight;
    }, 100);
}

function fecharChat() {
    Chat.chatAberto = false;
    Chat.chatMinimizado = false;
    
    $('#chatWidget').style.display = 'none';
}

function minimizarChat() {
    Chat.chatMinimizado = !Chat.chatMinimizado;
    
    if (Chat.chatMinimizado) {
        $('#chatWidget').classList.add('minimizado');
    } else {
        $('#chatWidget').classList.remove('minimizado');
        Chat.novasMensagens = 0;
        atualizarBadgeNotificacoes();
        $('#inputMensagem').focus();
    }
}

function atualizarBadgeNotificacoes() {
    const badge = $('#badgeNovasMensagensHeader');
    
    if (Chat.novasMensagens > 0) {
        badge.textContent = Chat.novasMensagens > 99 ? '99+' : Chat.novasMensagens;
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }
}

async function enviarMensagem(e) {
    e.preventDefault();
    
    const input = $('#inputMensagem');
    const mensagem = input.value.trim();
    
    if (!mensagem) return;
    
    try {
        const response = await fetch(`${API_BASE}/mensagens.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${State.token}`
            },
            body: JSON.stringify({ mensagem })
        });
        
        const result = await response.json();
        
        if (result.success) {
            input.value = '';
            
            // Adicionar à lista local
            Chat.mensagens.push(result.data);
            Chat.ultimoId = result.data.id;
            
            renderizarMensagens();
        } else {
            showToast(result.message || 'Erro ao enviar mensagem', 'error');
        }
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error);
        showToast('Erro ao enviar mensagem', 'error');
    }
}

function mostrarNotificacaoChat(msg) {
    // Som de notificação (opcional - pode ser implementado com Web Audio API)
    showToast(`💬 ${msg.nome}: ${msg.mensagem.substring(0, 50)}...`, 'info');
}

// ========================================
// INICIALIZAÇÃO
// ========================================

document.addEventListener('DOMContentLoaded', async () => {
    inicializarEventListeners();
    
    // Verificar autenticação antes de carregar dados
    const autenticado = await verificarAutenticacao();
    
    if (autenticado) {
        carregarListas();
        carregarCategorias();
        iniciarChat();
    }
    
    console.log('🛒 App Lista de Compras inicializado!');
});
