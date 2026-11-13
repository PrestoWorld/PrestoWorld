    /**
 * Dashboard sidebar menu concept (JS contract for PHP registration).
 *
 * PHP registers menu data via:
 * - `PrestoWorld\\Foundation\\Admin\\DashboardSubsystem::register($app, $id, $label, $subLabel, $icon, $priority, $source, $section)`
 *   → creates a top-level subsystem group (Level 1 rail) and screen.
 * - `MenuContextRepository::registerGroup/registerItem` / providers + filters
 *   → builds section/group/item trees serialized by `SpaController::buildMenuSections()`.
 * - Hooks: `admin.dashboard.nav`, `admin.screen.register`, `admin.dashboard.widgets`.
 *
 * Initial SPA state (`window.__INITIAL_DASHBOARD_STATE__` / `/api/admin/initial-state`):
 * - `subsystems: DashboardSubsystemNavItem[]`  → flat list of registered subsystems
 * - `menuSections: DashboardMenuSection[]`     → section/group/item trees
 *
 * Rendering model:
 * - Level 1: small icon rail — one button per *group* (defined in navGroups.ts).
 *   Each group clusters multiple subsystems into one logical category.
 * - Level 2: drawer listing the subsystems/items of the active group.
 *   Items may nest recursively (`items`/`children`).
 */
import type { ComponentType } from 'react';
import type { MenuItem, MenuSection, SubsystemNavItem } from '../types';
import { NAV_GROUP_CONFIGS } from './navGroups';
import type { ModuleMenuConfig } from '../../../../shared/menu-registry';

export type DashboardSubsystemNavItem = SubsystemNavItem;
export type DashboardMenuSection = MenuSection;
export type DashboardMenuItem = MenuItem;

export type IconComponent = ComponentType<{ className?: string }>;

export interface NavItem {
  id: string;
  label: string;
  iconName?: string;
  screenId?: string;
  url?: string;
  priority?: number;
  items?: NavItem[];
}

interface SubsystemChildItem {
  id?: string;
  label?: string;
  icon?: string;
  screenId?: string;
  url?: string;
  priority?: number;
}

export interface NavGroup {
  key: string;
  label: string;
  iconName?: string;
  /** Pre-resolved icon component (used when grouped via navGroups config) */
  iconComponent?: ComponentType<{ className?: string }>;
  kind: 'subsystem' | 'section' | 'group';
  screenId?: string;
  priority?: number;
  items: NavItem[];
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

function toNavItem(node: SidebarMenuNode): NavItem {
  return {
    id: node.id,
    label: node.label,
    iconName: node.icon,
    screenId: node.screenId,
    url: node.url,
    priority: node.priority,
    items: node.items.map(toNavItem),
  };
}

function getSectionNavItems(section: DashboardMenuSection): NavItem[] {
  return asArray(section.items)
    .map((item) => normalizeMenuNode(item))
    .filter((node): node is SidebarMenuNode => node !== null)
    .map(toNavItem);
}

/**
 * Build Level-1 navigation groups by clustering flat subsystems into logical
 * groups defined in `navGroups.ts` (NAV_GROUP_CONFIGS).
 *
 * - Each config entry becomes one Level-1 icon button.
 * - Matched subsystems become Level-2 `items` inside that group.
 * - Subsystems not matched by any config go into a catch-all "Khác" group.
 * - Groups with no matched subsystems AND no primaryScreenId are hidden.
 */
export function buildNavGroups(
  subsystems: DashboardSubsystemNavItem[] | undefined | null,
  menuSections: DashboardMenuSection[] | undefined | null,
): NavGroup[] {
  return mergeNavGroups(
    buildNavGroupsFromSubsystems(subsystems, menuSections),
    getRegisteredModuleMenusFromWindow(),
  );
}

/**
 * Build Level-1 navigation groups from subsystems only (without merging JS menus).
 * Used internally by buildNavGroups() and can be used by callers that want to
 * merge JS menus themselves.
 */
export function buildNavGroupsFromSubsystems(
  subsystems: DashboardSubsystemNavItem[] | undefined | null,
  menuSections: DashboardMenuSection[] | undefined | null,
): NavGroup[] {
  const normalizedSubs = normalizeSubsystemNav(subsystems);

  // If no subsystems are registered (e.g. sub-site dashboards like BookPress /bookpress/{siteId}/dashboard),
  // fall back to rendering groups from menuSections directly.
  if (normalizedSubs.length === 0) {
    const sections = normalizeMenuSections(menuSections);
    return sections.map((section) => ({
      key: `sec:${section.id}`,
      label: section.title,
      iconName: section.icon,
      kind: 'section' as const,
      screenId: section.screenId ?? getSectionNavItems(section)[0]?.screenId,
      priority: section.priority,
      items: getSectionNavItems(section),
    }));
  }

  // Build lookup: subsystemId → subsystem
  const subById = new Map<string, DashboardSubsystemNavItem>();
  for (const sub of normalizedSubs) {
    const id = sub.id ?? sub.screenId ?? '';
    if (id) subById.set(id, sub);
  }

  const placed = new Set<string>();
  const groups: NavGroup[] = [];

  for (const cfg of NAV_GROUP_CONFIGS) {
    const matchedItems: NavItem[] = [];

    for (const subsystemId of cfg.subsystemIds) {
      const sub = subById.get(subsystemId);
      if (!sub) continue;
      placed.add(subsystemId);

      const seenChildKeys = new Set<string>();
      const childItems: NavItem[] = [];
      for (const child of asArray<SubsystemChildItem>((sub as { items?: SubsystemChildItem[] }).items)) {
        const cId = child.screenId ?? child.id ?? '';
        const cLabel = child.label ?? '';
        const key = `${cId}|${cLabel}`;
        if (seenChildKeys.has(key)) continue;
        seenChildKeys.add(key);
        childItems.push({
          id: cId,
          label: cLabel,
          iconName: child.icon,
          screenId: child.screenId ?? child.id,
          url: child.url,
          priority: child.priority,
        });
      }

      const sId = sub.id ?? sub.screenId ?? '';
      matchedItems.push({
        id: sId,
        label: sub.label,
        iconName: sub.icon,
        screenId: sub.screenId ?? sub.id,
        priority: sub.priority,
        ...(childItems.length > 0 ? { items: childItems } : {}),
      });
    }

    matchedItems.sort((a, b) => (a.priority ?? 10) - (b.priority ?? 10));

    // Skip groups with nothing to show
    if (matchedItems.length === 0 && !cfg.primaryScreenId) continue;

    groups.push({
      key: cfg.key,
      label: cfg.label,
      iconComponent: cfg.icon,
      kind: 'group',
      screenId: cfg.primaryScreenId ?? matchedItems[0]?.screenId,
      items: matchedItems,
    });
  }

  // Catch-all for unplaced subsystems
  const unplaced = normalizedSubs.filter((sub) => {
    const id = sub.id ?? sub.screenId ?? '';
    return id && !placed.has(id);
  });

  if (unplaced.length > 0) {
    const seenCatchAll = new Set<string>();
    const catchAllItems: NavItem[] = [];
    for (const sub of unplaced) {
      const sId = sub.id ?? sub.screenId ?? '';
      const sLabel = sub.label ?? '';
      const key = `${sId}|${sLabel}`;
      if (seenCatchAll.has(key)) continue;
      seenCatchAll.add(key);
      catchAllItems.push({
        id: sId,
        label: sLabel,
        iconName: sub.icon,
        screenId: sub.screenId ?? sub.id,
        priority: sub.priority,
      });
    }
    catchAllItems.sort((a, b) => (a.priority ?? 10) - (b.priority ?? 10));
    groups.push({
      key: 'other',
      label: 'Khác',
      kind: 'group',
      items: catchAllItems,
    });
  }

  for (const section of normalizeMenuSections(menuSections)) {
    const config = NAV_GROUP_CONFIGS.find((group) => group.sectionIds?.includes(section.id));
    if (config) {
      let target = groups.find((group) => group.key === config.key);
      if (!target) {
        target = {
          key: config.key,
          label: config.label,
          iconComponent: config.icon,
          kind: 'group',
          screenId: config.primaryScreenId,
          items: [],
        };
        groups.push(target);
      }
      target.items.push(...getSectionNavItems(section));
      continue;
    }

    const items = getSectionNavItems(section);
    groups.push({
      key: `sec:${section.id}`,
      label: section.title,
      iconName: section.icon,
      kind: 'section',
      screenId: section.screenId ?? items[0]?.screenId,
      priority: section.priority,
      items,
    });
  }

  return groups;
}

/**
 * Merge JS-registered module menus (window.__moduleMenus) into existing nav groups.
 * Each module can register:
 * - `sections`: full MenuSection objects with groups/items
 * - `groups`: standalone NavGroup-like objects (no section wrapper)
 * - `items`: standalone NavItem-like objects (added to catch-all "Khác" group)
 *
 * Matching logic:
 * - If a section/group `id` matches an existing NavGroup's `key`, merge items.
 * - Otherwise, append as a new group (or section wrapper).
 */
export function mergeNavGroups(
  baseGroups: NavGroup[],
  moduleMenus: ModuleMenuConfig[],
): NavGroup[] {
  if (!moduleMenus.length) return baseGroups;

  const merged: NavGroup[] = baseGroups.map((g) => ({ ...g, items: [...(g.items ?? [])] }));
  const catchAll = merged.find((g) => g.key === 'other');
  const catchAllItems = catchAll ? [...(catchAll.items ?? [])] : [];

  const pushCatchAll = (item: NavItem) => {
    const key = `${item.id}|${item.label}`;
    if (!catchAllItems.some((c) => `${c.id}|${c.label}` === key)) {
      catchAllItems.push(item);
    }
  };

  for (const menu of moduleMenus) {
    // 1. Sections (full MenuSection with groups)
    for (const section of menu.sections ?? []) {
      const sectionKey = `sec:${section.id}`;
      let target = merged.find((g) => g.key === sectionKey);
      if (!target) {
        target = {
          key: sectionKey,
          label: section.title,
          iconName: section.icon,
          kind: 'section',
          screenId: section.screenId,
          priority: section.priority,
          items: [],
        };
        merged.push(target);
      }
      for (const group of section.groups ?? []) {
        const groupKey = group.id;
        let groupTarget = target.items.find((i) => i.id === groupKey);
        if (!groupTarget) {
          groupTarget = {
            id: groupKey,
            label: group.label,
            iconName: group.icon,
            screenId: group.screenId ?? groupKey,
            url: group.url,
            priority: group.priority ?? 10,
            items: [],
          };
          target.items.push(groupTarget);
        }
        groupTarget.items ??= [];
        for (const item of group.items ?? []) {
          const itemKey = `${item.id}|${item.label}`;
          if (!groupTarget.items.some((c) => `${c.id}|${c.label}` === itemKey)) {
            groupTarget.items.push({
              id: item.id,
              label: item.label,
              iconName: item.icon,
              screenId: item.screenId,
              url: item.url,
              priority: item.priority ?? 10,
              items: item.children?.map((c) => ({
                id: c.id,
                label: c.label,
                iconName: c.icon,
                screenId: c.screenId,
                url: c.url,
                priority: c.priority ?? 10,
              })),
            });
          }
        }
      }
    }

    // 2. Standalone groups (no section wrapper)
    for (const group of menu.groups ?? []) {
      const groupKey = group.id;
      let target = merged.find((g) => g.key === groupKey);
      if (!target) {
        target = {
          key: groupKey,
          label: group.label,
          iconName: group.icon,
          kind: 'group',
          screenId: group.screenId ?? groupKey,
          priority: group.priority ?? 10,
          items: [],
        };
        merged.push(target);
      }
      for (const item of group.items ?? []) {
        const itemKey = `${item.id}|${item.label}`;
        if (!target.items.some((c) => `${c.id}|${c.label}` === itemKey)) {
          target.items.push({
            id: item.id,
            label: item.label,
            iconName: item.icon,
            screenId: item.screenId,
            url: item.url,
            priority: item.priority ?? 10,
            items: item.children?.map((c) => ({
              id: c.id,
              label: c.label,
              iconName: c.icon,
              screenId: c.screenId,
              url: c.url,
              priority: c.priority ?? 10,
            })),
          });
        }
      }
    }

    // 3. Standalone items (added to catch-all "Khác")
    for (const item of menu.items ?? []) {
      pushCatchAll({
        id: item.id,
        label: item.label,
        iconName: item.icon,
        screenId: item.screenId,
        url: item.url,
        priority: item.priority ?? 10,
        items: item.children?.map((c) => ({
          id: c.id,
          label: c.label,
          iconName: c.icon,
          screenId: c.screenId,
          url: c.url,
          priority: c.priority ?? 10,
        })),
      });
    }
  }

  if (catchAll && catchAllItems.length > 0) {
    catchAll.items = catchAllItems;
  }

  // Sort items by priority within each group
  const sortItems = (items: NavItem[]) =>
    items.sort((a, b) => (a.priority ?? 10) - (b.priority ?? 10));
  for (const g of merged) {
    sortItems(g.items);
  }
  merged.sort((a, b) => (a.priority ?? 10) - (b.priority ?? 10));

  return merged;
}

/**
 * Read JS-registered module menus from window.__moduleMenus.
 * Falls back to empty array when not available (SSR/CSR).
 */
export function getRegisteredModuleMenusFromWindow(): ModuleMenuConfig[] {
  if (typeof window === 'undefined') return [];
  return (window as any).__moduleMenus ?? [];
}

export { MODULE_MENU_EVENT } from '../../../../shared/menu-registry';

/**
 * Helper concept for PHP registrars (documentation aid in JS).
 */
export const PhpMenuRegistrationConcept = {
  initialStateKeys: ['subsystems', 'menuSections', 'screens'] as const,
  level1Sources: ['subsystems', 'menuSections'] as const,
  nestedKeys: ['items', 'children'] as const,
  subsystemFields: ['id', 'label', 'subLabel', 'icon', 'screenId', 'priority'] as const,
  sectionFields: ['id', 'title', 'icon', 'priority', 'screenId', 'items'] as const,
  itemFields: ['id', 'label', 'icon', 'url', 'screenId', 'priority', 'items'] as const,
} as const;
