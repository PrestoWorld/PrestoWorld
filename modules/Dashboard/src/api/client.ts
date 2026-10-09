import type {
  InitialState,
  MenuSection,
  DashboardWidget,
} from '../types';

const API_BASE = '/api/admin';

async function fetchJson<T>(url: string): Promise<T> {
  const response = await fetch(url, {
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`API request failed: ${response.status} ${response.statusText}`);
  }

  return response.json();
}

const __INITIAL_DASHBOARD_STATE__ = (typeof window !== 'undefined')
  ? (window.__INITIAL_DASHBOARD_STATE__ as InitialState | undefined)
  : undefined;

export const api = {
  async getInitialState(): Promise<InitialState> {
    // Try to use inline state from the HTML first
    if (__INITIAL_DASHBOARD_STATE__) {
      return __INITIAL_DASHBOARD_STATE__;
    }
    // Fall back to API endpoint
    return fetchJson<InitialState>(`${API_BASE}/initial-state`);
  },

  async getMenu(): Promise<{ menuSections: MenuSection[] }> {
    return fetchJson(`${API_BASE}/menu`);
  },

  async getDashboardWidgets(): Promise<DashboardWidget[]> {
    return fetchJson(`${API_BASE}/dashboard/widgets`);
  },

  async getMenuTree(): Promise<MenuSection[]> {
    const response = await fetchJson<{ menuSections: MenuSection[] }>(`${API_BASE}/menu/tree`);
    return response.menuSections;
  },
};

export function createApiClient(baseUrl: string = API_BASE) {
  return {
    async getInitialState(): Promise<InitialState> {
      // Try to use inline state from the HTML first
      if (typeof window !== 'undefined') {
        const globalState = (window as any).__INITIAL_DASHBOARD_STATE__;
        if (globalState) {
          return globalState;
        }
      }
      // Fall back to API endpoint
      return fetchJson<InitialState>(`${baseUrl}/initial-state`);
    },

    async getMenu(): Promise<{ menuSections: MenuSection[] }> {
      return fetchJson(`${baseUrl}/menu`);
    },

    async getDashboardWidgets(): Promise<DashboardWidget[]> {
      return fetchJson(`${baseUrl}/dashboard/widgets`);
    },

    async getMenuTree(): Promise<MenuSection[]> {
      const response = await fetchJson<{ menuSections: MenuSection[] }>(`${baseUrl}/menu/tree`);
      return response.menuSections;
    },
  };
}