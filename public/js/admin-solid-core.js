import { createSignal, createEffect, onCleanup, For } from 'https://esm.sh/solid-js';
import { render } from 'https://esm.sh/solid-js/web';
import html from 'https://esm.sh/solid-js/html';

// ─── PURE VANILLA COMBOBOX ────────────────────────────────────────────────────
// Avoids solid-js/html reactive template quirks with attribute interpolation.
// Uses direct DOM manipulation which is perfectly fine and extremely fast.
class AdminComboBox {
    constructor(container, config) {
        this.container = container;
        this.config = config;
        this.isOpen = false;
        this.selected = config.options.find(o => String(o.value) === String(config.value)) || null;
        this.searchTerm = '';
        this.filteredOptions = config.options;

        this._render();
        this._attachEvents();
        this._ensureHiddenInput();
    }

    _ensureHiddenInput() {
        let input = this.container.querySelector(`input[name="${this.config.name}"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = this.config.name;
            this.container.appendChild(input);
        }
        if (this.selected) input.value = this.selected.value;
        this.hiddenInput = input;
    }

    _render() {
        this.container.innerHTML = `
        <div class="solid-combobox" id="${this.container.id}-inner">
            <div class="combobox-toggle" role="combobox" tabindex="0">
                <span class="combobox-label">${this.selected ? this._escape(this.selected.label) : (this.config.placeholder || 'Select...')}</span>
                <span class="combobox-chevron"></span>
            </div>
            <div class="combobox-menu" role="listbox">
                <div class="combobox-search-wrap">
                    <input class="combobox-search" type="text" placeholder="Type to search..." autocomplete="off" />
                </div>
                <ul class="combobox-options"></ul>
            </div>
        </div>
        <style>
            .solid-combobox { position: relative; width: 100%; font-family: inherit; }
            .combobox-toggle {
                background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 14px; padding: 14px 20px; color: #fff; font-size: 15px;
                font-weight: 500; cursor: pointer; display: flex; justify-content: space-between;
                align-items: center; transition: border-color .25s, box-shadow .25s; backdrop-filter: blur(10px);
            }
            .solid-combobox.is-open .combobox-toggle { border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99,102,241,.2); }
            .combobox-chevron { width: 8px; height: 8px; border-right: 2px solid #64748b; border-bottom: 2px solid #64748b; transform: rotate(45deg); transition: transform .25s, border-color .25s; margin-top: -4px; flex-shrink: 0; }
            .solid-combobox.is-open .combobox-chevron { transform: rotate(-135deg); border-color: #6366f1; margin-top: 4px; }
            .combobox-menu {
                position: absolute; top: calc(100% + 10px); left: 0; right: 0;
                background: #111827; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;
                z-index: 9999; box-shadow: 0 20px 50px rgba(0,0,0,.6);
                opacity: 0; transform: translateY(-10px); pointer-events: none;
                transition: opacity .2s, transform .2s cubic-bezier(.34,1.56,.64,1); padding: 8px;
            }
            .solid-combobox.is-open .combobox-menu { opacity: 1; transform: translateY(0); pointer-events: auto; }
            .combobox-search-wrap { padding: 4px; border-bottom: 1px solid rgba(255,255,255,.05); margin-bottom: 8px; }
            .combobox-search {
                width: 100%; background: rgba(0,0,0,.2); border: 1px solid rgba(255,255,255,.1);
                border-radius: 10px; padding: 10px 14px; color: #fff; font-size: 14px; outline: none; box-sizing: border-box;
            }
            .combobox-search:focus { border-color: #6366f1; }
            .combobox-options { max-height: 280px; overflow-y: auto; list-style: none; padding: 0; margin: 0; }
            .combobox-options::-webkit-scrollbar { width: 6px; }
            .combobox-options::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 10px; }
            .combobox-option { padding: 12px 16px; border-radius: 10px; color: #9ca3af; font-size: 14px; cursor: pointer; transition: background .15s, color .15s; margin-bottom: 2px; }
            .combobox-option:hover { background: rgba(255,255,255,.05); color: #fff; }
            .combobox-option.is-selected { background: #6366f1; color: #fff; font-weight: 600; }
            .combobox-no-results { padding: 24px; text-align: center; color: #4b5563; font-size: 14px; font-style: italic; }
        </style>`;

        this.el = this.container.querySelector('.solid-combobox');
        this.toggle = this.container.querySelector('.combobox-toggle');
        this.label = this.container.querySelector('.combobox-label');
        this.menu = this.container.querySelector('.combobox-menu');
        this.search = this.container.querySelector('.combobox-search');
        this.optsList = this.container.querySelector('.combobox-options');

        this._renderOptions();
    }

    _renderOptions() {
        this.optsList.innerHTML = '';
        if (this.filteredOptions.length === 0) {
            this.optsList.innerHTML = '<li class="combobox-no-results">No matches found</li>';
            return;
        }
        this.filteredOptions.forEach(opt => {
            const li = document.createElement('li');
            li.className = 'combobox-option' + (this.selected && this.selected.value === opt.value ? ' is-selected' : '');
            li.textContent = opt.label;
            li.addEventListener('click', (e) => {
                e.stopPropagation();
                this._select(opt);
            });
            this.optsList.appendChild(li);
        });
    }

    _select(opt) {
        this.selected = opt;
        this.label.textContent = opt.label;
        this.hiddenInput.value = opt.value;
        this.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        this._close();
    }

    _open() {
        this.isOpen = true;
        this.el.classList.add('is-open');
        this.search.value = '';
        this.searchTerm = '';
        this.filteredOptions = this.config.options;
        this._renderOptions();
        setTimeout(() => this.search.focus(), 50);

        this._outsideHandler = (e) => {
            if (!this.container.contains(e.target)) this._close();
        };
        document.addEventListener('click', this._outsideHandler, { passive: true, capture: true });
    }

    _close() {
        this.isOpen = false;
        this.el.classList.remove('is-open');
        if (this._outsideHandler) {
            document.removeEventListener('click', this._outsideHandler, { capture: true });
            this._outsideHandler = null;
        }
    }

    _attachEvents() {
        this.toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            this.isOpen ? this._close() : this._open();
        });

        this.search.addEventListener('input', (e) => {
            this.searchTerm = e.target.value.toLowerCase();
            this.filteredOptions = this.config.options.filter(o => o.label.toLowerCase().includes(this.searchTerm));
            this._renderOptions();
        });

        this.search.addEventListener('click', (e) => e.stopPropagation());
    }

    _escape(str) {
        return String(str).replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
}

// ─── BULK ACTIONS (SolidJS – simpler template, no attr interpolation issues) ──
const BulkActionUI = (props) => {
    const config = props.config;
    const mountId = props.mountId;
    const [isOpen, setIsOpen] = createSignal(false);
    const [selected, setSelected] = createSignal(null);

    const handleApply = () => {
        const action = selected();
        if (!action) return;
        const container = document.getElementById(mountId);
        const form = container?.closest('form') || document.querySelector('#posts-filter');
        if (form) {
            let input = form.querySelector(`input[name="${config.name}"]`);
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = config.name;
                form.appendChild(input);
            }
            input.value = action.value;
            form.submit();
        }
    };

    createEffect(() => {
        if (!isOpen()) return;
        const handler = () => setIsOpen(false);
        document.addEventListener('click', handler);
        onCleanup(() => document.removeEventListener('click', handler));
    });

    return html`
        <div class="solid-bulk-wrapper" onclick=${(e) => e && e.stopPropagation && e.stopPropagation()}>
            <div class="solid-dropdown">
                <button type="button" class="button action-toggle" onclick=${(e) => { if (e) { e.preventDefault(); e.stopPropagation(); } setIsOpen(!isOpen()); }}>
                    <span>${() => selected() ? selected().label : 'Bulk Actions'}</span>
                </button>
                <div class="bulk-menu" style=${() => isOpen() ? 'opacity:1;pointer-events:auto;transform:translateY(0)' : 'opacity:0;pointer-events:none;transform:translateY(-8px)'}>
                    <ul>
                        <${For} each=${config.actions}>
                            ${(item) => html`<li onclick=${() => { setSelected(item); setIsOpen(false); }} class="bulk-item">${item.label}</li>`}
                        <//>
                    </ul>
                </div>
            </div>
            <button type="button" class="button button-primary" onclick=${handleApply} disabled=${() => !selected()}>Apply</button>
            <style>
                .solid-bulk-wrapper { display:inline-flex; align-items:center; gap:12px; }
                .solid-dropdown { position:relative; }
                .action-toggle { min-width:160px; height:32px; display:flex; justify-content:space-between; align-items:center; padding:0 12px !important; background:#fff; border:1px solid #8c8f94; border-radius:4px; cursor:pointer; color:#2c3338; }
                .bulk-menu { position:absolute; top:calc(100% + 4px); left:0; background:#fff; box-shadow:0 5px 20px rgba(0,0,0,.15); border-radius:4px; padding:4px; transition:.2s; z-index:9991; min-width:180px; }
                .bulk-item { padding:8px 12px; cursor:pointer; border-radius:3px; font-size:13px; color:#2c3338; list-style:none; }
                .bulk-item:hover { background:#f0f0f1; }
            </style>
        </div>
    `;
};

// ─── INIT ────────────────────────────────────────────────────────────────────
const init = () => {
    document.querySelectorAll('[data-solid-component]').forEach((el, idx) => {
        if (el.dataset.mounted) return;
        el.dataset.mounted = 'true';

        const name = el.dataset.solidComponent;
        let cfg = {};
        try { cfg = JSON.parse(el.dataset.config || '{}'); } catch (e) { console.error('SolidAdmin config parse error:', e); }

        if (name === 'ComboBox') {
            const id = el.id || ('combobox-' + idx);
            el.id = id;
            new AdminComboBox(el, cfg);
        } else if (name === 'BulkActions') {
            const id = el.id || ('bulk-' + idx);
            el.id = id;
            render(() => html`<${BulkActionUI} config=${cfg} mountId=${id} />`, el);
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
