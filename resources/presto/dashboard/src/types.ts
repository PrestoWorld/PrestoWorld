// ── Data Models ──────────────────────────────────────────────

export interface WPPost {
  id: number;
  title: string;
  author: string;
  category: string;
  status: 'Published' | 'Draft' | 'Scheduled';
  date: string;
  commentsCount: number;
}

export interface WPActivity {
  id: number;
  text: string;
  time: string;
  type: 'post' | 'comment' | 'update' | 'security';
}

export interface WPPlugin {
  id: string;
  name: string;
  desc: string;
  version: string;
  author: string;
  active: boolean;
  updateAvailable: boolean;
  category: string;
}

export interface WPToast {
  id: string;
  message: string;
  type: 'success' | 'error' | 'info';
}

// ── Admin Contexts ───────────────────────────────────────────

export interface WPTheme {
  directory: string;
  name: string;
  uri: string | null;
  author: string | null;
  author_uri: string | null;
  description: string | null;
  version: string | null;
  requires: string | null;
  requires_php: string | null;
  tested: string | null;
  tags: string | null;
  text_domain: string | null;
  license: string | null;
  license_uri: string | null;
  screenshot: string | null;
  is_active: boolean;
}

/** WordPress-style screen ID (dashboard, posts, plugins, settings) */
export type ScreenId = 'dashboard' | 'posts' | 'plugins' | 'themes' | 'settings';

/** Registered screen definition */
export interface AdminScreen {
  id: ScreenId | string;
  title: string;
  parent?: string;
  capability?: string;
  icon?: string;
  position?: number;
}

// ── Menu Context ─────────────────────────────────────────────

export interface AdminUser {
  name: string;
  role: string;
  avatar: string | null;
}

export interface AdminMenuItem {
  id: string;
  label: string;
  screenId: string;
  icon?: string;
  href?: string;
  badge?: string | number;
  badgeClass?: string;
  children?: AdminMenuItem[];
}

export interface AdminMenuSection {
  id: string;
  title: string;
  priority: number;
  items: AdminMenuItem[];
}

// ── Dashboard Widget Context ─────────────────────────────────

export type DashboardWidgetGrid = 'full' | 'half' | 'third' | 'quarter';

export interface DashboardWidgetDefinition {
  id: string;
  title: string;
  component: string;
  grid: DashboardWidgetGrid;
  priority: number;
  visible: boolean;
  props?: Record<string, unknown>;
}

// ── Screen Options Context ───────────────────────────────────

export type ScreenOptionType = 'checkbox' | 'select' | 'text' | 'number';

export interface ScreenOption {
  id: string;
  label: string;
  type: ScreenOptionType;
  default?: unknown;
  options?: { label: string; value: string }[];
}

export interface ScreenOptionsContext {
  screenId: string;
  title: string;
  options: ScreenOption[];
}

// ── Admin Bar Context ────────────────────────────────────────

export type AdminBarItemType = 'link' | 'button' | 'divider' | 'notification';

export interface AdminBarItem {
  id: string;
  label: string;
  icon?: string;
  href?: string;
  type: AdminBarItemType;
  badge?: string | number;
  children?: AdminBarItem[];
}

export interface AdminBarContext {
  items: AdminBarItem[];
}

// ── Page Context ─────────────────────────────────────────────

export interface AdminPageContext {
  path: string;
  title: string;
  screenId: string;
}

// ── Root Initial State ───────────────────────────────────────

export interface AdminInitialState {
  user: AdminUser;
  screens: AdminScreen[];
  menuSections: AdminMenuSection[];
  widgets: DashboardWidgetDefinition[];
  screenOptions: ScreenOptionsContext[];
  adminBar: AdminBarContext;
  page: AdminPageContext;
}
