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

export interface AdminUser {
  name: string;
  role: string;
  avatar: string | null;
}

export interface AdminMenuItem {
  id: string;
  label: string;
  icon?: string;
  path?: string;
  children?: AdminMenuItem[];
}

export interface AdminInitialState {
  user: AdminUser;
  menu: AdminMenuItem[];
  widgets: Record<string, unknown[]>;
  page: {
    path: string;
    title: string;
  };
}
