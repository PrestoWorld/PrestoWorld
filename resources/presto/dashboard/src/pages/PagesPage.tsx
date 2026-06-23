import { createSignal, For, Show, onMount } from 'solid-js';
import { FileText, Plus, Trash2 } from 'lucide-solid';
import { WPPost } from '../types';
import { fetchPages } from '../api';

export default function PagesPage() {
  const [pages, setPages] = createSignal<WPPost[]>([]);
  const [loading, setLoading] = createSignal(true);

  onMount(() => {
    fetchPages().then(setPages).catch(() => {}).finally(() => setLoading(false));
  });

  return (
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-white p-5 border border-slate-150/60 rounded-2xl shadow-sm">
        <div>
          <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Pages</h2>
          <p class="text-xs text-slate-400 font-mono mt-0.5">Manage your site pages</p>
        </div>
        <button
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs tracking-tight px-5 py-3 rounded-xl flex items-center gap-2 cursor-pointer shadow-md shadow-indigo-200 transition-all"
        >
          <Plus size={14} strokeWidth={2.5} />
          <span>Add New Page</span>
        </button>
      </div>

      <Show when={!loading()} fallback={
        <div class="flex items-center justify-center min-h-[200px]">
          <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
        </div>
      }>
        <div class="wp-card overflow-hidden">
          <div class="overflow-x-auto">
            <table class="wp-styled-table whitespace-nowrap">
              <thead>
                <tr>
                  <th class="w-[50%] min-w-[280px]">Title</th>
                  <th>Author</th>
                  <th>Status</th>
                  <th class="text-center">Date</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <For each={pages()} fallback={
                  <tr>
                    <td colspan="5" class="p-12 text-center text-xs font-bold text-slate-400 font-mono tracking-wider">
                      No pages found.
                    </td>
                  </tr>
                }>
                  {(page) => (
                    <tr>
                      <td>
                        <div class="space-y-1">
                          <span class="font-bold text-slate-800 text-[13px] leading-snug block hover:text-indigo-650 cursor-pointer transition-colors max-w-sm sm:max-w-md truncate" title={page.title}>
                            {page.title || '(no title)'}
                          </span>
                          <div class="flex items-center gap-2.5 text-[10px] text-slate-400 font-semibold font-mono">
                            <span>ID: {page.id}</span>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div class="flex items-center gap-2.5">
                          <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center font-extrabold text-[9px] text-white uppercase shadow-sm">
                            {page.author.slice(0, 2)}
                          </div>
                          <span class="font-bold text-slate-700 font-mono text-[11px]">{page.author}</span>
                        </div>
                      </td>
                      <td>
                        <span class="wp-badge select-none"
                              classList={{
                                'badge-published': page.status === 'Published',
                                'badge-draft': page.status === 'Draft',
                              }}
                        >
                          {page.status}
                        </span>
                      </td>
                      <td class="text-center text-xs font-semibold text-slate-500 font-mono">
                        {page.date}
                      </td>
                      <td class="text-right">
                        <button class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg border border-transparent hover:border-rose-100 transition-all cursor-pointer"
                                aria-label="Delete page"
                                title="Delete"
                        >
                          <Trash2 size={13} strokeWidth={2.5} />
                        </button>
                      </td>
                    </tr>
                  )}
                </For>
              </tbody>
            </table>
          </div>
        </div>
      </Show>
    </div>
  );
}
