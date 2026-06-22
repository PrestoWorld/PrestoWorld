import { For, Show } from 'solid-js';
import { Check, Sparkles } from 'lucide-solid';
import { WPTheme } from '../types';

interface ThemesPageProps {
  themes: () => WPTheme[];
  activeTheme: () => WPTheme | undefined;
  activating: string | null;
  onActivate: (directory: string) => void;
}

export default function ThemesPage(props: ThemesPageProps) {
  return (
    <div class="space-y-8">

      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Themes</h2>
          <p class="text-xs text-slate-500 font-semibold mt-1">
            Choose a theme for your site. The active theme is highlighted.
          </p>
        </div>
        <a
          href="https://wordpress.org/themes/"
          target="_blank"
          class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-150 px-4 py-2 rounded-xl transition-colors flex items-center gap-1.5"
          rel="noopener"
        >
          <Sparkles size={13} />
          Browse WordPress.org
        </a>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <For each={props.themes()}>
          {(theme) => (
            <div
              class="wp-card flex flex-col overflow-hidden transition-all duration-300"
              classList={{
                'ring-2 ring-indigo-500 shadow-lg shadow-indigo-100/50 scale-[1.02]': theme.is_active,
                'hover:shadow-md hover:-translate-y-0.5': !theme.is_active,
              }}
            >
              <div class="relative bg-slate-100 aspect-video overflow-hidden">
                <Show
                  when={theme.screenshot}
                  fallback={
                    <div class="flex items-center justify-center h-full text-slate-300">
                      <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                  }
                >
                  <img
                    src={theme.screenshot!}
                    alt={`${theme.name} screenshot`}
                    class="w-full h-full object-cover"
                    loading="lazy"
                  />
                </Show>

                <Show when={theme.is_active}>
                  <div class="absolute top-3 left-3 bg-indigo-600 text-white text-[9px] font-bold px-2.5 py-1 rounded-lg flex items-center gap-1 shadow-lg shadow-indigo-600/30 uppercase tracking-wider">
                    <Check size={10} strokeWidth={3} />
                    Active
                  </div>
                </Show>
              </div>

              <div class="flex flex-col flex-grow p-5">
                <div class="flex items-start justify-between gap-3 mb-2">
                  <h3 class="font-bold text-sm text-slate-900 leading-snug">{theme.name}</h3>
                  <span class="text-[10px] font-mono font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded shrink-0">
                    v{theme.version}
                  </span>
                </div>

                <Show when={theme.author}>
                  <p class="text-[11px] text-slate-500 font-semibold mb-2">
                    By <span class="text-slate-700">{theme.author}</span>
                  </p>
                </Show>

                <p class="text-xs text-slate-500 leading-relaxed mb-4 line-clamp-3 flex-grow">
                  {theme.description}
                </p>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100 mt-auto">
                  <div class="flex items-center gap-2 text-[10px] text-slate-400 font-mono font-semibold">
                    <Show when={theme.requires}>
                      <span class="bg-slate-50 border border-slate-200 px-2 py-0.5 rounded">WP {theme.requires}+</span>
                    </Show>
                    <Show when={theme.tags}>
                      <span class="truncate max-w-[120px]">{theme.tags?.split(',').slice(0, 2).join(', ')}</span>
                    </Show>
                  </div>

                  <Show when={!theme.is_active}>
                    <button
                      onClick={() => props.onActivate(theme.directory)}
                      class="text-[11px] font-bold px-4 py-2 rounded-xl transition-all cursor-pointer active:scale-95"
                      classList={{
                        'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm shadow-indigo-100': !props.activating || props.activating !== theme.directory,
                        'bg-slate-200 text-slate-400 cursor-wait': props.activating === theme.directory,
                      }}
                      disabled={props.activating === theme.directory}
                    >
                      {props.activating === theme.directory ? 'Activating...' : 'Activate'}
                    </button>
                  </Show>
                </div>
              </div>
            </div>
          )}
        </For>
      </div>

    </div>
  );
}
