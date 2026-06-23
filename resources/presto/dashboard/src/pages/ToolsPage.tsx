import { For } from 'solid-js';

const tools = [
  { name: 'Import', desc: 'Import content from other systems.', icon: '↓' },
  { name: 'Export', desc: 'Export your content as XML.', icon: '↑' },
  { name: 'Site Health', desc: 'Check the health of your site.', icon: '♥' },
];

export default function ToolsPage() {
  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Tools</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">Available tools for managing your site.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <For each={tools}>
          {(tool) => (
            <div class="bg-white rounded-xl border border-slate-200 p-6 hover:shadow-md transition-shadow">
              <div class="text-2xl mb-3">{tool.icon}</div>
              <h3 class="font-bold text-sm text-slate-900 mb-1">{tool.name}</h3>
              <p class="text-xs text-slate-500">{tool.desc}</p>
            </div>
          )}
        </For>
      </div>
    </div>
  );
}
