import { createSignal } from 'solid-js';

export default function CustomizePage() {
  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Customize Your Site</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">
          Customize your site's appearance and functionality
        </p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-center py-12">
          <div class="text-center">
            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 mb-4">
              <span class="text-xl">🎨</span>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Customizer</h3>
            <p class="text-xs text-slate-500">
              The Customizer allows you to preview changes to your site's appearance in real time.
            </p>
            <p class="mt-4">
              <button class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition-colors">
                Customize Your Site
              </button>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}