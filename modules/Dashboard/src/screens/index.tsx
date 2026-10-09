import { registerScreen } from './registry';
import { OverviewPage } from '../components/pages/OverviewPage';
import { OrdersPage } from '../components/pages/OrdersPage';
import { InventoryPage } from '../components/pages/InventoryPage';
import { MessagesPage } from '../components/pages/MessagesPage';
import { AnalyticsPage } from '../components/pages/AnalyticsPage';

registerScreen('overview', (props) => <OverviewPage widgets={props.widgets} />);
registerScreen('orders', () => <OrdersPage />);
registerScreen('inventory', () => <InventoryPage />);
registerScreen('messages', () => <MessagesPage />);
registerScreen('analytics', () => <AnalyticsPage />);

export { registerScreen, getScreenComponent, hasScreen } from './registry';
export { GenericScreen } from './GenericScreen';