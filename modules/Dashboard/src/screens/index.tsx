import { lazy } from 'react';
import { registerScreen } from './registry';

registerScreen(
  'overview',
  lazy(() => import('../components/pages/OverviewPage').then((m) => ({ default: m.OverviewPage }))),
);
registerScreen(
  'orders',
  lazy(() => import('../components/pages/OrdersPage').then((m) => ({ default: m.OrdersPage }))),
);
registerScreen(
  'inventory',
  lazy(() => import('../components/pages/InventoryPage').then((m) => ({ default: m.InventoryPage }))),
);
registerScreen(
  'analytics',
  lazy(() => import('../components/pages/AnalyticsPage').then((m) => ({ default: m.AnalyticsPage }))),
);
registerScreen(
  'cache',
  lazy(() => import('../components/pages/CachePage').then((m) => ({ default: m.CachePage }))),
);

export { registerScreen, getScreenComponent, hasScreen } from './registry';
export { GenericScreen } from './GenericScreen';
