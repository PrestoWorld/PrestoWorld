import { createSignal } from 'solid-js';

export default function WidgetsPage() {
  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Widgets</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">
          Manage your widgets
        </p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="space-y-4">
          <h3 class="text-sm font-bold text-slate-900 mb-2">Available Widgets</h3>
          <div class="border border-slate-200 rounded-md p-4">
            <div class="space-y-2">
              <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                  <span class="text-xs font-bold">📝</span>
                </div>
                <div class="space-y-0.5">
                  <p class="text-sm font-bold text-slate-900">Custom HTML</p>
                  <p class="text-xs text-slate-500">Arbitrary HTML code</p>
                </div>
              </div>
              <div class="flex items-start space-x-3 mt-2">
                <div class="w-8 h-8 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                  <span class="text-xs font-bold">🖼️</span>
                </div>
                <div class="space-y-0.5">
                  <p class="text-sm font-bold text-slate-900">Image</p>
                  <p class="text-xs text-slate-500">Display an image</p>
                </div>
              </div>
              <div class="flex items-start space-x-3 mt-2">
                <div class="w-8 h-8 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                  <span class="text-xs font-bold">📋</span>
                </div>
                <div class="space-y-0.5">
                  <p class="text-sm font-bold text-slate-900">Text</p>
                  <p class="text-xs text-slate-500">Arbitrary text or HTML</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-4">
          <h3 class="text-sm font-bold text-slate-900 mb-2">Widget Areas</h3>
          <div class="border border-slate-200 rounded-md p-4">
            <div class="space-y-2">
              <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                  <span class="text-xs font-bold">📍</span>
                </div>
                <div class="space-y-0.5">
                  <p class="text-sm font-bold text-slate-900">Blog Sidebar</p>
                  <p class="text-xs text-slate-500">Displayed in the blog sidebar</p>
                </div>
              </div>
              <div class="flex items-start space-x-3 mt-2">
                <div class="w-8 h-8 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                  <span class="text-xs font-bold">📍</span>
                </div>
                <div class="space-y-0.5">
                  <p class="text-sm font-bold text-slate-900">Footer Column 1</p>
                  <p class="text-xs text-slate-500">Displayed in the footer</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}