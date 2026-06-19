import { For, Show } from 'solid-js';
import { Search, Sparkles, Plus, RefreshCw } from 'lucide-solid';
import { WPPlugin } from '../types';

interface PluginsPageProps {
  pluginSearch: () => string;
  setPluginSearch: (v: string) => void;
  pluginCategoryFilter: () => string;
  setPluginCategoryFilter: (v: string) => void;
  filteredPlugins: () => WPPlugin[];
  togglePlugin: (id: string, name: string) => void;
  updatePlugin: (id: string, name: string) => void;
  updatingPlugins: () => Record<string, boolean>;
  mockInstallPlugin: (name: string, desc: string, cat: string) => void;
}

export default function PluginsPage(props: PluginsPageProps) {
  return (
    <div class="space-y-6">

      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white p-5 border border-slate-150/60 rounded-2xl shadow-sm">

        <div class="relative flex-grow max-w-md">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
            <Search size={14} />
          </span>
          <input
            type="text"
            placeholder="Search plugins..."
            value={props.pluginSearch()}
            onInput={(e) => props.setPluginSearch(e.currentTarget.value)}
            class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-650 rounded-xl pl-10 pr-4 py-3 outline-none transition-all focus:bg-white"
          />
        </div>

        <div class="flex items-center gap-3">
          <select
            value={props.pluginCategoryFilter()}
            onChange={(e) => props.setPluginCategoryFilter(e.target.value)}
            class="text-xs font-semibold bg-[#f8fafc] border border-slate-200 hover:bg-slate-50 px-3.5 py-3 rounded-xl outline-none cursor-pointer transition-colors"
          >
            <option value="All">All Categories</option>
            <option value="Performance">Performance</option>
            <option value="Optimization">Optimization</option>
            <option value="Marketing">Marketing</option>
            <option value="Security">Security</option>
            <option value="Development">Development</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <For each={props.filteredPlugins()}>
          {(plugin) => (
            <div
              class="wp-card flex flex-col justify-between plugin-active-status"
              classList={{ 'is-active': plugin.active }}
            >
              <div class="card-body p-6 space-y-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="space-y-0.5">
                    <h4 class="font-bold text-sm tracking-tight text-slate-900 flex items-center gap-1.5 leading-snug">
                      {plugin.name}
                    </h4>
                    <p class="text-[10px] font-mono text-slate-400 font-semibold uppercase tracking-wider">
                      v{plugin.version} &bull; {plugin.author}
                    </p>
                  </div>
                  <span class="text-[9px] font-bold px-2 py-0.5 rounded border uppercase font-mono tracking-wide shadow-sm shrink-0"
                        classList={{
                          'bg-indigo-50 text-indigo-700 border-indigo-150': plugin.category === 'Development',
                          'bg-emerald-50 text-emerald-700 border-emerald-150': plugin.category === 'Performance',
                          'bg-sky-50 text-sky-700 border-sky-150': plugin.category === 'Optimization',
                          'bg-amber-50 text-amber-700 border-amber-100': plugin.category === 'Marketing',
                          'bg-rose-50 text-rose-700 border-rose-100': plugin.category === 'Security'
                        }}
                  >
                    {plugin.category}
                  </span>
                </div>

                <p class="text-xs text-slate-500 leading-relaxed font-sans font-medium min-h-[48px] line-clamp-3">
                  {plugin.desc}
                </p>

                <Show when={plugin.updateAvailable}>
                  <div class="p-3 bg-gradient-to-r from-amber-50 to-amber-50/20 border border-amber-100 rounded-xl flex items-center justify-between text-[11px] font-semibold text-amber-800 shadow-sm animate-pulse">
                    <span>Update Ready! (v{plugin.version.split('.').map((p, i) => i === 2 ? Number(p)+1 : p).join('.')})</span>
                    <button
                      onClick={() => props.updatePlugin(plugin.id, plugin.name)}
                      class="text-[9px] font-bold font-mono bg-amber-600 hover:bg-amber-700 text-white px-2 py-1 rounded-lg transition-colors flex items-center gap-1 cursor-pointer shadow border border-amber-700"
                    >
                      <Show when={props.updatingPlugins()[plugin.id]} fallback={
                        <>
                          <RefreshCw size={9} strokeWidth={2.5} />
                          <span>UPGRADE</span>
                        </>
                      }>
                        <span class="w-2 h-2 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span>UPGRADING</span>
                      </Show>
                    </button>
                  </div>
                </Show>
              </div>

              <div class="bg-slate-50/50 border-t border-slate-100 px-6 py-4 flex items-center justify-between">
                <span
                  class="text-[10px] font-bold font-mono tracking-widest flex items-center gap-1.5"
                  classList={{
                    'text-emerald-600': plugin.active,
                    'text-slate-400': !plugin.active
                  }}
                >
                  <span class="w-2 h-2 rounded-full shadow-inner"
                        classList={{
                          'bg-emerald-500 animate-pulse': plugin.active,
                          'bg-slate-300': !plugin.active
                        }}
                  ></span>
                  {plugin.active ? 'ACTIVE' : 'DEACTIVATED'}
                </span>

                <div class="flex items-center gap-1.5">
                  <button
                    onClick={() => props.togglePlugin(plugin.id, plugin.name)}
                    class="text-[11px] font-bold px-3 py-1.5 rounded-lg border transition-all cursor-pointer active:scale-95"
                    classList={{
                      'bg-white hover:bg-slate-100 text-slate-700 border-slate-200 shadow-sm': plugin.active,
                      'bg-indigo-600 hover:bg-indigo-700 text-white border-transparent shadow shadow-indigo-100': !plugin.active
                    }}
                  >
                    {plugin.active ? 'Deactivate' : 'Activate'}
                  </button>
                </div>
              </div>

            </div>
          )}
        </For>
      </div>

      <div class="wp-card bg-slate-50/10">
        <div class="card-header border-b border-slate-50 bg-slate-50/20 px-6 py-4.5 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Sparkles size={16} class="text-indigo-600 animate-pulse" />
            <h3 class="font-bold text-sm tracking-tight text-slate-800">Add Plugins</h3>
          </div>
          <span class="text-[9px] font-mono text-slate-400 font-bold border border-slate-200 bg-white px-2 py-0.5 rounded uppercase">Plugin Directory</span>
        </div>
        <div class="card-body p-6 space-y-6">
          <p class="text-[13px] text-slate-500 max-w-xl leading-relaxed font-medium">
            Extend and customize your dashboard with certified plugins. Install in one-click directly into your platform.
          </p>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-5 border border-dashed border-slate-200 bg-[#fafbfc]/35 hover:border-solid hover:border-emerald-500/40 hover:bg-indigo-50/5 hover:shadow-md rounded-2xl space-y-4 transition-all duration-300 flex flex-col justify-between group">
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <h5 class="font-bold text-[13px] text-slate-800 group-hover:text-emerald-700 transition-colors">Analytics Engine Pro</h5>
                  <span class="text-[9px] font-black px-2 py-0.5 rounded-md border border-emerald-100 bg-emerald-50 text-emerald-700 uppercase font-mono tracking-wider shadow-sm shrink-0">STORE</span>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed font-semibold">Advanced analytics dashboard with real-time visitor tracking, conversion funnels, and performance metrics.</p>
              </div>
              <button
                onClick={() => props.mockInstallPlugin("Analytics Engine Pro", "Advanced analytics with real-time tracking, conversion funnels, and performance metrics.", "Performance")}
                class="mt-1 text-xs font-extrabold bg-slate-900 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl transition-all hover:scale-[1.02] cursor-pointer self-start flex items-center gap-1.5 shadow-sm"
              >
                <Plus size={11} strokeWidth={3} />
                <span>Install</span>
              </button>
            </div>

            <div class="p-5 border border-dashed border-slate-200 bg-[#fafbfc]/35 hover:border-solid hover:border-indigo-500/40 hover:bg-[#fafbfc] hover:shadow-md rounded-2xl space-y-4 transition-all duration-300 flex flex-col justify-between group">
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <h5 class="font-bold text-[13px] text-slate-800 group-hover:text-indigo-700 transition-colors">Security Guard Elite</h5>
                  <span class="text-[9px] font-black px-2 py-0.5 rounded-md border border-indigo-100 bg-indigo-50 text-indigo-700 uppercase font-mono tracking-wider shadow-sm shrink-0">STORE</span>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed font-semibold">Enterprise-grade firewall, brute force protection, and real-time threat monitoring for your platform.</p>
              </div>
              <button
                onClick={() => props.mockInstallPlugin("Security Guard Elite", "Enterprise-grade firewall, brute force protection, and threat monitoring.", "Security")}
                class="mt-1 text-xs font-extrabold bg-slate-900 hover:bg-indigo-650 text-white px-4 py-2.5 rounded-xl transition-all hover:scale-[1.02] cursor-pointer self-start flex items-center gap-1.5 shadow-sm"
              >
                <Plus size={11} strokeWidth={3} />
                <span>Install</span>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  );
}
