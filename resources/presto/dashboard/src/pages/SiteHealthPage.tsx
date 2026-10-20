import { createSignal } from 'solid-js';

export default function SiteHealthPage() {
  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Site Health</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">
          Check the health of your site
        </p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="space-y-4">
          <div class="flex items-start space-x-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
              <span class="text-xs font-bold">98</span>
            </div>
            <div class="space-y-2">
              <p class="text-base font-bold text-slate-900">Site Health Status</p>
              <p class="text-xs text-slate-500">Good</p>
            </div>
          </div>
        </div>
        <div class="space-y-4">
          <div class="border border-slate-200 rounded-md p-4">
            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                <span class="text-xs font-bold">80</span>
              </div>
              <div class="space-y-2">
                <p class="text-sm font-bold text-slate-900">Performance</p>
                <p class="text-xs text-slate-500">Good</p>
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-4">
          <div class="border border-slate-200 rounded-md p-4">
            <div class="flex items-start space-x-4">
              <div class="w-12 h-12 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                <span class="text-xs font-bold">95</span>
              </div>
              <div class="space-y-2">
                <p class="text-sm font-bold text-slate-900">Security</p>
                <p class="text-xs text-slate-500">Good</p>
              </div>
            </div>
          </div>
        </div>
        <div class="flex justify-end pt-4">
          <button class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition-colors">
            Run Test
          </button>
        </div>
      </div>
    </div>
  );
}