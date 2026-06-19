import { createSignal, For, Show, Switch, Match, createEffect } from 'solid-js';
import { 
  LayoutDashboard, 
  FileText, 
  Blocks, 
  Settings as SettingsIcon, 
  MessageSquare, 
  Plus, 
  Trash2, 
  Search, 
  Globe, 
  RefreshCw, 
  User, 
  Check, 
  X, 
  Bell, 
  ExternalLink, 
  ShieldAlert, 
  Sparkles,
  Layers,
  Wrench,
  Activity as ActivityIcon,
  BookOpen
} from 'lucide-solid';
import { initialPosts, initialPlugins, initialActivities } from './mockData';
import { WPPost, WPPlugin, WPActivity, WPToast, AdminInitialState } from './types';

declare global {
  interface Window {
    __INITIAL_STATE__?: AdminInitialState;
  }
}

const initialState = window.__INITIAL_STATE__;
const initialUser = initialState?.user ?? { name: 'Administrator', role: 'admin', avatar: null };

export default function App() {
  const [currentTab, setCurrentTab] = createSignal<'dashboard' | 'posts' | 'plugins' | 'settings'>('dashboard');
  const [posts, setPosts] = createSignal<WPPost[]>(initialPosts);
  const [plugins, setPlugins] = createSignal<WPPlugin[]>(initialPlugins);
  const [activities, setActivities] = createSignal<WPActivity[]>(initialActivities);
  const [toasts, setToasts] = createSignal<WPToast[]>([]);

  const [isMobileOpen, setIsMobileOpen] = createSignal(false);

  const [postSearch, setPostSearch] = createSignal('');
  const [postCategoryFilter, setPostCategoryFilter] = createSignal('All');
  const [pluginSearch, setPluginSearch] = createSignal('');
  const [pluginCategoryFilter, setPluginCategoryFilter] = createSignal('All');

  const [draftTitle, setDraftTitle] = createSignal('');
  const [draftContent, setDraftContent] = createSignal('');

  const [isAddPostOpen, setIsAddPostOpen] = createSignal(false);
  const [newPostTitle, setNewPostTitle] = createSignal('');
  const [newPostCategory, setNewPostCategory] = createSignal('Development');
  const [newPostStatus, setNewPostStatus] = createSignal<'Published' | 'Draft'>('Published');
  const [newPostAuthor, setNewPostAuthor] = createSignal('admin');

  const [siteTitle, setSiteTitle] = createSignal('PrestoWorld');
  const [siteTagline, setSiteTagline] = createSignal('Digital marketplace platform');
  const [siteUrl, setSiteUrl] = createSignal('https://prestoworld.org');
  const [adminEmail, setAdminEmail] = createSignal('admin@prestoworld.org');
  const [membershipOpen, setMembershipOpen] = createSignal(true);
  const [defaultRole, setDefaultRole] = createSignal('Subscriber');
  const [permalinkStructure, setPermalinkStructure] = createSignal('post-name');

  const addToast = (message: string, type: 'success' | 'error' | 'info' = 'success') => {
    const id = Math.random().toString(36).substr(2, 9);
    setToasts([...toasts(), { id, message, type }]);
    setTimeout(() => {
      setToasts(toasts().filter(t => t.id !== id));
    }, 4000);
  };

  const handleSaveDraft = (e: Event) => {
    e.preventDefault();
    if (!draftTitle().trim()) {
      addToast('Draft title is required', 'error');
      return;
    }

    const newDraft: WPPost = {
      id: posts().length + 1,
      title: draftTitle(),
      author: initialUser.name,
      category: 'Uncategorized',
      status: 'Draft',
      date: new Date().toISOString().replace('T', ' ').substr(0, 16),
      commentsCount: 0
    };

    setPosts([newDraft, ...posts()]);

    const log: WPActivity = {
      id: activities().length + 1,
      text: `Saved quick draft: "${draftTitle()}"`,
      time: 'Just now',
      type: 'post'
    };
    setActivities([log, ...activities()]);
    addToast(`Quick draft "${draftTitle()}" saved successfully`);
    setDraftTitle('');
    setDraftContent('');
  };

  const handleAddPost = (e: Event) => {
    e.preventDefault();
    if (!newPostTitle().trim()) {
      addToast('Post title is required', 'error');
      return;
    }

    const newPostItem: WPPost = {
      id: posts().length + 1,
      title: newPostTitle(),
      author: newPostAuthor() || initialUser.name,
      category: newPostCategory(),
      status: newPostStatus(),
      date: new Date().toISOString().replace('T', ' ').substr(0, 16),
      commentsCount: 0
    };

    setPosts([newPostItem, ...posts()]);

    const log: WPActivity = {
      id: activities().length + 1,
      text: `Created new post: "${newPostTitle()}" (${newPostStatus()})`,
      time: 'Just now',
      type: 'post'
    };
    setActivities([log, ...activities()]);
    addToast(`Post "${newPostTitle()}" created!`);
    setIsAddPostOpen(false);
    setNewPostTitle('');
    setNewPostCategory('Development');
    setNewPostStatus('Published');
  };

  const handleDeletePost = (id: number, title: string) => {
    setPosts(posts().filter(p => p.id !== id));
    addToast(`Post "${title}" deleted`, 'info');
  };

  const togglePostStatus = (id: number) => {
    setPosts(
      posts().map(p => {
        if (p.id === id) {
          const nextStatus = p.status === 'Published' ? 'Draft' : 'Published';
          addToast(`"${p.title}" status changed to ${nextStatus}`, 'success');
          return { ...p, status: nextStatus as 'Published' | 'Draft' };
        }
        return p;
      })
    );
  };

  const togglePlugin = (id: string, name: string) => {
    setPlugins(
      plugins().map(pl => {
        if (pl.id === id) {
          const nextState = !pl.active;
          addToast(`Plugin "${name}" ${nextState ? 'activated' : 'deactivated'}`, nextState ? 'success' : 'info');
          return { ...pl, active: nextState };
        }
        return pl;
      })
    );
  };

  const [updatingPlugins, setUpdatingPlugins] = createSignal<Record<string, boolean>>({});

  const updatePlugin = (id: string, name: string) => {
    if (updatingPlugins()[id]) return;

    setUpdatingPlugins({ ...updatingPlugins(), [id]: true });
    addToast(`Downloading updates for ${name}...`, 'info');

    setTimeout(() => {
      setPlugins(
        plugins().map(pl => {
          if (pl.id === id) {
            const currentParts = pl.version.split('.').map(Number);
            currentParts[currentParts.length - 1] += 1;
            const newVersion = currentParts.join('.');
            return {
              ...pl,
              version: newVersion,
              updateAvailable: false
            };
          }
          return pl;
        })
      );
      setUpdatingPlugins({ ...updatingPlugins(), [id]: false });
      addToast(`Plugin "${name}" updated successfully to the latest version!`, 'success');

      const log: WPActivity = {
        id: activities().length + 1,
        text: `Updated plugin: ${name}`,
        time: 'Just now',
        type: 'update'
      };
      setActivities([log, ...activities()]);
    }, 2000);
  };

  const saveSettings = (e: Event) => {
    e.preventDefault();
    addToast('Saving configuration...', 'info');
    setTimeout(() => {
      addToast('Site configuration updated successfully', 'success');
    }, 800);
  };

  const [repoQuery, setRepoQuery] = createSignal('');
  const [installingRepoId, setInstallingRepoId] = createSignal<string | null>(null);

  const mockInstallPlugin = (name: string, desc: string, cat: string) => {
    const slug = name.toLowerCase().replace(/\s+/g, '-');
    if (plugins().some(p => p.id === slug)) {
      addToast(`Plugin "${name}" is already installed`, 'error');
      return;
    }

    setInstallingRepoId(slug);
    addToast(`Installing ${name} from directory...`, 'info');

    setTimeout(() => {
      const newPl: WPPlugin = {
        id: slug,
        name,
        desc,
        version: '1.0.0',
        author: 'Ecosystem Contributor',
        active: false,
        updateAvailable: false,
        category: cat
      };
      setPlugins([...plugins(), newPl]);
      setInstallingRepoId(null);
      addToast(`"${name}" is installed successfully! Click Activate to initialize.`, 'success');

      const log: WPActivity = {
        id: activities().length + 1,
        text: `Installed plugin: ${name}`,
        time: 'Just now',
        type: 'update'
      };
      setActivities([log, ...activities()]);
    }, 1500);
  };

  const filteredPosts = () => {
    return posts().filter(p => {
      const matchesSearch = p.title.toLowerCase().includes(postSearch().toLowerCase()) || 
                            p.category.toLowerCase().includes(postSearch().toLowerCase()) ||
                            p.author.toLowerCase().includes(postSearch().toLowerCase());
      const matchesCat = postCategoryFilter() === 'All' || p.category === postCategoryFilter();
      return matchesSearch && matchesCat;
    });
  };

  const filteredPlugins = () => {
    return plugins().filter(pl => {
      const matchesSearch = pl.name.toLowerCase().includes(pluginSearch().toLowerCase()) || 
                            pl.desc.toLowerCase().includes(pluginSearch().toLowerCase());
      const matchesCat = pluginCategoryFilter() === 'All' || pl.category === pluginCategoryFilter();
      return matchesSearch && matchesCat;
    });
  };

  const activePluginsCount = () => plugins().filter(p => p.active).length;
  const updatePluginsCount = () => plugins().filter(p => p.updateAvailable).length;
  const publishedPostsCount = () => posts().filter(p => p.status === 'Published').length;
  const totalComments = () => posts().reduce((sum, p) => sum + p.commentsCount, 0);

  return (
    <div class="wp-admin-layout">
      
      <div class="wp-toast-container">
        <For each={toasts()}>
          {(toast) => (
            <div 
              class="wp-toast transition-all"
              classList={{
                'toast-success': toast.type === 'success',
                'toast-error': toast.type === 'error',
                'border-l-indigo-600': toast.type === 'info',
              }}
            >
              <div class="flex-grow flex items-center gap-2">
                <Switch>
                  <Match when={toast.type === 'success'}>
                    <div class="flex items-center justify-center text-emerald-500 w-4 h-4 bg-emerald-55 rounded-full">
                      <Check size={12} strokeWidth={3} />
                    </div>
                  </Match>
                  <Match when={toast.type === 'error'}>
                    <div class="flex items-center justify-center text-rose-500 w-4 h-4 bg-rose-50 rounded-full">
                      <X size={12} strokeWidth={3} />
                    </div>
                  </Match>
                  <Match when={toast.type === 'info'}>
                    <div class="flex items-center justify-center text-indigo-500 w-4 h-4 bg-indigo-50 rounded-full">
                      <Sparkles size={11} />
                    </div>
                  </Match>
                </Switch>
                <span>{toast.message}</span>
              </div>
              <button 
                onClick={() => setToasts(toasts().filter(t => t.id !== toast.id))} 
                class="text-slate-300 hover:text-slate-600 transition-colors p-1"
                aria-label="Close toast"
              >
                <X size={14} />
              </button>
            </div>
          )}
        </For>
      </div>

      <aside 
        class={`wp-sidebar flex flex-col justify-between fixed lg:static top-0 bottom-0 left-0 transform lg:transform-none transition-transform duration-300 ${
          isMobileOpen() ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        }`}
      >
        <div>
          <div class="sidebar-header">
            <div class="logo-icon w-9 h-9 rounded-lg flex items-center justify-center text-white font-extrabold text-[16px] tracking-tight">
              P
            </div>
            <div class="flex flex-col">
              <span class="font-bold text-sm tracking-tight text-white uppercase">PrestoWorld</span>
              <span class="text-[10px] text-slate-500 font-bold font-mono">Admin Dashboard</span>
            </div>
            <button 
              class="lg:hidden ml-auto text-slate-400 hover:text-white p-1"
              onClick={() => setIsMobileOpen(false)}
              aria-label="Close menu"
            >
              <X size={16} />
            </button>
          </div>

          <nav class="sidebar-menu">
            <span class="menu-title">Management</span>

            <div 
              class={`menu-item ${currentTab() === 'dashboard' ? 'active' : ''}`}
              onClick={() => { setCurrentTab('dashboard'); setIsMobileOpen(false); }}
            >
              <span class="icon-wrapper">
                <LayoutDashboard size={18} />
              </span>
              <span>Dashboard</span>
            </div>

            <div 
              class={`menu-item ${currentTab() === 'posts' ? 'active' : ''}`}
              onClick={() => { setCurrentTab('posts'); setIsMobileOpen(false); }}
            >
              <span class="icon-wrapper">
                <FileText size={18} />
              </span>
              <span>Posts</span>
              <span class="count-badge bg-slate-800">{posts().length}</span>
            </div>

            <div 
              class={`menu-item ${currentTab() === 'plugins' ? 'active' : ''}`}
              onClick={() => { setCurrentTab('plugins'); setIsMobileOpen(false); }}
            >
              <span class="icon-wrapper">
                <Blocks size={18} />
              </span>
              <span>Plugins</span>
              <Show when={updatePluginsCount() > 0}>
                <span class="count-badge bg-indigo-600 text-[10px] ml-auto animate-pulse">
                  {updatePluginsCount()} UP
                </span>
              </Show>
            </div>

            <span class="menu-title">Configuration</span>

            <div 
              class={`menu-item ${currentTab() === 'settings' ? 'active' : ''}`}
              onClick={() => { setCurrentTab('settings'); setIsMobileOpen(false); }}
            >
              <span class="icon-wrapper">
                <SettingsIcon size={18} />
              </span>
              <span>Settings</span>
            </div>
          </nav>
        </div>

        <div class="p-4 border-t border-slate-800 m-3 bg-[#161616] rounded-xl flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-[11px] font-mono text-indigo-400 font-bold border border-slate-600">
            {initialUser.name.slice(0, 2).toUpperCase()}
          </div>
          <div class="flex-grow min-w-0">
            <p class="text-xs font-semibold text-white truncate">{initialUser.name}</p>
            <p class="text-[9px] text-slate-500 font-mono truncate capitalize">{initialUser.role}</p>
          </div>
          <div class="text-right">
            <span class="inline-block w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
          </div>
        </div>
      </aside>

      <div class="wp-content-wrapper bg-[#f6f8fa]">
        
        <header class="wp-topbar bg-white px-6">
          <div class="flex items-center gap-3">
            <button 
              class="lg:hidden text-slate-600 hover:text-indigo-600 p-1 bg-slate-100 hover:bg-slate-200/50 rounded-lg transition-colors cursor-pointer"
              onClick={() => setIsMobileOpen(true)}
              aria-label="Open menu"
            >
              <span class="block w-5 h-0.5 bg-current mb-1"></span>
              <span class="block w-5 h-0.5 bg-current mb-1"></span>
              <span class="block w-5 h-0.5 bg-current"></span>
            </button>
            
            <div class="flex items-center gap-2">
              <span class="text-sm font-bold text-slate-800 tracking-tight hidden sm:inline-block">
                {siteTitle()}
              </span>
              <a 
                href={siteUrl()} 
                target="_blank" 
                class="flex items-center gap-1 text-[11px] font-mono text-indigo-600 hover:text-indigo-800 transition-colors bg-indigo-50/50 hover:bg-indigo-50 px-2 py-1 rounded-md border border-indigo-100 font-semibold"
                referrerpolicy="no-referrer"
              >
                <Globe size={11} />
                <span>Visit Site</span>
                <ExternalLink size={9} />
              </a>
            </div>
          </div>

          <div class="flex items-center gap-3 sm:gap-4">
            <button
              onClick={() => setIsAddPostOpen(true)}
              class="flex items-center gap-1.5 bg-slate-900 hover:bg-indigo-600 text-white font-semibold text-xs tracking-tight px-3 py-2 rounded-lg transition-colors cursor-pointer shadow-sm shadow-slate-200"
            >
              <Plus size={13} strokeWidth={2.5} />
              <span class="hidden sm:inline">Add Post</span>
            </button>

            <div class="h-4 w-px bg-slate-200"></div>

            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 cursor-pointer transition-colors border border-slate-150 relative">
                <Bell size={14} />
                <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-indigo-650 rounded-full animate-ping"></span>
              </div>
            </div>
          </div>
        </header>

        <main class="wp-main-content">
          <Switch>
            <Match when={currentTab() === 'dashboard'}>
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
                        <h4 class="text-3xl font-extrabold tracking-tight text-slate-900 leading-none">{publishedPostsCount()}</h4>
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
                        <h4 class="text-3xl font-extrabold tracking-tight text-slate-900 leading-none">{totalComments()}</h4>
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
                          {activePluginsCount()}
                          <span class="text-slate-300 font-semibold text-sm ml-1.5">/ {plugins().length}</span>
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

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                  
                  <div class="lg:col-span-6 wp-card flex flex-col justify-between">
                    <div class="card-header px-6 py-4.5 flex items-center justify-between">
                      <div class="flex items-center gap-2">
                        <Sparkles size={15} class="text-indigo-600 animate-pulse" />
                        <h3 class="font-bold text-sm tracking-tight text-slate-800">Quick Draft</h3>
                      </div>
                      <span class="text-[9px] font-black tracking-wider font-mono text-indigo-600 bg-indigo-50 border border-indigo-100/60 px-2 py-0.5 rounded-md">AUTO-SAVE</span>
                    </div>
                    <form onSubmit={handleSaveDraft} class="card-body p-6 space-y-4">
                      <div>
                        <input
                          type="text"
                          placeholder="Title of draft post..."
                          value={draftTitle()}
                          onInput={(e) => setDraftTitle(e.currentTarget.value)}
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
                          value={draftContent()}
                          onInput={(e) => setDraftContent(e.currentTarget.value)}
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
                        onClick={() => addToast('Refreshed activity log', 'info')}
                        class="text-indigo-600 hover:text-indigo-800 text-[9px] font-bold font-mono tracking-wider flex items-center gap-1.5 bg-indigo-50/70 hover:bg-indigo-100/50 border border-indigo-100/60 px-2.5 py-1 rounded-lg cursor-pointer transition-all"
                      >
                        <RefreshCw size={9} strokeWidth={2.5} />
                        <span>RELOAD</span>
                      </button>
                    </div>
                    
                    <div class="card-body p-6 flex-grow overflow-hidden">
                      <div class="timeline-logs max-h-[310px] overflow-y-auto pr-1">
                        <For each={activities()}>
                          {(activity) => (
                            <div class="timeline-item">
                              <div class={`timeline-icon-box timeline-${activity.type}`}>
                                <Switch>
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
                      addToast('Running diagnostics...', 'info');
                      setTimeout(() => addToast('Scan completed. All systems healthy!', 'success'), 1200);
                    }}
                    class="bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-905 border border-slate-200 font-bold text-xs tracking-tight px-4 py-2.5 rounded-xl transition-all cursor-pointer shadow-sm hover:shadow"
                  >
                    Run Diagnostics
                  </button>
                </div>

              </div>
            </Match>

            <Match when={currentTab() === 'posts'}>
              <div class="space-y-6">
                
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-white p-5 border border-slate-150/60 rounded-2xl shadow-sm">
                  
                  <div class="relative flex-grow max-w-md">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                      <Search size={14} />
                    </span>
                    <input
                      type="text"
                      placeholder="Search posts by title, category or tags..."
                      value={postSearch()}
                      onInput={(e) => setPostSearch(e.currentTarget.value)}
                      class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-650 rounded-xl pl-10 pr-4 py-3 outline-none transition-all focus:bg-white"
                    />
                  </div>

                  <div class="flex items-center gap-3 flex-wrap">
                    <select
                      value={postCategoryFilter()}
                      onChange={(e) => setPostCategoryFilter(e.target.value)}
                      class="text-xs font-semibold bg-[#f8fafc] border border-slate-200 hover:bg-slate-50 px-3.5 py-3 rounded-xl outline-none cursor-pointer transition-colors"
                    >
                      <option value="All">All Categories</option>
                      <option value="Development">Development</option>
                      <option value="Performance">Performance</option>
                      <option value="E-Commerce">E-Commerce</option>
                      <option value="Design">Design</option>
                      <option value="Security">Security</option>
                    </select>

                    <button
                      onClick={() => setIsAddPostOpen(true)}
                      class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs tracking-tight px-4.5 py-3 rounded-xl flex items-center gap-1.5 cursor-pointer shadow-md shadow-indigo-150/40 transition-all hover:scale-[1.02] duration-200"
                    >
                      <Plus size={14} strokeWidth={2.5} />
                      <span>Write New Post</span>
                    </button>
                  </div>
                </div>

                <div class="wp-card overflow-hidden">
                  <div class="overflow-x-auto">
                    <table class="wp-styled-table whitespace-nowrap">
                      <thead>
                        <tr>
                          <th class="w-[50%] min-w-[280px]">Title</th>
                          <th>Author</th>
                          <th>Category</th>
                          <th>Status</th>
                          <th class="text-center">Date</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <For each={filteredPosts()} fallback={
                          <tr>
                            <td colspan="6" class="p-12 text-center text-xs font-bold text-slate-400 font-mono tracking-wider">
                              No matching posts found.
                            </td>
                          </tr>
                        }>
                          {(post) => (
                            <tr>
                              <td>
                                <div class="space-y-1">
                                  <span class="font-bold text-slate-800 text-[13px] leading-snug block hover:text-indigo-650 cursor-pointer transition-colors max-w-sm sm:max-w-md truncate" title={post.title}>
                                    {post.title}
                                  </span>
                                  <div class="flex items-center gap-2.5 text-[10px] text-slate-400 font-semibold font-mono">
                                    <span>ID: {post.id}</span>
                                    <span class="text-slate-205">&bull;</span>
                                    <span class="flex items-center gap-1">
                                      <MessageSquare size={9} strokeWidth={2.5} class="text-slate-400" />
                                      {post.commentsCount} Comments
                                    </span>
                                  </div>
                                </div>
                              </td>

                              <td>
                                <div class="flex items-center gap-2.5">
                                  <div class="w-6 h-6 rounded-full flex items-center justify-center font-extrabold text-[9px] text-white uppercase shadow-sm"
                                       classList={{
                                         'bg-[#4ade80]': post.author === 'admin',
                                         'bg-indigo-500': post.author === 'editor',
                                         'bg-[#38bdf8]': post.author === 'contributor',
                                         'bg-purple-500': post.author !== 'admin' && post.author !== 'editor' && post.author !== 'contributor'
                                       }}
                                  >
                                    {post.author.slice(0, 2)}
                                  </div>
                                  <span class="font-bold text-slate-700 font-mono text-[11px]">{post.author}</span>
                                </div>
                              </td>

                              <td>
                                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded border capitalize shadow-sm tracking-wide"
                                      classList={{
                                        'bg-indigo-50 text-indigo-700 border-indigo-100/70': post.category === 'Development',
                                        'bg-emerald-50 text-emerald-700 border-emerald-100/70': post.category === 'Performance',
                                        'bg-pink-50 text-pink-700 border-pink-100/70': post.category === 'E-Commerce',
                                        'bg-purple-50 text-purple-700 border-purple-100/70': post.category === 'Design',
                                        'bg-amber-50 text-amber-700 border-amber-100/70': post.category === 'Security',
                                        'bg-slate-50 text-slate-600 border-slate-200': !['Development', 'Performance', 'E-Commerce', 'Design', 'Security'].includes(post.category)
                                      }}
                                >
                                  {post.category}
                                </span>
                              </td>

                              <td>
                                <button 
                                  onClick={() => togglePostStatus(post.id)}
                                  class="focus:outline-none cursor-pointer hover:scale-[1.03] active:scale-[0.98] transition-transform"
                                  title="Toggle status"
                                >
                                  <span 
                                    class="wp-badge select-none"
                                    classList={{
                                      'badge-published': post.status === 'Published',
                                      'badge-draft': post.status === 'Draft',
                                      'badge-scheduled': post.status === 'Scheduled'
                                    }}
                                  >
                                    {post.status}
                                  </span>
                                </button>
                              </td>

                              <td class="text-center text-xs font-semibold text-slate-500 font-mono">
                                {post.date}
                              </td>

                              <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                  <button
                                    onClick={() => togglePostStatus(post.id)}
                                    class="text-[11px] font-bold px-3 py-1.5 text-slate-600 hover:text-indigo-600 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-lg transition-all cursor-pointer shadow-sm active:scale-95"
                                    title="Toggle Status"
                                  >
                                    Toggle
                                  </button>
                                  <button
                                    onClick={() => handleDeletePost(post.id, post.title)}
                                    class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg border border-transparent hover:border-rose-100 transition-all cursor-pointer active:scale-90"
                                    aria-label="Delete post"
                                  >
                                    <Trash2 size={13} strokeWidth={2.5} />
                                  </button>
                                </div>
                              </td>

                            </tr>
                          )}
                        </For>
                      </tbody>
                    </table>
                  </div>
                </div>

              </div>
            </Match>

            <Match when={currentTab() === 'plugins'}>
              <div class="space-y-6">
                
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white p-5 border border-slate-150/60 rounded-2xl shadow-sm">
                  
                  <div class="relative flex-grow max-w-md">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                      <Search size={14} />
                    </span>
                    <input
                      type="text"
                      placeholder="Search plugins..."
                      value={pluginSearch()}
                      onInput={(e) => setPluginSearch(e.currentTarget.value)}
                      class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-650 rounded-xl pl-10 pr-4 py-3 outline-none transition-all focus:bg-white"
                    />
                  </div>

                  <div class="flex items-center gap-3">
                    <select
                      value={pluginCategoryFilter()}
                      onChange={(e) => setPluginCategoryFilter(e.target.value)}
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
                  <For each={filteredPlugins()}>
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
                                onClick={() => updatePlugin(plugin.id, plugin.name)}
                                class="text-[9px] font-bold font-mono bg-amber-600 hover:bg-amber-700 text-white px-2 py-1 rounded-lg transition-colors flex items-center gap-1 cursor-pointer shadow border border-amber-700"
                              >
                                <Show when={updatingPlugins()[plugin.id]} fallback={
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
                              onClick={() => togglePlugin(plugin.id, plugin.name)}
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
                          onClick={() => mockInstallPlugin("Analytics Engine Pro", "Advanced analytics with real-time tracking, conversion funnels, and performance metrics.", "Performance")}
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
                          onClick={() => mockInstallPlugin("Security Guard Elite", "Enterprise-grade firewall, brute force protection, and threat monitoring.", "Security")}
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
            </Match>

            <Match when={currentTab() === 'settings'}>
              <form onSubmit={saveSettings} class="space-y-6 max-w-4xl">
                
                <div class="wp-card">
                  <div class="card-header border-b border-slate-100 bg-slate-50/20 px-6 py-4.5">
                    <h3 class="font-extrabold text-sm tracking-tight text-slate-800">General Settings</h3>
                  </div>
                  <div class="card-body p-6 divide-y divide-slate-100">
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5 first:pt-0">
                      <label for="site-title" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                        Site Title
                      </label>
                      <div class="md:col-span-3">
                        <input
                          id="site-title"
                          type="text"
                          value={siteTitle()}
                          onInput={(e) => setSiteTitle(e.currentTarget.value)}
                          class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-600 hover:bg-slate-50 focus:bg-white rounded-xl px-4 py-3 outline-none transition-all"
                        />
                      </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5">
                      <label for="site-tagline" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                        Tagline
                      </label>
                      <div class="md:col-span-3">
                        <input
                          id="site-tagline"
                          type="text"
                          value={siteTagline()}
                          onInput={(e) => setSiteTagline(e.currentTarget.value)}
                          class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-600 hover:bg-slate-50 focus:bg-white rounded-xl px-4 py-3 outline-none transition-all"
                        />
                      </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5">
                      <label for="site-email" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                        Admin Email
                      </label>
                      <div class="md:col-span-3">
                        <input
                          id="site-email"
                          type="email"
                          value={adminEmail()}
                          onInput={(e) => setAdminEmail(e.currentTarget.value)}
                          class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-600 hover:bg-slate-50 focus:bg-white rounded-xl px-4 py-3 outline-none transition-all"
                        />
                      </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5 last:pb-0">
                      <label for="site-url" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                        Site URL
                      </label>
                      <div class="md:col-span-3">
                        <input
                          id="site-url"
                          type="text"
                          value={siteUrl()}
                          onInput={(e) => setSiteUrl(e.currentTarget.value)}
                          class="w-full text-xs font-bold bg-[#f8fafc] border border-slate-200 focus:border-indigo-600 hover:bg-slate-50 focus:bg-white rounded-xl px-4 py-3 outline-none transition-all font-mono text-indigo-700 tracking-tight"
                        />
                      </div>
                    </div>

                  </div>
                </div>

                <div class="wp-card">
                  <div class="card-header border-b border-slate-100 bg-slate-50/20 px-6 py-4.5">
                    <h3 class="font-extrabold text-sm tracking-tight text-slate-800">Membership</h3>
                  </div>
                  <div class="card-body p-6 divide-y divide-slate-100">
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5 first:pt-0">
                      <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                        Registration
                      </span>
                      <div class="md:col-span-3 flex items-center gap-3">
                        <label class="wp-toggle">
                          <input
                            type="checkbox"
                            checked={membershipOpen()}
                            onChange={(e) => setMembershipOpen(e.currentTarget.checked)}
                          />
                          <span class="toggle-slider"></span>
                        </label>
                        <span class="text-xs font-semibold text-slate-600">Allow anyone to register</span>
                      </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5 last:pb-0">
                      <label for="default-role" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                        Default Role
                      </label>
                      <div class="md:col-span-3">
                        <select
                          id="default-role"
                          value={defaultRole()}
                          onChange={(e) => setDefaultRole(e.target.value)}
                          class="text-xs font-semibold bg-[#f8fafc] border border-slate-200 hover:bg-slate-50 px-3.5 py-2.5 rounded-xl outline-none cursor-pointer transition-colors"
                        >
                          <option value="Subscriber">Subscriber</option>
                          <option value="Contributor">Contributor</option>
                          <option value="Author">Author</option>
                          <option value="Editor">Editor</option>
                          <option value="Administrator">Administrator</option>
                        </select>
                      </div>
                    </div>

                  </div>
                </div>

                <div class="wp-card">
                  <div class="card-header border-b border-slate-100 bg-slate-50/20 px-6 py-4.5">
                    <h3 class="font-extrabold text-sm tracking-tight text-slate-800">Permalinks</h3>
                  </div>
                  <div class="card-body p-6 space-y-5">
                    <p class="text-xs text-slate-500 leading-relaxed font-semibold max-w-xl">
                      Configure URL structures for your content. Clean URLs improve SEO and discoverability.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-2">
                      <label 
                        class="flex items-start gap-3.5 p-4 border rounded-xl hover:bg-slate-55 relative cursor-pointer overflow-hidden transition-all duration-200"
                        classList={{
                          'border-indigo-650 bg-indigo-50/10 ring-1 ring-indigo-100': permalinkStructure() === 'plain',
                          'border-slate-200 bg-white hover:border-slate-300': permalinkStructure() !== 'plain'
                        }}
                      >
                        <input
                          type="radio"
                          name="permalink-choice"
                          checked={permalinkStructure() === 'plain'}
                          onChange={() => setPermalinkStructure('plain')}
                          class="mt-1 accent-indigo-600 scale-105 cursor-pointer"
                        />
                        <div class="space-y-0.5 select-none">
                          <span class="text-xs font-bold text-slate-900 block">Plain</span>
                          <span class="text-[10px] text-slate-500 font-mono block font-semibold">?p=123</span>
                        </div>
                      </label>

                      <label 
                        class="flex items-start gap-3.5 p-4 border rounded-xl hover:bg-slate-55 relative cursor-pointer overflow-hidden transition-all duration-200"
                        classList={{
                          'border-indigo-650 bg-indigo-50/10 ring-1 ring-indigo-100': permalinkStructure() === 'post-name',
                          'border-slate-200 bg-white hover:border-slate-300': permalinkStructure() !== 'post-name'
                        }}
                      >
                        <input
                          type="radio"
                          name="permalink-choice"
                          checked={permalinkStructure() === 'post-name'}
                          onChange={() => setPermalinkStructure('post-name')}
                          class="mt-1 accent-indigo-600 scale-105 cursor-pointer"
                        />
                        <div class="space-y-0.5 select-none">
                          <span class="text-xs font-bold text-slate-900 block flex items-center gap-1.5">
                            Post Name
                            <span class="text-[9px] font-bold px-1 rounded bg-indigo-100 text-indigo-700">RECOMMENDED</span>
                          </span>
                          <span class="text-[10px] text-indigo-650 font-mono block font-semibold">/sample-post/</span>
                        </div>
                      </label>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-end pt-3">
                  <button
                    type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs tracking-tight px-6 py-3.5 rounded-xl transition-all cursor-pointer shadow-md shadow-indigo-100/40 hover:scale-[1.02] active:scale-95 duration-200"
                  >
                    Save Changes
                  </button>
                </div>

              </form>
            </Match>
          </Switch>
        </main>

        <footer class="mt-auto px-6 py-4 bg-white border-t border-slate-200 text-center text-[10px] uppercase tracking-[0.2em] font-mono text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-2">
          <span>&copy; PrestoWorld &bull; 2026</span>
          <span class="normal-case leading-none tracking-normal font-sans font-medium text-[11px]">Powered by SolidJS</span>
        </footer>

      </div>

      <Show when={isAddPostOpen()}>
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] z-100 flex items-center justify-center p-4">
          <div class="bg-white rounded-2xl w-full max-w-md border border-slate-200 shadow-2xl overflow-hidden animate-slideIn">
            
            <div class="px-6 py-4 border-b border-slate-150 flex items-center justify-between bg-slate-50/50">
              <span class="font-bold text-sm tracking-tight text-slate-800 flex items-center gap-1.5">
                <Sparkles size={14} class="text-indigo-600" /> New Post
              </span>
              <button 
                onClick={() => setIsAddPostOpen(false)} 
                class="text-slate-400 hover:text-slate-705 p-1 bg-slate-100 hover:bg-slate-200/55 rounded-full transition-colors cursor-pointer"
                aria-label="Close dialog"
              >
                <X size={14} />
              </button>
            </div>

            <form onSubmit={handleAddPost} class="p-6 space-y-4">
              
              <div class="space-y-1.5">
                <label for="post-title" class="text-xs font-bold text-slate-450 uppercase tracking-wider text-slate-500">
                  Post Title
                </label>
                <input
                  id="post-title"
                  type="text"
                  placeholder="e.g. Getting Started with Your Marketplace"
                  value={newPostTitle()}
                  onInput={(e) => setNewPostTitle(e.currentTarget.value)}
                  class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 focus:border-indigo-600 rounded-lg px-4 py-2.5 outline-none transition-all focus:bg-white"
                  required
                />
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label for="post-cat" class="text-xs font-bold text-slate-450 uppercase tracking-wider text-slate-500">
                    Category
                  </label>
                  <select
                    id="post-cat"
                    value={newPostCategory()}
                    onChange={(e) => setNewPostCategory(e.target.value)}
                    class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 hover:bg-slate-100 px-3 py-2.5 rounded-lg outline-none cursor-pointer transition-colors"
                  >
                    <option value="Development">Development</option>
                    <option value="Performance">Performance</option>
                    <option value="E-Commerce">E-Commerce</option>
                    <option value="Design">Design</option>
                    <option value="Security">Security</option>
                  </select>
                </div>

                <div class="space-y-1.5">
                  <label for="post-status" class="text-xs font-bold text-slate-450 uppercase tracking-wider text-slate-500">
                    Status
                  </label>
                  <select
                    id="post-status"
                    value={newPostStatus()}
                    onChange={(e) => setNewPostStatus(e.target.value as 'Published' | 'Draft')}
                    class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 hover:bg-slate-100 px-3 py-2.5 rounded-lg outline-none cursor-pointer transition-colors"
                  >
                    <option value="Published">Publish Immediately</option>
                    <option value="Draft">Save as Draft</option>
                  </select>
                </div>
              </div>

              <div class="space-y-1.5">
                <label for="post-author" class="text-xs font-bold text-slate-450 uppercase tracking-wider text-slate-500">
                  Author
                </label>
                <input
                  id="post-author"
                  type="text"
                  value={newPostAuthor()}
                  onInput={(e) => setNewPostAuthor(e.currentTarget.value)}
                  class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 focus:border-indigo-600 rounded-lg px-4 py-2.5 outline-none transition-all focus:bg-white"
                  placeholder="admin"
                />
              </div>

              <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 mt-4 h-11">
                <button
                  type="button"
                  onClick={() => setIsAddPostOpen(false)}
                  class="text-xs font-semibold px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="text-xs font-bold bg-[#3858e9] hover:bg-indigo-700 text-white px-5 py-2 rounded-lg transition-colors cursor-pointer"
                >
                  Publish
                </button>
              </div>

            </form>

          </div>
        </div>
      </Show>

    </div>
  );
}
