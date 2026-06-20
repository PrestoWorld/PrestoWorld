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

export interface DashboardStats {
  posts: { total: number; published: number; draft: number };
  plugins: { total: number; active: number; inactive: number };
}

export async function fetchPosts(): Promise<WPPost[]> {
  return fetchJSON<WPPost[]>(`${BASE}/posts`);
}

export async function fetchPlugins(): Promise<WPPlugin[]> {
  return fetchJSON<WPPlugin[]>(`${BASE}/plugins`);
}

export async function fetchStats(): Promise<DashboardStats> {
  return fetchJSON<DashboardStats>(`${BASE}/stats`);
}

export async function fetchActivities(): Promise<WPActivity[]> {
  return fetchJSON<WPActivity[]>(`${BASE}/activities`);
}
