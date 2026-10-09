import type { ComponentType } from 'react';
import type { DashboardWidget, MenuSection, Screen, User } from '../types';

export interface ScreenProps {
  screenId: string;
  title: string;
  widgets: DashboardWidget[];
  screens: Screen[];
  menuSections: MenuSection[];
  user: User;
  settings?: Record<string, unknown>;
}

export type ScreenComponent = ComponentType<ScreenProps>;

const registry = new Map<string, ScreenComponent>();

export function registerScreen(id: string, component: ScreenComponent): void {
  registry.set(id, component);
}

export function getScreenComponent(id: string): ScreenComponent | undefined {
  return registry.get(id);
}

export function hasScreen(id: string): boolean {
  return registry.has(id);
}