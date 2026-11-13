// ── Menu Registry ──────────────────────────────────────────────
// Shared registry for modules to register dashboard menus from JS.
// Merged with PHP menu sections in SpaController and rendered in App.tsx.

export interface MenuItem {
  id: string;
  screenId: string;
  label: string;
  icon?: string;
  url?: string;
  href?: string;
  priority?: number;
  badge?: string | number;
  badgeClass?: string;
  children?: MenuItem[];
}

export interface MenuGroup {
  id: string;
  label: string;
  icon?: string;
  screenId?: string;
  url?: string;
  priority?: number;
  items?: MenuItem[];
  attributes?: Record<string, string>;
}

export interface MenuSection {
  id: string;
  title: string;
  priority?: number;
  icon?: string;
  screenId?: string;
  groups: MenuGroup[];
}

export interface ModuleMenuConfig {
  sections?: MenuSection[];
  groups?: MenuGroup[];
  items?: MenuItem[];
}

declare global {
  interface Window {
    __moduleMenus?: ModuleMenuConfig[];
  }
}

// Registry helpers
export const MODULE_MENU_EVENT = 'presto:module-menu-registered';

export function registerModuleMenu(config: ModuleMenuConfig): void {
  const w = window as any;
  w.__moduleMenus = w.__moduleMenus || [];
  w.__moduleMenus.push(config);
  try {
    window.dispatchEvent(new CustomEvent(MODULE_MENU_EVENT, { detail: config }));
  } catch {
    // CustomEvent may be unavailable in some SSR/test environments
  }
}

export function getRegisteredModuleMenus(): ModuleMenuConfig[] {
  const w = window as any;
  return w.__moduleMenus || [];
}

export function createMenuItem(data: MenuItem): MenuItem {
  return {
    id: data.id,
    screenId: data.screenId,
    label: data.label,
    icon: data.icon || 'Circle',
    url: data.url || '',
    priority: data.priority ?? 10,
    badge: data.badge,
    badgeClass: data.badgeClass,
    children: data.children,
  };
}

export function createMenuGroup(data: MenuGroup): MenuGroup {
  return {
    id: data.id,
    label: data.label,
    icon: data.icon || 'Circle',
    screenId: data.screenId || data.id,
    url: data.url || '',
    priority: data.priority ?? 10,
    items: data.items?.map(createMenuItem) || [],
    attributes: data.attributes || {},
  };
}

export function createMenuSection(data: MenuSection): MenuSection {
  return {
    id: data.id,
    title: data.title,
    priority: data.priority ?? 10,
    icon: data.icon || 'Circle',
    screenId: data.screenId,
    groups: data.groups.map(createMenuGroup),
  };
}