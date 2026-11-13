/**
 * Frontend grouping configuration for the Level-1 sidebar rail.
 *
 * The backend registers every module as a flat list of subsystems.
 * This file clusters those subsystems into logical Level-1 groups so the
 * sidebar shows a small set of icon buttons (groups) instead of one per module.
 *
 * Rules:
 * - `subsystemIds`: subsystem IDs that belong to this group (from `subsystems[]` in initialState).
 * - `sectionIds`:   menu-section IDs that belong to this group (from `menuSections[]`).
 * - Subsystems/sections NOT matched by any group fall into the "Khác" catch-all.
 * - Order in this array = display order in the rail.
 */

import type { IconComponent } from './subsystemIcons';
import {
  LayoutDashboard,
  ShoppingBag,
  Boxes,
  BookOpen,
  Megaphone,
  Users,
  MessageSquare,
  BarChart2,
  Settings,
  FileText,
} from 'lucide-react';

export interface NavGroupConfig {
  /** Unique key for this group */
  key: string;
  /** Display label (shown in tooltip and Level-2 header) */
  label: string;
  /** Lucide icon component */
  icon: IconComponent;
  /** Subsystem IDs (from `subsystems[]`) that belong to this group */
  subsystemIds: string[];
  /** Menu-section IDs (from `menuSections[]`) that belong to this group */
  sectionIds?: string[];
  /**
   * Optional: a single screenId to navigate to when clicking the group icon
   * when no Level-2 panel is needed (e.g. the Overview group).
   */
  primaryScreenId?: string;
}

export const NAV_GROUP_CONFIGS: NavGroupConfig[] = [
  {
    key: 'overview',
    label: 'Tổng Quan',
    icon: LayoutDashboard,
    subsystemIds: ['overview'],
    primaryScreenId: 'overview',
  },
  {
    key: 'sales',
    label: 'Bán Hàng',
    icon: ShoppingBag,
    subsystemIds: ['orders', 'products', 'ecom', 'customers', 'bills', 'invoices'],
  },
  {
    key: 'inventory',
    label: 'Kho & NCC',
    icon: Boxes,
    subsystemIds: ['inventory', 'inventory-movements', 'suppliers', 'nhanh-sync'],
  },
  {
    key: 'finance',
    label: 'Tài Chính',
    icon: FileText,
    subsystemIds: ['accounting'],
  },
  {
    key: 'content',
    label: 'Nội Dung & Sách',
    icon: BookOpen,
    subsystemIds: ['books', 'book-social', 'book-auction', 'book-rental', 'social-feed', 'user-follows'],
  },
  {
    key: 'marketing',
    label: 'Marketing',
    icon: Megaphone,
    subsystemIds: ['marketing', 'promotions', 'owlroyalty', 'zalo', 'nhanh-api'],
  },
  {
    key: 'hr',
    label: 'Nhân Sự',
    icon: Users,
    subsystemIds: ['staff', 'departments', 'employees'],
  },
  {
    key: 'crm',
    label: 'CSKH',
    icon: MessageSquare,
    subsystemIds: ['messages', 'chat-order', 'crm'],
  },
  {
    key: 'analytics',
    label: 'Phân Tích',
    icon: BarChart2,
    subsystemIds: ['analytics'],
  },
  {
    key: 'system',
    label: 'Hệ Thống',
    icon: Settings,
    subsystemIds: ['owlpress', 'cache', 'bookpress', 'nhanh-api-legacy'],
  },
];
