export interface User {
  id: string;
  name: string;
  role: string;
  avatar?: string;
  email?: string;
}

export interface MenuItem {
  id: string;
  label: string;
  url: string;
  icon?: string;
  priority: number;
  screenId?: string;
  children?: MenuItem[];
}

export interface MenuSection {
  id: string;
  title: string;
  priority: number;
  icon?: string;
  screenId?: string;
  items: MenuItem[];
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
  menuSections: MenuSection[];
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

export {};