import { useState, useEffect, useCallback } from 'react';
import { api, type InitialState } from '../api/client';
import type { MenuSection, DashboardWidget } from '../types';

export function useInitialState() {
  const [state, setState] = useState<InitialState | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchState = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await api.getInitialState();
      setState(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load initial state');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchState();
  }, [fetchState]);

  return { state, loading, error, refetch: fetchState };
}

export function useMenu() {
  const [sections, setSections] = useState<MenuSection[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchMenu = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await api.getMenu();
      setSections(data.menuSections);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load menu');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchMenu();
  }, [fetchMenu]);

  return { sections, loading, error, refetch: fetchMenu };
}

export function useDashboardWidgets() {
  const [widgets, setWidgets] = useState<DashboardWidget[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchWidgets = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await api.getDashboardWidgets();
      setWidgets(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load widgets');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchWidgets();
  }, [fetchWidgets]);

  return { widgets, loading, error, refetch: fetchWidgets };
}