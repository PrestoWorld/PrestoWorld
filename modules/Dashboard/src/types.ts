export interface User {
  id: string;
  name: string;
  role: string;
  avatar?: string;
  email?: string;
}

/**
 * Menu item contract for PHP-registered dashboard menus.
 * PHP may serialize nested entries as `items` and/or `children`.
 */
export interface MenuItem {
  id: string;
  label: string;
  url: string;
  icon?: string;
  priority: number;
  screenId?: string;
  children?: MenuItem[];
  /** Serialized children (backend key for nested menu items). */
  items?: MenuItem[];
}

/**
 * Menu section contract (Level 1 group with nested Level 2+ items).
 * Registered/assembled on PHP side and delivered via initial state/API.
 */
export interface MenuSection {
  id: string;
  title: string;
  priority: number;
  icon?: string;
  screenId?: string;
  items: MenuItem[];
}

/**
 * Subsystem nav contract (Level 1 rail item), typically registered via
 * `DashboardSubsystem::register(...)` on PHP side.
 */
export interface SubsystemNavItem {
  id: string;
  label: string;
  subLabel?: string;
  icon?: string;
  screenId?: string;
  priority?: number;
}

export interface DashboardWidget {
  id: string;
  title: string;
  component: string;
  grid: 'half' | 'full';
  priority: number;
  visible: boolean;
  props: {
    content: string;
    column: number;
  };
}

export interface Screen {
  id: string;
  title: string;
  icon: string;
  position: number;
  component?: string | null;
  source?: string;
  settings?: Record<string, unknown>;
}

export interface ScreenOption {
  id: string;
  label: string;
  type: 'number' | 'checkbox' | 'select' | 'text';
  default: unknown;
  options?: { value: string; label: string }[];
}

export interface ScreenOptionsContext {
  screenId: string;
  title: string;
  options: ScreenOption[];
}

export interface AdminBarItem {
  id: string;
  label: string;
  icon: string;
  href?: string;
  type: 'link' | 'button' | 'notification';
  badge?: number | string;
}

export interface AdminBarContext {
  items: AdminBarItem[];
}

export interface InitialState {
  user: User;
  screens: Screen[];
  /** PHP-registered content/menu sections rendered as Level 1 groups with nested items. */
  menuSections: MenuSection[];
  /** PHP-registered subsystems rendered as Level 1 rail entries. */
  subsystems?: SubsystemNavItem[];
  widgets: DashboardWidget[];
  screenOptions: ScreenOptionsContext[];
  adminBar: AdminBarContext;
  page: {
    path: string;
    title: string;
    screenId: string;
  };
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  error?: string;
}

declare global {
  interface Window {
    __INITIAL_DASHBOARD_STATE__?: InitialState;
  }
}

export type {
  DashboardMenuItem,
  DashboardMenuSection,
  DashboardSubsystemNavItem,
  SidebarLevel,
  SidebarMenuGroup,
  SidebarMenuNode,
} from './nav/menuConcept';
export {
  PhpMenuRegistrationConcept,
  normalizeMenuSections,
  normalizeSubsystemNav,
  normalizeMenuNode,
  resolveMenuItemId,
} from './nav/menuConcept';

export {};