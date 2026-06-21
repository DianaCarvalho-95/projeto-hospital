document.addEventListener('DOMContentLoaded', function () {
    inicializarNotificacoesDiarias();
    inicializarEdicaoConteudosPublicos();
    inicializarDashboardCalendario();
    inicializarAbasEquipamento();
    inicializarFiltrosLocalizacoes();
    inicializarModalDesativarUtilizador();
    inicializarPreenchimentoDemonstracao();
    inicializarIndicacaoCamposObrigatorios();
});

function inicializarNotificacoesDiarias() {
    var menu = document.querySelector('[data-notification-menu]');
    if (!menu) return;

    var dateKey = menu.getAttribute('data-notification-date');
    var storageKey = 'medtech_notifications_seen_date';
    var indicator = menu.querySelector('[data-notification-indicator]');
    var button = menu.querySelector('[data-notification-button]');

    if (indicator && localStorage.getItem(storageKey) === dateKey) {
        indicator.classList.add('is-hidden');
    }

    if (button && indicator) {
        button.addEventListener('click', function () {
            localStorage.setItem(storageKey, dateKey);
            indicator.classList.add('is-hidden');
        });
    }
}

function inicializarEdicaoConteudosPublicos() {
    document.querySelectorAll('.js-open-edit').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = document.getElementById(button.dataset.target);
            if (modal) {
                modal.classList.add('is-visible');
                modal.setAttribute('aria-hidden', 'false');
            }
        });
    });

    document.querySelectorAll('.js-close-edit').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = button.closest('.edit-overlay');
            if (modal) {
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
            }
        });
    });

    document.querySelectorAll('.edit-overlay').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
            }
        });
    });
}

function inicializarDashboardCalendario() {
    const calendar = document.querySelector('[data-dashboard-calendar]');
    if (!calendar) return;

    let eventosAgenda = {};
    try {
        eventosAgenda = JSON.parse(calendar.getAttribute('data-calendar-events') || '{}');
    } catch (error) {
        eventosAgenda = {};
    }

    function formatarData(data) {
        const partes = data.split('-');
        return partes[2] + '/' + partes[1] + '/' + partes[0];
    }

    function criarEvento(evento) {
        const card = document.createElement('div');
        card.className = 'selected-event ' + evento.classe;

        const titulo = document.createElement('div');
        titulo.className = 'selected-event-title';

        if (evento.equipamento_id) {
            const link = document.createElement('a');
            link.href = '../equipamentos/detalhes.php?id=' + evento.equipamento_id;
            link.textContent = evento.titulo;
            titulo.appendChild(link);
        } else {
            titulo.textContent = evento.titulo;
        }

        const descricao = document.createElement('div');
        descricao.className = 'selected-event-sub';
        descricao.textContent = evento.descricao || '';

        card.appendChild(titulo);
        card.appendChild(descricao);
        return card;
    }

    function selecionarDia(data) {
        const eventos = eventosAgenda[data] || [];
        document.querySelectorAll('[data-calendar-date]').forEach((dia) => {
            dia.classList.toggle('selected', dia.dataset.calendarDate === data);
        });

        const selectedDate = document.getElementById('selectedDayDate');
        const selectedCount = document.getElementById('selectedDayCount');
        const lista = document.getElementById('selectedDayEvents');
        if (!selectedDate || !selectedCount || !lista) return;

        selectedDate.textContent = formatarData(data);
        selectedCount.textContent = eventos.length;
        lista.innerHTML = '';

        if (eventos.length === 0) {
            const vazio = document.createElement('p');
            vazio.className = 'selected-empty';
            vazio.textContent = 'Sem tarefas planeadas para este dia.';
            lista.appendChild(vazio);
            return;
        }

        eventos.forEach((evento) => lista.appendChild(criarEvento(evento)));
    }

    document.querySelectorAll('[data-calendar-view]').forEach((botao) => {
        botao.addEventListener('click', () => {
            const vista = botao.dataset.calendarView;
            document.querySelectorAll('[data-calendar-view]').forEach((item) => item.classList.remove('active'));
            botao.classList.add('active');
            document.getElementById('calendar-week').hidden = vista !== 'week';
            document.getElementById('calendar-month').hidden = vista !== 'month';
            document.querySelector('.calendar-card').classList.toggle('month-mode', vista === 'month');
        });
    });

    document.querySelectorAll('[data-calendar-date]').forEach((dia) => {
        dia.addEventListener('click', () => selecionarDia(dia.dataset.calendarDate));
    });

    selecionarDia(calendar.getAttribute('data-calendar-initial-date'));
}

function inicializarAbasEquipamento() {
    var initialHash = window.location.hash;

    if (initialHash) {
        var initialTab = document.querySelector('.nav-link[data-bs-target=  + initialHash +  ]');
        if (initialTab) {
            if (window.bootstrap && bootstrap.Tab) {
                bootstrap.Tab.getOrCreateInstance(initialTab).show();
            } else {
                initialTab.click();
            }
        }
    }

    document.querySelectorAll('.nav-link[data-bs-toggle= tab]').forEach(function (tabButton) {
        tabButton.addEventListener('shown.bs.tab', function (event) {
            var target = event.target.getAttribute('data-bs-target');
            if (target) {
                history.replaceState(null, '', target);
            }
        });
    });
}

function inicializarFiltrosLocalizacoes() {
    document.querySelectorAll('.auto-submit-filter').forEach((select) => {
        select.addEventListener('change', () => select.form.submit());
    });
}

function inicializarModalDesativarUtilizador() {
    const modal = document.getElementById('confirmDisableModal');
    const text = document.getElementById('confirmDisableText');
    const cancelBtn = document.getElementById('cancelDisableUser');
    const confirmBtn = document.getElementById('confirmDisableUser');
    if (!modal || !text || !cancelBtn || !confirmBtn) return;

    let pendingForm = null;

    document.querySelectorAll('.js-confirm-disable').forEach(function (button) {
        button.addEventListener('click', function () {
            pendingForm = button.closest('form');
            const user = button.dataset.user || 'este utilizador';
            text.textContent = 'Pretende desativar ' + user + '? O acesso à área privada ficará bloqueado.';
            modal.classList.add('is-visible');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    function closeModal() {
        pendingForm = null;
        modal.classList.remove('is-visible');
        modal.setAttribute('aria-hidden', 'true');
    }

    cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-visible')) {
            closeModal();
        }
    });

    confirmBtn.addEventListener('click', function () {
        if (pendingForm) {
            pendingForm.submit();
        }
    });
}

function inicializarPreenchimentoDemonstracao() {
    var form = document.querySelector('form[method="post"], form[action="novo.php"], form[action="novo-edificio.php"]');
    if (!form) return;

    var path = window.location.pathname.toLowerCase();
    var isNovo = path.indexOf('/novo.php') !== -1
        || path.indexOf('/novo-edificio.php') !== -1
        || path.indexOf('/novo-documento.php') !== -1
        || path.indexOf('/nova-manutencao.php') !== -1
        || path.indexOf('/nova-movimentacao.php') !== -1;
    if (!isNovo) return;

    var submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton || form.querySelector('[data-demo-fill]')) return;

    var demoButton = document.createElement('button');
    demoButton.type = 'button';
    demoButton.className = 'btn btn-outline-secondary fw-bold';
    demoButton.setAttribute('data-demo-fill', 'true');
    demoButton.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles me-1"></i>Preencher teste';

    submitButton.parentNode.insertBefore(demoButton, submitButton);

    demoButton.addEventListener('click', function () {
        preencherFormularioDemo(form, path);
    });
}

function preencherFormularioDemo(form, path) {
    var now = new Date();
    var stamp = String(now.getHours()).padStart(2, '0') + String(now.getMinutes()).padStart(2, '0') + String(now.getSeconds()).padStart(2, '0');
    var today = now.toISOString().slice(0, 10);
    var validade = new Date(now.getFullYear() + 3, now.getMonth(), now.getDate()).toISOString().slice(0, 10);

    function setValue(name, value) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) return;
        field.value = value;
        field.dispatchEvent(new Event('change', { bubbles: true }));
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function setSelect(name, preferred) {
        var field = form.querySelector('select[name="' + name + '"]');
        if (!field) return;
        var options = Array.from(field.options).filter(function (option) { return option.value !== ''; });
        if (preferred) {
            var match = options.find(function (option) { return option.value === preferred || option.textContent.trim() === preferred; });
            if (match) {
                field.value = match.value;
                field.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }
        }
        if (options.length) {
            field.value = options[0].value;
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    if (path.indexOf('/equipamentos/novo.php') !== -1) {
        setValue('codigo', 'EQD' + stamp);
        setValue('designacao', 'Monitor multiparamétrico');
        setSelect('categoria', 'Monitorização');
        setValue('marca', 'Philips');
        setValue('modelo', 'IntelliVue MX450');
        setValue('numero_serie', 'PHI-DEMO-' + stamp);
        setValue('fabricante', 'Philips');
        setValue('data_aquisicao', today);
        setValue('ano_fabrico', String(now.getFullYear() - 1));
        setValue('custo_aquisicao', '3650.00');
        setSelect('tipo_entrada', 'Compra');
        setSelect('estado', 'Ativo');
        setSelect('criticidade', 'Média');
        setSelect('localizacao_id');
        setSelect('fornecedor_id');
        setValue('observacoes', 'Registo criado para demonstração durante a apresentação.');
        return;
    }

    if (path.indexOf('/localizacoes/novo.php') !== -1) {
        setSelect('edificio');
        setSelect('piso');
        setSelect('servico');
        setValue('sala', 'DEMO' + stamp.slice(-2));
        setValue('observacoes', 'Localização criada para demonstração.');
        return;
    }

    if (path.indexOf('/localizacoes/novo-edificio.php') !== -1) {
        setValue('nome', 'Edifício D');
        setValue('observacoes', 'Novo edifício para demonstração.');
        return;
    }

    if (path.indexOf('/fornecedores/novo.php') !== -1) {
        setValue('nome_empresa', 'DemoCare Medical');
        setValue('nif', '509' + stamp);
        setValue('morada', 'Rua de Júlio Dinis, 728, Porto');
        setSelect('tipo_fornecedor');
        setValue('observacoes', 'Fornecedor criado para demonstração.');
        setValue('telefone', '222258053');
        setValue('email', 'geral@democare.pt');
        setValue('pessoa_contacto', 'Carla Mendes');
        setValue('telefone_contacto', '912345678');
        setValue('website', 'https://www.democare.pt');
        return;
    }

    if (path.indexOf('/equipamentos/novo-documento.php') !== -1) {
        setSelect('equipamento_id');
        setSelect('fornecedor_id');
        setValue('data_documento', today);
        setValue('data_validade', validade);

        var tipoRegisto = form.querySelector('[name="tipo_registo"]');
        if (tipoRegisto && tipoRegisto.value === 'manual') {
            setValue('titulo', 'Manual de Utilizador - Demonstração');
            setSelect('tipo_manual', 'Manual de Utilizador');
            setSelect('idioma', 'Português');
            setValue('observacoes', 'Manual associado para demonstração. Falta apenas escolher o PDF.');
        } else {
            setSelect('tipo_documento', 'Ficha Técnica');
            setValue('nome_documento', 'Ficha Técnica - Demonstração');
        }

        return;
    }

    if (path.indexOf('/equipamentos/nova-manutencao.php') !== -1) {
        var equipamento = form.querySelector('[name="equipamento_id"]');
        if (!equipamento || !equipamento.value) {
            setSelect('equipamento_id');
        }

        var proximaData = new Date(now);
        proximaData.setFullYear(proximaData.getFullYear() + 1);

        setSelect('tipo_manutencao', 'Preventiva');
        setSelect('fornecedor_id');
        setValue('data_manutencao', today);
        setValue('proxima_manutencao', proximaData.toISOString().slice(0, 10));
        setValue('responsavel', 'Ana Silva');
        setValue('custo', '150.00');
        setValue('descricao', 'Verificação geral e manutenção preventiva.');
        setValue('observacoes', 'Registo de manutenção criado para demonstração.');

        return;
    }

    if (path.indexOf('/equipamentos/nova-movimentacao.php') !== -1) {
        var equipamentoMovimentacao = form.querySelector('[name="equipamento_id"]');
        if (!equipamentoMovimentacao || !equipamentoMovimentacao.value) {
            setSelect('equipamento_id');
        }

        setSelect('local_origem');

        var origem = form.querySelector('[name="local_origem"]');
        var destino = form.querySelector('[name="local_destino"]');
        if (destino) {
            var destinosDisponiveis = Array.from(destino.options).filter(function (option) {
                return option.value !== '' && (!origem || option.value !== origem.value);
            });
            if (destinosDisponiveis.length) {
                destino.value = destinosDisponiveis[0].value;
                destino.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        setValue('data_movimentacao', today);
        setValue('responsavel', 'Diana Carvalho');
        setValue('motivo', 'Transferência temporária para apoio clínico');
        setValue('observacoes', 'Movimentação criada para demonstração durante a apresentação.');
    }
}

function inicializarIndicacaoCamposObrigatorios() {
    var form = document.querySelector('form');
    if (!form) return;

    var path = window.location.pathname.toLowerCase();
    var campos = [];

    if (path.indexOf('/equipamentos/novo.php') !== -1 || path.indexOf('/equipamentos/editar.php') !== -1) {
        campos = ['codigo', 'designacao', 'categoria', 'estado', 'criticidade'];
    } else if (path.indexOf('/localizacoes/novo.php') !== -1 || path.indexOf('/localizacoes/editar.php') !== -1) {
        campos = ['edificio', 'piso', 'servico', 'sala'];
    } else if (path.indexOf('/localizacoes/novo-edificio.php') !== -1) {
        campos = ['nome'];
    } else if (path.indexOf('/fornecedores/novo.php') !== -1 || path.indexOf('/fornecedores/editar.php') !== -1) {
        campos = ['nome_empresa', 'nif', 'telefone', 'email', 'tipo_fornecedor'];
    } else if (path.indexOf('/equipamentos/novo-documento.php') !== -1) {
        var tipoRegisto = form.querySelector('[name="tipo_registo"]');
        campos = tipoRegisto && tipoRegisto.value === 'manual'
            ? ['equipamento_id', 'titulo', 'tipo_manual', 'ficheiro']
            : ['equipamento_id', 'tipo_documento', 'nome_documento', 'ficheiro'];
    } else if (path.indexOf('/equipamentos/nova-manutencao.php') !== -1) {
        campos = ['equipamento_id', 'tipo_manutencao', 'data_manutencao', 'fornecedor_id', 'responsavel', 'custo'];
    } else if (path.indexOf('/equipamentos/nova-movimentacao.php') !== -1) {
        campos = ['equipamento_id', 'local_origem', 'local_destino', 'data_movimentacao', 'responsavel', 'motivo'];
    } else if (path.indexOf('/equipamentos/nova-garantia.php') !== -1) {
        campos = ['equipamento_id', 'tipo_documento', 'entidade_responsavel', 'data_inicio', 'data_fim'];
    }

    if (!campos.length) return;

    campos.forEach(function (nomeCampo) {
        var campo = form.querySelector('[name="' + nomeCampo + '"]');
        if (!campo) return;

        campo.setAttribute('required', 'required');
        var grupo = campo.closest('.col-lg-4, .col-lg-8, .col-md-6, .col-md-4, .col-md-3, .col-md-8, .col-12, .mb-3');
        var label = grupo ? grupo.querySelector('label.form-label') : null;
        if (!label || label.querySelector('.required-mark')) return;

        var marca = document.createElement('span');
        marca.className = 'required-mark';
        marca.textContent = ' *';
        label.appendChild(marca);
    });

    if (!form.querySelector('.required-note')) {
        var nota = document.createElement('div');
        nota.className = 'required-note';
        nota.innerHTML = '<span class="required-mark">*</span> Campos obrigatórios';
        var firstRow = form.querySelector('.row, .form-hint');
        if (firstRow) {
            form.insertBefore(nota, firstRow);
        } else {
            form.insertBefore(nota, form.firstChild);
        }
    }
}
