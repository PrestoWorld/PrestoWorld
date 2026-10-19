export const DASHBOARD_BASE = '/dashboard';

const MARKETING_SUB_TABS = ['marketing', 'bookpress', 'promotions', 'events'];

const SCREEN_SEGMENT = /^[a-z0-9-]+$/i;

/** Extract the screen id from a dashboard path (`/dashboard/orders` → `orders`). */
export function screenFromPath(pathname: string): string | null {
  const path = pathname.replace(/\/+$/, '') || '/';
  if (path === DASHBOARD_BASE) {
    return null;
  }

  const prefix = DASHBOARD_BASE + '/';
  if (!path.startsWith(prefix)) {
    return null;
  }

  const segment = path.slice(prefix.length).split('/')[0] ?? '';
  return SCREEN_SEGMENT.test(segment) ? segment : 'overview';
}

/** Path for a screen id (`overview` maps to the bare `/dashboard`). */
export function pathForScreen(screenId: string): string {
  if (screenId === '' || screenId === 'overview') {
    return DASHBOARD_BASE;
  }
  return `${DASHBOARD_BASE}/${screenId}`;
}

/** True when the id corresponds to a route the dashboard can render. */
export function isKnownScreen(screenId: string, known: Set<string>): boolean {
  return known.has(screenId) || MARKETING_SUB_TABS.includes(screenId);
}

/** Push the screen path onto the history stack (no reload). */
export function pushScreen(screenId: string): void {
  const path = pathForScreen(screenId);
  if (window.location.pathname !== path) {
    window.history.pushState(null, '', path);
  }
}

/** Replace the current history entry (used for redirecting unknown routes). */
export function replaceScreen(screenId: string): void {
  const path = pathForScreen(screenId);
  if (window.location.pathname !== path) {
    window.history.replaceState(null, '', path);
  }
}
