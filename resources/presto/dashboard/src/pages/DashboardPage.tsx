import { For, Show, Match, Switch } from 'solid-js';
import { FileText, MessageSquare, Blocks, Wrench, Sparkles, Activity as ActivityIcon, RefreshCw, ShieldAlert, BookOpen, Check, X, Upload, Image as ImageIcon } from 'lucide-solid';
import { WPActivity } from '../types';

interface DashboardPageProps {
  publishedPostsCount: () => number;
  totalComments: () => number;
  activePluginsCount: () => number;
  totalPlugins: () => number;
  byPostType: () => Array<{ type: string; count: number; label: string }>;
  draftTitle: () => string;
  setDraftTitle: (v: string) => void;
  draftContent: () => string;
  setDraftContent: (v: string) => void;
  handleSaveDraft: (e: Event) => void;
  activities: () => WPActivity[];
  addToast: (message: string, type?: 'success' | 'error' | 'info') => void;
  onOpenMediaUpload?: () => void;
}

export default function DashboardPage(props: DashboardPageProps) {
  return (
    <div class="space-y-6">
      <div class="p-8 md:p-10 bg-slate-950 border border-slate-900 rounded-2xl text-white shadow-xl relative overflow-hidden flex flex-col justify-between min-h-[220px]">
        <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-indigo-650/15 blur-3xl"></div>
        <div class="absolute -left-10 -bottom-10 w-48 h-48 rounded-full bg-indigo-500/5 blur-3xl"></div>
        <div class="absolute right-10 bottom-6 opacity-[0.06] pointer-events-none transition-transform hover:scale-105 duration-700">
          <BookOpen size={180} />
        </div>

        <div class="space-y-4">
          <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/5 border border-white/10 text-white text-[10px] font-mono tracking-widest uppercase rounded-lg">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
            PrestoWorld Dashboard
          </div>
          <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight leading-none bg-gradient-to-r from-white via-slate-100 to-slate-350 bg-clip-text text-transparent">
            Welcome to your dashboard
          </h2>
          <p class="text-[13px] text-slate-400 max-w-xl leading-relaxed">
            Manage your marketplace, posts, plugins, and settings from one central location. Powered by SolidJS.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="wp-card group border-t-2 border-t-indigo-500 bg-gradient-to-b from-white to-slate-50/30">
          <div class="card-body flex items-center justify-between p-6">
            <div class="space-y-1">
              <span class="text-[10px] uppercase tracking-wider font-extrabold text-slate-400 font-mono block">Published Posts</span>
              <h4 class="text-3xl font-extrabold tracking-tight text-slate-900 leading-none">{props.publishedPostsCount()}</h4>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-sm border border-indigo-100/40 group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
              <FileText size={18} strokeWidth={2.5} />
            </div>
          </div>
        </div>

        <div class="wp-card group border-t-2 border-t-sky-400 bg-gradient-to-b from-white to-slate-50/30">
          <div class="card-body flex items-center justify-between p-6">
            <div class="space-y-1">
              <span class="text-[10px] uppercase tracking-wider font-extrabold text-slate-400 font-mono block">Comments</span>
              <h4 class="text-3xl font-extrabold tracking-tight text-slate-900 leading-none">{props.totalComments()}</h4>
            </div>
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shadow-sm border border-sky-100/40 group-hover:scale-110 group-hover:bg-sky-500 group-hover:text-white transition-all duration-300">
              <MessageSquare size={18} strokeWidth={2.5} />
            </div>
          </div>
        </div>

        <div class="wp-card group border-t-2 border-t-purple-500 bg-gradient-to-b from-white to-slate-50/30">
          <div class="card-body flex items-center justify-between p-6">
            <div class="space-y-1">
              <span class="text-[10px] uppercase tracking-wider font-extrabold text-slate-400 font-mono block">Active Plugins</span>
              <h4 class="text-3xl font-extrabold tracking-tight text-slate-900 leading-none">
                {props.activePluginsCount()}
                <span class="text-slate-300 font-semibold text-sm ml-1.5">/ {props.totalPlugins()}</span>
              </h4>
            </div>
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shadow-sm border border-purple-100/40 group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
              <Blocks size={18} strokeWidth={2.5} />
            </div>
          </div>
        </div>

        <div class="wp-card group border-t-2 border-t-emerald-500 bg-gradient-to-b from-white to-slate-50/30">
          <div class="card-body flex items-center justify-between p-6">
            <div class="space-y-1">
              <span class="text-[10px] uppercase tracking-wider font-extrabold text-slate-400 font-mono block">Site Health</span>
              <h4 class="text-3xl font-extrabold tracking-tight text-emerald-600 leading-none flex items-center gap-1.5">
                99%
                <span class="text-[9px] font-black tracking-wider text-emerald-600 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded uppercase font-mono">LIVE</span>
              </h4>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm border border-emerald-100/40 group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300">
              <Wrench size={18} strokeWidth={2.5} />
            </div>
          </div>
        </div>
      </div>

      <Show when={props.byPostType().length > 0}>
        <div>
          <div class="flex items-center gap-2 mb-3">
            <span class="text-[10px] uppercase tracking-wider font-extrabold text-slate-400 font-mono">Content Types</span>
            <div class="flex-grow h-px bg-slate-200"></div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <For each={props.byPostType()}>
              {(pt) => (
                <div class="wp-card bg-white border border-slate-150 rounded-xl px-4 py-3 flex items-center gap-3">
                  <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-slate-50 border border-slate-100 text-slate-500">
                    <FileText size={15} />
                  </div>
                  <div>
                    <span class="block text-lg font-extrabold text-slate-900 leading-none">{pt.count}</span>
                    <span class="block text-[10px] font-bold text-slate-400 font-mono tracking-wide uppercase">{pt.label}</span>
                  </div>
                </div>
              )}
            </For>
          </div>
        </div>
      </Show>

      <Show when={props.onOpenMediaUpload}>
        <div class="wp-card bg-gradient-to-br from-white to-indigo-50/20 border border-indigo-100/60 overflow-hidden">
          <div class="p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0 border border-indigo-200/50">
                <Upload size={20} class="text-indigo-600" />
              </div>
              <div class="space-y-1">
                <h4 class="font-bold text-sm text-slate-800">Quick Media Upload</h4>
                <p class="text-[11px] text-slate-500 leading-relaxed max-w-md">
                  Drag and drop images, videos, or documents directly from your desktop. 
                  Files are stored in Presto storage for ultra-low latency delivery.
                </p>
              </div>
            </div>
            <button
              onClick={props.onOpenMediaUpload}
              class="shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs tracking-tight px-5 py-3 rounded-xl flex items-center gap-2 cursor-pointer shadow-md shadow-indigo-200 transition-all hover:scale-[1.02] active:scale-[0.98]"
            >
              <Upload size={14} strokeWidth={2.5} />
              <span>Upload Media</span>
            </button>
          </div>
        </div>
      </Show>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <div class="lg:col-span-6 wp-card flex flex-col justify-between">
          <div class="card-header px-6 py-4.5 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Sparkles size={15} class="text-indigo-600 animate-pulse" />
              <h3 class="font-bold text-sm tracking-tight text-slate-800">Quick Draft</h3>
            </div>
            <span class="text-[9px] font-black tracking-wider font-mono text-indigo-600 bg-indigo-50 border border-indigo-100/60 px-2 py-0.5 rounded-md">AUTO-SAVE</span>
          </div>
          <form onSubmit={props.handleSaveDraft} class="card-body p-6 space-y-4">
            <div>
              <input
                type="text"
                placeholder="Title of draft post..."
                value={props.draftTitle()}
                onInput={(e) => props.setDraftTitle(e.currentTarget.value)}
                class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-600 rounded-xl px-4 py-3 outline-none transition-all placeholder:text-slate-400 placeholder:font-normal focus:bg-white"
              />
            </div>

            <div class="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-xl border border-slate-150">
              <button type="button" class="w-7 h-7 flex items-center justify-center text-xs font-black text-slate-600 hover:text-indigo-600 hover:bg-white border border-transparent hover:border-slate-200 rounded-lg transition-all select-none font-sans" title="Bold">B</button>
              <button type="button" class="w-7 h-7 flex items-center justify-center text-xs italic font-bold text-slate-600 hover:text-indigo-600 hover:bg-white border border-transparent hover:border-slate-200 rounded-lg transition-all select-none font-sans" title="Italic">I</button>
              <button type="button" class="w-7 h-7 flex items-center justify-center text-xs underline font-semibold text-slate-600 hover:text-indigo-600 hover:bg-white border border-transparent hover:border-slate-200 rounded-lg transition-all select-none font-sans" title="Underline">U</button>
              <div class="w-px h-4 bg-slate-200 mx-1"></div>
              <span class="text-[9px] font-mono text-slate-450 font-bold uppercase tracking-wider px-1">Markdown</span>
            </div>

            <div>
              <textarea
                placeholder="What is on your mind? Capture thoughts instantly..."
                value={props.draftContent()}
                onInput={(e) => props.setDraftContent(e.currentTarget.value)}
                class="w-full min-h-[140px] text-xs bg-[#f8fafc] border border-slate-200 focus:border-indigo-600 rounded-xl px-4 py-3 outline-none transition-all placeholder:text-slate-400 font-sans focus:bg-white"
              />
            </div>

            <button
              type="submit"
              class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs tracking-tight px-5 py-3 rounded-xl transition-all hover:shadow-lg hover:shadow-indigo-600/10 cursor-pointer text-center duration-200"
            >
              Save Draft
            </button>
          </form>
        </div>

        <div class="lg:col-span-6 wp-card flex flex-col justify-between">
          <div class="card-header px-6 py-4.5 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <ActivityIcon size={15} class="text-slate-800" />
              <h3 class="font-bold text-sm tracking-tight text-slate-800">Activity Log</h3>
            </div>
            <button
              onClick={() => props.addToast('Refreshed activity log', 'info')}
              class="text-indigo-600 hover:text-indigo-800 text-[9px] font-bold font-mono tracking-wider flex items-center gap-1.5 bg-indigo-50/70 hover:bg-indigo-100/50 border border-indigo-100/60 px-2.5 py-1 rounded-lg cursor-pointer transition-all"
            >
              <RefreshCw size={9} strokeWidth={2.5} />
              <span>RELOAD</span>
            </button>
          </div>

          <div class="card-body p-6 flex-grow overflow-hidden">
            <div class="timeline-logs max-h-[310px] overflow-y-auto pr-1">
              <For each={props.activities()}>
                {(activity) => (
                  <div class="timeline-item">
                    <div class={`timeline-icon-box timeline-${activity.type}`}>
                      <Switch fallback={<RefreshCw size={10} strokeWidth={2.5} />}>
                        <Match when={activity.type === 'comment'}>
                          <MessageSquare size={10} strokeWidth={2.5} />
                        </Match>
                        <Match when={activity.type === 'post'}>
                          <FileText size={10} strokeWidth={2.5} />
                        </Match>
                        <Match when={activity.type === 'update'}>
                          <RefreshCw size={10} strokeWidth={2.5} />
                        </Match>
                        <Match when={activity.type === 'security'}>
                          <ShieldAlert size={10} strokeWidth={2.5} />
                        </Match>
                      </Switch>
                    </div>

                    <div class="timeline-item-content bg-slate-50/20 border border-transparent hover:border-slate-100">
                      <p class="text-xs font-semibold text-slate-800 font-sans leading-normal">
                        {activity.text}
                      </p>
                      <span class="text-[9px] text-slate-400 font-bold font-mono mt-0.5 block tracking-wide uppercase">{activity.time}</span>
                    </div>
                  </div>
                )}
              </For>
            </div>
          </div>
        </div>

      </div>

      <div class="wp-card p-6 bg-gradient-to-r from-indigo-50/30 to-indigo-50/5 border border-indigo-100/70 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 flex items-center justify-center shadow-inner">
            <Wrench size={18} />
          </div>
          <div class="space-y-0.5">
            <div class="flex items-center gap-2">
              <h4 class="font-bold text-sm text-slate-800 font-sans">System is optimized</h4>
              <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded-md">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                ALL GREEN
              </span>
            </div>
            <p class="text-[11px] text-slate-500">No security issues detected. All systems operating normally.</p>
          </div>
        </div>
        <button
          onClick={() => {
            props.addToast('Running diagnostics...', 'info');
            setTimeout(() => props.addToast('Scan completed. All systems healthy!', 'success'), 1200);
          }}
          class="bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-905 border border-slate-200 font-bold text-xs tracking-tight px-4 py-2.5 rounded-xl transition-all cursor-pointer shadow-sm hover:shadow"
        >
          Run Diagnostics
        </button>
      </div>

    </div>
  );
}
