import React, { useState, useMemo } from 'react';
import {
  LayoutDashboard,
  ShoppingBag,
  Boxes,
  MessageSquare,
  BarChart2,
  Globe,
  ArrowLeft,
  Search,
  CheckCircle2,
  ChevronDown,
  MoreHorizontal,
  X,
  Sparkles,
  Layers,
} from 'lucide-react';
import type { MenuSection, DashboardWidget, User } from '../types';
import { SellerCenterHeader } from './SellerCenterHeader';
import { SellerCenterSidebar } from './SellerCenterSidebar';
import { OverviewPage } from './pages/OverviewPage';
import { OrdersPage } from './pages/OrdersPage';
import { InventoryPage } from './pages/InventoryPage';
import { MessagesPage } from './pages/MessagesPage';
import { AnalyticsPage } from './pages/AnalyticsPage';
import { MarketingPage } from './pages/MarketingPage';

interface SellerCenterPageProps {
  initialState: {
    user: User;
    screens: { id: string; title: string; icon: string }[];
    menuSections: MenuSection[];
    widgets: DashboardWidget[];
    page: { title: string; screenId: string };
  };
  onBackToMain: () => void;
}

type ActiveTab =
  | 'overview'
  | 'orders'
  | 'inventory'
  | 'messages'
  | 'analytics'
  | 'marketing'
  | 'bookpress'
  | 'promotions'
  | 'events';

const navItems = [
  {
    id: 'overview' as ActiveTab,
    label: 'Tổng Quan',
    subLabel: 'Bảng điều khiển kinh doanh',
    icon: LayoutDashboard,
  },
  {
    id: 'orders' as ActiveTab,
    label: 'Quản Lý Đơn Hàng',
    subLabel: 'Chốt đơn & vận chuyển',
    icon: ShoppingBag,
  },
  {
    id: 'inventory' as ActiveTab,
    label: 'Kho Hàng & Sách',
    subLabel: 'Bán lẻ, Nhập tấn, PNK, API',
    icon: Boxes,
  },
  {
    id: 'messages' as ActiveTab,
    label: 'Tin Nhắn & CSKH',
    subLabel: 'Hộp thư đa kênh SNS',
    icon: MessageSquare,
  },
  {
    id: 'analytics' as ActiveTab,
    label: 'Phân Tích & Tối Ưu',
    subLabel: 'Hiệu suất, Phễu CVR, Tốc độ web',
    icon: BarChart2,
  },
  {
    id: 'marketing' as ActiveTab,
    label: 'Kênh Bán & Marketing',
    subLabel: 'BookPress Sub-site, Voucher, Hội sách',
    icon: Globe,
  },
];

export const SellerCenterPage: React.FC<SellerCenterPageProps> = ({
  initialState,
  onBackToMain,
}) => {
  const [activeTab, setActiveTab] = useState<ActiveTab>('overview');
  const [marketingSubTab, setMarketingSubTab] = useState<'bookpress' | 'promotions' | 'events'>('bookpress');
  const [showMobileMoreMenu, setShowMobileMoreMenu] = useState(false);
  const [toastMsg, setToastMsg] = useState<string | null>(null);

  const showToast = (msg: string) => {
    setToastMsg(msg);
    setTimeout(() => setToastMsg(null), 3000);
  };

  const isTabActive = (tabId: string) => {
    if (tabId === 'overview') return activeTab === 'overview';
    if (tabId === 'orders') return activeTab === 'orders';
    if (tabId === 'inventory') return activeTab === 'inventory';
    if (tabId === 'messages') return activeTab === 'messages';
    if (tabId === 'analytics') return activeTab === 'analytics';
    if (tabId === 'marketing')
      return (
        activeTab === 'marketing' ||
        activeTab === 'bookpress' ||
        activeTab === 'promotions' ||
        activeTab === 'events'
      );
    return activeTab === tabId;
  };

  const handleSelectNavTab = (tabId: string) => {
    if (tabId === 'overview') setActiveTab('overview');
    else if (tabId === 'orders') setActiveTab('orders');
    else if (tabId === 'inventory') setActiveTab('inventory');
    else if (tabId === 'messages') setActiveTab('messages');
    else if (tabId === 'analytics') setActiveTab('analytics');
    else if (tabId === 'marketing') {
      setActiveTab('marketing');
      if (
        activeTab !== 'bookpress' &&
        activeTab !== 'promotions' &&
        activeTab !== 'events'
      ) {
        setMarketingSubTab('bookpress');
      }
    }
  };

  const handleMobileMoreClick = (tabId: string) => {
    handleSelectNavTab(tabId);
    setShowMobileMoreMenu(false);
  };

  const renderPageContent = () => {
    switch (activeTab) {
      case 'overview':
        return <OverviewPage widgets={initialState.widgets} />;
      case 'orders':
        return <OrdersPage />;
      case 'inventory':
        return <InventoryPage />;
      case 'messages':
        return <MessagesPage />;
      case 'analytics':
        return <AnalyticsPage />;
      case 'marketing':
      case 'bookpress':
      case 'promotions':
      case 'events':
        return <MarketingPage subTab={activeTab === 'marketing' ? marketingSubTab : (activeTab as 'bookpress' | 'promotions' | 'events')} onSubTabChange={setMarketingSubTab} />;
      default:
        return <OverviewPage widgets={initialState.widgets} />;
    }
  };

  return (
    <div className="min-h-screen bg-slate-100 flex flex-col font-sans transition-colors animate-in fade-in duration-200 overflow-x-hidden">
      {toastMsg && (
        <div className="fixed top-5 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white font-bold text-xs px-4 py-2.5 rounded-full shadow-2xl flex items-center gap-2 border border-slate-700 animate-in slide-in-from-top-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-400" />
          <span>{toastMsg}</span>
        </div>
      )}

      <SellerCenterHeader
        onBackToMain={onBackToMain}
        activeTab={activeTab}
        onTabChange={setActiveTab}
        user={initialState.user}
        pageTitle={initialState.page.title}
      />

      <div className="flex-1 flex flex-col md:flex-row overflow-hidden">
        <SellerCenterSidebar
          activeTab={activeTab}
          onTabChange={handleSelectNavTab}
          navItems={navItems}
          isTabActive={isTabActive}
          showMobileMoreMenu={showMobileMoreMenu}
          setShowMobileMoreMenu={setShowMobileMoreMenu}
          onBackToMain={onBackToMain}
          mobileMoreClick={handleMobileMoreClick}
        />

        <main className="flex-1 overflow-y-auto p-3 sm:p-4 md:p-6 pb-24 md:pb-6 bg-slate-50/50">
          {renderPageContent()}
        </main>
      </div>
    </div>
  );
};