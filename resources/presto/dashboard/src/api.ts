const BASE = '/api/admin';

async function fetchJSON<T>(url: string): Promise<T> {
  const res = await fetch(url);
  if (!res.ok) {
    throw new Error(`API error: ${res.status} ${res.statusText}`);
  }
  return res.json();
}

export interface WPPost {
  id: number;
  title: string;
  author: string;
  category: string;
  status: 'Published' | 'Draft' | 'Scheduled';
  date: string;
  commentsCount: number;
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

export interface WPActivity {
  id: number;
  text: string;
  time: string;
  type: 'post' | 'comment' | 'update' | 'security';
}

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

export interface DashboardStats {
  posts: { total: number; published: number; draft: number };
  plugins: { total: number; active: number; inactive: number };
  byPostType?: Array<{ type: string; count: number; label: string }>;
}

export async function fetchPosts(type?: string): Promise<WPPost[]> {
  const qs = type ? `?type=${encodeURIComponent(type)}` : '';
  return fetchJSON<WPPost[]>(`${BASE}/posts${qs}`);
}

export async function fetchPages(): Promise<WPPost[]> {
  return fetchPosts('page');
}

export async function fetchPlugins(): Promise<WPPlugin[]> {
  return fetchJSON<WPPlugin[]>(`${BASE}/plugins`);
}

export async function fetchStats(): Promise<DashboardStats> {
  return fetchJSON<DashboardStats>(`${BASE}/stats`);
}

export async function fetchThemes(): Promise<WPTheme[]> {
  return fetchJSON<WPTheme[]>(`${BASE}/themes`);
}

export async function fetchActivities(): Promise<WPActivity[]> {
  return fetchJSON<WPActivity[]>(`${BASE}/activities`);
}
