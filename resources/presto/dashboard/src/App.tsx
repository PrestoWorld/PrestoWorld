import { createSignal, For, Show, Switch, Match, lazy, Suspense, onMount, onCleanup, type Component } from 'solid-js';
import { 
  LayoutDashboard,
  FileText,
  Puzzle,
  Settings,
  Palette,
  Plus,
  Globe,
  Bell,
  ExternalLink,
  Sparkles,
  X,
  Check,
  Upload,
  ImageIcon,
} from 'lucide-solid';
import { WPPost, WPPlugin, WPTheme, WPActivity, WPToast, AdminInitialState, AdminMenuItem, AdminMenuSection, AdminBarItem, DashboardWidgetDefinition } from './types';
import { fetchPosts, fetchPlugins, fetchThemes, fetchActivities, fetchStats, type DashboardStats } from './api';

const DashboardPage = lazy(() => import('./pages/DashboardPage'));
const PostsPage = lazy(() => import('./pages/PostsPage'));
const PostEditorPage = lazy(() => import('./pages/PostEditorPage'));
const PagesPage = lazy(() => import('./pages/PagesPage'));
const PluginsPage = lazy(() => import('./pages/PluginsPage'));
const ThemesPage = lazy(() => import('./pages/ThemesPage'));
const UsersPage = lazy(() => import('./pages/UsersPage'));
const MediaPage = lazy(() => import('./pages/MediaPage'));
const CommentsPage = lazy(() => import('./pages/CommentsPage'));
const ToolsPage = lazy(() => import('./pages/ToolsPage'));
const SettingsPage = lazy(() => import('./pages/SettingsPage'));
const GenericPage = lazy(() => import('./pages/GenericPage'));
const MediaUploadModal = lazy(() => import('./components/MediaUploadModal'));

declare global {
  interface Window {
    __INITIAL_STATE__?: AdminInitialState;
  }
}

const initialState = window.__INITIAL_STATE__;
const initialUser = initialState?.user ?? { name: 'Administrator', role: 'admin', avatar: null };
const initialScreens = initialState?.screens ?? [
  { id: 'dashboard', title: 'Dashboard', position: 0 },
  { id: 'posts',     title: 'Posts',     position: 10 },
  { id: 'post-new',  title: 'New Post',  position: 11 },
  { id: 'post',      title: 'Edit Post', position: 12 },
  { id: 'themes',    title: 'Themes',    position: 15 },
  { id: 'plugins',   title: 'Plugins',   position: 20 },
  { id: 'settings',  title: 'Settings',  position: 30 },
];
const initialMenuSections = initialState?.menuSections ?? [
  {
    id: 'management', title: 'Management', priority: 10,
    items: [
      { id: 'dashboard-item', screenId: 'dashboard', label: 'Dashboard', icon: 'LayoutDashboard' },
      { id: 'posts-item',     screenId: 'posts',     label: 'Posts',     icon: 'FileText' },
      { id: 'post-new-item',  screenId: 'post-new',  label: 'Add New Post', icon: 'Plus' },
      { id: 'pages-item',     screenId: 'edit-pages', label: 'Pages',    icon: 'FileText' },
    ],
  },
  {
    id: 'appearance', title: 'Appearance', priority: 15,
    items: [
      { id: 'themes-item', screenId: 'themes', label: 'Themes', icon: 'Palette' },
    ],
  },
  {
    id: 'configuration', title: 'Configuration', priority: 20,
    items: [
      { id: 'plugins-item',   screenId: 'plugins',   label: 'Plugins',   icon: 'Puzzle' },
      { id: 'settings-item', screenId: 'settings', label: 'Settings', icon: 'Settings' },
    ],
  },
];
const initialAdminBar = initialState?.adminBar ?? {
  items: [
    { id: 'new-post',       label: 'New Post',       icon: 'Plus',  type: 'button' },
    { id: 'notifications',  label: 'Notifications',  icon: 'Bell',  type: 'notification', badge: 3 },
  ],
};
const initialScreenOptions = initialState?.screenOptions ?? [];

const ICON_MAP: Record<string, Component<{ size?: number }>> = {
  LayoutDashboard,
  FileText,
  Puzzle,
  Settings,
  Palette,
  Plus,
  Globe,
  Bell,
  ExternalLink,
  Sparkles,
  X,
  Check,
};

function resolveIcon(name?: string): Component<{ size?: number }> {
  if (!name) return () => null;
  return ICON_MAP[name] ?? (() => null);
}

function hashToScreen(hash: string): string {
  const cleaned = hash.replace(/^#\/+/, '').replace(/\/+$/, '');
  if (cleaned && initialScreens.some(s => s.id === cleaned)) return cleaned;
  // Handle #/post/123
  const parts = cleaned.split('/');
  if (parts.length === 2 && parts[0] === 'post' && /^\d+$/.test(parts[1])) return 'post';
  return initialScreens[0]?.id ?? 'dashboard';
}

function hashToPostId(hash: string): number {
  const cleaned = hash.replace(/^#\/+/, '').replace(/\/+$/, '');
  const parts = cleaned.split('/');
  if (parts.length === 2 && parts[0] === 'post' && /^\d+$/.test(parts[1])) return parseInt(parts[1], 10);
  return 0;
}

export default function App() {
  const [screenId, setScreenId] = createSignal<string>(hashToScreen(window.location.hash));
  const [currentPostId, setCurrentPostId] = createSignal<number>(hashToPostId(window.location.hash));

  onMount(() => {
    const onHashChange = () => {
      setScreenId(hashToScreen(window.location.hash));
      setCurrentPostId(hashToPostId(window.location.hash));
    };
    window.addEventListener('hashchange', onHashChange);
    onCleanup(() => window.removeEventListener('hashchange', onHashChange));

    Promise.all([
      fetchPosts().then(setPosts).catch(() => {}),
      fetchPlugins().then(setPlugins).catch(() => {}),
      fetchThemes().then(setThemes).catch(() => {}),
      fetchActivities().then(setActivities).catch(() => {}),
      fetchStats().then(setStats).catch(() => {}),
    ]).finally(() => setLoading(false));
  });

  const goTo = (screen: string, extra?: string) => {
    if (extra) {
      window.location.hash = `#/${screen}/${extra}`;
    } else {
      window.location.hash = screen === initialScreens[0]?.id ? '#' : `#/${screen}`;
    }
    setIsMobileOpen(false);
  };

  const [posts, setPosts] = createSignal<WPPost[]>([]);
  const [plugins, setPlugins] = createSignal<WPPlugin[]>([]);
  const [activities, setActivities] = createSignal<WPActivity[]>([]);
  const [toasts, setToasts] = createSignal<WPToast[]>([]);
  const [loading, setLoading] = createSignal(true);
  const [stats, setStats] = createSignal<DashboardStats>({ posts: { total: 0, published: 0, draft: 0 }, plugins: { total: 0, active: 0, inactive: 0 }, byPostType: [] });

  const [isMobileOpen, setIsMobileOpen] = createSignal(false);
  const [isMediaUploadOpen, setIsMediaUploadOpen] = createSignal(false);

  const [postSearch, setPostSearch] = createSignal('');
  const [postCategoryFilter, setPostCategoryFilter] = createSignal('All');
  const [themes, setThemes] = createSignal<WPTheme[]>([]);
  const [activatingTheme, setActivatingTheme] = createSignal<string | null>(null);

  const activeTheme = () => themes().find(t => t.is_active);

  const activateTheme = (directory: string) => {
    setActivatingTheme(directory);
    fetch(`/api/admin/themes/activate`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ theme: directory }),
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setThemes(themes().map(t => ({ ...t, is_active: t.directory === directory })));
          addToast(`Theme "${data.name}" activated successfully`);
        } else {
          addToast(data.error || 'Failed to activate theme', 'error');
        }
      })
      .catch(() => addToast('Failed to activate theme', 'error'))
      .finally(() => setActivatingTheme(null));
  };

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

  const mockInstallPlugin = (name: string, desc: string, cat: string) => {
    const slug = name.toLowerCase().replace(/\s+/g, '-');
    if (plugins().some(p => p.id === slug)) {
      addToast(`Plugin "${name}" is already installed`, 'error');
      return;
    }

    setUpdatingPlugins({ ...updatingPlugins(), [slug]: true });
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
      setUpdatingPlugins({ ...updatingPlugins(), [slug]: false });
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

  const publishedPostsCount = () => stats().posts.published;
  const activePluginsCount = () => stats().plugins.active;
  const updatePluginsCount = () => plugins().filter(p => p.updateAvailable).length;
  const totalComments = () => posts().reduce((sum, p) => sum + p.commentsCount, 0);

  return (
    <div class="wp-admin-layout" data-screen-id={screenId()}>
      
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
            <For each={initialMenuSections}>
              {(section) => {
                const firstItem = section.items[0];
                if (!firstItem) return null;
                const parentIcon = () => resolveIcon(firstItem.icon || section.icon);
                const parentScreenId = section.screenId || firstItem.screenId;
                const hasChildren = section.items.length > 1;
                const anyChildActive = () => section.items.some(item => screenId() === item.screenId);
                const [expanded, setExpanded] = createSignal(anyChildActive());
                const wrapperRef = (el: HTMLElement | null) => {
                  if (!el || !hasChildren) return;
                };

                return (
                  <div
                    ref={wrapperRef}
                    style={hasChildren ? { position: 'relative' } : undefined}
                    onMouseEnter={() => hasChildren && setExpanded(true)}
                    onMouseLeave={() => hasChildren && setExpanded(false)}
                  >
                    <button
                      class={`menu-item w-full text-left ${hasChildren ? 'menu-parent' : ''} ${anyChildActive() ? 'active' : ''}`}
                      onClick={() => goTo(parentScreenId)}
                    >
                      <span class="icon-wrapper">
                        <parentIcon size={18} />
                      </span>
                      <span class="menu-parent-label">{section.title || firstItem.label}</span>
                      <Show when={anyChildActive()}>
                        <span class="active-indicator" />
                      </Show>
                      <Show when={hasChildren}>
                        <span class="submenu-arrow" data-open={expanded()}>&#9662;</span>
                      </Show>
                    </button>
                    <Show when={hasChildren}>
                      <div class="submenu-items" data-show={expanded()}>
                        <For each={section.items}>
                          {(item) => {
                            const Icon = resolveIcon(item.icon);
                            const isChildActive = () => screenId() === item.screenId;
                            return (
                              <button
                                class={`menu-item sub-menu-item w-full text-left ${isChildActive() ? 'active' : ''}`}
                                onClick={() => goTo(item.screenId)}
                              >
                                <span class="icon-wrapper">
                                  <Icon size={14} />
                                </span>
                                <span>{item.label}</span>
                              </button>
                            );
                          }}
                        </For>
                      </div>
                    </Show>
                  </div>
                );
              }}
            </For>
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
              onClick={() => setIsMediaUploadOpen(true)}
              class="flex items-center gap-1.5 text-indigo-600 hover:text-white bg-indigo-50 hover:bg-indigo-600 font-semibold text-xs tracking-tight px-3 py-2 rounded-lg transition-all cursor-pointer shadow-sm border border-indigo-100 hover:border-indigo-600"
            >
              <Upload size={13} strokeWidth={2.5} />
              <span class="hidden sm:inline">Upload</span>
            </button>

            <For each={initialAdminBar.items}>
              {(barItem) => {
                const BarIcon = resolveIcon(barItem.icon);
                const isNotif = barItem.type === 'notification';
                return (
                  <>
                    <Show when={barItem.type === 'button'}>
                      <button
                        onClick={() => { if (barItem.id === 'new-post') setIsAddPostOpen(true); }}
                        class="flex items-center gap-1.5 bg-slate-900 hover:bg-indigo-600 text-white font-semibold text-xs tracking-tight px-3 py-2 rounded-lg transition-colors cursor-pointer shadow-sm shadow-slate-200"
                      >
                        <BarIcon size={13} strokeWidth={2.5} />
                        <span class="hidden sm:inline">{barItem.label}</span>
                      </button>
                    </Show>
                    <Show when={isNotif}>
                      <div class="h-4 w-px bg-slate-200"></div>
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 cursor-pointer transition-colors border border-slate-150 relative">
                          <BarIcon size={14} />
                          <Show when={barItem.badge}>
                            <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-indigo-650 rounded-full animate-ping"></span>
                          </Show>
                        </div>
                      </div>
                    </Show>
                  </>
                );
              }}
            </For>
            <div class="h-5 w-px bg-slate-200"></div>
            <div class="flex items-center gap-2 pl-1">
              <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-700 uppercase border border-indigo-200">
                {initialUser.name.slice(0, 2)}
              </div>
              <span class="hidden sm:inline text-xs font-semibold text-slate-700">{initialUser.name}</span>
            </div>
          </div>
        </header>

        <main class="wp-main-content" data-screen-id={screenId()}>
          <Show when={loading()} fallback={
            <Suspense fallback={
              <div class="flex items-center justify-center min-h-[400px]">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-8 h-8 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                  <span class="text-xs font-mono font-bold text-slate-400 tracking-widest uppercase">Loading</span>
                </div>
              </div>
            }>
            <Switch>
              <Match when={screenId() === 'dashboard'}>
                <DashboardPage
                  publishedPostsCount={publishedPostsCount}
                  totalComments={totalComments}
                  activePluginsCount={activePluginsCount}
                  totalPlugins={() => plugins().length}
                  byPostType={() => stats().byPostType ?? []}
                  draftTitle={draftTitle}
                  setDraftTitle={setDraftTitle}
                  draftContent={draftContent}
                  setDraftContent={setDraftContent}
                  handleSaveDraft={handleSaveDraft}
                  activities={activities}
                  addToast={addToast}
                  onOpenMediaUpload={() => setIsMediaUploadOpen(true)}
                />
              </Match>

              <Match when={screenId() === 'posts'}>
                <PostsPage
                  postSearch={postSearch}
                  setPostSearch={setPostSearch}
                  postCategoryFilter={postCategoryFilter}
                  setPostCategoryFilter={setPostCategoryFilter}
                  filteredPosts={filteredPosts}
                  togglePostStatus={togglePostStatus}
                  handleDeletePost={handleDeletePost}
                  setIsAddPostOpen={setIsAddPostOpen}
                  goTo={goTo}
                />
              </Match>

              <Match when={screenId() === 'post-new' || screenId() === 'post'}>
                <PostEditorPage screenId={screenId} postId={currentPostId} />
              </Match>

              <Match when={screenId() === 'edit-pages'}>
                <PagesPage />
              </Match>

              <Match when={screenId() === 'themes'}>
                <ThemesPage
                  themes={themes}
                  activeTheme={activeTheme}
                  activating={activatingTheme()}
                  onActivate={activateTheme}
                />
              </Match>

              <Match when={screenId() === 'plugins'}>
                <PluginsPage
                  pluginSearch={pluginSearch}
                  setPluginSearch={setPluginSearch}
                  pluginCategoryFilter={pluginCategoryFilter}
                  setPluginCategoryFilter={setPluginCategoryFilter}
                  filteredPlugins={filteredPlugins}
                  togglePlugin={togglePlugin}
                  updatePlugin={updatePlugin}
                  updatingPlugins={updatingPlugins}
                  mockInstallPlugin={mockInstallPlugin}
                />
              </Match>

              <Match when={screenId() === 'settings' || screenId() === 'options-writing' || screenId() === 'options-reading' || screenId() === 'options-discussion' || screenId() === 'options-media' || screenId() === 'options-permalink' || screenId() === 'options-privacy'}>
                <SettingsPage
                  siteTitle={siteTitle}
                  setSiteTitle={setSiteTitle}
                  siteTagline={siteTagline}
                  setSiteTagline={setSiteTagline}
                  siteUrl={siteUrl}
                  setSiteUrl={setSiteUrl}
                  adminEmail={adminEmail}
                  setAdminEmail={setAdminEmail}
                  membershipOpen={membershipOpen}
                  setMembershipOpen={setMembershipOpen}
                  defaultRole={defaultRole}
                  setDefaultRole={setDefaultRole}
                  permalinkStructure={permalinkStructure}
                  setPermalinkStructure={setPermalinkStructure}
                  saveSettings={saveSettings}
                />
              </Match>

              <Match when={screenId() === 'upload' || screenId() === 'media-new'}>
                <MediaPage />
              </Match>

              <Match when={screenId() === 'users' || screenId() === 'user-new' || screenId() === 'profile'}>
                <UsersPage />
              </Match>

              <Match when={screenId() === 'edit-comments'}>
                <CommentsPage />
              </Match>

              <Match when={screenId() === 'tools' || screenId() === 'import' || screenId() === 'export' || screenId() === 'site-health'}>
                <ToolsPage />
              </Match>

              <Match when={true}>
                <GenericPage screenId={screenId} />
              </Match>
            </Switch>
            </Suspense>
          }>
            <div class="flex items-center justify-center min-h-[400px]">
              <div class="flex flex-col items-center gap-3">
                <div class="w-8 h-8 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-xs font-mono font-bold text-slate-400 tracking-widest uppercase">Loading</span>
              </div>
            </div>
          </Show>
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

      <Suspense fallback={null}>
        <MediaUploadModal
          isOpen={isMediaUploadOpen()}
          onClose={() => setIsMediaUploadOpen(false)}
        />
      </Suspense>

    </div>
  );
}
