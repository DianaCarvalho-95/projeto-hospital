document.addEventListener('DOMContentLoaded', function () {
    inicializarNotificacoesDiarias();
    inicializarEdicaoConteudosPublicos();
    inicializarDashboardCalendario();
    inicializarAbasEquipamento();
    inicializarFiltrosLocalizacoes();
    inicializarModalDesativarUtilizador();
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
