export default function MediaPage() {
  return (
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Media Library</h2>
          <p class="text-xs text-slate-500 font-semibold mt-1">Upload and manage media files.</p>
        </div>
        <button class="text-[11px] font-bold bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl transition-colors">
          Add New
        </button>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden p-12 flex flex-col items-center justify-center text-slate-400">
        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="text-sm font-semibold">No media items yet.</p>
        <p class="text-xs mt-1">Drag and drop files here to upload.</p>
      </div>
    </div>
  );
}
