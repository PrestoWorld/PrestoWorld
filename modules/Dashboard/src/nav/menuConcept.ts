/**
 * Dashboard sidebar menu concept (JS contract for PHP registration).
 *
 * PHP registers menu data via:
 * - `PrestoWorld\Foundation\Admin\DashboardSubsystem::register($app, $id, $label, $subLabel, $icon, $priority, $source, $section)`
 *   → creates a top-level subsystem group (Level 1 rail) and screen.
 * - `MenuContextRepository::registerGroup/registerItem` / providers + filters
 *   → builds section/group/item trees serialized by `SpaController::buildMenuSections()`.
 * - Hooks: `admin.dashboard.nav`, `admin.screen.register`, `admin.dashboard.widgets`.
 *
 * Initial SPA state (`window.__INITIAL_DASHBOARD_STATE__` / `/api/admin/initial-state`):
 * - `subsystems: DashboardSubsystemNavItem[]`  → Level 1 rail entries (direct screens)
 * - `menuSections: DashboardMenuSection[]`     → Level 1 rail entries with nested Level 2+ items
 *
 * Rendering model:
 * - Level 1: icon rail of groups (subsystems + menu sections). Click selects group and toggles Level 2 drawer.
 * - Level 2: drawer listing items of the active group. Items may nest recursively (`items`/`children`).
 */
import type { ComponentType } from 'react';
import type { MenuItem, MenuSection, SubsystemNavItem } from '../types';

export type DashboardSubsystemNavItem = SubsystemNavItem;
export type DashboardMenuSection = MenuSection;
export type DashboardMenuItem = MenuItem;

export interface DashboardNavItem {
  id: string;
  label: string;
  subLabel: string;
  icon: ComponentType<{ className?: string }>;
}

export const SidebarLevel = {
  Group: 1,
  Item: 2,
} as const;

export type SidebarLevel = (typeof SidebarLevel)[keyof typeof SidebarLevel];

export interface SidebarMenuGroup {
  key: string;
  kind: 'subsystem' | 'section';
  level: typeof SidebarLevel.Group;
  title: string;
  subtitle?: string;
  iconName?: string;
  navId?: string;
  section?: DashboardMenuSection;
}

export interface SidebarMenuNode {
  id: string;
  label: string;
  icon?: string;
  screenId?: string;
  url?: string;
  priority?: number;
  items: SidebarMenuNode[];
}

export function resolveMenuItemId(item: { id?: string; screenId?: string } | null | undefined): string {
  if (!item) return '';
  return item.screenId ?? item.id ?? '';
}

function asArray<T>(value: T[] | undefined | null): T[] {
  return Array.isArray(value) ? value : [];
}

/**
 * Normalize a PHP/menu item into a recursive sidebar node.
 * Accepts both `items` and `children` (PHP MenuItem serializes both).
 */
export function normalizeMenuNode(raw: any): SidebarMenuNode | null {
  if (!raw || typeof raw !== 'object') return null;
  const id = String(raw.id ?? raw.screenId ?? '');
  const label = String(raw.label ?? raw.title ?? id);
  if (!id && !label) return null;
  const childrenRaw = [...asArray(raw.items), ...asArray(raw.children)];
  const seen = new Set<string>();
  const items: SidebarMenuNode[] = [];
  for (const child of childrenRaw) {
    const node = normalizeMenuNode(child);
    if (!node) continue;
    const key = `${node.id}|${node.screenId ?? ''}|${node.label}`;
    if (seen.has(key)) continue;
    seen.add(key);
    items.push(node);
  }
  items.sort((a, b) => (a.priority ?? 10) - (b.priority ?? 10));
  return {
    id: id || label,
    label,
    icon: raw.icon ?? undefined,
    screenId: raw.screenId ?? raw.id ?? undefined,
    url: raw.url ?? undefined,
    priority: typeof raw.priority === 'number' ? raw.priority : undefined,
    items,
  };
}

/**
 * Normalize sections coming from PHP so the sidebar can render 2+ levels reliably.
 * - Ensures `items` arrays exist
 * - Merges `children` into `items`
 * - Sorts by priority
 */
export function normalizeMenuSections(sections: DashboardMenuSection[] | undefined | null): DashboardMenuSection[] {
  return asArray(sections)
    .map((section) => {
      const nodes = asArray((section as any).items)
        .map((item) => normalizeMenuNode(item))
        .filter((n): n is SidebarMenuNode => n !== null);
      return {
        ...section,
        id: String((section as any).id ?? ''),
        title: String((section as any).title ?? (section as any).label ?? ''),
        priority: typeof (section as any).priority === 'number' ? (section as any).priority : 10,
        items: nodes as any,
      } as DashboardMenuSection;
    })
    .sort((a, b) => (a.priority ?? 10) - (b.priority ?? 10));
}

export function normalizeSubsystemNav(items: DashboardSubsystemNavItem[] | undefined | null): DashboardSubsystemNavItem[] {
  return asArray(items)
    .map((item) => ({
      ...item,
      id: String(item.id ?? item.screenId ?? ''),
      label: String(item.label ?? ''),
      subLabel: item.subLabel ?? '',
      screenId: item.screenId ?? item.id ?? undefined,
      priority: typeof item.priority === 'number' ? item.priority : 10,
    }))
    .filter((item) => item.id || item.label)
    .sort((a, b) => (a.priority ?? 10) - (b.priority ?? 10));
}

/**
 * Helper concept for PHP registrars (documentation aid in JS):
 * - Level 1 group: provide a subsystem (`subsystems[]`) or a menu section (`menuSections[]`).
 * - Level 2+ entries: provide nested `items` (preferred) or `children` on section items.
 * - Each item should include `id`/`screenId`, `label`, optional `icon`, `priority`, and optional nested `items`.
 */
export const PhpMenuRegistrationConcept = {
  initialStateKeys: ['subsystems', 'menuSections', 'screens'] as const,
  level1Sources: ['subsystems', 'menuSections'] as const,
  nestedKeys: ['items', 'children'] as const,
  subsystemFields: ['id', 'label', 'subLabel', 'icon', 'screenId', 'priority'] as const,
  sectionFields: ['id', 'title', 'icon', 'priority', 'screenId', 'items'] as const,
  itemFields: ['id', 'label', 'icon', 'url', 'screenId', 'priority', 'items'] as const,
} as const;
